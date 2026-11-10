<?php
declare(strict_types=1);
namespace App\Core;

// Giữ hợp đồng request phẳng; danh sách chọn được kiểm tra riêng sau khi giải mã JSON.
final class BulkSelection
{
    public static function parse(string $json): array
    {
        if (strlen($json)>20000) throw new HttpError(422,'Mỗi lượt chọn tối đa 100 mục.');
        try {$rows=json_decode($json,true,8,JSON_THROW_ON_ERROR);} catch (\JsonException) {throw new HttpError(422,'Danh sách chọn không hợp lệ.');}
        if (!is_array($rows) || !array_is_list($rows) || count($rows)<1 || count($rows)>100) throw new HttpError(422,'Chọn từ 1 đến 100 mục để xử lý.');
        $seen=[];
        foreach($rows as $row) {
            if (!is_array($row) || !is_int($row['id']??null) || $row['id']<1 || isset($seen[$row['id']])
                || !is_string($row['fingerprint']??null) || !preg_match('/\A[a-f0-9]{64}\z/D',$row['fingerprint'])) throw new HttpError(422,'Danh sách chọn bị trùng hoặc thiếu phiên bản dữ liệu.');
            $seen[$row['id']]=true;
        }
        return $rows;
    }

    public static function couponFingerprint(array $row): string
    {
        $values=[];
        foreach(['id','merchant_id','category_id','source','title','code','description','terms','discount_type','discount_value','cashback_unit',
            'min_order_amount','max_discount_amount','usage_channel','promotion_url','starts_at','ends_at','moderation_status','publication_status','deleted_at','updated_at'] as $field) $values[$field]=isset($row[$field])?(string)$row[$field]:null;
        return hash('sha256',json_encode($values,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE));
    }
}
