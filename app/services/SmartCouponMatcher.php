<?php

declare(strict_types=1);

namespace App\Services;

/** Rule-based discovery only; never interprets coupon terms as contractual guarantees. */
final class SmartCouponMatcher
{
    /** @var array<string,bool> */
    private const STOP_WORDS = [
        'toi' => true,
        'muon' => true,
        'mua' => true,
        'tim' => true,
        'can' => true,
        'co' => true,
        'ma' => true,
        'giam' => true,
        'gia' => true,
        'cho' => true,
        'don' => true,
        'hang' => true,
        'khoang' => true,
        'tam' => true,
        'duoi' => true,
        'tren' => true,
        'tu' => true,
        'den' => true,
        'voi' => true,
        'nhung' => true,
        'nay' => true,
        'hom' => true,
        'mai' => true,
        'coupon' => true,
        'voucher' => true,
        'khuyen' => true,
        'mai' => true,
        'ma' => true,
        'tai' => true,
        'mot' => true,
        'trieu' => true,
        'nghin' => true,
        'ngan' => true,
        'tr' => true,
        'k' => true,
    ];

    /** Turns simple text budgets like "2 triệu" or "500k" into integer VND. */
    public function budgetFromText(string $query): ?int
    {
        if (preg_match('/(?:^|[^\p{L}\p{N}])(\d{1,4}(?:[,.]\d{1,2})?)\s*(triệu|trieu|tr|nghìn|nghin|ngàn|ngan|k)(?!\p{L})/iu', $query, $m) !== 1) {
            return null;
        }
        $unit = $this->fold($m[2]);
        $multiplier = in_array($unit, ['trieu', 'tr'], true) ? 1_000_000 : 1_000;
        $number = (float) str_replace(',', '.', $m[1]);
        $value = (int) round($number * $multiplier, 0, PHP_ROUND_HALF_UP);
        return $value >= 1 && $value <= BestDealCalculator::MAX_ORDER ? $value : null;
    }

    public function fold(string $value): string
    {
        $value = strtr($value, [
            'à' => 'a',
            'á' => 'a',
            'ạ' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ầ' => 'a',
            'ấ' => 'a',
            'ậ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ă' => 'a',
            'ằ' => 'a',
            'ắ' => 'a',
            'ặ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            'è' => 'e',
            'é' => 'e',
            'ẹ' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ê' => 'e',
            'ề' => 'e',
            'ế' => 'e',
            'ệ' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'ị' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ọ' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ồ' => 'o',
            'ố' => 'o',
            'ộ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ơ' => 'o',
            'ờ' => 'o',
            'ớ' => 'o',
            'ợ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'ụ' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ư' => 'u',
            'ừ' => 'u',
            'ứ' => 'u',
            'ự' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            'ỳ' => 'y',
            'ý' => 'y',
            'ỵ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'đ' => 'd',
            'À' => 'A',
            'Á' => 'A',
            'Ạ' => 'A',
            'Ả' => 'A',
            'Ã' => 'A',
            'Â' => 'A',
            'Ầ' => 'A',
            'Ấ' => 'A',
            'Ậ' => 'A',
            'Ẩ' => 'A',
            'Ẫ' => 'A',
            'Ă' => 'A',
            'Ằ' => 'A',
            'Ắ' => 'A',
            'Ặ' => 'A',
            'Ẳ' => 'A',
            'Ẵ' => 'A',
            'È' => 'E',
            'É' => 'E',
            'Ẹ' => 'E',
            'Ẻ' => 'E',
            'Ẽ' => 'E',
            'Ê' => 'E',
            'Ề' => 'E',
            'Ế' => 'E',
            'Ệ' => 'E',
            'Ể' => 'E',
            'Ễ' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Ị' => 'I',
            'Ỉ' => 'I',
            'Ĩ' => 'I',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ọ' => 'O',
            'Ỏ' => 'O',
            'Õ' => 'O',
            'Ô' => 'O',
            'Ồ' => 'O',
            'Ố' => 'O',
            'Ộ' => 'O',
            'Ổ' => 'O',
            'Ỗ' => 'O',
            'Ơ' => 'O',
            'Ờ' => 'O',
            'Ớ' => 'O',
            'Ợ' => 'O',
            'Ở' => 'O',
            'Ỡ' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Ụ' => 'U',
            'Ủ' => 'U',
            'Ũ' => 'U',
            'Ư' => 'U',
            'Ừ' => 'U',
            'Ứ' => 'U',
            'Ự' => 'U',
            'Ử' => 'U',
            'Ữ' => 'U',
            'Ỳ' => 'Y',
            'Ý' => 'Y',
            'Ỵ' => 'Y',
            'Ỷ' => 'Y',
            'Ỹ' => 'Y',
            'Đ' => 'D',
        ]);
        return strtolower($value);
    }

    /** @return array<int,string> */
    public function keywords(string $query): array
    {
        $query = preg_replace('/\d{1,4}(?:[,.]\d{1,2})?\s*(?:triệu|trieu|tr|nghìn|nghin|ngàn|ngan|k)\b/iu', ' ', $query) ?? $query;
        preg_match_all('/[a-z0-9]{2,}/', $this->fold($query), $tokens);
        $unique = [];
        foreach ($tokens[0] as $term) {
            if (!isset(self::STOP_WORDS[$term]) && !ctype_digit($term)) $unique[$term] = true;
        }
        return array_slice(array_keys($unique), 0, 12);
    }

    /**
     * @param array<int,array<string,mixed>> $candidates
     * @return array<int,array<string,mixed>>
     */
    public function recommend(array $candidates, string $query, ?int $amount, string $channel, int $limit = 8): array
    {
        $terms = $this->keywords($query);
        $calculator = new BestDealCalculator();
        $matched = [];
        foreach ($candidates as $coupon) {
            // Defence in depth in case a query or test double omits public filtering.
            if (isset($coupon['effective_status']) && $coupon['effective_status'] !== 'ACTIVE') continue;
            if (($coupon['currency'] ?? '') !== 'VND') continue;
            if (!in_array(($coupon['usage_channel'] ?? ''), [$channel, 'BOTH'], true)) continue;
            $min = (float) ($coupon['min_order_amount'] ?? 0);
            if (!is_finite($min) || $min < 0 || ($amount !== null && $min > $amount)) continue;

            $merchant = $this->fold((string)($coupon['merchant_name'] ?? ''));
            $category = $this->fold((string)($coupon['category_name'] ?? ''));
            $title = $this->fold((string)($coupon['title'] ?? ''));
            $description = $this->fold((string)($coupon['description'] ?? ''));
            $matchedTerms = 0;
            $score = 0;
            foreach ($terms as $term) {
                $weight = 0;
                if (str_contains($merchant, $term)) $weight = max($weight, 9);
                if (str_contains($category, $term)) $weight = max($weight, 6);
                if (str_contains($title, $term)) $weight = max($weight, 4);
                if (str_contains($description, $term)) $weight = max($weight, 1);
                if ($weight > 0) {
                    $score += $weight;
                    ++$matchedTerms;
                }
            }
            if ($terms !== [] && $matchedTerms === 0) continue;
            $quote = $amount === null ? null : $calculator->estimate($coupon, $amount, 0, $channel);
            if ($amount !== null && $quote !== null && !$quote['eligible'] && ($coupon['discount_type'] ?? '') !== 'FREE_SHIPPING') continue;
            $coupon['match_score'] = $score;
            $coupon['match_terms'] = $matchedTerms;
            $coupon['estimated_saving'] = ($quote !== null && $quote['eligible']) ? $quote['saving'] : null;
            $matched[] = $coupon;
        }
        usort(
            $matched,
            static fn(array $a, array $b): int => ($b['match_score'] <=> $a['match_score'])
                ?: (($b['estimated_saving'] ?? 0) <=> ($a['estimated_saving'] ?? 0))
                ?: strcmp((string)$a['ends_at'], (string)$b['ends_at'])
                ?: ((int)$a['id'] <=> (int)$b['id'])
        );
        return array_slice($matched, 0, max(1, min(20, $limit)));
    }
}
