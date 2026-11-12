<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\{Database, HttpError, OAuthAccountLinkRequired};

final class OAuthRepository implements \App\Contracts\OAuthRepositoryInterface
{
    public function __construct(private Database $db) {}

    public function linkedProviders(int $userId): array
    {
        $read=$this->db->prepare('SELECT provider FROM oauth_accounts WHERE user_id=? ORDER BY provider');
        $read->execute([$userId]);
        return array_column($read->fetchAll(),'provider');
    }

    public function resolve(array $identity, ?array $linkActor = null): array
    {
        return $this->resolveAtomic($identity, $linkActor, null);
    }

    public function resolveLinked(array $google, array $facebook): array
    {
        if (($google['provider'] ?? '') !== 'google' || ($facebook['provider'] ?? '') !== 'facebook'
            || !is_string($facebook['subject'] ?? null) || $facebook['subject'] === '' || strlen($facebook['subject']) > 255) {
            throw new HttpError(422, 'Danh tính liên kết không hợp lệ.');
        }
        return $this->resolveAtomic($google, null, $facebook);
    }

    // Retry toàn bộ transaction khi tranh chấp UNIQUE/deadlock; không dùng lại user đọc từ lần thua.
    private function resolveAtomic(array $identity, ?array $linkActor, ?array $facebook): array
    {
        for ($attempt = 0; ; $attempt++) {
            try { return $this->resolveOnce($identity, $linkActor, $facebook); }
            catch (\mysqli_sql_exception $error) {
                if ($attempt < 2 && in_array($error->getCode(), [1062, 1213, 1205], true)) { usleep(random_int(1000, 20000)); continue; }
                if ($error->getCode() === 1062) throw new HttpError(409, 'Danh tính đăng nhập đang liên kết với tài khoản khác. Vui lòng thử lại.');
                throw $error;
            }
        }
    }

    // Khóa danh tính và email đã xác minh; tạo/link tài khoản cùng audit trong một transaction.
    private function resolveOnce(array $identity, ?array $linkActor, ?array $facebook): array
    {
        if (!in_array($identity['provider'] ?? '', ['google', 'github', 'facebook'], true)
            || !is_string($identity['subject'] ?? null) || $identity['subject'] === '' || strlen($identity['subject']) > 255) {
            throw new HttpError(422, 'Danh tính OAuth không hợp lệ.');
        }
        $this->db->beginTransaction();
        try {
            if ($linkActor !== null) {
                $actor=$this->db->prepare("SELECT id FROM users WHERE id=? AND auth_version=? AND status='ACTIVE' AND deleted_at IS NULL FOR UPDATE");
                $actor->execute([(int)$linkActor['id'],(int)$linkActor['auth_version']]);
                if (!$actor->fetch()) throw new HttpError(401,'Phiên liên kết tài khoản đã hết hiệu lực.');
            }
            $find=$this->db->prepare('SELECT u.id,u.email,u.full_name,u.role,u.status,u.deleted_at,u.auth_version FROM oauth_accounts o JOIN users u ON u.id=o.user_id WHERE o.provider=? AND o.provider_user_id=? FOR UPDATE');
            $find->execute([$identity['provider'],$identity['subject']]);
            $user=$find->fetch();
            // Email is an account-matching signal only after the service has
            // independently verified it. The HTTP request cannot set this flag.
            $verifiedEmail = ($identity['email_verified'] ?? false) === true
                && is_string($identity['email'] ?? null)
                && strlen($identity['email']) <= 191
                && filter_var($identity['email'], FILTER_VALIDATE_EMAIL)
                ? mb_strtolower($identity['email']) : null;
            $emailUser = null;
            if ($linkActor === null && $verifiedEmail !== null) {
                $email = $this->db->prepare('SELECT id,email,role,status,deleted_at FROM users WHERE email=? FOR UPDATE');
                $email->execute([$verifiedEmail]);
                $emailUser = $email->fetch() ?: null;
            }
            if ($user) {
                if ($user['status']!=='ACTIVE' || $user['deleted_at']!==null) throw new HttpError(403,'Tài khoản không còn hoạt động.');
                if ($linkActor !== null && (int)$user['id']!==(int)$linkActor['id']) throw new HttpError(409,'Danh tính này đã liên kết với tài khoản khác.');
                if ($emailUser && (int)$emailUser['id'] !== (int)$user['id']) throw new HttpError(409, 'Danh tính đăng nhập và email thuộc hai tài khoản khác nhau. Vui lòng liên hệ hỗ trợ.');
                if ($facebook !== null) $this->attachFacebook((int)$user['id'], $facebook);
                $this->db->commit();
                return $user;
            }
            $created = false;
            if ($linkActor !== null) {
                $existing=$this->db->prepare('SELECT id FROM oauth_accounts WHERE user_id=? AND provider=?');
                $existing->execute([(int)$linkActor['id'],$identity['provider']]);
                if ($existing->fetch()) throw new HttpError(409,'Tài khoản đã liên kết với nhà cung cấp này.');
                $id=(int)$linkActor['id'];
            } else {
                if ($verifiedEmail === null) throw new OAuthAccountLinkRequired();
                if ($emailUser) {
                    if ($emailUser['status'] !== 'ACTIVE' || $emailUser['deleted_at'] !== null) throw new HttpError(403, 'Tài khoản không còn hoạt động.');
                    $id = (int)$emailUser['id'];
                    $existing = $this->db->prepare('SELECT provider_user_id FROM oauth_accounts WHERE user_id=? AND provider=?');
                    $existing->execute([$id, $identity['provider']]);
                    if ($existing->fetch()) throw new HttpError(409, 'Email này đã liên kết với một danh tính khác của cùng nhà cung cấp.');
                } else {
                    $create=$this->db->prepare("INSERT INTO users(email,password_hash,full_name,role,status) VALUES(?,?,?,'USER','ACTIVE')");
                    $create->execute([$verifiedEmail,password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),mb_substr($identity['name'],0,120)]);
                    $id=(int)$this->db->lastInsertId();
                    $created = true;
                }
            }
            $this->db->prepare('INSERT INTO oauth_accounts(user_id,provider,provider_user_id,provider_email,email_verified) VALUES(?,?,?,?,?)')->execute([$id,$identity['provider'],$identity['subject'],$verifiedEmail ?? ($identity['email'] ?? null),$verifiedEmail !== null ? 1 : 0]);
            $this->db->prepare('INSERT INTO activity_logs(user_id,action,description) VALUES(?,?,?)')->execute([$id,$created?'OAUTH_SIGNUP':'OAUTH_LINK',$identity['provider']]);
            $read=$this->db->prepare('SELECT id,email,full_name,role,status,deleted_at,auth_version FROM users WHERE id=?');
            $read->execute([$id]); $user=$read->fetch();
            if ($facebook !== null) $this->attachFacebook($id, $facebook);
            $this->db->commit();
            return $user;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    // Chỉ gắn Facebook trong transaction xác thực Google; xung đột sở hữu phải rollback.
    private function attachFacebook(int $userId, array $facebook): void
    {
        $find = $this->db->prepare("SELECT user_id FROM oauth_accounts WHERE provider='facebook' AND provider_user_id=? FOR UPDATE");
        $find->execute([$facebook['subject']]);
        $owner = $find->fetch();
        if ($owner) {
            if ((int)$owner['user_id'] !== $userId) throw new HttpError(409, 'Facebook này đã thuộc tài khoản CouponHub khác. Không thể tự chuyển liên kết.');
            return;
        }
        $find = $this->db->prepare("SELECT id FROM oauth_accounts WHERE user_id=? AND provider='facebook' FOR UPDATE");
        $find->execute([$userId]);
        if ($find->fetch()) throw new HttpError(409, 'Tài khoản Google đã liên kết với một Facebook khác.');
        $this->db->prepare("INSERT INTO oauth_accounts(user_id,provider,provider_user_id,provider_email,email_verified) VALUES(?,'facebook',?,?,0)")
            ->execute([$userId, $facebook['subject'], $facebook['email'] ?? null]);
        $this->db->prepare('INSERT INTO activity_logs(user_id,action,description) VALUES(?,?,?)')->execute([$userId, 'OAUTH_LINK', 'facebook']);
    }
}
