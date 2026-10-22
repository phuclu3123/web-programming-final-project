<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Contracts\CouponRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use mysqli_sql_exception;
use RuntimeException;

final class CouponService
{
    private const DISCOUNT_TYPES = ['PERCENTAGE', 'FIXED_AMOUNT', 'FREE_SHIPPING', 'BUY_X_GET_Y', 'GIFT', 'CASHBACK'];
    private const CHANNELS = ['ONLINE', 'OFFLINE', 'BOTH'];
    private const SORTS = ['NEWEST', 'POPULAR', 'ENDING_SOON'];

    public function __construct(private CouponRepositoryInterface $coupons) {}

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function normalizeSearch(array $input): array
    {
        $keyword = trim((string) ($input['keyword'] ?? ''));
        if (mb_strlen($keyword) > 200) {
            $keyword = mb_substr($keyword, 0, 200);
        }

        $type = strtoupper(trim((string) ($input['discount_type'] ?? '')));
        $channel = strtoupper(trim((string) ($input['usage_channel'] ?? '')));
        $sort = strtoupper(trim((string) ($input['sort'] ?? 'NEWEST')));

        return [
            'keyword' => $keyword !== '' ? $keyword : null,
            'category_id' => $this->positiveIntOrNull($input['category_id'] ?? null),
            'merchant_id' => $this->positiveIntOrNull($input['merchant_id'] ?? null),
            'discount_type' => in_array($type, self::DISCOUNT_TYPES, true) ? $type : null,
            'usage_channel' => in_array($channel, self::CHANNELS, true) ? $channel : null,
            'ending_soon' => in_array((string) ($input['ending_soon'] ?? ''), ['1', 'true', 'on'], true),
            'sort' => in_array($sort, self::SORTS, true) ? $sort : 'NEWEST',
            'page' => max(1, (int) ($input['page'] ?? 1)),
            'page_size' => min(24, max(1, (int) ($input['page_size'] ?? 12))),
        ];
    }

    /** @param array<string,mixed> $filters @return array<int,array<string,mixed>> */
    public function search(array $filters): array
    {
        return $this->coupons->search($filters);
    }

    /** @param array<string,mixed> $input */
    public function submitCommunity(int $actorId, array $input): int
    {
        $data = $this->validateSubmission($input);

        try {
            $couponId = $this->coupons->submitCommunity($actorId, $data);
            if ($couponId <= 0) {
                throw new RuntimeException('Không thể xác định coupon vừa tạo.');
            }
            return $couponId;
        } catch (mysqli_sql_exception $e) {
            $message = $e->getMessage();
            if (stripos($message, 'Duplicate code with overlapping validity') !== false) {
                throw new RuntimeException('Mã coupon này đã tồn tại ở cùng Merchant và bị trùng khoảng thời gian sử dụng.');
            }
            if (stripos($message, 'Merchant unavailable') !== false) {
                throw new RuntimeException('Merchant hiện không khả dụng.');
            }
            if (stripos($message, 'Active category required') !== false) {
                throw new RuntimeException('Danh mục không hợp lệ hoặc đã bị tắt.');
            }
            if (stripos($message, 'Invalid validity period') !== false) {
                throw new RuntimeException('Khoảng thời gian sử dụng coupon không hợp lệ.');
            }
            throw $e;
        }
    }

    /** @param array<string,mixed> $input */
    public function submitOfficial(int $actorId, array $input): int
    {
        $data = $this->validateSubmission($input);
        try {
            $couponId = $this->coupons->submitOfficial($actorId, $data);
            if ($couponId <= 0) throw new RuntimeException('Không thể xác định coupon Official vừa tạo.');
            return $couponId;
        } catch (mysqli_sql_exception $e) {
            $message = $e->getMessage();
            if (stripos($message, 'Only approved merchant owner') !== false) {
                throw new RuntimeException('Chỉ chủ Merchant đã được duyệt mới có thể tạo Official Deal.');
            }
            if (stripos($message, 'Duplicate code with overlapping validity') !== false) {
                throw new RuntimeException('Mã coupon này bị trùng khoảng hiệu lực với coupon khác của Merchant.');
            }
            throw $e;
        }
    }

    /** @param array<string,mixed> $input */
    public function updateOfficial(int $actorId, int $couponId, array $input): void
    {
        $data = $this->validateSubmission($input);
        $this->coupons->updateOwnedOfficial($actorId, $couponId, $data);
    }

    public function moderate(int $actorId, int $couponId, string $action, ?string $note): void
    {
        $action = strtoupper(trim($action));
        if ($couponId <= 0 || !in_array($action, ['APPROVE', 'REJECT'], true)) {
            throw new RuntimeException('Yêu cầu kiểm duyệt không hợp lệ.');
        }

        [$moderation, $publication] = $action === 'APPROVE'
            ? ['APPROVED', 'ENABLED']
            : ['REJECTED', 'DISABLED'];

        if ($action === 'REJECT' && (mb_strlen(trim((string)$note)) < 10 || mb_strlen(trim((string)$note)) > 1000)) {
            throw new RuntimeException('Lý do từ chối cần từ 10 đến 1.000 ký tự để người gửi biết cần chỉnh gì.');
        }

        try {
            $this->coupons->moderate($actorId, $couponId, $moderation, $publication, $note);
        } catch (mysqli_sql_exception $e) {
            $message = $e->getMessage();
            if (stripos($message, 'Duplicate code with overlapping validity') !== false) {
                throw new RuntimeException('Không thể duyệt vì mã coupon bị trùng trong cùng khoảng thời gian.');
            }
            if (stripos($message, 'Coupon not found') !== false) {
                throw new RuntimeException('Không tìm thấy coupon cần kiểm duyệt.');
            }
            throw $e;
        }
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function validateSubmission(array $input): array
    {
        $errors = [];
        $title = trim((string) ($input['title'] ?? ''));
        $code = trim((string) ($input['code'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $terms = trim((string) ($input['terms'] ?? ''));
        $promotionUrl = trim((string) ($input['promotion_url'] ?? ''));
        $type = strtoupper(trim((string) ($input['discount_type'] ?? '')));
        $channel = strtoupper(trim((string) ($input['usage_channel'] ?? '')));
        $cashbackUnit = strtoupper(trim((string) ($input['cashback_unit'] ?? '')));
        $merchantId = $this->positiveIntOrNull($input['merchant_id'] ?? null);
        $categoryId = $this->positiveIntOrNull($input['category_id'] ?? null);

        if ($title === '') $errors['title'] = 'Tên coupon là bắt buộc.';
        elseif (mb_strlen($title) > 200) $errors['title'] = 'Tên coupon không được vượt quá 200 ký tự.';
        if ($terms === '') $errors['terms'] = 'Điều kiện sử dụng là bắt buộc.';
        if ($merchantId === null) $errors['merchant_id'] = 'Vui lòng chọn Merchant.';
        if ($categoryId === null) $errors['category_id'] = 'Vui lòng chọn danh mục.';
        if (!in_array($type, self::DISCOUNT_TYPES, true)) $errors['discount_type'] = 'Loại giảm giá không hợp lệ.';
        if (!in_array($channel, self::CHANNELS, true)) $errors['usage_channel'] = 'Hình thức sử dụng không hợp lệ.';
        if ($code === '' && $promotionUrl === '') $errors['code'] = 'Cần có mã coupon hoặc Promotion URL.';
        if ($promotionUrl !== '' && safeHttpUrl($promotionUrl) === null) $errors['promotion_url'] = 'Liên kết ưu đãi cần dùng http hoặc https và không chứa thông tin đăng nhập.';

        $discountRaw = trim((string) ($input['discount_value'] ?? ''));
        $minOrderRaw = trim((string) ($input['min_order_amount'] ?? ''));
        $maxDiscountRaw = trim((string) ($input['max_discount_amount'] ?? ''));
        $discountValue = $this->nullableDecimal($discountRaw);
        $minOrder = $this->nullableDecimal($minOrderRaw) ?? 0.0;
        $maxDiscount = $this->nullableDecimal($maxDiscountRaw);
        if ($minOrderRaw !== '' && !is_numeric($minOrderRaw)) $errors['min_order_amount'] = 'Giá trị đơn hàng tối thiểu phải là số.';
        elseif ($minOrder < 0) $errors['min_order_amount'] = 'Giá trị đơn hàng tối thiểu không được âm.';
        if ($maxDiscountRaw !== '' && !is_numeric($maxDiscountRaw)) $errors['max_discount_amount'] = 'Mức giảm tối đa phải là số.';
        elseif ($maxDiscount !== null && $maxDiscount <= 0) $errors['max_discount_amount'] = 'Mức giảm tối đa phải lớn hơn 0.';

        if (in_array($type, ['PERCENTAGE', 'FIXED_AMOUNT', 'CASHBACK'], true)) {
            if ($discountValue === null || $discountValue <= 0) {
                $errors['discount_value'] = 'Giá trị giảm phải lớn hơn 0.';
            }
        } else {
            $discountValue = null;
        }

        if ($type === 'PERCENTAGE' && $discountValue !== null && $discountValue > 100) {
            $errors['discount_value'] = 'Giảm theo phần trăm không được vượt quá 100%.';
        }
        if ($type === 'CASHBACK') {
            if (!in_array($cashbackUnit, ['PERCENTAGE', 'FIXED_AMOUNT'], true)) {
                $errors['cashback_unit'] = 'Vui lòng chọn đơn vị hoàn tiền.';
            }
            if ($cashbackUnit === 'PERCENTAGE' && $discountValue !== null && $discountValue > 100) {
                $errors['discount_value'] = 'Hoàn tiền theo phần trăm không được vượt quá 100%.';
            }
        } else {
            $cashbackUnit = '';
        }

        $startRaw = trim((string) ($input['starts_at'] ?? ''));
        $endRaw = trim((string) ($input['ends_at'] ?? ''));
        $startUtc = $this->localInputToUtc($startRaw);
        $endUtc = $this->localInputToUtc($endRaw);
        if ($startUtc === null) $errors['starts_at'] = 'Thời gian bắt đầu không hợp lệ.';
        if ($endUtc === null) $errors['ends_at'] = 'Thời gian kết thúc không hợp lệ.';
        if ($startUtc !== null && $endUtc !== null) {
            $utc = new DateTimeZone('UTC');
            $startTimestamp = (new DateTimeImmutable($startUtc, $utc))->getTimestamp();
            $endTimestamp = (new DateTimeImmutable($endUtc, $utc))->getTimestamp();
            if ($endTimestamp <= $startTimestamp) {
                $errors['ends_at'] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
            } elseif ($endTimestamp <= time()) {
                $errors['ends_at'] = 'Thời gian kết thúc phải ở tương lai.';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'merchant_id' => $merchantId,
            'category_id' => $categoryId,
            'title' => $title,
            'code' => $code !== '' ? $code : null,
            'description' => $description !== '' ? $description : null,
            'terms' => $terms,
            'discount_type' => $type,
            'discount_value' => $discountValue,
            'cashback_unit' => $cashbackUnit !== '' ? $cashbackUnit : null,
            'min_order_amount' => $minOrder,
            'max_discount_amount' => $maxDiscount,
            'usage_channel' => $channel,
            'promotion_url' => $promotionUrl !== '' ? $promotionUrl : null,
            'starts_at_utc' => $startUtc,
            'ends_at_utc' => $endUtc,
        ];
    }

    private function positiveIntOrNull($value): ?int
    {
        if ($value === null || $value === '' || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }
        $number = (int) $value;
        return $number > 0 ? $number : null;
    }

    private function nullableDecimal($value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        return (float) $value;
    }

    private function localInputToUtc(string $value): ?string
    {
        if ($value === '') return null;
        $timezone = new DateTimeZone((string) config('app.timezone', 'Asia/Ho_Chi_Minh'));
        $format = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/D', $value) ? '!Y-m-d\\TH:i:s' : '!Y-m-d\\TH:i';
        $date = DateTimeImmutable::createFromFormat($format, $value, $timezone);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
