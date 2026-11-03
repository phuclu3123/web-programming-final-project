"""Full local acceptance: only disposable couponhub_merge_test on port 13379.
COUPONHUB_RUN_DB_TEST=1 python tests/run-phase4.py
Never imports schema/demo into the configured live database.
"""
import concurrent.futures
import json
import os
from pathlib import Path
import subprocess
import sys
import time

sys.stdout.reconfigure(encoding='utf-8',errors='replace')
ROOT=Path(__file__).resolve().parents[1]
if os.environ.get('COUPONHUB_RUN_DB_TEST')!='1':raise SystemExit('SKIP: isolated DB opt-in required')
assert not (ROOT/'config/local.php').exists(),'Unsafe local override'
mysql=['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379','--user=root','--default-character-set=utf8mb4','--batch','--skip-column-names','couponhub_merge_test']
assert subprocess.check_output(mysql+['-e','SELECT @@port']).strip()==b'13379'
runtime=ROOT/'.runtime'/('phase4-review-'+str(time.time_ns()));runtime.mkdir(parents=True)
(runtime/'sessions').mkdir()
env=os.environ|{'PYTHONIOENCODING':'utf-8','COUPONHUB_RUN_DB_TEST':'1','COUPONHUB_RUN_HTTP_TEST':'1','COUPONHUB_HOST':'127.0.0.1','COUPONHUB_PORT':'13379','COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_APP_URL':'','COUPONHUB_SESSION_PATH':str(runtime/'sessions'),'COUPONHUB_RATE_LIMIT_DIR':str(runtime/'limits')}
php='C:/xampp/php/php.exe';results=[]
def run(name,args):
    start=time.monotonic();p=subprocess.run(args,cwd=ROOT,env=env,stdout=subprocess.PIPE,stderr=subprocess.STDOUT)
    (runtime/(name+'.log')).write_bytes(p.stdout)
    results.append({'name':name,'exit':p.returncode,'seconds':round(time.monotonic()-start,2)})
    (runtime/'results.json').write_text(json.dumps(results,indent=2))
    print(('PASS' if p.returncode==0 else 'FAIL')+' '+name,flush=True)
    if p.returncode: print(p.stdout.decode('utf-8',errors='replace')[-5000:]);raise SystemExit(p.returncode)
run('phase3-regression',[sys.executable,'tests/run-phase3.py'])
for name in ['facebook-oauth','phase4-security','public-header-notifications-phase6','coupon-interaction-errors-phase4','oauth-setup-access-phase5']:
    run(name,[php,'tests/'+name+'.php'])
# Upgrade an existing schema containing an existing OAuth row; migration must preserve it.
subprocess.run(mysql+['-e',"ALTER TABLE oauth_accounts MODIFY provider ENUM('github','google') NOT NULL; INSERT INTO oauth_accounts(user_id,provider,provider_user_id,provider_email,email_verified) SELECT id,'github','phase4-migration-fixture',email,1 FROM users WHERE username='admin_demo'"],check=True)
snapshot='SELECT id,user_id,provider,HEX(provider_user_id),provider_email,email_verified FROM oauth_accounts ORDER BY id; SELECT COUNT(*),SUM(auth_version) FROM users; SELECT COUNT(*) FROM coupons; SELECT COUNT(*) FROM campaign_coupons'
before=subprocess.check_output(mysql+['-e',snapshot])
for _ in range(2):subprocess.run(mysql,input=(ROOT/'database/migrations/20261104_phase4_auth_guards.sql').read_bytes(),check=True)
assert subprocess.check_output(mysql+['-e',snapshot])==before,'Migration changed application records'
print('PASS repeatable phase 4 migration preserves records',flush=True)
run('phase4-database',[php,'tests/phase4-integration.php'])
run('fixture-http',[sys.executable,'tests/sql-install.py'])
run('phase4-http',[sys.executable,'tests/phase4-http.py'])
# Verify flock under competing processes rather than just a sequential unit test.
code="require 'app/bootstrap.php'; try {App\\Core\\AuthRateLimit::hit('concurrent-fixture','same',7,60); exit(0);} catch(App\\Core\\HttpError $e) {exit($e->status===429 ? 42 : 43);}"
with concurrent.futures.ThreadPoolExecutor(max_workers=10) as pool:
    exits=list(pool.map(lambda _:subprocess.run([php,'-r',code],cwd=ROOT,env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE).returncode,range(20)))
assert exits.count(0)==7 and exits.count(42)==13,exits
print('PASS concurrent rate limit: 7 accepted, 13 rejected',flush=True)
run('fixture-final',[sys.executable,'tests/sql-install.py'])
print('PASS phase 4 local acceptance; logs: '+str(runtime),flush=True)
