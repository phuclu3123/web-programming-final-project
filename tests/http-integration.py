import os
if os.environ.get('COUPONHUB_RUN_HTTP_TEST') != '1':
 print('SKIP: start the isolated test server and set COUPONHUB_RUN_HTTP_TEST=1');raise SystemExit(0)
import urllib.request, urllib.parse, urllib.error, http.cookiejar, re, json
base='http://127.0.0.1:8076'
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,*args,**kwargs): return None
class Client:
 def __init__(self):
  self.cookies=http.cookiejar.CookieJar();self.open=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies),NoRedirect())
 def request(self,path,data=None,headers=None,method=None):
  if data is not None: data=urllib.parse.urlencode(data).encode()
  req=urllib.request.Request(base+path,data=data,headers=headers or {},method=method)
  try: res=self.open.open(req)
  except urllib.error.HTTPError as err: res=err
  return res.status,res.headers,res.read().decode('utf-8')
 def token(self):
  status,headers,html=self.request('/login.php'); assert status==200
  return re.search(r'name="csrf" value="([a-f0-9]+)"',html).group(1)
 def login(self,name,password):
  token=self.token();s,h,b=self.request('/api/login.php',{'csrf':token,'account':'test_'+name,'password':password})
  result=json.loads(b);assert s==200 and result['success'],(s,b);return result
c=Client()
for path in ['/login','/register','/forgot-password','/about','/terms','/privacy','/community-guidelines','/contact','/portal','/coupons','/coupons/1','/articles','/']:
 s,h,b=c.request(path);assert s==200,(path,s,b[:300])
print('PASS public/module routes, preserved login UI and public coupon detail')
for path in ['/admin','/admin/users','/merchant','/merchant/coupons','/admin.php?section=dashboard','/shop.php']:
 s,h,b=c.request(path);assert s==303 and '/login' in h['Location'],(path,s,b)
s,h,b=c.request('/api/coupons/1/favorite',{'saved':'1'},headers={'Accept':'application/json'});assert s==401,(s,b)
for path in ['/app/core/Database.php','/config/database.php','/database/schema.sql','/.env','/tests/mysqli-integration.php']:
 assert c.request(path)[0]==403,path
assert c.request('/api/login.php')[0]==405
assert c.request('/login',{},method='PUT')[0]==405
assert c.request('/missing-page')[0]==404
assert c.request('/api/login.php',{'account':'test_user','password':'NewTestPass!123'})[0]==403
print('PASS guest guards, source protection, method/CSRF/404 policies')
user=Client();result=user.login('user','NewTestPass!123');assert result['redirect']=='index.php'
for path in ['/dashboard','/saved-coupons','/my-coupons','/merchant/apply','/coupons/create']:
 s,h,b=user.request(path);assert s==200,(path,s,b[:200])
assert user.request('/admin')[0]==403
assert user.request('/merchant')[0]==403
admin=Client();admin.login('admin','TestPass!123')
for path in ['/admin','/admin/users','/admin/merchants','/admin/merchant-requests','/admin/categories','/admin/coupons','/admin/campaigns','/admin/reports','/admin/articles']:
 s,h,b=admin.request(path);assert s==200,(path,s,b[:300])
manager=Client();manager.login('manager','TestPass!123')
for path in ['/shop.php','/merchant','/merchant/profile/1','/merchant/coupons','/merchant/coupons/create','/merchant/campaigns','/merchant/campaigns/create']:
 s,h,b=manager.request(path);assert s==200,(path,s,b[:300])
assert manager.request('/admin')[0]==403
print('PASS logged-in User/Admin/Merchant routes and role isolation')
# OAuth stays preparation only, provider names and state route are fixed.
s,h,b=c.request('/oauth-setup.php');assert s==200 and 'accounts.google.com' in b and 'Facebook' not in b
csrf=re.search(r'name="csrf" value="([a-f0-9]+)"',b).group(1)
for provider in ['google','github']:
 s,h,b=c.request('/api/oauth/prepare.php',{'csrf':csrf,'provider':provider,'mode':'probe'});assert s==200,(s,b)
 url=json.loads(b)['url'];s,h,b=c.request('/'+url);result=json.loads(b)
 assert s==200 and result['prepared'] and not result['authenticated'],(s,b)
 assert c.request('/'+url)[0]==403
# Same csrf token works across both UI families, and logout removes the shared login.
s,h,b=user.request('/dashboard');csrf=re.search(r'name="csrf-token" content="([a-f0-9]+)"',b).group(1)
s,h,b=user.request('/logout',{'_token':csrf});assert s==303,(s,b)
assert user.request('/dashboard')[0]==303
print('PASS Google/GitHub probes/replay protection and shared logout')
