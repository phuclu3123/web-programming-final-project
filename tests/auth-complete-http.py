"""Isolated PHP HTTP + local SMTP fixture. No actual email or OAuth API calls."""
import base64
from email import policy
from email.parser import BytesParser
import http.cookiejar
import json
import os
from pathlib import Path
import re
import socketserver
import subprocess
import threading
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT=Path(__file__).resolve().parents[1]
if os.environ.get('COUPONHUB_RUN_HTTP_TEST')!='1': raise SystemExit('SKIP: isolated test opt-in required')
assert not (ROOT/'config/local.php').exists()
messages=[]
class SMTP(socketserver.StreamRequestHandler):
    def handle(self):
        self.wfile.write(b'220 local fixture\r\n')
        while True:
            line=self.rfile.readline()
            if not line: break
            command=line.decode().upper()
            if command.startswith('EHLO'): self.wfile.write(b'250 local fixture\r\n')
            elif command.startswith('DATA'):
                self.wfile.write(b'354 continue\r\n'); body=b''
                while True:
                    part=self.rfile.readline()
                    if part in (b'.\r\n',b''): break
                    body+=part
                msg=BytesParser(policy=policy.default).parsebytes(body)
                messages.append(msg.get_content())
                self.wfile.write(b'250 accepted\r\n')
            elif command.startswith('QUIT'): self.wfile.write(b'221 bye\r\n'); break
            else: self.wfile.write(b'250 ok\r\n')
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs): return None
ORIGIN='http://127.0.0.1:8079'
class Client:
    def __init__(self):
        self.cookies=http.cookiejar.CookieJar()
        self.open=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies),NoRedirect())
    def request(self,path,data=None,headers=None):
        body=urllib.parse.urlencode(data).encode() if data is not None else None
        try: response=self.open.open(urllib.request.Request(ORIGIN+path,data=body,headers=headers or {}),timeout=15)
        except urllib.error.HTTPError as error: response=error
        return response.status,response.headers,response.read().decode('utf-8')
    def token(self,path='/login'):
        status,_,html=self.request(path); assert status==200,(path,status)
        return re.search(r'name="csrf-token" content="([a-f0-9]+)"',html).group(1)
    def login(self,password='UserDemo!2026'):
        status,headers,body=self.request('/api/login.php',{'csrf':self.token(),'account':'user_community','password':password},{'Accept':'application/json'})
        assert status==200 and json.loads(body)['success'],(status,body)
        return headers
runtime=ROOT/'.runtime'/('auth-http-'+str(time.time_ns())); runtime.mkdir(parents=True)
session=runtime/'sessions'; session.mkdir()
env=os.environ|{'COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_PORT':'13379','COUPONHUB_HOST':'127.0.0.1',
    'COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_APP_URL':ORIGIN,
    'COUPONHUB_MAIL_DRIVER':'smtp','COUPONHUB_SMTP_HOST':'127.0.0.1','COUPONHUB_SMTP_PORT':'11025',
    'COUPONHUB_SMTP_ENCRYPTION':'none','COUPONHUB_MAIL_FROM':'test@couponhub.test',
    'COUPONHUB_OTP_SECRET':'isolated-test-secret-not-for-production-2026',
    'COUPONHUB_RATE_LIMIT_DIR':str(runtime/'limits'),'COUPONHUB_SESSION_PATH':str(session),
    'COUPONHUB_GITHUB_CLIENT_ID':'fixture-public-client','COUPONHUB_GITHUB_CLIENT_SECRET':'fixture-private-secret'}
smtp=socketserver.ThreadingTCPServer(('127.0.0.1',11025),SMTP)
threading.Thread(target=smtp.serve_forever,daemon=True).start()
with (runtime/'php.log').open('w',encoding='utf-8') as log:
    proc=subprocess.Popen(['C:/xampp/php/php.exe','-S','127.0.0.1:8079','router.php'],cwd=ROOT,env=env,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    try:
        client=Client()
        for _ in range(50):
            try:
                if client.request('/login')[0]==200: break
            except OSError: pass
            time.sleep(.1)
        else: raise AssertionError('PHP test server unavailable')
        token=client.token('/forgot-password')
        html=client.request('/forgot-password')[2]
        assert 'data-auth-auto="forgot"' in html and 'id="resetOtpForm"' in html
        assert client.request('/forgot-password',{'csrf':'bad','email':'unknown@example.test'},{'Accept':'application/json'})[0]==403
        status,_,unknown=client.request('/forgot-password',{'csrf':token,'email':'unknown@example.test'},{'Accept':'application/json'})
        assert status==200 and json.loads(unknown)['success'] and not messages
        a=Client(); b=Client()
        headers=a.login(); b.login()
        cookie=';'.join(headers.get_all('Set-Cookie',[])).lower()
        assert 'httponly' in cookie and 'samesite=lax' in cookie and 'expires=' in cookie
        assert any(not c.discard and c.expires for c in a.cookies if c.name=='COUPONHUBSESSID')
        assert 'fixture-private-secret' not in a.request('/account')[2]
        assert a.request('/api/oauth/prepare.php',{'csrf':a.token('/account'),'provider':'github','mode':'provider'},{'Accept':'application/json'})[0]==200
        guest=Client(); csrf=guest.token('/forgot-password')
        mysql=['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379','--user=root','--batch','--skip-column-names','couponhub_merge_test','-e',"SELECT email FROM users WHERE username='user_community'"]
        email=subprocess.check_output(mysql).decode().strip()
        status,_,body=guest.request('/forgot-password',{'csrf':csrf,'email':email},{'Accept':'application/json'})
        assert status==200 and len(messages)==1,(status,body,len(messages))
        assert json.loads(body)['message']==json.loads(unknown)['message']
        code=re.search(r'\b([0-9]{6})\b',messages[-1]).group(1)
        assert code not in body and 'token' not in json.loads(body)
        assert guest.request('/forgot-password',{'csrf':csrf,'email':email},{'Accept':'application/json'})[0]==429
        reset={'csrf':csrf,'otp':'000000','new_password':'HttpComplete!2026','password_confirmation':'HttpComplete!2026'}
        assert guest.request('/auth/reset-otp',reset,{'Accept':'application/json'})[0]==422
        reset['otp']=code
        status,_,body=guest.request('/auth/reset-otp',reset,{'Accept':'application/json'})
        assert status==200 and json.loads(body)['redirect']==ORIGIN+'/login',(status,body)
        assert a.request('/account')[0]==303 and b.request('/account')[0]==303
        fresh=Client(); fresh.login('HttpComplete!2026')
        assert fresh.request('/account')[0]==200
        assert guest.request('/auth/reset-otp',reset,{'Accept':'application/json'})[0]==403
        print('PASS: HTTP OTP/SMTP, generic email response, cooldown, one use, persistent HttpOnly/Lax cookie and revocation of two sessions.',flush=True)
        oauth=Client(); csrf=oauth.token()
        assert oauth.request('/auth/oauth/github',{'csrf':'bad'})[0]==403
        status,headers,_=oauth.request('/auth/oauth/github',{'csrf':csrf,'return_url':'https://evil.test/'})
        target=headers.get('Location',''); params=urllib.parse.parse_qs(urllib.parse.urlparse(target).query)
        assert status==303 and target.startswith('https://github.com/login/oauth/authorize?') and 'fixture-private-secret' not in target
        assert params['code_challenge_method']==['S256']
        callback='/api/oauth/github-callback.php?'+urllib.parse.urlencode({'state':params['state'][0],'error':'access_denied'})
        status,headers,_=oauth.request(callback)
        assert status==303 and headers['Location']==ORIGIN+'/login'
        assert 'hủy' in oauth.request('/login')[2]
        assert oauth.request(callback)[0]==403
        assert oauth.request('/account')[0]==303
        print('PASS: real HTTP OAuth start, CSRF, PKCE, cancellation, no secret exposure and no authentication from a callback alone.',flush=True)
        # A failed explicit link must keep the existing account and show feedback
        # on its profile, rather than sending an authenticated user through login.
        status,headers,_=fresh.request('/auth/oauth/github',{'csrf':fresh.token('/account'),'mode':'link'})
        assert status==303
        state=urllib.parse.parse_qs(urllib.parse.urlparse(headers['Location']).query)['state'][0]
        status,headers,_=fresh.request('/api/oauth/github-callback.php?'+urllib.parse.urlencode({'state':state,'error':'access_denied'}))
        assert status==303 and headers['Location']==ORIGIN+'/account',('Link cancellation destination',status,headers['Location'])
        status,_,profile=fresh.request('/account')
        assert status==200 and 'hủy' in profile and 'user.community@couponhub.test' in profile
        print('PASS: cancelled OAuth link keeps the authenticated account and displays feedback on its profile.',flush=True)
        for path in ['/.env','/config/oauth.php','/storage/sessions/test']:
            assert oauth.request(path)[0]==403,path
    finally:
        proc.terminate(); proc.wait(timeout=10)
        smtp.shutdown(); smtp.server_close()
