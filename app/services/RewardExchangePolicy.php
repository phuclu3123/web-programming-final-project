<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/** Keep lifetime points/Level independent from spendable CouponPoint. */
final class RewardExchangePolicy
{
    public static function balance(int $earned, int $reserved): int
    {
        if ($earned < 0 || $reserved < 0) throw new DomainException('Dữ liệu điểm không hợp lệ.');
        return max(0, $earned - $reserved);
    }

    public static function assertCanRedeem(int $earned, int $reserved, int $cost): void
    {
        if ($cost <= 0) throw new DomainException('Phần thưởng không hợp lệ.');
        if (self::balance($earned, $reserved) < $cost) {
            throw new DomainException('Số dư CouponPoint không đủ để đổi phần thưởng này.');
        }
    }

    public static function idempotencyKey(string $raw): string
    {
        if (!preg_match('/\A[a-f0-9]{32}\z/D', $raw)) {
            throw new DomainException('Mã yêu cầu không hợp lệ. Hãy tải lại trang và thử lại.');
        }
        return $raw;
    }

    public static function adminNote(string $note): string
    {
        $note = trim($note);
        $length = function_exists('mb_strlen') ? mb_strlen($note, 'UTF-8') : strlen($note);
        if ($length < 10 || $length > 500) {
            throw new DomainException('Ghi chú xử lý phải có độ dài từ 10 đến 500 ký tự.');
        }
        return $note;
    }
}
