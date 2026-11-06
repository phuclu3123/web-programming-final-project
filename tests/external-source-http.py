"""Real HTTP: review -> pending -> moderation -> revoke. Disposable DB only, no provider calls."""
import http.cookiejar,json,os,re,socket,subprocess,time,urllib.error,urllib.parse,urllib.request
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
if os.environ.get('COUPONHUB_RUN_HTTP_TEST')!='1':raise SystemExit('SKIP isolated HTTP opt-in required')
assert not (ROOT/'config/local.php').exists()
mysql=['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379','--user=root','--batch','--skip-column-names','couponhub_merge_test','-e']
def sql(q):return subprocess.check_output(mysql+[q]).decode('utf-8').strip()
assert sql('SELECT @@port')=='13379'
runtime=ROOT/'.runtime'/('external-http-'+str(time.time_ns()));runtime.mkdir();(runtime/'sessions').mkdir()
origin='http://127.0.0.1:8102'
with socket.socket() as p:assert p.connect_ex(('127.0.0.1',8102))!=0
env=os.environ|{'COUPONHUB_RUN_DB_TEST':'1','COUPONHUB_HOST':'127.0.0.1','COUPONHUB_PORT':'13379','COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_APP_URL':origin,'COUPONHUB_SESSION_PATH':str(runtime/'sessions'),'COUPONHUB_RATE_LIMIT_DIR':str(runtime/'limits'),'COUPONHUB_TURNSTILE_MODE':'off','ACCESSTRADE_ACCESS_KEY':''}
offer=subprocess.check_output(['C:/xampp/php/php.exe','tests/external-offer-fixture.php','--suffix=http-'+str(time.time_ns())],cwd=ROOT,env=env).decode().strip();assert offer.isdigit()
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,*args,**kwargs):return None
jar=http.cookiejar.CookieJar();opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar),NoRedirect())
def req(path,data=None,json_accept=False):
 r=urllib.request.Request(origin+path,data=urllib.parse.urlencode(data).encode() if data is not None else None,headers={'Accept':'application/json' if json_accept else 'text/html'})
 try:v=opener.open(r,timeout=10)
 except urllib.error.HTTPError as error:v=error
 return v.status,v.read().decode('utf-8')
n=0
def check(condition,label):
 global n
 assert condition,label
 n+=1;print('PASS '+label,flush=True)
with (runtime/'server.log').open('w',encoding='utf-8') as log:
 server=subprocess.Popen(['C:/xampp/php/php.exe','-S','127.0.0.1:8102','router.php'],cwd=ROOT,env=env,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
 try:
  for _ in range(50):
   assert server.poll() is None
   try:
    if req('/login')[0]==200:break
   except OSError:time.sleep(.1)
  csrf=re.search(r'name="csrf-token" content="([a-f0-9]+)"',req('/login')[1])[1]
  status,body=req('/api/login.php',{'csrf':csrf,'account':'admin_demo','password':'AdminDemo!2026'},True)
  check(status==200 and json.loads(body)['success'],'admin signs in through actual endpoint')
  status,html=req('/admin/integrations/'+offer+'/review');check(status==200,'source review renders')
  csrf=re.search(r'name="csrf-token" content="([a-f0-9]+)"',html)[1]
  fingerprint=re.search(r'name="source_fingerprint" value="([a-f0-9]+)"',html)[1]
  path='/admin/integrations/'+offer+'/import'
  status,html=req(path,{'csrf':csrf,'source_fingerprint':fingerprint,'title':'<script>preserved</script>'})
  check(status==422 and '&lt;script&gt;preserved&lt;/script&gt;' in html and '<script>preserved</script>' not in html,'native validation returns escaped HTML and preserves input')
  merchant=sql("SELECT id FROM merchants WHERE status='APPROVED' AND deleted_at IS NULL ORDER BY id LIMIT 1")
  category=sql('SELECT id FROM categories WHERE is_active=1 ORDER BY id LIMIT 1')
  payload={'csrf':csrf,'merchant_id':merchant,'category_id':category,'title':'[TEST] Provider HTTP flow','code':'HTTP_'+str(time.time_ns()),'description':'Isolated acceptance fixture only','terms':'Independent review of order and expiry requirements','discount_type':'PERCENTAGE','discount_value':'10','min_order_amount':'0','max_discount_amount':'30000','usage_channel':'ONLINE','promotion_url':'https://example.com/offer','starts_at':time.strftime('%Y-%m-%dT%H:%M:%S',time.localtime(time.time()-86400)),'ends_at':time.strftime('%Y-%m-%dT%H:%M:%S',time.localtime(time.time()+86400*7))}
  before=sql('SELECT COUNT(*) FROM coupons')
  check(req(path,payload,True)[0]==409,'missing source revision rejected over HTTP')
  check(req(path,payload|{'source_fingerprint':'0'*64},True)[0]==409,'stale source revision rejected over HTTP')
  check(sql('SELECT COUNT(*) FROM coupons')==before,'revision rejections have no coupon side effects')
  status,body=req(path,payload|{'source_fingerprint':fingerprint},True);data=json.loads(body)
  check(status==200 and data['success'],'review form creates one pending coupon')
  coupon=int(data['coupon_id'])
  check(sql(f"SELECT CONCAT(source,':',moderation_status) FROM coupons WHERE id={coupon}")=='COMMUNITY:PENDING','source provenance and pending state persist')
  check(req('/coupons/'+str(coupon))[0]==404,'unmoderated source coupon stays private')
  check(req(path,payload|{'source_fingerprint':fingerprint},True)[0]==409,'repeat import rejected at HTTP boundary')
  status,_=req(f'/admin/coupons/{coupon}/moderate',{'csrf':csrf,'action':'APPROVE','review_confirm':'1','note':'Reviewed provider source during acceptance'})
  check(status in [302,303] and sql(f'SELECT moderation_status FROM coupons WHERE id={coupon}')=='APPROVED','existing admin moderation accepts the pending source coupon')
  check(req('/coupons/'+str(coupon))[0]==200,'approved source coupon becomes publicly visible')
  status,_=req(f'/admin/coupons/{coupon}/revoke',{'csrf':csrf,'reason':'Source coupon withdrawn during isolated HTTP acceptance'})
  check(status in [302,303] and sql(f'SELECT publication_status FROM coupons WHERE id={coupon}')=='ARCHIVED','admin revocation persists terminal state')
  check(req('/coupons/'+str(coupon))[0]==404,'withdrawn source coupon disappears from public detail')
 finally:server.terminate();server.wait(timeout=10)
assert not re.search(r'PHP (Warning|Fatal error|Parse error)',(runtime/'server.log').read_text(encoding='utf-8',errors='replace'))
print(f'PASS {n} real source workflow HTTP checks; no provider calls. Evidence: {runtime}')
