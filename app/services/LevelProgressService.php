<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Tính tiến độ lên cấp dựa trên số điểm yêu cầu của cấp hiện tại, không tính từ mốc 0 điểm.
 * Các mốc điểm được lấy từ bảng levels trong db, không sử dụng giá trị cấp độ cố định trong mã nguồn.
 */
final class LevelProgressService
{
    public static function percentage(int $earnedPoints, int $currentLevelMin, ?int $nextLevelMin): int
    {
        // Nếu không có cấp tiếp theo, người dùng đã đạt cấp cao nhất.
        if ($nextLevelMin === null) {
            return 100;
        }

        $currentLevelMin = max(0, $currentLevelMin);
        $earnedPoints = max(0, $earnedPoints);

        // Xử lý an toàn khi mốc điểm cấp độ trong ab không hợp lệ.
        if ($nextLevelMin <= $currentLevelMin) {
            return 100;
        }

        $currentPoints = max(0, min($earnedPoints - $currentLevelMin, $nextLevelMin - $currentLevelMin));
        return (int) floor(100 * $currentPoints / ($nextLevelMin - $currentLevelMin));
    }
}
