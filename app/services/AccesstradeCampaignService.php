<?php
declare(strict_types=1);
namespace App\Services;

use App\Contracts\HttpClientInterface;
use App\Core\HttpError;

// Campaign là chương trình đối tác; hoa hồng publisher không được đổi thành mức giảm coupon.
final class AccesstradeCampaignService
{
    public function __construct(private HttpClientInterface $http, private string $key) {}

    public function fetch(int $page=1): array
    {
        if($this->key==='' || strlen($this->key)>1024 || preg_match('/[\x00-\x20]/',$this->key)) throw new HttpError(503,'Chưa cấu hình khóa ACCESSTRADE hợp lệ.');
        if($page<1 || $page>1000) throw new HttpError(422,'Trang nguồn phải từ 1 đến 1.000.');
        try {
            $response=$this->http->json('GET','https://api.accesstrade.vn/v1/campaigns?'.http_build_query(['limit'=>20,'page'=>$page]),['Authorization: Token '.$this->key,'Content-Type: application/json']);
        } catch(\Throwable) {throw new HttpError(502,'Không lấy được chiến dịch ACCESSTRADE. Vui lòng kiểm tra quyền API và thử lại.');}
        return $this->normalize($response);
    }

    public function normalize(array $response): array
    {
        if((isset($response['success']) && $response['success']!==true) || (isset($response['status']) && in_array($response['status'],[false,'error','failed'],true))) throw new HttpError(502,'Nguồn chưa xác nhận lấy chiến dịch thành công.');
        $rows=$response['data']??null;
        if(!is_array($rows) || !array_is_list($rows)) throw new HttpError(502,'Cấu trúc dữ liệu chiến dịch không hợp lệ.');
        $out=[];$skipped=0;
        foreach($rows as $row) {
            if(!is_array($row)){++$skipped;continue;}
            $id=$row['id']??null;$name=self::plain($row['name']??'',200);
            if((!is_string($id)&&!is_int($id)) || !preg_match('/\A[A-Za-z0-9_-]{1,191}\z/D',(string)$id) || $name===''){++$skipped;continue;}
            $description=$row['description']??'';
            if(is_array($description))$description=$description['introduction']??'';
            $start=self::date($row['start_time']??null);$end=self::date($row['end_time']??null);
            if(($start===null && !empty($row['start_time'])) || ($end===null && !empty($row['end_time'])) || (isset($row['status']) && !is_scalar($row['status']))){++$skipped;continue;}
            if($start!==null && $end!==null && $end<=$start){++$skipped;continue;}
            $out[(string)$id]=['provider_id'=>(string)$id,'payload'=>[
                'name'=>$name,'merchant'=>self::plain($row['merchant']??'',191),'description'=>self::plain($description,2000),
                'url'=>self::https($row['url']??null),'logo'=>self::https($row['logo']??null),
                'approval'=>self::plain($row['approval']??'',32),'running'=>(string)($row['status']??'')==='1',
                'starts_at'=>$start,'ends_at'=>$end,
            ]];
        }
        return ['campaigns'=>array_values($out),'received'=>count($rows),'skipped'=>$skipped];
    }

    private static function plain(mixed $value,int $limit): string
    {
        if(!is_string($value) || !mb_check_encoding($value,'UTF-8'))return '';
        return mb_substr(trim(preg_replace('/[\x00-\x1f\x7f]/',' ',html_entity_decode(strip_tags($value),ENT_QUOTES|ENT_HTML5,'UTF-8'))),0,$limit);
    }
    private static function https(mixed $value): ?string
    {
        if(!is_string($value) || strlen($value)>500 || preg_match('/[\x00-\x20\x7f]/',$value))return null;
        $parts=parse_url($value);
        return filter_var($value,FILTER_VALIDATE_URL) && ($parts['scheme']??'')==='https' && !isset($parts['user']) && !isset($parts['pass']) ? $value:null;
    }
    private static function date(mixed $value): ?string
    {
        if($value===null || $value==='')return null;
        if(!is_string($value) || !preg_match('/\A\d{4}-\d{2}-\d{2}(?:[T ][0-9:.]+)?(?:Z|[+-]\d{2}:?\d{2})?\z/D',$value))return null;
        try {
            $date=new \DateTimeImmutable($value,new \DateTimeZone('UTC'));
            $errors=\DateTimeImmutable::getLastErrors();
            if($errors && ($errors['warning_count'] || $errors['error_count']))return null;
            return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }catch(\Throwable){return null;}
    }
}
