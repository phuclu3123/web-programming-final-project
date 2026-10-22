<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpError;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Repositories\PasswordResetRepository;
use App\Services\AuthService;
use App\Services\PasswordResetService;
use App\Core\AuthRateLimit;
use App\Services\{AuthMailFactory, OtpPasswordResetService};

/** ---- HTTP AUTH: đọc Request, gọi AuthService rồi trả JSON/View/redirect ---- */
final class AuthController extends Controller
{
    public function showLogin(Request $request): void { $this->authPage($request); }
    public function showRegister(Request $request): void
    {
        $this->authPage($request);
    }
    private function authPage(Request $request): void
    {
        $currentUser = Auth::user();
        if ($currentUser) Response::redirect(url(Auth::safeReturn($this->field($request, 'return'), $currentUser['role'])), 303);
        header('Cache-Control: no-store');
        // Direct login/guard URLs open the same popup over the public homepage.
        (new CatalogController())->index($request);
    }
    private function authService(): AuthService { return new AuthService(new UserRepository(Database::connection())); }
    private function field(Request $request, string $key): string
    {
        $value = $request->input($key, '');
        if (!is_string($value) || preg_match('//u', $value) !== 1) throw new HttpError(400, 'Dữ liệu gửi lên không hợp lệ.');
        return $value;
    }
    public function login(Request $request): void
    {
        $account = $request->input('account', $request->input('email', ''));
        if (!is_string($account)) throw new HttpError(400, 'Tài khoản không hợp lệ.');
        AuthRateLimit::request('login',$account);
        $result = $this->authService()->login($account, $this->field($request, 'password'), $this->field($request, 'return_url'));
        // JSON and regular forms receive the same URL, including the deployment folder.
        if ($result['success']) $result['redirect'] = url($result['redirect']);
        if ($request->expectsJson()) Response::json($result, $result['success'] ? 200 : 422);
        if (!$result['success']) Session::flash('error', $result['message']);
        Response::redirect($result['success'] ? $result['redirect'] : url('/login'), 303);
    }
    public function register(Request $request): void
    {
        AuthRateLimit::request('register','',20);
        $input = [];
        foreach (['full_name','username','phone','email','password','password_confirmation'] as $field) $input[$field] = $this->field($request, $field);
        $result = $this->authService()->register($input);
        if ($request->expectsJson()) Response::json($result, $result['success'] ? 201 : 422);
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        Response::redirect(url($result['success'] ? '/login' : '/register'), 303);
    }
    public function logout(Request $request): void { Auth::logout(); Response::redirect(url('/'), 303); }

    // ---- QUÊN MẬT KHẨU TỪ QUANG: dùng cùng users/session và cấu hình SMTP ----
    private function resetService(): PasswordResetService
    {
        $db = Database::connection();
        return new PasswordResetService(new UserRepository($db), new PasswordResetRepository($db), AuthMailFactory::create());
    }
    private function resetHeaders(): void
    {
        header('Cache-Control: no-store, private'); header('Referrer-Policy: no-referrer'); header('X-Robots-Tag: noindex, nofollow');
    }
    public function showForgotPassword(Request $request): void
    {
        $this->resetHeaders();
        $this->authPage($request);
    }
    public function forgotPassword(Request $request): void
    {
        $this->resetHeaders();
        $email = trim($this->field($request, 'email'));
        AuthRateLimit::request('reset-request',$email,8,4);
        AuthRateLimit::hit('reset-cooldown',mb_strtolower($email),1,60);
        $this->otpService()->request($email,$_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if ($request->expectsJson()) Response::json(['success'=>true,'message'=>OtpPasswordResetService::MESSAGE,'step'=>'reset']);
        Session::flash('info',OtpPasswordResetService::MESSAGE);
        Response::redirect(url('/forgot-password?step=reset'),303);
    }

    private function otpService(): OtpPasswordResetService
    {
        $db=Database::connection();
        return new OtpPasswordResetService(new UserRepository($db),new PasswordResetRepository($db),AuthMailFactory::create(),(string)config('auth.otp_secret',''));
    }

    public function resetOtp(Request $request): void
    {
        $this->resetHeaders();
        AuthRateLimit::request('reset-attempt','',30);
        $ok=$this->otpService()->reset(trim($this->field($request,'otp')),$this->field($request,'new_password'),$this->field($request,'password_confirmation'));
        if (!$ok) {
            if ($request->expectsJson()) Response::json(['success'=>false,'message'=>'Mã không đúng, đã hết hạn hoặc hết lượt thử. Hãy yêu cầu mã mới.'],422);
            Session::flash('error','Mã không đúng, đã hết hạn hoặc hết lượt thử. Hãy yêu cầu mã mới.');
            Response::redirect(url('/forgot-password?step=reset'),303);
        }
        Auth::logout(); Session::start();
        $message='Đã đổi mật khẩu. Vui lòng đăng nhập lại.';
        if ($request->expectsJson()) Response::json(['success'=>true,'message'=>$message,'redirect'=>url('/login')]);
        Session::flash('success',$message);
        Response::redirect(url('/login'),303);
    }
    public function showResetPassword(Request $request): void
    {
        $this->resetHeaders(); $token = $this->field($request, 'token');
        $valid = $this->resetService()->isValid($token);
        $this->view('auth/reset-password', ['pageTitle'=>'Đặt lại mật khẩu', 'token'=>$valid ? $token : '', 'valid'=>$valid, 'errors'=>[]]);
    }
    public function resetPassword(Request $request): void
    {
        $this->resetHeaders(); $token = $this->field($request, 'token'); $password = $this->field($request, 'password');
        $errors = [];
        if (!hash_equals($password, $this->field($request, 'password_confirmation'))) $errors['password_confirmation'] = 'Mật khẩu xác nhận chưa khớp.';
        $service = $this->resetService();
        try {
            if (!$errors && $service->reset($token, $password)) {
                Auth::logout(); Session::start(); Session::flash('success', 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.');
                Response::redirect(url('/login'), 303);
            }
        } catch (\RuntimeException $error) {
            if ($error instanceof \mysqli_sql_exception) error_log('CouponHub reset failed: '.get_class($error));
            $errors['password'] = $error instanceof \mysqli_sql_exception ? 'Không thể đặt lại mật khẩu lúc này.' : $error->getMessage();
        }
        if (!$errors) $errors['reset'] = 'Liên kết không hợp lệ hoặc đã hết hạn.';
        $valid = $service->isValid($token);
        $this->view('auth/reset-password', ['pageTitle'=>'Đặt lại mật khẩu', 'token'=>$valid ? $token : '', 'valid'=>$valid, 'errors'=>$errors]);
    }
}
