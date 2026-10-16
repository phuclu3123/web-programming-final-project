<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CampaignRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class CampaignService
{
    public function __construct(private CampaignRepositoryInterface $campaigns) {}

    /** @param array<string,mixed> $input */
    public function create(int $actorId, ?int $merchantId, array $input): int
    {
        $data = $this->normalize($input);
        $slugBase = slugify($data['name']);
        $slug = $slugBase . '-' . strtolower(substr(bin2hex(random_bytes(3)), 0, 6));
        return $this->campaigns->create($actorId, $merchantId, $data['name'], $slug, $data['description'], $data['starts_at_utc'], $data['ends_at_utc'], $data['status']);
    }

    /** @param array<string,mixed> $input */
    public function updateOwned(int $campaignId, int $userId, array $input): void
    {
        $data = $this->normalize($input);
        $this->campaigns->updateOwned($campaignId, $userId, $data['name'], $data['description'], $data['starts_at_utc'], $data['ends_at_utc'], $data['status']);
    }

    /** @param array<string,mixed> $input @return array{name:string,description:?string,starts_at_utc:string,ends_at_utc:string,status:string} */
    private function normalize(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $status = strtoupper(trim((string) ($input['status'] ?? 'DRAFT')));
        if ($name === '' || mb_strlen($name) > 200) throw new RuntimeException('Tên Campaign là bắt buộc và tối đa 200 ký tự.');
        if (!in_array($status, ['DRAFT', 'PUBLISHED'], true)) throw new RuntimeException('Merchant chỉ có thể lưu nháp hoặc xuất bản Campaign.');
        $start = $this->localToUtc((string) ($input['starts_at'] ?? ''));
        $end = $this->localToUtc((string) ($input['ends_at'] ?? ''));
        if ($start === null || $end === null) throw new RuntimeException('Thời gian Campaign không hợp lệ.');
        $utc = new DateTimeZone('UTC');
        if ((new DateTimeImmutable($end, $utc))->getTimestamp() <= (new DateTimeImmutable($start, $utc))->getTimestamp()) {
            throw new RuntimeException('Campaign phải có thời gian kết thúc sau thời gian bắt đầu.');
        }
        if ((new DateTimeImmutable($end, $utc))->getTimestamp() <= time()) throw new RuntimeException('Campaign phải kết thúc trong tương lai.');
        return ['name' => $name, 'description' => $description !== '' ? $description : null, 'starts_at_utc' => $start, 'ends_at_utc' => $end, 'status' => $status];
    }

    private function localToUtc(string $value): ?string
    {
        if (trim($value) === '') return null;
        $tz = new DateTimeZone((string) config('app.timezone', 'Asia/Ho_Chi_Minh'));
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', trim($value), $tz);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || (is_array($errors) && ($errors['warning_count'] || $errors['error_count']))) return null;
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
