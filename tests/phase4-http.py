"""HTTP acceptance on root and subfolder; isolated MariaDB only, no providers."""
import http.cookiejar
import json
import os
from pathlib import Path
import re
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT=Path(__file__).resolve().parents[1]
if os.environ.get('COUPONHUB_RUN_HTTP_TEST')!='1':raise SystemExit('SKIP: isolated HTTP opt-in required')
assert not (ROOT/'config/local.php').exists()
mysql=['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379','--user=root','--batch','--skip-column-names','couponhub_merge_test','-e']
def sql(query):return subprocess.check_output(mysql+[query]).decode('utf-8').strip()
assert sql('SELECT @@port')=='13379'
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs):return None
class Client:
    def __init__(self,origin,base):
        self.origin,self.base=origin,base;self.cookies=http.cookiejar.CookieJar()
        self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies),NoRedirect())
    def request(self,path,data=None,headers=None):
        body=data if isinstance(data,bytes) else urllib.parse.urlencode(data).encode() if data is not None else None
        try:r=self.opener.open(urllib.request.Request(self.origin+self.base+path,data=body,headers=headers or {}),timeout=15)
        except urllib.error.HTTPError as error:r=error
        return r.status,r.headers,r.read().decode('utf-8')
    def token(self,path='/login'):
        status,_,body=self.request(path);assert status==200,(path,status,body[:150])
        return re.search(r'name="csrf-token" content="([a-f0-9]+)"',body).group(1)
    def login(self,name,password):
        status,headers,body=self.request('/login',{'csrf':self.token(),'account':name,'password':password})
        assert status==303,(name,status,body[:150])
        return headers['Location']
routes=re.findall(r"\$router->(get|post)\('(/admin[^']*)'",(ROOT/'config/routes.php').read_text(encoding='utf-8'))
assert len(routes)>20
for base,port in [('',8096),('/CouponHub_UI',8097)]:
    origin=f'http://127.0.0.1:{port}';runtime=ROOT/'.runtime'/('phase4-http-'+str(port)+'-'+str(time.time_ns()));runtime.mkdir(parents=True)
    (runtime/'sessions').mkdir()
    env=os.environ|{'COUPONHUB_HOST':'127.0.0.1','COUPONHUB_PORT':'13379','COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_APP_URL':origin+base,'COUPONHUB_SESSION_PATH':str(runtime/'sessions'),'COUPONHUB_RATE_LIMIT_DIR':str(runtime/'limits')}
    command=['C:/xampp/php/php.exe','-S',f'127.0.0.1:{port}']+(['-t',str(ROOT.parent)] if base else ['router.php'])
    with (runtime/'server.log').open('w',encoding='utf-8') as log:
        server=subprocess.Popen(command,cwd=ROOT,env=env,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
        try:
            guest=Client(origin,base)
            for _ in range(50):
                if server.poll() is not None:raise RuntimeError('Server did not start')
                try:
                    if guest.request('/login')[0]==200:break
                except OSError:pass
                time.sleep(.1)
            else:raise RuntimeError('Server unavailable')
            _,headers,html=guest.request('/login')
            assert 'no-store' in headers['Cache-Control']
            assert headers['X-Content-Type-Options']=='nosniff' and headers['X-Frame-Options']=='DENY'
            cookie=';'.join(headers.get_all('Set-Cookie') or [])
            # A fresh client confirms attributes; existing requests need not issue a new cookie.
            _,fresh_headers,_=Client(origin,base).request('/login')
            cookie=';'.join(fresh_headers.get_all('Set-Cookie') or [])
            assert 'HttpOnly' in cookie and 'SameSite=Lax' in cookie
            admin=Client(origin,base);assert admin.login('admin_demo','AdminDemo!2026')==origin+base+'/admin'
            user=Client(origin,base);user.login('user_demo','UserDemo!2026')
            merchant=Client(origin,base);assert merchant.login('merchant_shops','MerchantDemo!2026')==origin+base+'/merchant'
            csrf=user.token('/account');admin_csrf=admin.token('/admin')
            for method,path in routes:
                path=re.sub(r'\{[^}]+\}','1',path)
                payload={'_token':csrf} if method=='post' else None
                assert user.request(path,payload,{'Accept':'application/json'})[0]==403,(method,path,'User')
                assert merchant.request(path,{'_token':merchant.token('/account')} if method=='post' else None,{'Accept':'application/json'})[0]==403,(method,path,'Merchant')
                assert guest.request(path,{} if method=='post' else None,{'Accept':'application/json'})[0]==401,(method,path,'Guest')
                if method=='post':
                    assert admin.request(path,{'_token':'bad'}, {'Accept':'application/json'})[0]==403,(path,'CSRF')
                    assert admin.request(path,{'_token':admin_csrf,'note[]':'bad'}, {'Accept':'application/json'})[0]==422,(path,'array field')
            print(f'PASS {base or "/"}: all {len(routes)} Admin routes reject wrong roles; every Admin POST rejects invalid CSRF',flush=True)
            assert user.request('/notifications/read',{'_token':csrf},{'Origin':'https://evil.test','Accept':'application/json'})[0]==403
            assert user.request('/notifications/read',{'_token':csrf},{'Origin':origin,'Sec-Fetch-Site':'cross-site','Accept':'application/json'})[0]==403
            assert user.request('/notifications/read',b'x'*1048577,{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'})[0]==413
            for provider in ['google','github','facebook']:
                status,headers,body=guest.request('/api/oauth/'+provider+'-callback.php?state=bad&code=bad')
                assert status==403 and 'no-store' in headers['Cache-Control'],(provider,status)
            # No cookies from another client/session are accepted as bearer JWTs.
            assert Client(origin,base).request('/admin',headers={'Authorization':'Bearer forged.jwt.token','Accept':'application/json'})[0]==401
            for index in range(61):
                status,headers,body=user.request('/notifications/read',{'_token':csrf},{'Accept':'application/json','Origin':origin})
                assert status==(200 if index<60 else 429),(index,status,body[:160])
            assert int(headers['Retry-After'])>0 and json.loads(body)['success'] is False
            # Account throttle persists even when every attempt uses a fresh cookie jar.
            for index in range(13):
                visitor=Client(origin,base)
                status,headers,body=visitor.request('/api/login.php',{'csrf':visitor.token(),'account':'unknown-phase4','password':'WrongPassword!2026'},{'Accept':'application/json'})
                assert status==(422 if index<12 else 429),(index,status,body[:160])
            assert int(headers['Retry-After'])>0
            uid=int(sql("SELECT id FROM users WHERE username='merchant_shops'"))
            sql(f"UPDATE users SET status='LOCKED',auth_version=auth_version+1 WHERE id={uid}")
            sql(f"UPDATE users SET status='ACTIVE',auth_version=auth_version+1 WHERE id={uid}")
            assert merchant.request('/merchant',headers={'Accept':'application/json'})[0]==401
            assert admin.request('/logout',{'_token':admin_csrf})[0]==303
            assert admin.request('/admin',headers={'Accept':'application/json'})[0]==401
            print(f'PASS {base or "/"}: headers/cookies, origins, body limit, OAuth rejection, throttles, revoked session, logout',flush=True)
        finally:
            server.terminate();server.wait(timeout=10)
    errors=(runtime/'server.log').read_text(encoding='utf-8',errors='replace')
    assert not re.search(r'PHP (?:Fatal error|Parse error|Warning)',errors),errors[-2500:]
print('PASS phase 4 HTTP acceptance; no external identity/email calls')
