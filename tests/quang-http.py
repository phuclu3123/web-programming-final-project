"""Real HTTP checks for Quang writes, role boundaries and session invalidation.

Requires a fresh demo fixture. Mutates only couponhub_merge_test on port 13379.
"""
import http.cookiejar
from html.parser import HTMLParser
import json
import os
from pathlib import Path
import re
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
if os.environ.get('COUPONHUB_RUN_HTTP_TEST') != '1':
    raise SystemExit('SKIP: set COUPONHUB_RUN_HTTP_TEST=1')
assert not (ROOT / 'config/local.php').exists(), 'Unsafe local database override'
env = os.environ | {'COUPONHUB_DB':'couponhub_merge_test', 'COUPONHUB_HOST':'127.0.0.1',
                    'COUPONHUB_PORT':'13379', 'COUPONHUB_USER':'root', 'COUPONHUB_PASSWORD':''}
env.pop('COUPONHUB_APP_URL', None)
ORIGIN = 'http://127.0.0.1:8077'
BASE = '/CouponHub_UI'
mysql = ['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379',
         '--user=root','--default-character-set=utf8mb4','--init-command=SET collation_connection=utf8mb4_vietnamese_ci','--batch','--skip-column-names','couponhub_merge_test','-e']
def query(sql):
    return subprocess.check_output(mysql+[sql]).decode('utf-8').strip()
assert query('SELECT @@port') == '13379'

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs): return None

class Client:
    def __init__(self):
        self.open = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())
    def req(self, path, data=None, as_json=False, headers=None):
        headers = {'Accept':'application/json' if as_json else 'text/html'} | (headers or {})
        if data is not None:
            body = json.dumps(data).encode() if as_json else urllib.parse.urlencode(data).encode()
            headers['Content-Type'] = 'application/json' if as_json else 'application/x-www-form-urlencoded'
        else: body = None
        try: r = self.open.open(urllib.request.Request(ORIGIN+BASE+path, data=body, headers=headers), timeout=15)
        except urllib.error.HTTPError as error: r = error
        return r.status, r.headers, r.read().decode('utf-8')
    def token(self, path='/'):
        status, _, html = self.req(path)
        assert status == 200, (path,status)
        return re.search(r'name="csrf-token" content="([a-f0-9]+)"',html).group(1)
    def login(self, username, password):
        token = self.token('/login.php')
        status, _, body = self.req('/api/login.php', {'csrf':token,'account':username,'password':password}, True)
        assert status == 200 and json.loads(body)['success'], (status, body[:200])

class Forms(HTMLParser):
    def __init__(self, html):
        super().__init__(); self.forms=[]; self.form=None; self.field=None; self.select=None; self.feed(html)
    def handle_starttag(self, tag, pairs):
        a=dict(pairs)
        if tag=='form': self.form={'action':a.get('action',''),'fields':{}}; self.forms.append(self.form)
        if not self.form: return
        if tag=='input' and a.get('name') and a.get('type') not in ('checkbox','radio','submit'):
            self.form['fields'][a['name']]=a.get('value','')
        if tag=='textarea': self.field=a.get('name'); self.form['fields'][self.field]=''
        if tag=='select': self.select=a.get('name')
        if tag=='option' and self.select:
            if self.select not in self.form['fields'] or 'selected' in a: self.form['fields'][self.select]=a.get('value','')
    def handle_data(self, value):
        if self.form and self.field: self.form['fields'][self.field]+=value
    def handle_endtag(self, tag):
        if tag=='textarea': self.field=None
        if tag=='select': self.select=None
        if tag=='form': self.form=None

session_dir=ROOT/'.runtime/quang-http-sessions'; session_dir.mkdir(parents=True,exist_ok=True)
log_path=ROOT/'.runtime/quang-http.log'
with log_path.open('w',encoding='utf-8') as log:
    proc=subprocess.Popen(['C:/xampp/php/php.exe','-d','session.save_path='+str(session_dir),'-S','127.0.0.1:8077','-t',str(ROOT.parent)], cwd=ROOT, env=env, stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    try:
        guest=Client()
        for _ in range(50):
            try:
                if guest.req('/')[0]==200: break
            except OSError: pass
            time.sleep(.1)
        else: raise AssertionError('Server not ready')
        admin=Client(); admin.login('admin_demo','AdminDemo!2026')
        user=Client(); user.login('user_demo','UserDemo!2026')
        merchant=Client(); merchant.login('merchant_shops','MerchantDemo!2026')
        token=admin.token('/admin/coupons')
        cid=int(query("SELECT id FROM coupons WHERE moderation_status='APPROVED' AND publication_status='ENABLED' AND code IS NOT NULL ORDER BY id LIMIT 1"))
        route=f'/admin/coupons/{cid}/edit'
        for client in [user,merchant]:
            assert client.req(route)[0]==403
            assert client.req(route,{'_token':client.token(),'title':'tampered'},True)[0]==403
        assert guest.req(route)[0]==303
        assert admin.req(f'/admin/coupons/{cid}/delete')[0]==405
        assert admin.req(route,{'_token':'wrong'},True)[0]==403
        assert admin.req(route,{'_token':token},True,{'Origin':'https://evil.test'})[0]==403
        assert admin.req('/admin/coupons?status[]=ACTIVE')[0]==422
        assert admin.req('/admin/coupons?page=-1')[0]==422
        status,_,html=admin.req(route); assert status==200
        form=next(f for f in Forms(html).forms if f['action'].endswith(route))
        assert re.search(r'\d{2}:\d{2}:\d{2}$',form['fields']['starts_at']), 'Preserve seconds in edit form'
        data=form['fields'] | {'title':'HTTP reviewed coupon', 'merchant_id':'999999', 'publication_status':'ARCHIVED'}
        assert admin.req(route,data)[0]==302
        assert 'HTTP reviewed coupon' in admin.req(route)[2]
        assert query(f'SELECT publication_status FROM coupons WHERE id={cid}')=='ENABLED'
        bad=data | {'discount_value':'101','discount_type':'PERCENTAGE'}
        assert admin.req(route,bad)[0]==422
        print('PASS: HTTP admin edit, validation, GET/POST, CSRF, Origin, role guards and immutable fields.',flush=True)
        for action in ['expire','delete']:
            assert admin.req(f'/admin/coupons/{cid}/{action}',{'_token':token,'reason':'HTTP isolated workflow verification'})[0]==302
        assert admin.req(route)[0]==404 and guest.req(f'/coupons/{cid}')[0]==404
        count=query(f'SELECT COUNT(*) FROM coupon_status_history WHERE coupon_id={cid}')
        assert int(count)>=3
        assert admin.req('/admin/categories/1/delete',{'_token':token})[0]==302
        assert query('SELECT COUNT(*) FROM categories WHERE id=1')=='1'
        print('PASS: HTTP expiry/deletion hide coupon and preserve audit; referenced category remains.',flush=True)
        copy_id=int(query("SELECT id FROM v_coupon_status WHERE effective_status='ACTIVE' AND code IS NOT NULL LIMIT 1"))
        event=f'/api/coupons/{copy_id}/events'
        guest_token=guest.token()
        assert guest.req(event,{'event':'COPY','_token':'wrong'},True)[0]==403
        assert guest.req(event,{'event':'VIEW','_token':guest_token},True)[0]==422
        payload={'event':'COPY','_token':guest_token,'actor_id':1,'user_id':1}
        assert json.loads(guest.req(event,payload,True)[2])['recorded']
        assert not json.loads(guest.req(event,payload,True)[2])['recorded']
        assert query(f"SELECT user_id IS NULL FROM coupon_events WHERE coupon_id={copy_id} AND event_type='COPY' ORDER BY id DESC LIMIT 1")=='1'
        assert guest.req(f'/api/coupons/{cid}/events',payload,True)[0]==422
        print('PASS: real JSON copy endpoint, throttle, guest identity cannot be spoofed, unavailable code rejected.',flush=True)
        user_token=user.token('/account')
        assert user.req('/account',{'_token':user_token,'full_name':'HTTP Profile','phone':'+84908887776','role':'ADMIN','id':1})[0]==303
        html=user.req('/account')[2]
        assert 'value="+84908887776"' in html and 'value="HTTP Profile"' in html
        assert query("SELECT role FROM users WHERE username='user_demo'")=='USER'
        second=Client(); second.login('user_demo','UserDemo!2026')
        password={'_token':user_token,'current_password':'UserDemo!2026','new_password':'HttpReview!2026','new_password_confirmation':'HttpReview!2026'}
        status,headers,_=user.req('/account/password',password)
        assert status==303 and headers['Location']==BASE+'/login'
        assert second.req('/account')[0]==303, 'Other session invalidated'
        fresh=Client(); fresh.login('user_demo','HttpReview!2026')
        assert fresh.req('/account')[0]==200
        print('PASS: HTTP profile prefill/update, no role escalation, password change invalidates both old sessions.',flush=True)
    finally:
        proc.terminate(); proc.wait(timeout=10)
