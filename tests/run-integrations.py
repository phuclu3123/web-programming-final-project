"""Strict local acceptance. Resets ONLY couponhub_merge_test on 127.0.0.1:13379.
COUPONHUB_RUN_DB_TEST=1 python tests/run-integrations.py [--browser]
Requires the owned disposable MariaDB service, PHP, Python, Postman CLI, optional Chrome.
"""
import json,os,subprocess,sys,time
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
sys.stdout.reconfigure(encoding='utf-8',errors='replace')
if os.environ.get('COUPONHUB_RUN_DB_TEST')!='1':raise SystemExit('SKIP explicit opt-in required')
assert not (ROOT/'config/local.php').exists()
mysql=['C:/xampp/mysql/bin/mysql.exe','--no-defaults','--host=127.0.0.1','--port=13379','--user=root','--batch','--skip-column-names','couponhub_merge_test']
assert subprocess.check_output(mysql+['-e','SELECT @@port']).strip()==b'13379'
runtime=ROOT/'.runtime'/('integrations-acceptance-'+str(time.time_ns()));runtime.mkdir()
env=os.environ|{'PYTHONIOENCODING':'utf-8','COUPONHUB_RUN_DB_TEST':'1','COUPONHUB_RUN_HTTP_TEST':'1','COUPONHUB_HOST':'127.0.0.1','COUPONHUB_PORT':'13379','COUPONHUB_DB':'couponhub_merge_test','COUPONHUB_USER':'root','COUPONHUB_PASSWORD':'','COUPONHUB_APP_URL':'','COUPONHUB_TURNSTILE_MODE':'off','ACCESSTRADE_ACCESS_KEY':'','TURNSTILE_SITE_KEY':'','TURNSTILE_SECRET_KEY':''}
results=[]
def run(name,args):
 start=time.monotonic();p=subprocess.run(args,cwd=ROOT,env=env,stdout=subprocess.PIPE,stderr=subprocess.STDOUT)
 (runtime/(name+'.log')).write_bytes(p.stdout)
 results.append({'name':name,'exit':p.returncode,'seconds':round(time.monotonic()-start,2)})
 (runtime/'results.json').write_text(json.dumps(results,indent=2),encoding='utf-8')
 print(('PASS ' if not p.returncode else 'FAIL ')+name,flush=True)
 if p.returncode:print(p.stdout.decode('utf-8',errors='replace')[-5000:]);raise SystemExit(p.returncode)
 return p.stdout
if '--regression-evidence' in sys.argv:
 evidence=Path(sys.argv[sys.argv.index('--regression-evidence')+1]).resolve()
 assert evidence.is_relative_to(ROOT/'.runtime') and 'PASS phase 4 local acceptance' in evidence.read_text(encoding='utf-8'), 'Missing completed regression evidence'
 results.append({'name':'phase4-regression','exit':0,'reused_evidence':str(evidence)})
 print('PASS verified completed phase 4 evidence: '+str(evidence),flush=True)
 run('fresh-isolated-fixture',[sys.executable,'tests/sql-install.py'])
else:run('phase4-regression',[sys.executable,'tests/run-phase4.py'])
run('provider-contracts',['C:/xampp/php/php.exe','tests/provider-integrations.php'])
run('csrf-contract',['C:/xampp/php/php.exe','tests/request-token-contract.php'])
snapshot='SELECT COUNT(*),SUM(auth_version) FROM users;SELECT COUNT(*) FROM coupons;SELECT COUNT(*) FROM campaigns;SELECT COUNT(*) FROM campaign_coupons'
before=subprocess.check_output(mysql+['-e',snapshot])
# Simulate adding only this feature to a populated pre-integration install, twice.
subprocess.run(mysql+['-e','DROP TABLE external_offers'],check=True)
for _ in range(2):subprocess.run(mysql,input=(ROOT/'database/migrations/20261105_external_offers.sql').read_bytes(),check=True)
assert subprocess.check_output(mysql+['-e',snapshot])==before
print('PASS additive source migration twice preserves populated accounts/coupons/campaigns',flush=True)
run('external-source-revocation',['C:/xampp/php/php.exe','tests/external-offers-database.php'])
run('source-http',[sys.executable,'tests/external-source-http.py'])
run('turnstile-http',[sys.executable,'tests/turnstile-http.py'])
run('postman',[sys.executable,'tests/postman-acceptance.py'])
if '--browser' in sys.argv:
 run('showcase-fixture',['C:/xampp/php/php.exe','scripts/seed-ui-showcase.php','--apply','--expected-database=couponhub_merge_test'])
 offer=run('review-fixture',['C:/xampp/php/php.exe','tests/external-offer-fixture.php']).decode().strip();assert offer.isdigit()
 env|={'COUPONHUB_FULL_UI_AUDIT':'1','COUPONHUB_PROVIDER_UI_TEST':'1','COUPONHUB_REVIEW_OFFER_ID':offer}
 run('browser-full-app',[sys.executable,'tests/phase4-browser.py'])
run('restore-isolated-fixture',[sys.executable,'tests/sql-install.py'])
print('PASS strict local acceptance. No real provider credentials used. Evidence: '+str(runtime),flush=True)
