<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/** Validates public community input independently of HTTP and MySQL. */
final class DealRequestValidator
{
    private static function characters(string $value): int
    {
        if (function_exists('mb_strlen')) return mb_strlen($value, 'UTF-8');
        return preg_match_all('/./us', $value) ?: 0;
    }

    public static function request(string $title, string $body): array
    {
        $title = trim($title);
        $body = trim($body);
        if (!preg_match('//u', $title) || !preg_match('//u', $body)) {
            throw new DomainException('Văn bản phải sử dụng UTF-8 hợp lệ.');
        }
        if (self::characters($title) < 10 || self::characters($title) > 160) {
            throw new DomainException('Tiêu đề phải có từ 10 đến 160 ký tự.');
        }
        if (self::characters($body) < 10 || self::characters($body) > 1500) {
            throw new DomainException('Nội dung phải có từ 10 đến 1.500 ký tự.');
        }
        return ['title' => $title, 'body' => $body];
    }

    public static function reply(string $rawCouponId, string $note): array
    {
        $note = trim($note);
        if (
            !preg_match('/\A[1-9][0-9]{0,17}\z/D', $rawCouponId)
            || filter_var($rawCouponId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false
        ) {
            throw new DomainException('ID coupon phải là số nguyên dương hợp lệ.');
        }
        if (!preg_match('//u', $note) || self::characters($note) > 500) {
            throw new DomainException('Ghi chú không được vượt quá 500 ký tự.');
        }
        return ['coupon_id' => (int)$rawCouponId, 'note' => $note];
    }
}
