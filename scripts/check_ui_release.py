"""Read-only SSR audit of public, user, partner and administrator screens."""
import concurrent.futures
import json
import subprocess
from pathlib import Path
from urllib.request import urlopen
import re

ROOT=Path(__file__).resolve().parents[1]
PHP='C:/xampp/php/php.exe'
PAGES={
    'GUEST':['/','/categories','/coupons','/articles','/campaigns','/campaign-calendar','/login','/register','/forgot-password','/reset-password','/about','/terms','/privacy','/community-guidelines','/contact','/guides/doc-dieu-kien-ma','/guides/so-sanh-muc-tiet-kiem','/guides/kiem-chung-cung-cong-dong'],
    'USER':['/dashboard','/saved-coupons','/my-coupons','/coupons/create','/merchant/apply'],
    'MERCHANT':['/shop.php','/merchant','/merchant/coupons','/merchant/coupons/create','/merchant/campaigns','/merchant/campaigns/create','/saved-coupons','/coupons/create'],
    'ADMIN':['/admin.php?section=dashboard','/admin','/admin/users','/admin/merchants','/admin/merchant-requests','/admin/categories','/admin/coupons','/admin/campaigns','/admin/reports','/admin/articles','/admin/articles/create'],
}
def check(item):
    role,path=item
    result=subprocess.run([PHP,'scripts/audit_ui.php',role,path],cwd=ROOT,capture_output=True,text=True,encoding='utf-8',timeout=40)
    if result.returncode or not result.stdout.strip():
        raise RuntimeError(f'{role} {path}: renderer failed: {result.stderr[:300]}')
    report=json.loads(result.stdout)
    return report

if __name__=='__main__':
    jobs=[(role,path) for role,paths in PAGES.items() for path in paths]
    base='http://127.0.0.1:8000/CouponHub_UI/'
    with urlopen(base+'api/coupons/search',timeout=15) as response:
        listing=json.load(response)
    public_ids=[row['id'] for row in listing['data']]
    for page in range(2,listing['pagination']['totalPages']+1):
        with urlopen(base+'api/coupons/search?page='+str(page),timeout=15) as response:
            public_ids.extend(row['id'] for row in json.load(response)['data'])
    jobs.extend(('GUEST','/coupons/'+str(coupon_id)) for coupon_id in public_ids)
    jobs.extend((role,'/coupons/'+str(public_ids[0])) for role in ['USER','MERCHANT','ADMIN'])
    with urlopen(base+'campaigns',timeout=15) as response:
        campaign_ids=set(re.findall(r'/campaigns/(\d+)',response.read().decode('utf-8')))
    jobs.extend(('GUEST','/campaigns/'+campaign_id) for campaign_id in campaign_ids)
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
        reports=list(pool.map(check,jobs))
        visited=set(jobs)
        while True:
            discovered={(report['role'],link) for report in reports for link in report['links'] if (report['role'],link) not in visited}
            if not discovered: break
            visited.update(discovered)
            reports.extend(pool.map(check,sorted(discovered)))
    output=ROOT/'.runtime/ui-release-audit.json'
    output.parent.mkdir(exist_ok=True)
    output.write_text(json.dumps(reports,ensure_ascii=False,indent=2),encoding='utf-8')
    failures=[]
    for report in reports:
        expected=303 if report['path']=='/admin.php?section=dashboard' else 200
        if report['status']!=expected or report['error'] or report['duplicateIds'] or report['missingAssets'] or report['unlabelledFields'] or (report['status']==200 and report['h1']!=1):
            failures.append(report)
            print('FAIL',json.dumps(report,ensure_ascii=True))
            continue
        note=f"; labels: {', '.join(report['unlabelledFields'])}" if report['unlabelledFields'] else ''
        if report['status']==200 and report['h1']!=1: note+=f"; h1={report['h1']}"
        print(f"PASS {report['role']} {report['path']}{note}")
    assert not failures, f'{len(failures)} screen audits failed; inspect .runtime/ui-release-audit.json'
    print(f'PASS {len(reports)} screens; isolated sessions; database READ ONLY; report: .runtime/ui-release-audit.json')
