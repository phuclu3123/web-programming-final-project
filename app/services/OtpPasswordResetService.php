<?php
declare(strict_types=1);
namespace App\Services;

use App\Contracts\{UserProfileRepositoryInterface, OtpResetRepositoryInterface, AuthMailerInterface};
use App\Core\{Session, HttpError};

/** Session holds only a keyed OTP hash + random reset token; SQL enforces one use. */
final class OtpPasswordResetService
{
    public const MESSAGE = 'Nếu email có tài khoản hợp lệ, mã xác minh sẽ được gửi trong ít phút.';

    public function __construct(private UserProfileRepositoryInterface $users, private OtpResetRepositoryInterface $tokens,
        private AuthMailerInterface $mail, private string $secret) {}

    private function configured(): void
    {
        if (strlen($this->secret) < 32) throw new HttpError(503, 'Chưa cấu hình khôi phục mật khẩu. Vui lòng liên hệ quản trị viên.');
    }

    public function request(string $email, string $ip): void
    {
        $this->configured();
        Session::start();
        $email = mb_strtolower(trim($email));
        if (strlen($email)>191 || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new HttpError(422,'Email không hợp lệ.');
        $token=bin2hex(random_bytes(32)); $otp=(string)random_int(100000,999999);
        // Same browser state and public response for known and unknown addresses.
        $_SESSION['password_reset_flow']=['token'=>$token,'email'=>$email,'expires'=>time()+600,'attempts'=>0,
            'otp_hash'=>hash_hmac('sha256',$token."\0".$otp,$this->secret)];
        $user=$this->users->findByEmail($email);
        if (!$user || $user['status']!=='ACTIVE' || $user['deleted_at']!==null) return;
        $ipHash=hash('sha256',$ip);
        if (!$this->tokens->canRequest($ipHash,(int)$user['id'])) return;
        $hash=hash('sha256',$token);
        $this->tokens->issueOtp((int)$user['id'],$hash,$ipHash);
        if (!$this->tokens->isUsable($hash)) return; // A concurrent request won the cooldown.
        try { $this->mail->sendOtp($email,$otp); }
        catch (\Throwable $e) {
            $this->tokens->delete($hash);
            error_log('CouponHub OTP delivery failed: '.get_class($e));
        }
    }

    public function reset(string $code, string $password, string $confirmation): bool
    {
        $this->configured(); Session::start();
        if (!AccountService::validateNewPassword($password) || !hash_equals($password,$confirmation)) throw new HttpError(422,'Mật khẩu cần ít nhất 8 ký tự, tối đa 72 byte, gồm chữ hoa, chữ thường, số, ký tự đặc biệt và xác nhận trùng khớp.');
        $flow=$_SESSION['password_reset_flow'] ?? null;
        if (!is_array($flow) || $flow['expires']<=time() || $flow['attempts']>=5) return false;
        $_SESSION['password_reset_flow']['attempts']++;
        if (!preg_match('/^\d{6}$/D',$code) || !hash_equals($flow['otp_hash'],hash_hmac('sha256',$flow['token']."\0".$code,$this->secret))) {
            if ($_SESSION['password_reset_flow']['attempts']>=5) {
                $this->tokens->delete(hash('sha256',$flow['token']));
                unset($_SESSION['password_reset_flow']);
            }
            return false;
        }
        $success=$this->tokens->consume(hash('sha256',$flow['token']),password_hash($password,PASSWORD_DEFAULT));
        unset($_SESSION['password_reset_flow']);
        return $success;
    }
}
