<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CouponRepositoryInterface;
use App\Contracts\InteractionRepositoryInterface;
use mysqli_sql_exception;
use RuntimeException;

final class InteractionService
{
    private const VOTES = ['WORKING', 'NOT_WORKING', 'CONDITION_CHANGED'];
    private const REPORT_REASONS = ['EXPIRED', 'NOT_WORKING', 'WRONG_TERMS', 'DUPLICATE', 'SPAM', 'FAKE', 'INAPPROPRIATE', 'OTHER'];

    public function __construct(
        private InteractionRepositoryInterface $interactions,
        private CouponRepositoryInterface $coupons
    ) {}

    /** @return array<string,mixed> */
    public function initialState(?int $actorId, int $couponId): array
    {
        return [
            'saved' => $actorId !== null ? $this->interactions->isFavorite($actorId, $couponId) : false,
            'vote' => $actorId !== null ? $this->interactions->currentVote($actorId, $couponId) : null,
            'voteRestriction' => $actorId !== null ? $this->interactions->voteRestriction($actorId, $couponId) : null,
            'stats' => $this->normalizeStats($this->interactions->stats($couponId)),
            'comments' => $this->interactions->comments($couponId),
        ];
    }

    /** @return array<string,mixed> */
    public function setFavorite(int $actorId, int $couponId, bool $saved): array
    {
        // A coupon may expire or be archived after being saved. Users must
        // still be able to remove it from their library. Only adding a new
        // favorite requires the coupon to be publicly active.
        if ($saved) {
            $this->assertCouponId($couponId);
        } elseif ($couponId <= 0 || !$this->interactions->isFavorite($actorId, $couponId)) {
            throw new RuntimeException('Coupon không có trong danh sách đã lưu.');
        }

        try {
            $this->interactions->setFavorite($actorId, $couponId, $saved);
        } catch (mysqli_sql_exception $e) {
            throw new RuntimeException($this->businessMessage($e, 'Không thể cập nhật coupon đã lưu.'));
        }

        return [
            'saved' => $this->interactions->isFavorite($actorId, $couponId),
            'stats' => $this->normalizeStats($this->interactions->stats($couponId)),
        ];
    }

    /** @return array<string,mixed> */
    public function setVote(int $actorId, int $couponId, string $vote): array
    {
        $this->assertCouponId($couponId);
        $vote = strtoupper(trim($vote));
        if (!in_array($vote, self::VOTES, true)) {
            throw new RuntimeException('Loại xác nhận không hợp lệ.');
        }

        try {
            $this->interactions->setVote($actorId, $couponId, $vote);
        } catch (mysqli_sql_exception $e) {
            throw new RuntimeException($this->businessMessage($e, 'Không thể cập nhật xác nhận coupon.'));
        }

        return [
            'vote' => $this->interactions->currentVote($actorId, $couponId),
            'stats' => $this->normalizeStats($this->interactions->stats($couponId)),
        ];
    }

    /** @return array<string,mixed> */
    public function addComment(int $actorId, int $couponId, string $body): array
    {
        $this->assertCouponId($couponId);
        $body = trim($body);
        if ($body === '') {
            throw new RuntimeException('Nội dung bình luận không được để trống.');
        }
        if (mb_strlen($body) > 3000) {
            throw new RuntimeException('Bình luận không được vượt quá 3000 ký tự.');
        }
        return $this->interactions->addComment($actorId, $couponId, $body);
    }

    public function createReport(int $actorId, int $couponId, string $reason, ?string $details): int
    {
        $this->assertCouponId($couponId);
        $reason = strtoupper(trim($reason));
        $details = $details !== null ? trim($details) : null;

        if (!in_array($reason, self::REPORT_REASONS, true)) {
            throw new RuntimeException('Lý do báo cáo không hợp lệ.');
        }
        if ($reason === 'OTHER' && ($details === null || $details === '')) {
            throw new RuntimeException('Vui lòng mô tả lý do khi chọn “Khác”.');
        }
        if ($details !== null && mb_strlen($details) > 2000) {
            throw new RuntimeException('Chi tiết báo cáo không được vượt quá 2000 ký tự.');
        }

        return $this->interactions->createReport($actorId, $couponId, $reason, $details ?: null);
    }

    public function resolveReport(int $actorId, int $reportId, string $action, string $note): void
    {
        if ($reportId <= 0) {
            throw new RuntimeException('Báo cáo không hợp lệ.');
        }
        $action = strtoupper(trim($action));
        $note = trim($note);
        if (!in_array($action, ['RESOLVED', 'DISMISSED'], true)) {
            throw new RuntimeException('Trạng thái xử lý báo cáo không hợp lệ.');
        }
        if ($note === '') {
            throw new RuntimeException('Admin cần nhập ghi chú xử lý báo cáo.');
        }

        try {
            $this->interactions->resolveReport($actorId, $reportId, $action, $note);
        } catch (mysqli_sql_exception $e) {
            throw new RuntimeException($this->businessMessage($e, 'Không thể xử lý báo cáo.'));
        }
    }

    /** @param array<string,mixed> $stats @return array<string,mixed> */
    private function normalizeStats(array $stats): array
    {
        return [
            'workingCount' => (int) ($stats['working_count'] ?? 0),
            'notWorkingCount' => (int) ($stats['not_working_count'] ?? 0),
            'conditionChangedCount' => (int) ($stats['condition_changed_count'] ?? 0),
            'totalVotes' => (int) ($stats['total_votes'] ?? 0),
            'trustScore' => $stats['trust_score'] !== null ? (float) $stats['trust_score'] : null,
            'lastVerificationAt' => $stats['last_verification_at'] ?? null,
            'favoriteCount' => (int) ($stats['favorite_count'] ?? 0),
            'qualifiedFavorites' => (int) ($stats['qualified_favorites'] ?? 0),
        ];
    }

    private function assertCouponId(int $couponId): void
    {
        if ($couponId <= 0 || !$this->coupons->isActive($couponId)) {
            throw new RuntimeException('Coupon không tồn tại hoặc hiện không hoạt động.');
        }
    }

    private function businessMessage(mysqli_sql_exception $e, string $fallback): string
    {
        $message = $e->getMessage();
        $map = [
            'Author and merchant owner cannot vote on own coupon' => 'Bạn là người đăng coupon hoặc đang quản lý Shop phát hành coupon này nên không thể tham gia xác minh cộng đồng.',
            'Coupon must be active' => 'Chỉ coupon đang hoạt động mới có thể được xác nhận.',
            'Coupon cannot be saved' => 'Coupon hiện không thể lưu.',
            'Active account required' => 'Tài khoản của bạn hiện không hoạt động.',
            'Coupon not found' => 'Không tìm thấy coupon.',
            'Invalid vote' => 'Loại xác nhận không hợp lệ.',
        ];
        foreach ($map as $needle => $friendly) {
            if (stripos($message, $needle) !== false) {
                return $friendly;
            }
        }
        return $fallback;
    }
}
