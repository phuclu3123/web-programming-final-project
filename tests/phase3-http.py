"""Phase-3 form/HTTP acceptance on disposable MariaDB only; no external services."""
import http.cookiejar
import os
from pathlib import Path
import re
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT=Path(__file__).resolve().parents[1]
if os.environ.get('COUPONHUB_RUN_HTTP_TEST')!='1': raise SystemExit('SKIP: opt in to isolated HTTP testing')
assert not (ROOT/'config/local.php').exists()
mysql=['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379','--user=root','--default-character-set=utf8mb4','--batch','--skip-column-names','couponhub_merge_test','-e']
def query(sql): return subprocess.check_output(mysql+[sql]).decode('utf-8').strip()
assert query('SELECT @@port')=='13379'
shop=int(query("SELECT id FROM merchants WHERE slug='demo-shopee'"))
cid=int(query(f"SELECT id FROM coupons WHERE merchant_id={shop} AND source='OFFICIAL' AND moderation_status='APPROVED' AND publication_status='ENABLED' AND deleted_at IS NULL ORDER BY id LIMIT 1"))
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs): return None
class Client:
    def __init__(self,origin,base):
        self.origin,self.base=origin,base
        self.open=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedirect())
    def request(self,path,data=None,headers=None):
        body=urllib.parse.urlencode(data).encode() if data is not None else None
        try: response=self.open.open(urllib.request.Request(self.origin+self.base+path,data=body,headers=headers or {}),timeout=15)
        except urllib.error.HTTPError as error: response=error
        return response.status,response.headers,response.read().decode('utf-8')
    def token(self,path='/'):
        status,_,body=self.request(path);assert status==200,(path,status,body[:160])
        return re.search(r'name="csrf-token" content="([a-f0-9]+)"',body).group(1)
    def login(self,account,password):
        status,_,body=self.request('/login',{'csrf':self.token('/login'),'account':account,'password':password})
        assert status==303,(account,status,body[:100])
for base,port in [('',8078),('/CouponHub_UI',8080)]:
    origin=f'http://127.0.0.1:{port}'
    runtime=ROOT/'.runtime'/f'phase3-http-{port}-{time.time_ns()}';runtime.mkdir(parents=True)
    (runtime/'sessions').mkdir()
    env=os.environ|{'COUPONHUB_HOST':'127.0.0.1','COUPONHUB_PORT':'13379','COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_APP_URL':origin+base,'COUPONHUB_SESSION_PATH':str(runtime/'sessions'),'COUPONHUB_RATE_LIMIT_DIR':str(runtime/'limits')}
    command=['C:/xampp/php/php.exe','-S',f'127.0.0.1:{port}']+(['-t',str(ROOT.parent)] if base else ['router.php'])
    with (runtime/'php.log').open('w',encoding='utf-8') as log:
        process=subprocess.Popen(command,cwd=ROOT,env=env,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
        try:
            guest=Client(origin,base)
            for _ in range(50):
                if process.poll() is not None: raise AssertionError('Test server failed to start')
                try:
                    if guest.request('/campaigns')[0]==200: break
                except OSError: pass
                time.sleep(.1)
            else: raise AssertionError('Test server unavailable')
            admin=Client(origin,base);admin.login('admin_demo','AdminDemo!2026')
            merchant=Client(origin,base);merchant.login('merchant_shops','MerchantDemo!2026')
            user=Client(origin,base);user.login('user_demo','UserDemo!2026')
            token=merchant.token('/merchant/campaigns/create')
            fields={'_token':token,'merchant_id':shop,'name':f'HTTP Phase3 {port}','description':'Isolated campaign','starts_at':time.strftime('%Y-%m-%dT%H:%M',time.localtime(time.time()-86400)),'ends_at':time.strftime('%Y-%m-%dT%H:%M',time.localtime(time.time()+604800)),'status':'PUBLISHED','banner_path':'assets/images/hero/category-food.webp'}
            assert merchant.request('/merchant/campaigns',fields|{'_token':'invalid'})[0]==403
            assert user.request('/merchant/campaigns',fields|{'_token':user.token('/account')})[0]==403
            status,headers,_=merchant.request('/merchant/campaigns',fields)
            assert status in (302,303) and re.search(r'/merchant/campaigns/\d+$',headers['Location']),headers['Location']
            campaign=int(headers['Location'].split('/')[-1])
            assert query(f'SELECT banner_path FROM campaigns WHERE id={campaign}')==fields['banner_path']
            status,_,html=guest.request(f'/campaigns/{campaign}')
            assert status==200 and origin+base+'/assets/images/hero/category-food.webp' in html
            assert merchant.request(f'/merchant/campaigns/{campaign}/edit',fields|{'banner_path':''})[0] in (302,303)
            assert query(f'SELECT banner_path IS NULL FROM campaigns WHERE id={campaign}')=='1'
            assert 'category-fashion.webp' in guest.request(f'/campaigns/{campaign}')[2]
            assert merchant.request(f'/merchant/campaigns/{campaign}/edit',fields|{'banner_path':'javascript:alert(1)'})[0] in (302,303)
            assert 'javascript:alert(1)' in merchant.request(f'/merchant/campaigns/{campaign}/edit')[2], 'validation must preserve entered values'
            selected=guest.request('/campaigns?'+urllib.parse.urlencode({'platform':shop,'usage_channel':'ONLINE'}))[2]
            assert re.search(fr'value="{shop}" selected',selected) and 'name="platform"' in selected
            assert guest.request('/campaigns?platform[]=1')[0]==422
            detail=guest.request('/campaigns/1?'+urllib.parse.urlencode({'platform':shop}),headers={'X-Requested-With':'XMLHttpRequest'})
            assert detail[0]==200 and 'id="campaign-coupon-results"' in detail[2]
            if not base:
                admin_token=admin.token('/admin/coupons')
                reason='Fraud verified during isolated HTTP review.'
                assert user.request(f'/admin/coupons/{cid}/revoke',{'_token':user.token('/account'),'reason':reason})[0]==403
                assert admin.request(f'/admin/coupons/{cid}/revoke',{'_token':'bad','reason':reason})[0]==403
                assert admin.request(f'/admin/coupons/{cid}/revoke',{'_token':admin_token,'reason':reason})[0]==303
                assert query(f'SELECT publication_status FROM coupons WHERE id={cid}')=='ARCHIVED'
                assert guest.request(f'/coupons/{cid}')[0]==404
                assert merchant.request(f'/merchant/coupons/{cid}/edit')[0]==404
                assert merchant.request(f'/merchant/coupons/{cid}/disable',{'_token':token})[0] in (302,303)
                assert query(f'SELECT publication_status FROM coupons WHERE id={cid}')=='ARCHIVED'
                assert admin.request(f'/admin/merchants/{shop}/status',{'_token':admin_token,'action':'LOCK','note':reason})[0] in (302,303)
                assert query(f'SELECT status FROM merchants WHERE id={shop}')=='LOCKED'
                assert merchant.request('/merchant/campaigns',fields)[0] in (302,303)
                assert query(f"SELECT COUNT(*) FROM campaigns WHERE name='HTTP Phase3 {port}'")=='1'
                assert admin.request(f'/admin/merchants/{shop}/status',{'_token':admin_token,'action':'UNLOCK','note':'Verification complete in HTTP isolated review.'})[0] in (302,303)
                assert query(f'SELECT publication_status FROM coupons WHERE id={cid}')=='ARCHIVED'
            query(f'UPDATE campaigns SET ends_at=UTC_TIMESTAMP()-INTERVAL 1 SECOND WHERE id={campaign}')
            status,_,html=guest.request(f'/campaigns/{campaign}')
            assert status==200 and 'Chiến dịch đã kết thúc.' in html
            for path in ['/','/portal','/categories','/coupons','/campaigns','/campaign-calendar','/articles','/about','/terms','/privacy','/contact']:
                assert guest.request(path)[0]==200,path
            for path in ['/dashboard','/account','/saved-coupons','/my-coupons','/notifications']:
                assert user.request(path)[0]==200,path
            print(f'PASS: phase 3 HTTP {base or "/"}: banner create/edit/default, filters, role/CSRF, expired campaign and page crawl.',flush=True)
        finally:
            process.terminate();process.wait(timeout=10)
    log_text=(runtime/'php.log').read_text(encoding='utf-8')
    assert not re.search(r'PHP (Warning|Fatal|Parse)',log_text),log_text[-2000:]
