<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use RuntimeException;

// Tập trung truy vấn bài viết; ghi nội dung và nhật ký trong cùng transaction.
final class ArticleRepository implements \App\Contracts\ArticleRepositoryInterface
{
    use AdminWriteGuard;
    public function __construct(private Database $db) {}
    // Chỉ đọc bài đã công bố và tới thời điểm xuất bản; bài nháp không ra trang công khai.
    public function published(int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        return $this->db->query("SELECT a.id,a.slug,a.title,a.summary,a.published_at,u.full_name AS author_name FROM articles a JOIN users u ON u.id=a.author_id WHERE a.status='PUBLISHED' AND a.published_at<=UTC_TIMESTAMP() ORDER BY a.published_at DESC,a.id DESC LIMIT " . $limit)->fetchAll();
    }
    public function findPublished(string $slug): ?array
    {
        $q = $this->db->prepare("SELECT a.*,u.full_name AS author_name FROM articles a JOIN users u ON u.id=a.author_id WHERE a.slug=? AND a.status='PUBLISHED' AND a.published_at<=UTC_TIMESTAMP() LIMIT 1");
        $q->execute([$slug]);
        $r = $q->fetch();
        return is_array($r) ? $r : null;
    }
    public function all(): array
    {
        return $this->db->query("SELECT a.id,a.title,a.slug,a.status,a.published_at,u.full_name AS author_name FROM articles a JOIN users u ON u.id=a.author_id ORDER BY a.created_at DESC,a.id DESC")->fetchAll();
    }
    public function find(int $id): ?array
    {
        $q = $this->db->prepare('SELECT * FROM articles WHERE id=?');
        $q->execute([$id]);
        $r = $q->fetch();
        return is_array($r) ? $r : null;
    }
    // Kiểm tra nội dung rồi lưu bài và nhật ký cùng transaction của admin đang hoạt động.
    public function save(int $actorId, ?int $id, array $values): int
    {
        $title = trim((string)($values['title'] ?? ''));
        $summary = trim((string)($values['summary'] ?? ''));
        $body = trim((string)($values['body'] ?? ''));
        $status = strtoupper(trim((string)($values['status'] ?? 'DRAFT')));
        if ($title === '' || mb_strlen($title) > 200) throw new RuntimeException('Tiêu đề bắt buộc, tối đa 200 ký tự.');
        if ($body === '' || mb_strlen($body) > 30000) throw new RuntimeException('Nội dung bắt buộc, tối đa 30.000 ký tự.');
        if (mb_strlen($summary) > 1000) throw new RuntimeException('Tóm tắt tối đa 1.000 ký tự.');
        if (!in_array($status, ['DRAFT', 'PUBLISHED', 'ARCHIVED'], true)) throw new RuntimeException('Trạng thái bài viết không hợp lệ.');
        $this->db->beginTransaction();
        try {
            $this->requireActiveAdmin($actorId);
            if ($id === null) {
                $slug = slugify($title) . '-' . bin2hex(random_bytes(3));
                $q = $this->db->prepare('INSERT INTO articles(author_id,title,slug,summary,body,status,published_at) VALUES(?,?,?,?,?,?,IF(?,UTC_TIMESTAMP(),NULL))');
                $q->execute([$actorId, $title, $slug, $summary ?: null, $body, $status, $status === 'PUBLISHED' ? 1 : 0]);
                $id = (int)$this->db->lastInsertId();
            } else {
                $lock = $this->db->prepare('SELECT id FROM articles WHERE id=? FOR UPDATE');
                $lock->execute([$id]);
                if (!$lock->fetch()) throw new RuntimeException('Bài viết không tồn tại.');
                $q = $this->db->prepare('UPDATE articles SET title=?,summary=?,body=?,status=?,published_at=IF(?,COALESCE(published_at,UTC_TIMESTAMP()),NULL) WHERE id=?');
                $q->execute([$title, $summary ?: null, $body, $status, $status === 'PUBLISHED' ? 1 : 0, $id]);
            }
            $this->db->prepare('INSERT INTO activity_logs(user_id,action,description) VALUES(?,?,?)')
                ->execute([$actorId, 'ARTICLE_SAVE', json_encode(['article_id'=>$id,'status'=>$status], JSON_THROW_ON_ERROR)]);
            $this->db->commit();
            return $id;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
