"""Local-only acceptance collection. Never contains real secrets or provider calls."""
import json,re
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
routes=json.loads((ROOT/'docs/api/route-catalog.json').read_text(encoding='utf-8'))
items=[]
def request(name,path,method='GET',data=None,status=200,test='',html=False):
 headers=[{'key':'Accept','value':'text/html' if html else 'application/json'}]
 req={'method':method,'header':headers,'url':'{{baseUrl}}'+path}
 if data is not None:req['body']={'mode':'urlencoded','urlencoded':[{'key':k,'value':str(v),'type':'text'} for k,v in data.items()]}
 script=[f"pm.test('HTTP {status}',()=>pm.response.to.have.status({status}));"]
 if test:script.append(test)
 item={'name':name,'request':req,'event':[{'listen':'test','script':{'type':'text/javascript','exec':script}}]}
 items.append(item)
token="const m=pm.response.text().match(/name=\"csrf-token\" content=\"([a-f0-9]+)\"/);pm.test('session CSRF exists',()=>pm.expect(m).not.equal(null));if(m)pm.collectionVariables.set('csrf',m[1]);"
request('Guest: get session and CSRF','/login',test=token,html=True)
for r in routes:
 if r['roles']:
  path=re.sub(r'\{[^}]+\}','1',r['path'])
  request('Guest denied '+r['method']+' '+path,path,r['method'],{'csrf':'{{csrf}}'} if r['method']=='POST' else None,401)
for provider in ['google','github','facebook']:request('Guest: reject invalid OAuth state '+provider,'/api/oauth/'+provider+'-callback.php?state=bad&code=bad',status=403)
request('Guest: malformed search is a validation error','/api/coupons/search?keyword[]=bad',status=422)
request('Guest: forged JWT cannot become Admin','/admin',status=401)
# Add an actual forged Authorization header, rather than just naming the check.
items[-1]['request']['header'].append({'key':'Authorization','value':'Bearer forged.jwt.token'})
for role,username,password,destination in [('USER','user_demo','UserDemo!2026','/index.php'),('MERCHANT','merchant_shops','MerchantDemo!2026','/merchant'),('ADMIN','admin_demo','AdminDemo!2026','/admin')]:
 request(role+': obtain login CSRF','/login',test=token,html=True)
 request(role+': login','/api/login.php','POST',{'csrf':'{{csrf}}','account':username,'password':password},test="const d=pm.response.json();pm.test('role destination stays in app',()=>{pm.expect(d.success).equal(true);pm.expect(d.redirect).equal(pm.variables.get('baseUrl')+'"+destination+"');});")
 request(role+': refresh rotated session CSRF',destination,test=token,html=True)
 path={'USER':'/account','MERCHANT':'/merchant/profile/1','ADMIN':'/admin/categories'}[role]
 request(role+': reject nested scalar form field',path,'POST',{'csrf':'{{csrf}}','name[]':'bad','full_name[]':'bad'},422)
 for r in routes:
  if r['roles'] and role not in r['roles']:
   path=re.sub(r'\{[^}]+\}','1',r['path'])
   request(role+' denied '+r['method']+' '+path,path,r['method'],{'csrf':'{{csrf}}'} if r['method']=='POST' else None,403)
  if role=='ADMIN' and r['roles']==['ADMIN'] and r['method']=='POST':
   path=re.sub(r'\{[^}]+\}','1',r['path'])
   request('ADMIN requires valid CSRF '+path,path,'POST',{'csrf':'invalid'},403)
 request(role+': search public coupons','/api/coupons/search',test="const d=pm.response.json();pm.test('search success',()=>pm.expect(d.success).equal(true));")
 if role=='ADMIN':
  request('ADMIN source unavailable without key','/admin/integrations/sync','POST',{'csrf':'{{csrf}}','resource':'offers','page':'1'},503)
  request('ADMIN rejects unsupported provider resource','/admin/integrations/sync','POST',{'csrf':'{{csrf}}','resource':'https://evil.example','page':'1'},422)
  request('ADMIN same campaign status with csrf alias','/admin/campaigns/{{campaignId}}/status','POST',{'csrf':'{{csrf}}','status':'PUBLISHED'},302)
  for path in ['/admin/integrations','/admin/api-docs','/admin/coupons','/admin/campaigns','/admin/merchant-requests','/admin/reports']:
   request('ADMIN operational page '+path,path,html=True)
  request('ADMIN OpenAPI live contract','/admin/api-spec',test="const d=pm.response.json();pm.test('OpenAPI + protected write operations',()=>{pm.expect(d.openapi).equal('3.0.3');pm.expect(d.paths['/admin/integrations/{id}/import'].post['x-role-policy']).include('ADMIN');pm.expect(d.paths['/login'].post.requestBody.content['application/x-www-form-urlencoded'].schema.properties.csrf.default).match(/^[a-f0-9]+$/);});")
 if role=='USER':
  # Runner supplies a disposable active coupon. Tests both router and controller token aliases.
  request('USER favorite with csrf alias','/api/coupons/{{couponId}}/favorite','POST',{'csrf':'{{csrf}}','saved':'1'},test="pm.test('saved',()=>pm.expect(pm.response.json().data.saved).equal(true));")
  request('USER favorite with legacy token alias','/api/coupons/{{couponId}}/favorite','POST',{'_token':'{{csrf}}','saved':'0'},test="pm.test('removed',()=>pm.expect(pm.response.json().data.saved).equal(false));")
 request(role+': logout','/logout','POST',{'csrf':'{{csrf}}'},303)
 request(role+': session gone','/account',status=401)
collection={'info':{'name':'CouponHub isolated API acceptance','schema':'https://schema.getpostman.com/json/collection/v2.1.0/collection.json','description':'Run only on disposable couponhub_merge_test:13379 via tests/postman-acceptance.py. Local collection; use --no-report-events. Demo identities only; no external provider requests.'},'variable':[{'key':'csrf','value':''}],'item':items}
out=ROOT/'docs/api';(out/'couponhub.postman_collection.json').write_text(json.dumps(collection,ensure_ascii=False,indent=2),encoding='utf-8')
environment={'name':'CouponHub isolated local only','values':[{'key':'baseUrl','value':'http://127.0.0.1:8099','enabled':True},{'key':'couponId','value':'1','enabled':True},{'key':'campaignId','value':'1','enabled':True}]}
(out/'local.postman_environment.json').write_text(json.dumps(environment,indent=2),encoding='utf-8')
print('Generated',len(items),'Postman requests from current route policy.')
