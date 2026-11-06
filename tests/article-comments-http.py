"""Real HTTP + MySQL acceptance. ONLY couponhub_merge_test on port 13379."""
import http.cookiejar,json,os,re,socket,subprocess,time,urllib.request,urllib.error,urllib.parse
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
assert os.environ.get('COUPONHUB_RUN_DB_TEST')=='1','Explicit isolated DB opt-in required'
assert not (ROOT/'config/local.php').exists()
mysql=['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379','--user=root','--default-character-set=utf8mb4','--batch','--skip-column-names','couponhub_merge_test','-e']
def sql(q):return subprocess.check_output(mysql+[q]).decode('utf-8').strip()
assert sql('SELECT @@port')=='13379'
runtime=ROOT/'.runtime'/('article-comments-'+str(time.time_ns()));runtime.mkdir()
env=os.environ|{'COUPONHUB_HOST':'127.0.0.1','COUPONHUB_PORT':'13379','COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_TURNSTILE_MODE':'off'}
author=sql("SELECT id FROM users WHERE username='admin_demo'")
command=['C:/xampp/php/php.exe','scripts/publish-editorial.php','--apply','--expected-database=couponhub_merge_test','--author-id='+author]
subprocess.run(command,cwd=ROOT,env=env,check=True,stdout=subprocess.PIPE)
before=sql('SELECT COUNT(*) FROM articles')
subprocess.run(command,cwd=ROOT,env=env,check=True,stdout=subprocess.PIPE)
assert sql('SELECT COUNT(*) FROM articles')==before=='8','Editorial publishing must be repeatable'
article=sql("SELECT id FROM articles WHERE slug='doc-dieu-kien-ma'")
sql("INSERT INTO articles(author_id,title,slug,body,status) VALUES("+author+",'Private fixture','comment-private-fixture','Private','DRAFT')")
draft=sql("SELECT id FROM articles WHERE slug='comment-private-fixture'")
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,*args,**kwargs):return None
class Client:
 def __init__(self,base):
  self.base=base;self.jar=http.cookiejar.CookieJar();self.http=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar),NoRedirect())
 def request(self,path,data=None,headers=None):
  h={'Accept':'application/json'};h.update(headers or {})
  r=urllib.request.Request(self.base+path,data=urllib.parse.urlencode(data).encode() if data is not None else None,headers=h)
  try:r=self.http.open(r,timeout=15)
  except urllib.error.HTTPError as e:r=e
  text=r.read().decode('utf-8');return r.status,r.headers,text
 def token(self,path='/login'):
  s,_,b=self.request(path,headers={'Accept':'text/html'});assert s==200,(s,b[:100]);return re.search(r'name="csrf-token" content="([a-f0-9]+)"',b)[1]
 def login(self,user,password):
  s,_,b=self.request('/api/login.php',{'csrf':self.token(),'account':user,'password':password});assert s==200,(s,b[:100]);self.csrf=self.token('/account')
n=0
def check(ok,name):
 global n
 assert ok,name
 n+=1;print('PASS '+name,flush=True)
for port,base in [(8091,''),(8092,'/CouponHub_UI')]:
 with socket.socket() as s:assert s.connect_ex(('127.0.0.1',port))!=0,'Test port in use'
 scope=runtime/str(port);scope.mkdir();(scope/'sessions').mkdir()
 origin=f'http://127.0.0.1:{port}';env2=env|{'COUPONHUB_APP_URL':origin+base,'COUPONHUB_SESSION_PATH':str(scope/'sessions'),'COUPONHUB_RATE_LIMIT_DIR':str(scope/'limits')}
 with (scope/'server.log').open('w') as log:
  server=subprocess.Popen(['C:/xampp/php/php.exe','-S',f'127.0.0.1:{port}','router.php'],cwd=ROOT,env=env2,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
  try:
   guest=Client(origin+base)
   for _ in range(60):
    try:
     if guest.request('/login')[0]==200:break
    except OSError:time.sleep(.1)
   endpoint='/api/articles/'+article+'/comments'
   check(guest.request(endpoint)[0]==200,'anonymous can read published discussion '+base)
   check(guest.request(endpoint,{'csrf':guest.token(),'body':'unauthorized','request_id':'a'*32})[0]==401,'anonymous write denied '+base)
   check(guest.request('/api/articles/'+draft+'/comments')[0]==404,'unpublished discussion not disclosed')
   check(guest.request(endpoint+'?before[]=1')[0]==422,'cursor array rejected')
   user=Client(origin+base);user.login('user_demo','UserDemo!2026')
   other=Client(origin+base);other.login('user_community','UserDemo!2026')
   admin=Client(origin+base);admin.login('admin_demo','AdminDemo!2026')
   def post(data,actor=user,path=endpoint):return actor.request(path,{'csrf':actor.csrf,**data})
   key=('1' if port==8091 else '2')*32
   body="HTTP acceptance: quotes ' and <img src=x onerror=alert(1)> remain plain text."
   s,_,b=post({'body':body,'request_id':key});d=json.loads(b);cid=d.get('comment_id')
   check(s==201 and bool(cid),'customer write returns persisted comment ID')
   check(sql(f'SELECT HEX(body) FROM article_comments WHERE id={cid}').lower()==body.encode().hex(),'exact body persisted in MySQL')
   s,_,b=post({'body':body,'request_id':key});check(s==200 and json.loads(b)['comment_id']==cid,'retry same request ID does not duplicate')
   check(sql(f"SELECT COUNT(*) FROM article_comments WHERE request_id='{key}'")=='1','one row after retry')
   check(post({'body':'changed','request_id':key})[0]==409,'request ID cannot be reused for a different body')
   check(post({'body':'  ','request_id':'3'*32})[0]==422,'blank body rejected')
   check(post({'body':'x'*2001,'request_id':'3'*32})[0]==422,'oversized body rejected')
   check(post({'body[]':'bad','request_id':'3'*32})[0]==422,'nested body rejected')
   check(user.request(endpoint,{'csrf':'invalid','body':'bad','request_id':'3'*32})[0]==403,'CSRF rejected')
   check(user.request(endpoint,{'csrf':user.csrf,'body':'bad','request_id':'3'*32},{'Origin':'https://evil.example'})[0]==403,'foreign Origin rejected')
   check(post({'body':'private','request_id':'3'*32},path='/api/articles/'+draft+'/comments')[0]==404,'cannot comment on a draft')
   nativekey=f'{port:032x}'
   s,h,_=other.request(endpoint,{'csrf':other.csrf,'body':'Native browser form acceptance','request_id':nativekey},{'Accept':'text/html'})
   check(s==303 and h['Location'].endswith('/articles/doc-dieu-kien-ma#article-comments'),'native HTML form redirects to persisted discussion')
   nativeid=sql(f"SELECT id FROM article_comments WHERE request_id='{nativekey}'")
   check(other.request('/api/article-comments/'+nativeid+'/delete',{'csrf':other.csrf},{'Accept':'text/html'})[0]==303,'native form author delete works without AJAX')
   s,_,b=user.request('/articles/doc-dieu-kien-ma',headers={'Accept':'text/html'})
   check(s==200 and '&lt;img' in b and body not in b,'reload renders persisted comment escaped')
   check(any(x['id']==cid for x in json.loads(guest.request(endpoint)[2])['data']['items']),'another browser sees saved comment')
   check(post({},other,'/api/article-comments/'+str(cid)+'/delete')[0]==403,'customer cannot delete another author')
   moderate='/admin/article-comments/'+str(cid)+'/status'
   check(post({'status':'HIDDEN'},user,moderate)[0]==403,'customer cannot moderate')
   check(post({'status':'HIDDEN'},admin,moderate)[0]==303,'admin hides via actual form endpoint')
   check(not any(x['id']==cid for x in json.loads(guest.request(endpoint)[2])['data']['items']),'hidden text absent from public API')
   check(post({'status':'VISIBLE'},admin,moderate)[0]==303,'admin can show a hidden comment again')
   check(post({},user,'/api/article-comments/'+str(cid)+'/delete')[0]==200,'author can delete own comment')
   check(sql(f'SELECT status FROM article_comments WHERE id={cid}')=='DELETED','delete preserves audit row')
   check(post({'status':'VISIBLE'},admin,moderate)[0]==409,'deleted comment cannot be resurrected')
   check(admin.request('/admin/article-comments',headers={'Accept':'text/html'})[0]==200,'admin moderation screen loads')
   # The shared account limit applies across posts, not just within one PHP session.
   for i in range(8):
    status=post({'body':'rate-limit fixture','request_id':f'{port:04x}{i:028x}'})[0]
    if status==429:break
   check(status==429,'comment rate limit returns 429')
   # Fixture rows only in the isolated database: enough rows to exercise actual cursor paging.
   for i in range(25):sql(f"INSERT INTO article_comments(article_id,user_id,body,request_id) VALUES({article},1,'Pagination fixture {i}','{port:04x}{i:027x}f')")
   first=json.loads(guest.request(endpoint)[2])['data'];second=json.loads(guest.request(endpoint+'?before='+str(first['next_before']))[2])['data']
   check(len(first['items'])==20 and bool(first['next_before']) and not ({x['id'] for x in first['items']}&{x['id'] for x in second['items']}),'cursor pagination has no duplicates')
   check(guest.request('/guides/doc-dieu-kien-ma')[0]==302,'guide bookmark resolves to database article')
  finally:server.terminate();server.wait(timeout=10)
 check(not re.search(r'PHP (Warning|Fatal error|Parse error)',(scope/'server.log').read_text(encoding='utf-8',errors='replace')),'no PHP warnings')
(runtime/'result.json').write_text(json.dumps({'checks':n,'status':'PASS','database':'couponhub_merge_test:13379'}),encoding='utf-8')
print('PASS real HTTP/MySQL article acceptance:',n,'checks; evidence:',runtime)
