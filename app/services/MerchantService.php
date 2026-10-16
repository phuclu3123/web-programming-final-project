<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MerchantRepositoryInterface;
use RuntimeException;

final class MerchantService
{
    public function __construct(private MerchantRepositoryInterface $merchants) {}

    /** @param array<string,mixed> $input */
    public function requestMerchant(int $userId, array $input): void
    {
        $mode = (string) ($input['mode'] ?? 'claim');
        if (!in_array($mode, ['claim', 'new'], true)) {
            throw new RuntimeException('Hình thức đăng ký Merchant không hợp lệ.');
        }
        $evidence = trim((string)($input['evidence_reference'] ?? ''));
        if (mb_strlen($evidence) > 1000) throw new RuntimeException('Bằng chứng sở hữu quá dài.');
        if ($mode === 'claim' && $evidence === '') throw new RuntimeException('Cần cung cấp liên kết hoặc thông tin chứng minh quyền đại diện Shop.');
        if ($mode === 'claim') {
            $merchantId = filter_var($input['merchant_id'] ?? null, FILTER_VALIDATE_INT);
            if ($merchantId === false || (int) $merchantId <= 0) throw new RuntimeException('Vui lòng chọn Merchant cần xác nhận quyền sở hữu.');
            $this->merchants->requestClaim($userId, (int) $merchantId, $evidence !== '' ? $evidence : null);
            return;
        }

        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $website = trim((string) ($input['website_url'] ?? ''));
        if ($name === '' || mb_strlen($name) > 160) throw new RuntimeException('Tên Merchant là bắt buộc và tối đa 160 ký tự.');
        if ($website !== '' && filter_var($website, FILTER_VALIDATE_URL) === false) throw new RuntimeException('Website Merchant không hợp lệ.');
        $slug = slugify($name);
        if ($slug === '') throw new RuntimeException('Không thể tạo slug cho Merchant.');
        try {
            $this->merchants->requestNew($userId, $name, $slug, $description !== '' ? $description : null, $website !== '' ? $website : null, $evidence !== '' ? $evidence : null);
        } catch (\mysqli_sql_exception $e) {
            if (stripos($e->getMessage(), 'Duplicate') !== false) throw new RuntimeException('Merchant/slug này đã tồn tại. Hãy dùng chức năng nhận quyền sở hữu nếu phù hợp.');
            throw $e;
        }
    }

    /** @param array<string,mixed> $input */
    public function updateProfile(int $merchantId, int $userId, array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $website = trim((string) ($input['website_url'] ?? ''));
        if ($name === '' || mb_strlen($name) > 160) throw new RuntimeException('Tên Merchant là bắt buộc và tối đa 160 ký tự.');
        if ($website !== '' && filter_var($website, FILTER_VALIDATE_URL) === false) throw new RuntimeException('Website Merchant không hợp lệ.');
        $this->merchants->updateProfile($merchantId, $userId, $name, $description !== '' ? $description : null, $website !== '' ? $website : null);
    }
}
