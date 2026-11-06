"""Real PHP middleware and markup, no Cloudflare calls; isolated sessions/database only."""
import http.cookiejar,json,os,re,socket,subprocess,time,urllib.error,urllib.parse,urllib.request
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
if os.environ.get('COUPONHUB_RUN_HTTP_TEST')!='1':raise SystemExit('SKIP isolated HTTP opt-in required')
assert not (ROOT/'config/local.php').exists()
mysql=['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379','--user=root','--batch','--skip-column-names','couponhub_merge_test','-e','SELECT @@port']
assert subprocess.check_output(mysql).strip()==b'13379'
runtime=ROOT/'.runtime'/('turnstile-http-'+str(time.time_ns()));runtime.mkdir()
n=0
for port,base in [(8100,''),(8101,'/CouponHub_UI')]:
 with socket.socket() as probe:assert probe.connect_ex(('127.0.0.1',port))!=0
 origin=f'http://127.0.0.1:{port}';(runtime/str(port)).mkdir()
 env=os.environ|{'COUPONHUB_APP_URL':origin+base,'COUPONHUB_HOST':'127.0.0.1','COUPONHUB_PORT':'13379','COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_SESSION_PATH':str(runtime/str(port)),'COUPONHUB_RATE_LIMIT_DIR':str(runtime/('limits-'+str(port)))}
 jar=http.cookiejar.CookieJar();opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
 def request(path,data=None):
  req=urllib.request.Request(origin+base+path,data=urllib.parse.urlencode(data).encode() if data is not None else None,headers={'Accept':'application/json'} if data is not None else {})
  try:r=opener.open(req,timeout=10)
  except urllib.error.HTTPError as error:r=error
  return r.status,r.read().decode('utf-8')
 def start(settings,log):
  command=['C:/xampp/php/php.exe','-S',f'127.0.0.1:{port}']+(['-t',str(ROOT.parent)] if base else ['router.php'])
  server=subprocess.Popen(command,cwd=ROOT,env=env|settings,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
  for _ in range(50):
   assert server.poll() is None
   try:
    if request('/login')[0]==200:return server
   except OSError:time.sleep(.1)
  server.terminate();raise AssertionError('Owned Turnstile server not ready')
 with (runtime/f'server-{port}.log').open('w',encoding='utf-8') as log:
  server=start({'COUPONHUB_TURNSTILE_MODE':'off','TURNSTILE_SITE_KEY':'','TURNSTILE_SECRET_KEY':''},log)
  try:
   status,html=request('/login');assert 'data-turnstile-field' not in html;n+=1
   csrf=re.search(r'name="csrf-token" content="([a-f0-9]+)"',html)[1]
   status,body=request('/api/login.php',{'csrf':csrf,'account':'user_demo','password':'UserDemo!2026'});assert status==200 and json.loads(body)['success'];n+=1
   csrf=re.search(r'name="csrf-token" content="([a-f0-9]+)"',request('/account')[1])[1]
  finally:server.terminate();server.wait(timeout=10)
  server=start({'COUPONHUB_TURNSTILE_MODE':'required','TURNSTILE_SITE_KEY':'public-contract-fixture','TURNSTILE_SECRET_KEY':'private-contract-fixture','TURNSTILE_HOSTNAMES':'127.0.0.1'},log)
  try:
   for path in ['/coupons/create','/merchant/apply']:
    status,html=request(path);assert status==200 and 'public-contract-fixture' in html and 'private-contract-fixture' not in html;n+=1
   for path in ['/login','/api/login.php','/register','/api/register.php','/forgot-password','/coupons','/merchant/apply']:
    status,body=request(path,{'csrf':csrf});assert status==422,(path,status,body);n+=1
    status,body=request(path,{'csrf':csrf,'cf-turnstile-response':'a'*2049});assert status==422,(path,status,body);n+=1
   assert request('/forgot-password',{'csrf':'bad'})[0]==403;n+=1
  finally:server.terminate();server.wait(timeout=10)
 errors=(runtime/f'server-{port}.log').read_text(encoding='utf-8',errors='replace')
 assert not re.search(r'PHP (Warning|Fatal error|Parse error)',errors),errors[-2000:]
print(f'PASS {n} Turnstile HTTP checks, root/subfolder middleware and markup; no provider requests. Evidence: {runtime}')
