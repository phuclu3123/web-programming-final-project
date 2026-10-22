<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpError;
use App\Core\OAuthPreparation;
use App\Core\Request;
use App\Core\Response;
use App\Core\{Auth, Database, Session, AuthRateLimit, HttpClient};
use App\Services\OAuthService;
use App\Repositories\OAuthRepository;

/** OAuth login/link callbacks and the separate, non-authenticating local probe. */
final class OAuthController extends Controller
{
    private function preparation(): OAuthPreparation
    {
        return new OAuthPreparation(require __DIR__ . '/../../config/oauth.php');
    }
    private function service(): OAuthService
    {
        return new OAuthService((array)config('oauth'), new HttpClient(), new OAuthRepository(Database::connection()));
    }

    public function start(Request $request, string $provider): void
    {
        AuthRateLimit::request('oauth', '', 30);
        $mode=$request->input('mode','login');
        $return=$request->input('return_url','');
        if (!is_string($return) || strlen($return)>2000 || !in_array($mode,['login','link'],true)) throw new HttpError(422,'Yêu cầu OAuth không hợp lệ.');
        $actor=$mode==='link' ? Auth::requireAuth() : null;
        Response::redirect($this->service()->begin($provider,$return,$actor),303);
    }
    public function prepare(Request $request): void
    {
        $preparation = $this->preparation();
        $mode = $request->input('mode', ''); $provider = $request->input('provider', '');
        if (!is_string($provider) || !in_array($mode, ['probe','provider'], true)) throw new HttpError(400, 'Chế độ thử không hợp lệ.');
        if ($mode==='probe') {
            OAuthPreparation::requireSetupAccess();
            Response::json(['success'=>true] + $preparation->begin($provider,true));
        }
        AuthRateLimit::request('oauth','',30);
        Response::json(['success'=>true,'url'=>$this->service()->begin($provider)]);
    }
    private function callback(string $provider, Request $request): void
    {
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: no-store, private');
        header('X-Robots-Tag: noindex, nofollow');
        $state=$request->input('state','');
        if (is_string($state) && ($_SESSION['oauth_pending'][$state]['probe'] ?? false)===true) {
            Response::json($this->preparation()->consume($provider,$request->all()));
        }
        // Missing/replayed states are rejected before touching Auth or the DB.
        // Redirect feedback below is reserved for an attempt initiated in this session.
        $pending=is_string($state) ? ($_SESSION['oauth_login_pending'][$state] ?? null) : null;
        if (!is_array($pending)) throw new HttpError(403,'Phiên OAuth không hợp lệ hoặc đã sử dụng.');
        $actor=null;
        $linkAttempt=false;
        try {
            $actor=Auth::user();
            // Resolve the destination from server-side intent, never a callback URL.
            $linkAttempt=is_array($pending) && ($pending['provider'] ?? null)===$provider
                && ($pending['link_actor'] ?? null)!==null;
            $result=$this->service()->complete($provider,$request->all(),$actor);
            if ($result['linked']) {
                Session::flash('success','Đã liên kết tài khoản '.$provider.'.');
                Response::redirect(url('/account'),303);
            }
            Auth::login($result['user']);
            $user=Auth::requireAuth();
            Response::redirect(url(Auth::safeReturn($result['return'],$user['role'])),303);
        } catch (\Throwable $error) {
            if (!$error instanceof HttpError) error_log('CouponHub OAuth failed: '.get_class($error));
            Session::flash('error',$error instanceof HttpError ? $error->getMessage() : 'Không thể hoàn tất đăng nhập. Hãy thử lại.');
            Response::redirect(url($linkAttempt && $actor ? '/account' : '/login'),303);
        }
    }
    public function google(Request $request): void { $this->callback('google', $request); }
    public function github(Request $request): void { $this->callback('github', $request); }
}
