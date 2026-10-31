"""Joint integration acceptance. Requires disposable MariaDB on 127.0.0.1:13379.

Explicit opt-in: COUPONHUB_RUN_DB_TEST=1 python tests/run-phase3.py
Resets only couponhub_merge_test. Never uses application config for SQL import.
"""
import json
import os
from pathlib import Path
import subprocess
import time
import urllib.request

ROOT=Path(__file__).resolve().parents[1]
if os.environ.get('COUPONHUB_RUN_DB_TEST')!='1': raise SystemExit('SKIP: isolated DB opt-in required')
assert not (ROOT/'config/local.php').exists(),'Unsafe local override'
mysql=['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379','--user=root','--default-character-set=utf8mb4','--batch','--skip-column-names']
assert subprocess.check_output(mysql+['-e','SELECT @@port']).strip()==b'13379'
runtime=ROOT/'.runtime'/('phase3-review-'+str(time.time_ns()));runtime.mkdir(parents=True)
(runtime/'sessions').mkdir()
env=os.environ|{'COUPONHUB_RUN_DB_TEST':'1','COUPONHUB_RUN_HTTP_TEST':'1','COUPONHUB_HOST':'127.0.0.1','COUPONHUB_PORT':'13379','COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_APP_URL':'','COUPONHUB_SESSION_PATH':str(runtime/'sessions'),'COUPONHUB_RATE_LIMIT_DIR':str(runtime/'limits')}
php='C:/xampp/php/php.exe'
results=[]
def run(name,args):
    started=time.monotonic()
    result=subprocess.run(args,cwd=ROOT,env=env,stdout=subprocess.PIPE,stderr=subprocess.STDOUT)
    (runtime/(name+'.log')).write_bytes(result.stdout)
    entry={'name':name,'exit':result.returncode,'seconds':round(time.monotonic()-started,2)}
    results.append(entry)
    (runtime/'results.json').write_text(json.dumps(results,indent=2),encoding='utf-8')
    print(('PASS' if result.returncode==0 else 'FAIL')+' '+name+' '+str(entry['seconds'])+'s',flush=True)
    if result.returncode:
        print(result.stdout.decode('utf-8',errors='replace')[-5000:],flush=True)
        raise SystemExit(result.returncode)
def reset_schema(legacy=False):
    subprocess.run(mysql+['-e','DROP DATABASE IF EXISTS couponhub_merge_test; CREATE DATABASE couponhub_merge_test CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci'],check=True)
    content=subprocess.check_output(['git','-c','safe.directory='+str(ROOT),'show','df119f8:database/schema.sql'],cwd=ROOT) if legacy else (ROOT/'database/schema.sql').read_bytes()
    subprocess.run(mysql+['couponhub_merge_test'],input=content,check=True)
def load(path): subprocess.run(mysql+['couponhub_merge_test'],input=path.read_bytes(),check=True)

for path in sorted((ROOT/'app').rglob('*.php'))+sorted((ROOT/'config').glob('*.php')):
    result=subprocess.run([php,'-l',str(path)],stdout=subprocess.PIPE,stderr=subprocess.STDOUT)
    if result.returncode: raise SystemExit(result.stdout.decode('utf-8',errors='replace'))
print('PASS PHP syntax for application and config',flush=True)
run('javascript',['node','--test']+[str(p) for p in sorted((ROOT/'tests').glob('*.test.cjs'))])
for name in ['env-auth','oauth-auth','session-guards','auth-registration','account-service','router-integration','admin-phase2','coupon-event-service','coupon-discovery','personalized-deal-feed','level-progress','contributor-quality']:
    run(name,[php,'tests/'+name+'.php'])

run('sql-handoff',['python','tests/sql-install.py'])
run('phase3-database',[php,'tests/phase3-integration.php'])
# Existing installs: apply non-destructive migration twice, preserving rows.
reset_schema(legacy=True);load(ROOT/'database/demo.sql')
before=subprocess.check_output(mysql+['couponhub_merge_test','-e','SELECT COUNT(*),SUM(auth_version) FROM users; SELECT COUNT(*) FROM coupons'])
load(ROOT/'database/migrations/20261031_phase3_risk_controls.sql')
load(ROOT/'database/migrations/20261031_phase3_risk_controls.sql')
after=subprocess.check_output(mysql+['couponhub_merge_test','-e','SELECT COUNT(*),SUM(auth_version) FROM users; SELECT COUNT(*) FROM coupons'])
assert before==after,'Migration changed account/coupon data'
run('phase3-migrated-database',[php,'tests/phase3-integration.php'])
run('sql-reset-http',['python','tests/sql-install.py'])
run('phase3-http',['python','tests/phase3-http.py'])
run('sql-reset-regression',['python','tests/sql-install.py'])
for name in ['truc-merge','truc-integration','quang-integration']:
    run(name,[php,'tests/'+name+'.php'])
run('sql-reset-login',['python','tests/sql-install.py'])
run('role-redirects',['python','tests/login-redirects.py'])
run('quang-http',['python','tests/quang-http.py'])
run('auth-database',[php,'tests/auth-complete-integration.php'])
run('auth-http',['python','tests/auth-complete-http.py'])

reset_schema()
run('mysqli',[php,'tests/mysqli-integration.php'])
with (runtime/'legacy-http-server.log').open('w',encoding='utf-8') as log:
    server=subprocess.Popen([php,'-S','127.0.0.1:8076','router.php'],cwd=ROOT,env=env,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    try:
        for _ in range(50):
            if server.poll() is not None: raise RuntimeError('Test server failed to start')
            try:
                if urllib.request.urlopen('http://127.0.0.1:8076/login',timeout=2).status==200: break
            except OSError: pass
            time.sleep(.1)
        else: raise RuntimeError('Test server unavailable')
        run('legacy-http',['python','tests/http-integration.py'])
    finally:
        server.terminate();server.wait(timeout=10)
run('sql-clean-fixture',['python','tests/sql-install.py'])
print('PASS joint acceptance; logs: '+str(runtime),flush=True)
