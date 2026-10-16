<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\HttpError;

/** A brand image is only used for that brand; generic scenes retain their licensed source. */
function brandPhoto(string $key): string
{
    $allowed=['grab-bike','grab-car','grab-food','grab-delivery','coolmate-men','coolmate-women','coolmate-autumn','coolmate-sport','be-food','be-ride'];
    return in_array($key,$allowed,true) && is_file(dirname(__DIR__).'/assets/images/brands/'.$key.'.webp')
        ? url('/assets/images/brands/'.$key.'.webp') : editorialPhoto('shopping',false);
}

function couponPhoto(array $coupon): string
{
    $brand=slugify((string)($coupon['merchant_name'] ?? ''));
    $variant=(int)($coupon['id'] ?? 0)%2;
    if ($brand==='coolmate') return brandPhoto($variant ? 'coolmate-women' : 'coolmate-men');
    if ($brand==='grab') return brandPhoto(str_contains(slugify($coupon['title'] ?? ''),'food') ? 'grab-food' : ($variant ? 'grab-bike' : 'grab-car'));
    if ($brand==='be') return brandPhoto(str_contains(slugify($coupon['title'] ?? ''),'food') ? 'be-food' : 'be-ride');
    return editorialPhoto((string)($coupon['category_name'] ?? 'shopping'));
}

/** Responsive hero sources use original photographs; smaller cards keep their own assets. */
function couponHeroMedia(array $coupon): array
{
    $normal=couponPhoto($coupon);
    $heroBrand=slugify($coupon['merchant_name']);
    $editorial=['shopee'=>'fashion','highlands-coffee'=>'food','highlands'=>'food','hasaki'=>'beauty','cgv'=>'cinema'];
    if (isset($editorial[$heroBrand])) $normal=editorialPhoto($editorial[$heroBrand],false);
    if ($heroBrand==='tiki') $normal=url('/assets/images/editorial/headphones-source.webp');
    if ($heroBrand==='traveloka' && is_file(dirname(__DIR__).'/assets/images/hero/travel-vivid.webp')) {
        $large=url('/assets/images/hero/travel-vivid.webp');
        $size=getimagesize(dirname(__DIR__).'/assets/images/hero/travel-vivid.webp');
        return ['src'=>$large,'srcset'=>$large.' '.$size[0].'w','width'=>$size[0],'height'=>$size[1]];
    }
    $brand=str_contains($normal,'/images/brands/');
    $name=basename((string)parse_url($normal,PHP_URL_PATH),'.webp');
    $normalPath=dirname(__DIR__).'/assets/images/'.($brand ? 'brands/' : 'editorial/').$name.'.webp';
    $heroName=($brand ? 'brand-' : 'category-').str_replace('-card','',$name).'.webp';
    $heroPath=dirname(__DIR__).'/assets/images/hero/'.$heroName;
    $normalSize=getimagesize($normalPath) ?: [1200,800];
    if (!is_file($heroPath)) return ['src'=>$normal,'srcset'=>'','width'=>$normalSize[0],'height'=>$normalSize[1]];
    $size=getimagesize($heroPath) ?: $normalSize;
    $large=url('/assets/images/hero/'.$heroName);
    return ['src'=>$large,'srcset'=>$normal.' '.$normalSize[0].'w, '.$large.' '.$size[0].'w','width'=>$size[0],'height'=>$size[1]];
}

function categoryStory(string $name): array
{
    $key=slugify($name);
    return [
        'mua-sam'=>['Những món đang cần.','Đơn tối thiểu, trần giảm, phí giao hàng.'],
        'an-uong'=>['Hẹn nhau một bữa ngon.','Đặt món, cà phê và những cuộc hẹn.'],
        'du-lich'=>['Đổi khung cảnh một chút.','Vé máy bay, nơi ở và hành trình mới.'],
        'cong-nghe'=>['Nâng cấp điều thường ngày.','Thiết bị, phụ kiện và lựa chọn phù hợp.'],
        'giai-tri'=>['Dành thời gian để vui.','Một bộ phim, một trải nghiệm mới.'],
        'lam-dep'=>['Chăm mình thêm một chút.','Mỹ phẩm và những bước chăm sóc nhỏ.'],
        'dich-vu'=>['Nhẹ việc, nhẹ cả chi phí.','Di chuyển và dịch vụ cho mỗi ngày.'],
        'thoi-trang'=>['Mặc theo cách của bạn.','Đồ thường ngày, thể thao và phong cách.'],
    ][$key] ?? ['Khám phá điều bạn thích.','Xem các ưu đãi còn hiệu lực.'];
}

function productGuides(): array
{
    return require __DIR__.'/content/guides.php';
}

/** Local photographs only: pinned sources and crop recipe are recorded with the assets. */
function editorialPhoto(string $category, bool $card = true): string
{
    $key = slugify($category);
    $map = ['mua-sam'=>'shopping','an-uong'=>'food','am-thuc'=>'food','du-lich'=>'travel','cong-nghe'=>'technology','lam-dep'=>'beauty','giai-tri'=>'cinema','dich-vu'=>'living','thoi-trang'=>'fashion'];
    $name = $map[$key] ?? (in_array($key,['shopping','food','travel','technology','beauty','cinema','living','fashion'],true) ? $key : 'shopping');
    return url('/assets/images/editorial/'.$name.($card ? '-card' : '').'.webp');
}

function editorialLargePhoto(string $category): string
{
    $normal=editorialPhoto($category,false);
    $name=basename((string)parse_url($normal,PHP_URL_PATH));
    return is_file(dirname(__DIR__).'/assets/images/hero/category-'.$name) ? url('/assets/images/hero/category-'.$name) : $normal;
}

function brandLogo(string $name): string
{
    $key = slugify($name);
    $map = ['shopee'=>'shopee','lazada'=>'lazada','grab'=>'grab','be'=>'be','tiki'=>'tiki','shopeefood'=>'shopeefood','traveloka'=>'traveloka','cgv'=>'cgv','hasaki'=>'hasaki','fpt-shop'=>'fpt-shop','coolmate'=>'coolmate','highlands-coffee'=>'highlands'];
    $file = $map[$key] ?? '';
    if ($file !== '' && is_file(dirname(__DIR__).'/assets/images/editorial/'.$file.'.png')) {
        return '<img class="merchant-logo-image" src="'.e(url('/assets/images/editorial/'.$file.'.png').'?v='.filemtime(dirname(__DIR__).'/assets/images/editorial/'.$file.'.png')).'" alt="" width="40" height="40" loading="lazy" decoding="async">';
    }
    return '<span class="merchant-logo-fallback" aria-hidden="true">'.e(textPrefix($name)).'</span>';
}

function articleTopic(string $title): array
{
    $key = slugify($title);
    foreach (['du-lich'=>['travel','Du lịch'],'an-uong'=>['food','Ẩm thực'],'cong-nghe'=>['technology','Công nghệ'],'mua-sam'=>['shopping','Mua sắm'],'coupon'=>['technology','Hướng dẫn']] as $word=>$topic) {
        if (str_contains($key,$word)) return $topic;
    }
    return ['shopping','Cẩm nang'];
}

/** Campaign dates are stored in UTC. Labels are rendered in the app's Vietnam timezone. */
function campaignTiming(array $campaign): array
{
    $start=(new DateTimeImmutable($campaign['starts_at'],new DateTimeZone('UTC')))->getTimestamp();
    $end=(new DateTimeImmutable($campaign['ends_at'],new DateTimeZone('UTC')))->getTimestamp();
    $now=time();
    $upcoming=$now<$start;
    $remaining=max(0,($upcoming ? $start : $end)-$now);
    return ['start'=>$start*1000,'end'=>$end*1000,'now'=>$now*1000,'upcoming'=>$upcoming,
        'days'=>intdiv($remaining,86400),'hours'=>intdiv($remaining%86400,3600),
        'minutes'=>intdiv($remaining%3600,60),'seconds'=>$remaining%60];
}

function campaignArtwork(array $campaign): array
{
    $key=slugify(($campaign['merchant_name'] ?? '').' '.$campaign['name']);
    if (str_contains($key,'du-lich') || str_contains($key,'travel')) return ['travel','ocean'];
    if (str_contains($key,'shopee') || str_contains($key,'lazada') || str_contains($key,'mua-sam')) return ['fashion','coral'];
    return ['technology','indigo'];
}

function editorialTitle(string $title): string
{
    return trim(str_replace('[DEMO]','',$title));
}


if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        return App::config($key, $default);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) config('app.base_url', ''), '/');
        $path = '/' . ltrim($path, '/');
        return $base . ($path === '/' ? ($base === '' ? '/' : '') : $path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');
        $assetUrl = url('/public' . $normalized);
        $file = dirname(__DIR__) . '/public' . $normalized;

        // Cache-bust local assets after each deploy/hotfix so browsers do not keep
        // an old JavaScript/CSS file while PHP has already changed.
        if (is_file($file)) {
            $assetUrl .= '?v=' . (string) filemtime($file);
        }

        return $assetUrl;
    }
}

if (!function_exists('format_money')) {
    function format_money($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        return number_format((float) $value, 0, ',', '.') . ' ₫';
    }
}

if (!function_exists('format_discount')) {
    function format_discount(array $coupon): string
    {
        $type = (string) ($coupon['discount_type'] ?? '');
        $value = $coupon['discount_value'] ?? null;

        return match ($type) {
            'PERCENTAGE' => $value !== null ? 'Giảm ' . rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') . '%' : 'Giảm theo %',
            'FIXED_AMOUNT' => $value !== null ? 'Giảm ' . format_money($value) : 'Giảm tiền',
            'FREE_SHIPPING' => 'Miễn phí vận chuyển',
            'BUY_X_GET_Y' => 'Mua X tặng Y',
            'GIFT' => 'Quà tặng',
            'CASHBACK' => !array_key_exists('cashback_unit', $coupon)
                ? 'Hoàn tiền'
                : ($value !== null
                    ? (($coupon['cashback_unit'] ?? '') === 'PERCENTAGE'
                        ? 'Hoàn ' . rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') . '%'
                        : 'Hoàn ' . format_money($value))
                    : 'Hoàn tiền'),
            default => 'Ưu đãi',
        };
    }
}

if (!function_exists('format_db_datetime')) {
    function format_db_datetime(?string $value, string $format = 'd/m/Y H:i'): string
    {
        if ($value === null || trim($value) === '') {
            return '—';
        }

        try {
            $utc = new DateTimeZone('UTC');
            $target = new DateTimeZone((string) config('app.timezone', 'Asia/Ho_Chi_Minh'));
            return (new DateTimeImmutable($value, $utc))->setTimezone($target)->format($format);
        } catch (Throwable $e) {
            return $value;
        }
    }
}

if (!function_exists('coupon_status_label')) {
    function coupon_status_label(string $status): string
    {
        return match ($status) {
            'ACTIVE' => 'Đang hoạt động',
            'SCHEDULED' => 'Sắp diễn ra',
            'PENDING' => 'Chờ duyệt',
            'REJECTED' => 'Bị từ chối',
            'EXPIRED' => 'Đã hết hạn',
            'DISABLED' => 'Đã tắt',
            'ARCHIVED' => 'Đã lưu trữ',
            default => $status,
        };
    }
}

if (!function_exists('coupon_status_class')) {
    function coupon_status_class(string $status): string
    {
        return match ($status) {
            'ACTIVE' => 'success',
            'SCHEDULED' => 'info',
            'PENDING' => 'warning',
            'REJECTED', 'DISABLED' => 'danger',
            'EXPIRED', 'ARCHIVED' => 'secondary',
            default => 'secondary',
        };
    }
}

if (!function_exists('category_badge_class')) {
    function category_badge_class(?string $categoryName): string
    {
        $name = mb_strtolower(trim((string) $categoryName));
        return match ($name) {
            'mua sắm' => 'badge-category-shopping',
            'ăn uống' => 'badge-category-food',
            'du lịch' => 'badge-category-travel',
            'công nghệ' => 'badge-category-tech',
            'giải trí' => 'badge-category-entertainment',
            'làm đẹp' => 'badge-category-beauty',
            'dịch vụ' => 'badge-category-service',
            'thời trang' => 'badge-category-fashion',
            default => 'badge-category-default',
        };
    }
}

if (!function_exists('usage_channel_label')) {
    function usage_channel_label(?string $channel): string
    {
        return match (strtoupper(trim((string) $channel))) {
            'ONLINE' => 'Online',
            'OFFLINE' => 'Offline',
            'BOTH' => 'Online + Offline',
            default => 'Không rõ',
        };
    }
}

if (!function_exists('usage_badge_class')) {
    function usage_badge_class(?string $channel): string
    {
        return match (strtoupper(trim((string) $channel))) {
            'ONLINE' => 'badge-usage-online',
            'OFFLINE' => 'badge-usage-offline',
            'BOTH' => 'badge-usage-both',
            default => 'badge-usage-default',
        };
    }
}

if (!function_exists('vote_label')) {
    function vote_label(?string $vote): string
    {
        return match (strtoupper(trim((string) $vote))) {
            'WORKING' => 'Dùng được',
            'NOT_WORKING' => 'Không dùng được',
            'CONDITION_CHANGED' => 'Điều kiện thay đổi',
            default => 'Chưa xác nhận',
        };
    }
}

if (!function_exists('report_reason_label')) {
    function report_reason_label(?string $reason): string
    {
        return match (strtoupper(trim((string) $reason))) {
            'EXPIRED' => 'Coupon đã hết hạn',
            'NOT_WORKING' => 'Coupon không hoạt động',
            'WRONG_TERMS' => 'Sai điều kiện sử dụng',
            'DUPLICATE' => 'Coupon bị trùng',
            'SPAM' => 'Spam',
            'FAKE' => 'Thông tin giả',
            'INAPPROPRIATE' => 'Nội dung không phù hợp',
            'OTHER' => 'Khác',
            default => (string) $reason,
        };
    }
}


if (!function_exists('slugify')) {
    function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $map = [
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
        ];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}

if (!function_exists('campaign_status_label')) {
    function campaign_status_label(string $status): string
    {
        return match (strtoupper($status)) {
            'DRAFT' => 'Bản nháp',
            'PUBLISHED' => 'Đã xuất bản',
            'DISABLED' => 'Đã tắt',
            'ARCHIVED' => 'Đã lưu trữ',
            default => $status,
        };
    }
}

if (!function_exists('campaign_status_class')) {
    function campaign_status_class(string $status): string
    {
        return match (strtoupper($status)) {
            'PUBLISHED' => 'success',
            'DRAFT' => 'secondary',
            'DISABLED' => 'warning',
            'ARCHIVED' => 'dark',
            default => 'secondary',
        };
    }
}

if (!function_exists('merchant_status_label')) {
    function merchant_status_label(string $status): string
    {
        return match (strtoupper($status)) {
            'UNCLAIMED' => 'Chưa có chủ',
            'PENDING' => 'Chờ duyệt',
            'APPROVED' => 'Đã duyệt',
            'REJECTED' => 'Bị từ chối',
            'LOCKED' => 'Đã khóa',
            default => $status,
        };
    }
}

// ---- HÀM HIỂN THỊ CHUNG: không truy vấn database tại đây ----
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(mixed $value): string
{
    return number_format((float) $value, 0, ',', '.') . 'đ';
}

function textLength(string $value): int
{
    if (function_exists('mb_strlen')) return mb_strlen($value);
    if (function_exists('iconv_strlen')) return (int) iconv_strlen($value, 'UTF-8');
    return strlen($value);
}

function textPrefix(string $value, int $length = 2): string
{
    if (function_exists('mb_substr')) return mb_substr($value, 0, $length);
    if (function_exists('iconv_substr')) return (string) iconv_substr($value, 0, $length, 'UTF-8');
    return substr($value, 0, $length);
}

function day(mixed $value): string
{
    return format_db_datetime((string) $value, 'd/m/Y');
}

function dateTimeVi(mixed $value): string
{
    return format_db_datetime((string) $value, 'H:i · d/m/Y');
}

function daysLeft(mixed $value): int
{
    $now = new DateTimeImmutable('today');
    $end = new DateTimeImmutable(format_db_datetime((string) $value, 'Y-m-d'));
    return (int) $now->diff($end)->format('%r%a');
}

function expiryLabel(mixed $value): string
{
    $days = daysLeft($value);
    return match (true) {
        $days < 0 => 'Đã hết hạn',
        $days === 0 => 'Hết hạn hôm nay',
        $days === 1 => 'Còn 1 ngày',
        $days <= 7 => 'Còn ' . $days . ' ngày',
        default => 'Đến ' . day($value),
    };
}

function saving(array $coupon): string
{
    return match ($coupon['discount_type']) {
        'PERCENTAGE' => rtrim(rtrim(number_format((float) $coupon['discount_value'], 2, ',', '.'), '0'), ',') . '%',
        'FIXED_AMOUNT' => money($coupon['discount_value']),
        'FREE_SHIPPING' => 'Miễn phí ship',
        'BUY_X_GET_Y' => 'Mua kèm ưu đãi',
        'GIFT' => 'Quà tặng',
        'CASHBACK' => 'Hoàn tiền',
        default => 'Ưu đãi',
    };
}

function icon(string $name): string
{
    return '<svg class="icon" aria-hidden="true"><use href="' . e(url('/assets/icons.svg') . '?v=' . filemtime(dirname(__DIR__) . '/assets/icons.svg')) . '#i-' . e($name) . '"/></svg>';
}

/** Chỉ cho phép liên kết web, không chứa credentials hoặc scheme thực thi. */
function safeHttpUrl(?string $value): ?string
{
    if ($value === null || filter_var($value, FILTER_VALIDATE_URL) === false) return null;
    $parts = parse_url($value);
    return is_array($parts) && in_array(strtolower($parts['scheme'] ?? ''), ['http','https'], true)
        && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']) ? $value : null;
}

function accountInitials(string $name): string
{
    $words = preg_split('/\s+/u', trim(preg_replace('/\[DEMO\]/iu', '', $name))) ?: [];
    if (!$words || $words[0] === '') return 'CH';
    return mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : ''), 'UTF-8');
}

function statusLabel(string $status): string
{
    return [
        'PENDING' => 'Chờ duyệt',
        'APPROVED' => 'Đã duyệt',
        'REJECTED' => 'Từ chối',
        'ENABLED' => 'Đang bật',
        'DISABLED' => 'Đã tắt',
        'EXPIRED' => 'Hết hạn',
    ][$status] ?? $status;
}

function channelLabel(string $channel): string
{
    return [
        'ONLINE' => 'Online',
        'OFFLINE' => 'Tại cửa hàng',
        'BOTH' => 'Online & tại cửa hàng',
    ][$channel] ?? $channel;
}

function sourceLabel(string $source): string
{
    return [
        'OFFICIAL' => 'Coupon chính thức từ cửa hàng',
        'COMMUNITY' => 'Cộng đồng đóng góp',
    ][$source] ?? $source;
}

function positiveId(string $value, bool $allowZero = false): int
{
    if (!ctype_digit($value) || strlen($value) > 15 || (int) $value < ($allowZero ? 0 : 1)) {
        throw new HttpError(422, 'ID không hợp lệ.');
    }
    return (int) $value;
}
