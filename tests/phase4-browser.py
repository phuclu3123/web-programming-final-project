"""Optional real Chrome layout/interaction checks against the isolated local DB.
Requires existing Chrome and websocket-client. No personal browser profile or providers.
COUPONHUB_RUN_HTTP_TEST=1 python tests/phase4-browser.py
"""
import base64
import http.cookiejar
import json
import os
from pathlib import Path
import re
import socket
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request
import websocket

ROOT=Path(__file__).resolve().parents[1]
if os.environ.get('COUPONHUB_RUN_HTTP_TEST')!='1':raise SystemExit('SKIP: explicit isolated HTTP opt-in required')
assert not (ROOT/'config/local.php').exists()
mysql=['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379','--user=root','--batch','--skip-column-names','couponhub_merge_test','-e']
assert subprocess.check_output(mysql+['SELECT @@port']).strip()==b'13379'
coupon=int(subprocess.check_output(mysql+["SELECT id FROM v_coupon_status WHERE effective_status='ACTIVE' AND source='OFFICIAL' ORDER BY id LIMIT 1"]).strip())
campaign=int(subprocess.check_output(mysql+["SELECT id FROM campaigns WHERE status='PUBLISHED' AND ends_at>UTC_TIMESTAMP() ORDER BY id LIMIT 1"]).strip())
chrome=Path(os.environ.get('COUPONHUB_TEST_CHROME','C:/Program Files/Google/Chrome/Application/chrome.exe'))
assert chrome.is_file(),'Chrome is required for the optional browser test'
runtime=ROOT/'.runtime'/('phase4-browser-'+str(time.time_ns()));runtime.mkdir()
(runtime/'sessions').mkdir()
origin='http://127.0.0.1:8098'
for port in (8098,9228):
    with socket.socket() as probe:
        probe.settimeout(1)
        assert probe.connect_ex(('127.0.0.1',port))!=0,f'Refusing to reuse occupied test port {port}'
env=os.environ|{'COUPONHUB_APP_URL':origin,'COUPONHUB_HOST':'127.0.0.1','COUPONHUB_PORT':'13379','COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_SESSION_PATH':str(runtime/'sessions'),'COUPONHUB_RATE_LIMIT_DIR':str(runtime/'limits')}
flags=getattr(subprocess,'CREATE_NO_WINDOW',0)
# Published editorial fixture for the database-backed guide bookmark.
article_author=subprocess.check_output(mysql+["SELECT id FROM users WHERE username='admin_demo'"]).decode().strip()
subprocess.run(['C:/xampp/php/php.exe','scripts/publish-editorial.php','--apply','--expected-database=couponhub_merge_test','--author-id='+article_author],cwd=ROOT,env=env,check=True,stdout=subprocess.PIPE)
class CDP:
    def __init__(self,url):self.socket=websocket.create_connection(url,timeout=15,suppress_origin=True);self.next=0;self.responses=[]
    def call(self,method,params=None):
        self.next+=1;key=self.next
        self.socket.send(json.dumps({'id':key,'method':method,'params':params or {}}))
        while True:
            value=json.loads(self.socket.recv())
            if value.get('method')=='Network.responseReceived':self.responses.append(value['params']['response'])
            if value.get('id')==key:
                if 'error' in value:raise AssertionError(value['error'])
                return value.get('result',{})
    def evaluate(self,expression):
        value=self.call('Runtime.evaluate',{'expression':expression,'returnByValue':True,'awaitPromise':True})
        assert 'exceptionDetails' not in value,value
        return value.get('result',{}).get('value')
    def navigate(self,path):
        self.call('Page.navigate',{'url':origin+path})
        for _ in range(100):
            if self.evaluate("location.pathname===%s && document.readyState!=='loading'" % json.dumps(urllib.parse.urlparse(path).path)):
                self.evaluate('new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r)))');return
            time.sleep(.1)
        raise AssertionError('Navigation not ready: '+path)
def cookies_for(username,password):
    jar=http.cookiejar.CookieJar();client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    html=client.open(origin+'/login').read().decode('utf-8')
    csrf=re.search(r'name="csrf-token" content="([a-f0-9]+)"',html).group(1)
    data=urllib.parse.urlencode({'csrf':csrf,'account':username,'password':password}).encode()
    response=client.open(urllib.request.Request(origin+'/api/login.php',data=data,headers={'Accept':'application/json'}))
    assert json.load(response)['success']
    return list(jar)
server=browser=None;cdp=None;results=[]
expected_operations=len(json.loads((ROOT/'docs/api/route-catalog.json').read_text(encoding='utf-8')))
with (runtime/'php.log').open('w') as php_log,(runtime/'chrome.log').open('w') as chrome_log:
    try:
        server=subprocess.Popen(['C:/xampp/php/php.exe','-S','127.0.0.1:8098','router.php'],cwd=ROOT,env=env,stdout=php_log,stderr=php_log,creationflags=flags)
        for _ in range(50):
            assert server.poll() is None,'Owned local server exited'
            try:
                if urllib.request.urlopen(origin+'/login',timeout=2).status==200:break
            except OSError:time.sleep(.1)
        else:raise AssertionError('Local server not available')
        browser=subprocess.Popen([str(chrome),'--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--disable-background-networking','--disable-component-update','--disable-sync','--remote-debugging-address=127.0.0.1','--remote-debugging-port=9228','--user-data-dir='+str(runtime/'chrome-profile'),'about:blank'],stdout=chrome_log,stderr=chrome_log,creationflags=flags)
        for _ in range(100):
            assert browser.poll() is None,'Owned Chrome process exited'
            try:
                targets=json.load(urllib.request.urlopen('http://127.0.0.1:9228/json/list',timeout=2))
                pages=[t for t in targets if t.get('type')=='page']
                if pages:break
            except OSError:pass
            time.sleep(.1)
        else:raise AssertionError('Chrome debugging endpoint unavailable')
        cdp=CDP(pages[0]['webSocketDebuggerUrl']);cdp.call('Page.enable');cdp.call('Network.enable')
        suites=[('guest',None,None,['/','/categories','/coupons',f'/coupons/{coupon}','/campaigns',f'/campaigns/{campaign}','/login','/coupons?keyword=phase4_no_matches_97','/missing-phase4-page']),
                ('user','user_demo','UserDemo!2026',['/coupons/create','/saved-coupons','/account']),
                ('merchant','merchant_shops','MerchantDemo!2026',['/merchant','/merchant/coupons','/merchant/coupons/create','/merchant/campaigns','/merchant/campaigns/create'])]
        full_audit=os.environ.get('COUPONHUB_FULL_UI_AUDIT')=='1'
        banner_only=os.environ.get('COUPONHUB_BANNER_ONLY')=='1'
        if full_audit:
            suites[0][3].extend(['/campaign-calendar?calendarOpen=1','/articles','/articles/doc-dieu-kien-ma','/about','/contact','/terms','/privacy','/community-guidelines','/forgot-password'])
            suites[1][3].extend(['/dashboard','/my-coupons','/notifications','/merchant/apply'])
            suites.append(('admin','admin_demo','AdminDemo!2026',['/admin','/admin/users','/admin/merchants','/admin/merchant-requests','/admin/categories','/admin/coupons','/admin/reports','/admin/campaigns',f'/admin/campaigns/{campaign}','/admin/articles','/admin/articles/create']))
        if os.environ.get('COUPONHUB_PROVIDER_UI_TEST')=='1':
            offer=int(os.environ['COUPONHUB_REVIEW_OFFER_ID'])
            suites.append(('admin-integrations','admin_demo','AdminDemo!2026',['/admin/integrations',f'/admin/integrations/{offer}/review','/admin/api-docs']))
        if banner_only:
            suites=[('guest',None,None,['/campaigns'])]
        if os.environ.get('COUPONHUB_UI_ROLE'):suites=[s for s in suites if s[0]==os.environ['COUPONHUB_UI_ROLE']]
        for role,username,password,paths in suites:
            cdp.call('Network.clearBrowserCookies')
            if username:
                for cookie in cookies_for(username,password):
                    cdp.call('Network.setCookie',{'name':cookie.name,'value':cookie.value,'url':origin,'httpOnly':True,'sameSite':'Lax'})
            widths=[int(w) for w in os.environ.get('COUPONHUB_UI_WIDTHS','320,390,768,1440').split(',')];assert all(w in [320,390,768,1440] for w in widths)
            for width in widths:
                cdp.call('Emulation.setDeviceMetricsOverride',{'width':width,'height':900,'deviceScaleFactor':1,'mobile':width==390})
                for path in paths:
                    cdp.navigate(path)
                    state=cdp.evaluate("({width:innerWidth,scroll:document.documentElement.scrollWidth,title:document.title,text:document.body.innerText.slice(0,120),overflow:[...document.querySelectorAll('body *')].filter(e=>{const r=e.getBoundingClientRect();return r.width>0&&r.right>innerWidth+2&&getComputedStyle(e).position!=='fixed'}).slice(0,6).map(e=>e.tagName+'.'+e.className)})")
                    results.append({'role':role,'viewport':width,'path':path,**state})
                    (runtime/'results.json').write_text(json.dumps(results,ensure_ascii=False,indent=2),encoding='utf-8')
                    assert state['scroll']<=width+2,(role,width,path,state)
                    assert state['width']==width,(role,path,'viewport scaled by overflow',state)
                    assert not any(word in state['text'] for word in ['Fatal error','Warning:','Parse error']),state
                    if path=='/admin/api-docs':
                        for _ in range(100):
                            if cdp.evaluate("document.querySelectorAll('#swagger-ui .opblock').length==="+str(expected_operations)):break
                            time.sleep(.1)
                        else:raise AssertionError('Swagger must render all '+str(expected_operations)+' current route operations')
                        assert cdp.evaluate("!document.querySelector('#swagger-ui .errors-wrapper')"),'Swagger spec validation error'
                        assert cdp.evaluate('document.documentElement.scrollWidth<=innerWidth+2'),'Swagger horizontally overflows'
                    if path=='/admin/api-docs' and width==1440:
                        cdp.evaluate("(()=>{const op=[...document.querySelectorAll('#swagger-ui .opblock')].find(o=>o.querySelector('.opblock-summary-path')?.textContent.trim()==='/notifications/read');op.querySelector('.opblock-summary-control').click()})()")
                        for _ in range(50):
                            if cdp.evaluate("!!document.querySelector('#swagger-ui .try-out__btn')"):break
                            time.sleep(.1)
                        cdp.evaluate("document.querySelector('#swagger-ui .try-out__btn').click()")
                        for _ in range(50):
                            if cdp.evaluate("!!document.querySelector('#swagger-ui .execute')"):break
                            time.sleep(.1)
                        cdp.evaluate("document.querySelector('#swagger-ui .execute').click()")
                        for _ in range(100):
                            cdp.evaluate('document.readyState')
                            if any(r['url']==origin+'/notifications/read' and r['status']==200 for r in cdp.responses):break
                            time.sleep(.1)
                        else:
                            debug=cdp.evaluate("({inputs:[...document.querySelectorAll('#swagger-ui input,#swagger-ui textarea')].map(e=>({name:e.name,type:e.type,length:e.value.length,placeholder:e.placeholder})),text:document.querySelector('#swagger-ui').innerText.replace(/[a-f0-9]{64}/g,'[redacted]').slice(-4000)})")
                            debug['responses']=[{'url':r['url'],'status':r['status']} for r in cdp.responses[-15:]]
                            (runtime/'swagger-debug.json').write_text(json.dumps(debug,indent=2),encoding='utf-8')
                            raise AssertionError('Swagger Execute failed; '+str(runtime/'swagger-debug.json'))
                        print('PASS real Swagger Execute: session cookie + CSRF POST /notifications/read',flush=True)
                    if role=='admin-integrations' and '/review' in path and width==390:
                        cdp.evaluate("(()=>{const f=document.querySelector('[data-integration-form]');f.noValidate=true;f.elements.namedItem('title').value='Keep my review input';f.requestSubmit()})()")
                        for _ in range(50):
                            if cdp.evaluate("document.querySelectorAll('.invalid-feedback').length>0"):break
                            time.sleep(.1)
                        else:raise AssertionError('Import form must show inline validation')
                        assert cdp.evaluate("document.querySelector('[name=title]').value==='Keep my review input' && document.activeElement.matches('[data-integration-message]')"),'Import validation lost values or focus'
                    if role=='guest' and width<=390 and path=='/campaigns':
                        assert cdp.evaluate("(()=>{const cards=[...document.querySelectorAll('.event-feature')];return cards.length>0&&cards.every(p=>p.querySelector('.event-feature-copy').getBoundingClientRect().width>=p.getBoundingClientRect().width*.85)})()"),'campaign text still constrained to desktop split on phone'
                    if role=='guest' and width==1440 and path=='/campaigns':
                        assert cdp.evaluate("(()=>{const p=document.querySelector('[data-event-prev]');return p&&p.disabled})()"),'gallery previous control must start disabled'
                        if cdp.evaluate("(()=>{const r=document.querySelector('.event-more-list');return r.scrollWidth>r.clientWidth+2})()"):
                            cdp.evaluate("document.querySelector('[data-event-next]').click()")
                            for _ in range(30):
                                if cdp.evaluate("document.querySelector('.event-more-list').scrollLeft>100&&!document.querySelector('[data-event-prev]').disabled"):break
                                time.sleep(.05)
                            assert cdp.evaluate("document.querySelector('.event-more-list').scrollLeft>100&&!document.querySelector('[data-event-prev]').disabled"),'brand gallery next control does not reveal another campaign'
                        else:assert cdp.evaluate("document.querySelector('[data-event-next]').disabled"),'nonoverflow gallery should disable next control'
                    if path=='/admin':
                        assert cdp.evaluate("!!document.querySelector('.workspace-header')&&!document.querySelector('.public-header,.public-footer,.floating-support')"),'Admin must use the task workspace shell'
                        assert cdp.evaluate("![...document.querySelectorAll('a')].some(a=>a.textContent.includes('Xem trang người dùng'))"),'Admin workspace still promotes public shopping'
                    if path=='/merchant':
                        assert cdp.evaluate("!!document.querySelector('.workspace-header .workspace-preview-link')&&!document.querySelector('.public-footer')"),'Merchant workspace needs the small public preview entry'
                    if role=='guest' and path=='/coupons':
                        assert cdp.evaluate("getComputedStyle(document.querySelector('.community-cover'),'::before').display==='none'"),'obsolete image scrim obscures the new community copy'
                        assert cdp.evaluate("Number(getComputedStyle(document.querySelector('.community-cover-photo')).zIndex)>=0"),'community photo is painted behind its mobile background'
                    if width<=390 and cdp.evaluate("!!document.querySelector('.public-header')"):
                        controls=cdp.evaluate("[...document.querySelectorAll('.public-header .brand,.public-header .header-account,.public-header .mobile-site-menu')].filter(e=>getComputedStyle(e).display!=='none').map(e=>{const r=e.getBoundingClientRect();return {name:e.className,left:r.left,right:r.right}})")
                        assert all(c['left']>=0 and c['right']<=width+1 for c in controls),(role,width,path,'clipped header controls',controls)
                        controls.sort(key=lambda c:c['left'])
                        assert all(a['right']<=b['left']+1 for a,b in zip(controls,controls[1:])),(role,width,path,'overlapping header controls',controls)
                        assert cdp.evaluate("(()=>{const b=document.querySelector('.header-notification');return b&&getComputedStyle(b).display!=='none'&&b.getBoundingClientRect().width>0})()"),(role,width,path,'notification bell is hidden')
                    if width==390 and path in ['/','/account','/admin','/merchant']:
                        assert cdp.evaluate("(()=>{const d=document.querySelector('.header-notification-wrapper');if(!d)return false;d.querySelector('summary').click();const r=d.querySelector('.notification-dropdown').getBoundingClientRect();const ok=d.open&&r.left>=0&&r.right<=innerWidth;d.open=false;return ok})()"),(role,path,'notification panel unavailable or clipped')
                    if banner_only or (full_audit and width in [390,1440]) or (width==390 and path in ['/','/coupons',f'/coupons/{coupon}','/merchant/coupons','/merchant/coupons/create']):
                        cdp.evaluate("Promise.all([...document.images].filter(i=>i.loading!=='lazy').map(i=>i.decode().catch(()=>{})))")
                        image=cdp.call('Page.captureScreenshot',{'format':'png'})['data']
                        name=role+'-'+re.sub('[^a-zA-Z0-9]+','-',path).strip('-')+'-'+str(width)+'.png'
                        (runtime/name).write_bytes(base64.b64decode(image))
                    if width==390 and role=='guest' and path=='/':
                        assert cdp.evaluate("(()=>{const m=document.querySelector('.mobile-site-menu');m.querySelector('summary').click();return m.open&&m.querySelectorAll('a').length>=5})()"),'mobile navigation cannot open'
                    if full_audit and width==390 and role=='guest' and path=='/categories':
                        assert cdp.evaluate("[...document.querySelectorAll('.category-directory-link > div')].every(e=>e.getBoundingClientRect().width>=100)"),'category labels squeezed into obsolete index column'
                        assert cdp.evaluate("(()=>{document.querySelector('[data-category-open]').click();return document.querySelector('dialog.couponhub-category-dialog').open})()"),'category picker cannot open'
                        assert cdp.evaluate("(()=>{const d=document.querySelector('dialog.couponhub-category-dialog'),s=d.querySelector('input');s.value='phase4-no-match-z97';s.dispatchEvent(new Event('input',{bubbles:true}));return !d.querySelector('[data-category-dialog-empty]').hidden&&d.querySelectorAll('[data-category-dialog-options] > button:not([hidden])').length===0})()"),'category search empty state fails'
                        cdp.evaluate("document.querySelector('dialog.couponhub-category-dialog [data-category-close]').click()")
                        cdp.evaluate('new Promise(r=>requestAnimationFrame(r))')
                        assert cdp.evaluate("!document.querySelector('dialog.couponhub-category-dialog').open&&document.activeElement.matches('[data-category-open]')"),'category close does not restore focus'
                    if full_audit and width==390 and role in ['admin','merchant'] and path in ['/admin','/merchant']:
                        assert cdp.evaluate("(()=>{const m=document.querySelector('[data-workspace-menu]');m.querySelector('summary').click();return m.open&&m.querySelectorAll('nav a').length>=3})()"),'workspace navigation cannot open'
            if role=='user':
                cdp.navigate(f'/coupons/{coupon}')
                assert cdp.evaluate("!!window.jQuery&&!!window.CouponHub&&!!document.querySelector('#favorite-btn')"),'interaction scripts not ready'
                cdp.call('Network.emulateNetworkConditions',{'offline':True,'latency':0,'downloadThroughput':-1,'uploadThroughput':-1})
                try:
                    cdp.evaluate("document.querySelector('#favorite-btn').click()")
                    for _ in range(50):
                        if cdp.evaluate("!document.querySelector('#favorite-btn').disabled&&!!document.querySelector('#interaction-message .alert-danger')"):break
                        time.sleep(.1)
                    else:raise AssertionError('offline favorite action must show feedback and unlock button')
                    assert cdp.evaluate("!!document.querySelector('#coupon-detail-page .offer-value')"),'network error erased coupon content'
                finally:cdp.call('Network.emulateNetworkConditions',{'offline':False,'latency':0,'downloadThroughput':-1,'uploadThroughput':-1})
                print('PASS real browser offline favorite: error feedback, content retained, button restored',flush=True)
            print('PASS browser layout: '+role+' at 320/390/768/1440',flush=True)
        if full_audit:
            cdp.call('Emulation.setEmulatedMedia',{'features':[{'name':'prefers-reduced-motion','value':'reduce'}]})
            cdp.navigate('/categories')
            assert cdp.evaluate("(()=>{document.querySelector('[data-category-open]').click();return getComputedStyle(document.querySelector('dialog.couponhub-category-dialog')).animationName==='none'})()"),'reduced motion must disable dialog entrance'
            cdp.call('Input.dispatchKeyEvent',{'type':'keyDown','key':'Escape','code':'Escape','windowsVirtualKeyCode':27})
            cdp.call('Input.dispatchKeyEvent',{'type':'keyUp','key':'Escape','code':'Escape','windowsVirtualKeyCode':27})
            cdp.evaluate('new Promise(r=>requestAnimationFrame(r))')
            assert cdp.evaluate("!document.querySelector('dialog.couponhub-category-dialog').open&&document.activeElement.matches('[data-category-open]')"),'keyboard Escape must close dialog and restore focus'
            print('PASS reduced motion and real keyboard Escape/focus restoration',flush=True)
        print('PASS real Chrome: '+str(len(results))+' page/viewport checks; '+str(runtime),flush=True)
    finally:
        if cdp:
            try:cdp.call('Browser.close')
            except Exception:pass
            cdp.socket.close()
        if browser:
            try:browser.wait(timeout=10)
            except subprocess.TimeoutExpired:browser.terminate();browser.wait(timeout=10)
        if server:server.terminate();server.wait(timeout=10)
