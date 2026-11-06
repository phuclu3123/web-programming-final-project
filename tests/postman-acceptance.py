"""Run official Postman CLI locally, no account/cloud reports; isolated database only."""
import json,os,re,shutil,socket,subprocess,time,urllib.request
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
if os.environ.get('COUPONHUB_RUN_DB_TEST')!='1':raise SystemExit('SKIP explicit isolated DB opt-in required')
assert not (ROOT/'config/local.php').exists(),'Unsafe local override'
mysql=['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379','--user=root','--batch','--skip-column-names','couponhub_merge_test','-e']
def sql(q):return subprocess.check_output(mysql+[q]).decode('utf-8').strip()
assert sql('SELECT @@port')=='13379'
subprocess.run(['python','scripts/build_api_assets.py'],cwd=ROOT,check=True)
subprocess.run(['python','scripts/build_postman.py'],cwd=ROOT,check=True)
cli=shutil.which('postman') or str(Path(os.environ['LOCALAPPDATA'])/'Microsoft/WindowsApps/postman.exe')
assert Path(cli).exists(),'Postman CLI missing'
runtime=ROOT/'.runtime'/('postman-'+str(time.time_ns()));runtime.mkdir();(runtime/'sessions').mkdir()
base='http://127.0.0.1:8099'
with socket.socket() as p:assert p.connect_ex(('127.0.0.1',8099))!=0,'Owned test port occupied'
env=os.environ|{'COUPONHUB_HOST':'127.0.0.1','COUPONHUB_PORT':'13379','COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_APP_URL':base,'COUPONHUB_SESSION_PATH':str(runtime/'sessions'),'COUPONHUB_RATE_LIMIT_DIR':str(runtime/'limits'),'COUPONHUB_TURNSTILE_MODE':'off','ACCESSTRADE_ACCESS_KEY':''}
coupon=sql("SELECT c.id FROM v_coupon_status c WHERE c.effective_status='ACTIVE' AND NOT EXISTS(SELECT 1 FROM favorites f JOIN users u ON u.id=f.user_id WHERE f.coupon_id=c.id AND u.username='user_demo') ORDER BY c.id LIMIT 1")
assert coupon
campaign=sql("SELECT id FROM campaigns WHERE status='PUBLISHED' AND ends_at>UTC_TIMESTAMP() ORDER BY id LIMIT 1");assert campaign
# Permission-only denied writes must not change domain records. Favorite is explicitly added/removed.
snapshot='CHECKSUM TABLE users,coupons,campaigns,campaign_coupons,external_offers,merchant_memberships,merchants,categories,articles,article_comments,reports,oauth_accounts,password_reset_tokens,point_transactions,favorites EXTENDED'
last_audit=int(sql('SELECT COALESCE(MAX(id),0) FROM activity_logs'))
user_id=sql("SELECT id FROM users WHERE username='user_demo'")
before=sql(snapshot)
with (runtime/'server.log').open('w',encoding='utf-8') as log:
 server=subprocess.Popen(['C:/xampp/php/php.exe','-S','127.0.0.1:8099','router.php'],cwd=ROOT,env=env,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
 try:
  for _ in range(50):
   assert server.poll() is None
   try:
    if urllib.request.urlopen(base+'/login',timeout=2).status==200:break
   except OSError:time.sleep(.1)
  else:raise AssertionError('Owned server not ready')
  args=[cli,'collection','run',str(ROOT/'docs/api/couponhub.postman_collection.json'),'-e',str(ROOT/'docs/api/local.postman_environment.json'),'--env-var','baseUrl='+base,'--env-var','couponId='+coupon,'--env-var','campaignId='+campaign,'--ignore-redirects','--no-report-events','--reporters','cli,json','--reporter-json-export',str(runtime/'report.json')]
  p=subprocess.run(args,cwd=ROOT,env=env,stdout=subprocess.PIPE,stderr=subprocess.STDOUT)
  (runtime/'cli.log').write_bytes(p.stdout)
  print(p.stdout.decode('utf-8',errors='replace')[-2600:])
  assert p.returncode==0,'Postman failures: '+str(runtime/'cli.log')
  report=json.loads((runtime/'report.json').read_text(encoding='utf-8'))
  run=report['run'];stats=run.get('stats') or run.get('summary');assert stats,'Unknown Postman report format'
  assert not run.get('runError') and not run.get('failures'),run.get('runError') or run.get('failures')
  if 'summary' in run:
   assert stats['executedRequests']['errors']==0 and stats['tests']['failed']==0 and stats['postresponseScripts']['errors']==0,stats
  after=sql(snapshot)
  (runtime/'snapshots.json').write_text(json.dumps({'before':before,'after':after},indent=2),encoding='utf-8')
  assert after==before,'Denied requests changed business records'
  expected=['FAVORITE_ADD\t'+user_id+'\t'+coupon,'FAVORITE_REMOVE\t'+user_id+'\t'+coupon]
  assert sql(f'SELECT action,user_id,coupon_id FROM activity_logs WHERE id>{last_audit} ORDER BY id').splitlines()==expected,'Unexpected audit mutations beyond the two authorized favorite actions'
  print('PASS Postman:',json.dumps(stats),'; evidence:',runtime)
 finally:server.terminate();server.wait(timeout=10)
assert not re.search(r'PHP (Fatal error|Parse error|Warning)',(runtime/'server.log').read_text(encoding='utf-8',errors='replace'))
