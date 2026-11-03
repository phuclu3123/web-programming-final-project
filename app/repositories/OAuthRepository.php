<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\{Database, HttpError};

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
            if ($user) {
                if ($user['status']!=='ACTIVE' || $user['deleted_at']!==null) throw new HttpError(403,'Tài khoản không còn hoạt động.');
                if ($linkActor !== null && (int)$user['id']!==(int)$linkActor['id']) throw new HttpError(409,'Danh tính này đã liên kết với tài khoản khác.');
                $this->db->commit();
                return $user;
            }
            if ($linkActor !== null) {
                $existing=$this->db->prepare('SELECT id FROM oauth_accounts WHERE user_id=? AND provider=?');
                $existing->execute([(int)$linkActor['id'],$identity['provider']]);
                if ($existing->fetch()) throw new HttpError(409,'Tài khoản đã liên kết với nhà cung cấp này.');
                $id=(int)$linkActor['id'];
            } else {
                if ($identity['provider'] === 'facebook') throw new HttpError(422, 'Lần đầu dùng Facebook, hãy đăng nhập CouponHub rồi liên kết Facebook trong Tài khoản.');
                if (!is_string($identity['email'] ?? null) || strlen($identity['email']) > 191 || !filter_var($identity['email'], FILTER_VALIDATE_EMAIL)) throw new HttpError(422, 'Cần email đã xác minh để tạo tài khoản.');
                // Never join accounts merely because the provider reports the same email.
                $email=$this->db->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
                $email->execute([$identity['email']]);
                if ($email->fetch()) throw new HttpError(409,'Email đã có tài khoản CouponHub. Hãy đăng nhập bằng mật khẩu rồi liên kết trong Hồ sơ cá nhân.');
                $create=$this->db->prepare("INSERT INTO users(email,password_hash,full_name,role,status) VALUES(?,?,?,'USER','ACTIVE')");
                $create->execute([$identity['email'],password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),mb_substr($identity['name'],0,120)]);
                $id=(int)$this->db->lastInsertId();
            }
            $this->db->prepare('INSERT INTO oauth_accounts(user_id,provider,provider_user_id,provider_email,email_verified) VALUES(?,?,?,?,?)')->execute([$id,$identity['provider'],$identity['subject'],$identity['email'],$identity['provider'] === 'facebook' ? 0 : 1]);
            $this->db->prepare('INSERT INTO activity_logs(user_id,action,description) VALUES(?,?,?)')->execute([$id,$linkActor?'OAUTH_LINK':'OAUTH_SIGNUP',$identity['provider']]);
            $read=$this->db->prepare('SELECT id,email,full_name,role,status,deleted_at,auth_version FROM users WHERE id=?');
            $read->execute([$id]); $user=$read->fetch();
            $this->db->commit();
            return $user;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($e instanceof \mysqli_sql_exception && $e->getCode()===1062) throw new HttpError(409,'Tài khoản vừa được tạo hoặc liên kết ở một yêu cầu khác. Hãy thử đăng nhập lại.');
            throw $e;
        }
    }
}
