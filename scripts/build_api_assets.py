"""Generate a route-complete OpenAPI contract from the actual PHP router; no DB/provider calls."""
import json,re,subprocess
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
code="require 'app/bootstrap.php';$router=new App\\Core\\Router();require 'config/routes.php';echo json_encode($router->routes());"
routes=json.loads(subprocess.check_output(['C:/xampp/php/php.exe','-r',code],cwd=ROOT))
out=ROOT/'docs/api';out.mkdir(parents=True,exist_ok=True)
def roles(path,method):
 if path=='/admin.php' or path=='/admin' or path.startswith('/admin/'):return ['ADMIN']
 if path=='/merchant/apply':return ['USER','MERCHANT']
 if path=='/shop.php' or path=='/merchant' or path.startswith('/merchant/'):return ['MERCHANT']
 if path=='/coupons' and method=='POST':return ['USER','MERCHANT']
 if path in ['/notifications','/notifications/read','/dashboard','/account','/account/password','/my-coupons','/saved-coupons','/coupons/create'] or re.match(r'^/(saved-coupons/[^/]+/(remove|save)|api/coupons/[^/]+/(favorite|vote|comments|reports))$',path):return ['USER','MERCHANT','ADMIN']
 return []
fields={
 'login':['account','password','return_url'], 'register':['full_name','username','phone','email','password','password_confirmation'],
 'forgotPassword':['email'], 'resetOtp':['otp','new_password','password_confirmation'],
 'resetPassword':['token','password','password_confirmation'], 'start':['mode','return_url'], 'prepare':['mode','provider'],
 'sync':['resource','page','merchant'], 'dismiss':[],
 'favorite':['saved'], 'vote':['vote'], 'comment':['body'], 'report':['reason','details'], 'record':['event'],
 'status':['status'], 'shopStatus':['action','note'], 'review':['action','note','duplicate_confirmed'], 'moderate':['action','note'], 'revoke':['reason'], 'expire':['reason'], 'delete':['reason'],
 'attach':['coupon_id'], 'detach':[], 'read':[], 'resolve':['action','note'],
}
coupon_fields=['merchant_id','category_id','title','code','description','terms','discount_type','discount_value','cashback_unit','min_order_amount','max_discount_amount','usage_channel','promotion_url','starts_at','ends_at']
spec={'openapi':'3.0.3','info':{'title':'CouponHub HTTP API and form contracts','version':'2026.11.06','description':'Actual PHP routes. Session cookie authentication, CSRF on every write. Browser form endpoints may return redirects/HTML; JSON APIs are explicitly marked. No bearer JWT is required. OAuth/Resend/ACCESSTRADE real-provider acceptance requires your credentials.'},'servers':[{'url':'http://localhost:8000/CouponHub_UI'}], 'paths':{},'components':{'securitySchemes':{'SessionCookie':{'type':'apiKey','in':'cookie','name':'COUPONHUBSESSID','description':'Obtain via GET /login followed by credential login. Browser sends its HttpOnly cookie automatically.'}},'schemas':{'Error':{'type':'object','properties':{'success':{'type':'boolean','example':False},'message':{'type':'string'},'errors':{'type':'object','additionalProperties':{'type':'string'}}}}}}}
catalog=[]
for i,r in enumerate(routes):
 path,method=r['path'],r['method'];handler,action=r['handler'];json_api=path=='/admin/api-spec' or path.startswith('/api/') or path.startswith('/admin/integrations/') and method=='POST'
 policy=roles(path,method)
 op={'operationId':handler.replace('\\','_')+'_'+action+'_'+str(i),'summary':action+' — '+path,'tags':[policy[0] if policy else 'Public / Auth'],'x-role-policy':policy,'x-handler':handler+'::'+action,'responses':{}}
 if policy or method=='POST':op['security']=[{'SessionCookie':[]}]
 for name in re.findall(r'\{(\w+)\}',path):
  schema={'type':'string'}
  if name in ['id','couponId','userId','merchantId']:schema.update(pattern='^[1-9][0-9]*$',example='1')
  if name=='provider':schema['enum']=['google','github','facebook']
  op.setdefault('parameters',[]).append({'name':name,'in':'path','required':True,'schema':schema})
 if method=='POST':
  names=list(fields.get(action,[]))
  if action in ['storeCoupon','updateCoupon','update','store','import'] and ('Coupon' in handler or action=='import'):names=coupon_fields
  if 'Campaign' in handler and action in ['store','merchantStore','merchantUpdate']:names=['name','description','merchant_id','starts_at','ends_at','status','banner_path']
  if path in ['/merchant/apply']:names=['merchant_id','name','description','website_url','evidence']
  if 'CategoryController' in handler and action in ['store','update']:names=['name','description','sort_order']
  if 'ArticleController' in handler and action in ['store','update']:names=['title','summary','body','status']
  if path=='/account':names=['full_name','phone']
  if path=='/account/password':names=['current_password','new_password','new_password_confirmation']
  if path=='/merchant/apply':names=['mode','merchant_id','name','description','website_url','evidence_reference']
  if path.startswith('/merchant/profile/'):names=['name','description','website_url']
  if action=='toggle':names=['active']
  if path=='/admin.php':names=['action','coupon_id','note','review_confirm']
  if path=='/shop.php':names=[n for n in coupon_fields if n not in ['starts_at','ends_at','cashback_unit','promotion_url']]+['starts_on','expires_on']
  if '/merchant-memberships/' in path:names=['note']
  if action=='record':names=['event']
  props={n:{'type':'string'} for n in ['csrf','_token']+names}
  props['csrf']['description']='Current session CSRF token, from HTML meta csrf-token. _token is the legacy alias.'
  protected=path in ['/login','/api/login.php','/register','/api/register.php','/forgot-password','/coupons','/merchant/apply']
  if protected:props['cf-turnstile-response']={'type':'string','maxLength':2048,'description':'Fresh one-use Turnstile token when protection is enabled. Never use production secrets here.'}
  required=['csrf']
  if action=='login':required+=['account','password']
  if action=='register':required+=['full_name','username','email','phone','password','password_confirmation']
  if action=='forgotPassword':required+=['email']
  if action=='import':props['source_fingerprint']={'type':'string','pattern':'^[a-f0-9]{64}$','description':'Current fingerprint from the review form; stale revision rejected with 409.'}
  if action=='import':required+=['source_fingerprint']
  if action=='import':required+=['merchant_id','category_id','title','terms','discount_type','usage_channel','starts_at','ends_at','min_order_amount']
  schema={'type':'object','required':required,'properties':props,'additionalProperties':True}
  content={'application/x-www-form-urlencoded':{'schema':schema}}
  if handler.endswith(('CouponInteractionController','CouponEventController')):content['application/json']={'schema':schema}
  op['requestBody']={'required':True,'content':content}
 else:
  params=[]
  if path=='/api/coupons/search':params=['keyword','category_id','merchant_id','discount_type','usage_channel','ending_soon','sort','page','page_size']
  elif path in ['/campaigns','/coupons','/admin/coupons','/admin/integrations']:params=['page']
  if path=='/coupons':params+=['keyword','category_id','merchant_id','discount_type','usage_channel','ending_soon','sort']
  if path=='/campaigns':params+=['phase','category_id','usage_channel','platform','preview']
  if path=='/campaigns/{id}':params+=['category_id','usage_channel','platform']
  if 'callback.php' in path:params=['state','code','error']
  elif path=='/reset-password':params=['token']
  for name in params:op.setdefault('parameters',[]).append({'name':name,'in':'query','schema':{'type':'string'}})
 if method=='POST' and not json_api:
  op['responses']['303']={'description':'Browser form redirect on success; some legacy forms also redirect with session flash on a business rejection. Inspect the destination and database state in acceptance tests.'}
  op['responses']['302']={'description':'Legacy form redirect.'}
 if method=='POST' and action in ['register','comment','report']:op['responses']['201']={'description':'Account created; JSON for API or Accept JSON.'}
 op['responses']['200']={'description':'JSON response' if json_api else 'HTML view or JSON when explicitly supported','content':{'application/json' if json_api else 'text/html':{'schema':{'type':'object','additionalProperties':True} if json_api else {'type':'string'}}}}
 for status,description in [('413','Request body exceeds 1 MiB'),('419','Legacy controller CSRF rejection'),('500','Unexpected API failure'),('400','Malformed input'),('401','No active session'),('403','Role, object permission, origin or CSRF rejection'),('404','Missing or not publicly visible resource'),('405','Wrong method'),('409','State conflict / already handled'),('422','Validation/business rule rejected'),('429','Rate limited'),('502','External provider failed'),('503','Missing configuration or service unavailable')]:
  op['responses'][status]={'description':description+'; JSON for Accept: application/json, otherwise HTML.','content':{'application/json':{'schema':{'$ref':'#/components/schemas/Error'}}}}
 spec['paths'].setdefault(path,{})[method.lower()]=op
 catalog.append({'method':method,'path':path,'roles':policy,'handler':handler+'::'+action})
(out/'openapi.json').write_text(json.dumps(spec,ensure_ascii=False,indent=2),encoding='utf-8')
(out/'route-catalog.json').write_text(json.dumps(catalog,ensure_ascii=False,indent=2),encoding='utf-8')
print('Generated',len(routes),'route operations; contract uses real router methods/paths.')
