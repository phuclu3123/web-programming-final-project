<?php
declare(strict_types=1);
namespace App\Services;

use App\Contracts\HttpClientInterface;
use App\Core\HttpError;

/** Fixed Vietnam Publisher endpoints, no arbitrary URL forwarding or browser API key. */
final class AccesstradeService
{
    private const BASE = 'https://api.accesstrade.vn/v1/offers_informations';
    public function __construct(private HttpClientInterface $http, private string $key) {}

    // GET endpoint cố định bằng key server; lỗi nguồn không được biến thành dữ liệu giả.
    public function fetch(string $resource = 'offers', int $page = 1, string $merchant = ''): array
    {
        if ($this->key === '' || strlen($this->key)>1024 || preg_match('/[\x00-\x20]/', $this->key)) throw new HttpError(503, 'Chưa cấu hình ACCESSTRADE_ACCESS_KEY.');
        if ($page<1 || $page>1000 || strlen($merchant)>100 || !preg_match('/\A[a-zA-Z0-9_.-]*\z/D',$merchant)) throw new HttpError(422, 'Trang hoặc mã nhà cung cấp không hợp lệ.');
        $path = match ($resource) {'offers'=>'', 'coupons'=>'/coupon', 'merchants'=>'/merchant_list', 'categories'=>'/list_category_coupons', default=>throw new HttpError(422,'Loại dữ liệu không hợp lệ.')};
        $params = in_array($resource,['offers','coupons'],true) ? ['page'=>$page,'limit'=>50,'status'=>1] : [];
        if ($resource==='coupons') $params=['page'=>$page,'limit'=>50,'is_next_day_coupon'=>'false'];
        if ($merchant!=='' && in_array($resource,['offers','coupons'],true)) $params['merchant']=$merchant;
        try {
            return $this->http->json('GET',self::BASE.$path.($params ? '?'.http_build_query($params) : ''),['Authorization: Token '.$this->key,'Content-Type: application/json']);
        } catch (\Throwable $error) { throw new HttpError(502,'Không lấy được dữ liệu ACCESSTRADE. Kiểm tra quyền API, giới hạn gọi và thử lại.'); }
    }

    // Chuẩn hóa JSON nguồn, giữ điều kiện không biết ở trạng thái thiếu để admin đối chiếu.
    public function normalize(array $response): array
    {
        if (array_key_exists('success',$response) && $response['success']!==true) throw new HttpError(502,'ACCESSTRADE chưa xác nhận lấy dữ liệu thành công.');
        $rows=$response['data'] ?? $response;
        // /offers_informations returns data[], while /coupon returns data.data[].
        if (is_array($rows) && isset($rows['data'])) $rows=$rows['data'];
        if (!is_array($rows) || !array_is_list($rows)) throw new HttpError(502,'Cấu trúc dữ liệu ACCESSTRADE chưa được hỗ trợ. Không nhập dữ liệu.');
        $out=[];$skipped=0;
        foreach ($rows as $row) {
            if (!is_array($row)) {++$skipped;continue;}
            $id=$row['id'] ?? null;
            if ((!is_string($id)&&!is_int($id)) || (string)$id==='' || strlen((string)$id)>191 || !mb_check_encoding((string)$id,'UTF-8')) {++$skipped;continue;}
            $title=self::plain($row['name'] ?? '',200);
            if ($title==='') {++$skipped;continue;}
            $codes=$row['coupons'] ?? [];
            if (is_string($codes)) $codes=[$codes];
            if (!is_array($codes) || !array_is_list($codes)) {++$skipped;continue;}
            if (!$codes) $codes=[null];
            foreach (array_slice($codes,0,100) as $coupon) {
                $details=is_array($coupon) ? $coupon : [];
                $code=is_array($coupon) ? ($coupon['coupon_code'] ?? $coupon['code'] ?? null) : $coupon;
                if ($code!==null && (!is_string($code) || strlen($code)>100 || preg_match('/[\x00-\x1f\x7f]/',$code) || !mb_check_encoding($code,'UTF-8'))) {++$skipped;continue;}
                $code=$code===null ? null : trim($code);
                $url=self::httpsUrl($row['aff_link'] ?? $row['prod_link'] ?? $row['link'] ?? null);
                if (!$code && !$url) {++$skipped;continue;}
                $percent=self::amount($details['discount_percentage'] ?? $row['discount_percentage'] ?? null);
                $amount=self::amount($details['discount_value'] ?? $row['discount_value'] ?? null);
                $type=$percent!==null && $percent>0 && $percent<=100 ? 'PERCENTAGE' : ($amount!==null && $amount>0 ? 'FIXED_AMOUNT' : '');
                $merchant=self::plain($row['merchant'] ?? '',100);
                $data=[
                    'title'=>$title,'code'=>$code,'merchant_ref'=>$merchant,
                    'description'=>self::plain($row['content'] ?? '',12000),
                    'terms'=>self::plain($details['terms'] ?? $row['terms'] ?? '',12000),
                    'discount_type'=>$type,'discount_value'=>$type==='PERCENTAGE' ? $percent : ($type==='FIXED_AMOUNT' ? $amount : null),
                    'min_order_amount'=>self::amount($details['min_order_amount'] ?? $row['min_order_amount'] ?? $row['min_spend'] ?? null),
                    'max_discount_amount'=>self::amount($details['max_discount_amount'] ?? $row['max_discount_amount'] ?? null),
                    'starts_at'=>self::date($details['start_time'] ?? $row['start_time'] ?? $row['start_date'] ?? null,false),
                    'ends_at'=>self::date($details['end_time'] ?? $row['end_time'] ?? $row['end_date'] ?? null,true),
                    'source_url'=>self::httpsUrl($row['link'] ?? null),'promotion_url'=>$url,
                    'image_url'=>self::httpsUrl($row['image'] ?? null),
                ];
                $identity=hash('sha256',json_encode([(string)$id,$code],JSON_THROW_ON_ERROR));
                $out[$identity]=['external_key'=>$identity,'provider_id'=>(string)$id,'code'=>$code,'payload'=>$data];
            }
        }
        return ['offers'=>array_values($out),'skipped'=>$skipped,'received'=>count($rows)];
    }

    private static function plain(mixed $value,int $limit): string
    {
        if (!is_string($value) || !mb_check_encoding($value,'UTF-8')) return '';
        return mb_substr(trim(preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/','',html_entity_decode(strip_tags($value),ENT_QUOTES|ENT_HTML5,'UTF-8'))),0,$limit);
    }
    private static function amount(mixed $value): ?float
    {
        if ((!is_string($value)&&!is_int($value)&&!is_float($value)) || !is_numeric($value)) return null;
        $n=(float)$value;
        return is_finite($n) && $n>=0 && $n<=9999999999999.99 ? $n : null;
    }
    private static function date(mixed $value,bool $end): ?string
    {
        // Documented coupon dates may be GMT strings or Mongo extended JSON milliseconds.
        if (is_array($value) && array_keys($value)===['$date']) {
            $milliseconds=$value['$date'];
            if ((!is_int($milliseconds)&&!is_string($milliseconds)) || !preg_match('/\A[0-9]{1,15}\z/D',(string)$milliseconds) || (int)$milliseconds>253402300799999) return null;
            return gmdate('Y-m-d H:i:s',intdiv((int)$milliseconds,1000));
        }
        if(is_string($value) && preg_match('/\A[A-Z][a-z]{2}, [0-9]{2} [A-Z][a-z]{2} [0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2} GMT\z/D',$value)) {
            $date=\DateTimeImmutable::createFromFormat('!D, d M Y H:i:s \G\M\T',$value,new \DateTimeZone('UTC'));
            return $date && $date->format('D, d M Y H:i:s \G\M\T')===$value ? $date->format('Y-m-d H:i:s') : null;
        }
        if (!is_string($value) || !preg_match('/\A\d{4}-\d\d-\d\d(?:[T ]\d\d:\d\d(?::\d\d)?(?:Z|[+-]\d\d:\d\d)?)?\z/D',$value)) return null;
        try {
            $date=new \DateTimeImmutable($value,new \DateTimeZone('Asia/Ho_Chi_Minh'));
            $errors=\DateTimeImmutable::getLastErrors();
            if (is_array($errors) && ($errors['warning_count'] || $errors['error_count'])) return null;
            if (strlen($value)===10) $date=$date->setTime($end?23:0,$end?59:0,$end?59:0);
            return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (\Throwable) { return null; }
    }
    private static function httpsUrl(mixed $value): ?string
    {
        if (!is_string($value) || strlen($value)>2048 || safeHttpUrl($value)===null || parse_url($value,PHP_URL_SCHEME)!=='https') return null;
        $host=strtolower(parse_url($value,PHP_URL_HOST) ?: '');
        if ($host==='localhost' || !str_contains($host,'.') || filter_var($host,FILTER_VALIDATE_IP)) return null;
        return $value;
    }
}
