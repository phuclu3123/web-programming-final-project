"""Real Chrome/XHR acceptance. Default isolated preview; explicit opt-in for one live customer test comment."""
import base64,http.cookiejar,json,os,socket,subprocess,time,urllib.request,urllib.parse,websocket
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
live=os.environ.get('COUPONHUB_ARTICLE_LIVE_ACCEPTANCE')=='1'
assert live or os.environ.get('COUPONHUB_RUN_DB_TEST')=='1'
origin='http://localhost:8080' if live else 'http://127.0.0.1:8096'
base=origin+('/CouponHub_UI' if live else '')
path='/articles/doc-dieu-kien-ma'
runtime=ROOT/'.runtime'/('article-browser-'+str(time.time_ns()));runtime.mkdir()
with socket.socket() as s:assert s.connect_ex(('127.0.0.1',9227))!=0,'Debug port in use'
class CDP:
 def __init__(self,url):self.s=websocket.create_connection(url,timeout=20,suppress_origin=True);self.n=0
 def call(self,method,params=None):
  self.n+=1;key=self.n;self.s.send(json.dumps({'id':key,'method':method,'params':params or {}}))
  while True:
   r=json.loads(self.s.recv())
   if r.get('id')==key:
    assert 'error' not in r,r
    return r.get('result',{})
 def js(self,expression):
  r=self.call('Runtime.evaluate',{'expression':expression,'returnByValue':True,'awaitPromise':True});assert 'exceptionDetails' not in r,r
  return r.get('result',{}).get('value')
 def wait(self,expression):
  for _ in range(100):
   if self.js(expression):return
   time.sleep(.1)
  raise AssertionError(expression)
 def go(self,url):
  self.call('Page.navigate',{'url':url});self.wait("location.href==="+json.dumps(url)+" && document.readyState==='complete'")
browser=None;cdp=None;server=None;result={}
try:
 if not live:
  with socket.socket() as s:assert s.connect_ex(('127.0.0.1',8096))!=0,'Test port in use'
  (runtime/'sessions').mkdir()
  env=os.environ|{'COUPONHUB_APP_URL':origin,'COUPONHUB_HOST':'127.0.0.1','COUPONHUB_PORT':'13379','COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_SESSION_PATH':str(runtime/'sessions'),'COUPONHUB_RATE_LIMIT_DIR':str(runtime/'limits'),'COUPONHUB_TURNSTILE_MODE':'off'}
  server=subprocess.Popen(['C:/xampp/php/php.exe','-S','127.0.0.1:8096','router.php'],cwd=ROOT,env=env,stdout=(runtime/'server.log').open('w'),stderr=subprocess.STDOUT,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
  for _ in range(60):
   try:
    if urllib.request.urlopen(base+'/login',timeout=2).status==200:break
   except OSError:time.sleep(.1)
 browser=subprocess.Popen(['C:/Program Files/Google/Chrome/Application/chrome.exe','--headless=new','--disable-gpu','--no-first-run','--disable-background-networking','--remote-debugging-address=127.0.0.1','--remote-debugging-port=9227','--user-data-dir='+str(runtime/'profile'),'about:blank'],stdout=(runtime/'chrome.log').open('w'),stderr=subprocess.STDOUT,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
 for _ in range(100):
  try:
   pages=json.load(urllib.request.urlopen('http://127.0.0.1:9227/json/list'));break
  except OSError:time.sleep(.1)
 cdp=CDP(next(p['webSocketDebuggerUrl'] for p in pages if p['type']=='page'));cdp.call('Page.enable');cdp.call('Network.enable')
 cdp.go(base+path)
 assert cdp.js("!!document.querySelector('[data-auth-open]') && !document.querySelector('[data-comment-form]')")
 cdp.js("document.querySelector('.article-comment-login a').click()")
 cdp.wait("document.querySelector('#loginModal').open")
 cdp.js("document.querySelector('#loginModal').close()")
 jar=http.cookiejar.CookieJar();http=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
 html=http.open(base+'/login').read().decode();import re
 token=re.search(r'name="csrf-token" content="([a-f0-9]+)"',html)[1]
 response=http.open(urllib.request.Request(base+'/api/login.php',data=urllib.parse.urlencode({'csrf':token,'account':'user_demo','password':'UserDemo!2026'}).encode(),headers={'Accept':'application/json'}))
 assert json.load(response)['success']
 for cookie in jar:cdp.call('Network.setCookie',{'name':cookie.name,'value':cookie.value,'url':origin,'path':cookie.path,'httpOnly':True})
 cdp.go(base+path)
 cdp.wait("!!document.querySelector('[data-comment-form]')")
 result['viewports']=[]
 for width in [320,390,768,1440]:
  cdp.call('Emulation.setDeviceMetricsOverride',{'width':width,'height':900,'deviceScaleFactor':1,'mobile':False})
  cdp.js("document.querySelector('#article-comments').scrollIntoView({block:'start'})")
  cdp.js('new Promise(r=>setTimeout(r,150))')
  state=cdp.js("(()=>{const f=document.querySelector('[data-comment-form]'),b=f.querySelector('button').getBoundingClientRect();return {viewport:innerWidth,scroll:document.documentElement.scrollWidth,buttonWidth:b.width,buttonHeight:b.height,label:!!f.querySelector('label')}})()")
  assert state['scroll']<=width+1 and state['buttonHeight']>=44 and state['label'],state
  result['viewports'].append(state)
  (runtime/f'comments-{width}.png').write_bytes(base64.b64decode(cdp.call('Page.captureScreenshot',{'format':'png','captureBeyondViewport':False})['data']))
 text='[Kiểm thử chức năng] Bình luận được gửi bằng tài khoản khách hàng qua AJAX và lưu vào MySQL; tải lại trang vẫn đọc được.' if live else 'Browser acceptance: <img src=x onerror=window.articleXss=1> is plain text.'
 cdp.js('document.querySelector("[name=body]").value='+json.dumps(text))
 # Offline failure must retain both input and idempotency key.
 key=cdp.js('document.querySelector("[name=request_id]").value')
 cdp.call('Network.emulateNetworkConditions',{'offline':True,'latency':0,'downloadThroughput':0,'uploadThroughput':0})
 cdp.js('document.querySelector("[data-comment-form]").requestSubmit()')
 cdp.wait("document.querySelector('#article-comment-feedback').classList.contains('is-error')")
 assert cdp.js('document.querySelector("[name=body]").value')==text
 assert cdp.js('document.querySelector("[name=request_id]").value')==key
 cdp.call('Network.emulateNetworkConditions',{'offline':False,'latency':0,'downloadThroughput':-1,'uploadThroughput':-1})
 cdp.js('document.querySelector("[data-comment-form]").requestSubmit()')
 cdp.wait('!document.querySelector("[data-article-comments]").hasAttribute("aria-busy") && document.querySelector("[name=body]").value===""')
 assert cdp.js('[...document.querySelectorAll(".article-comment-body")].some(n=>n.textContent==='+json.dumps(text)+')')
 assert cdp.js('document.querySelector("[name=request_id]").value')!=key
 cid=cdp.js('document.querySelector("[data-comment-id]").dataset.commentId');assert cid.isdigit()
 assert not cdp.js('!!window.articleXss || !!document.querySelector(".article-comment-body img")')
 cdp.call('Emulation.setDeviceMetricsOverride',{'width':390,'height':900,'deviceScaleFactor':1,'mobile':False})
 cdp.js("document.querySelector('#article-comments').scrollIntoView({block:'start'})")
 (runtime/'persisted-comment-390.png').write_bytes(base64.b64decode(cdp.call('Page.captureScreenshot',{'format':'png','captureBeyondViewport':False})['data']))
 previous_document=cdp.js('performance.timeOrigin')
 cdp.call('Page.reload',{'ignoreCache':True});cdp.wait('performance.timeOrigin!=='+json.dumps(previous_document)+' && document.readyState==="complete" && !!document.querySelector("[data-comment-form]")')
 assert cdp.js('[...document.querySelectorAll(".article-comment-body")].some(n=>n.textContent==='+json.dumps(text)+')')
 if not live:
  cdp.js('window.confirm=()=>true;document.querySelector("[data-comment-id=\\"'+cid+'\\"] [data-comment-delete]").requestSubmit()')
  cdp.wait('!document.querySelector("[data-comment-id=\\"'+cid+'\\"]")')
  result['delete_xhr']='PASS'
 result|={'status':'PASS','mode':'LIVE_PERSISTED_CUSTOMER_COMMENT' if live else 'ISOLATED','comment_id':cid,'request_id':key,'body':text,'ajax_json':'PASS','reload':'PASS','offline_retains_input':'PASS','xss_plain_text':'PASS','guest_login_popup':'PASS'}
 (runtime/'result.json').write_text(json.dumps(result,ensure_ascii=False,indent=2),encoding='utf-8')
 print('PASS Chrome article comments; evidence:',runtime,flush=True)
finally:
 if cdp:
  try:cdp.call('Browser.close')
  except Exception:pass
  cdp.s.close()
 if browser:
  try:browser.wait(timeout=10)
  except subprocess.TimeoutExpired:browser.terminate();browser.wait(timeout=10)
 if server:server.terminate();server.wait(timeout=10)
