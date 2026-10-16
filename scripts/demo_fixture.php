<?php
declare(strict_types=1);

// ---- DỮ LIỆU THỬ LOCAL: được seed_demo.php nạp; không phải mã phát hành thật ----
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

$offer = static fn (string $code, string $title, string $type, ?int $value, int $minimum, ?int $maximum = null, string $channel = 'ONLINE', int $days = 90): array => [
    'code'=>$code, 'title'=>$title, 'discount_type'=>$type, 'discount_value'=>$value,
    'min_order_amount'=>$minimum, 'max_discount_amount'=>$maximum, 'usage_channel'=>$channel, 'days'=>$days,
];

return [
    'accounts'=>[
        'admin_demo'=>['email'=>'admin.demo@couponhub.test','name'=>'Quản trị CouponHub [DEMO]','role'=>'ADMIN','password'=>'AdminDemo!2026','phone'=>'0900000001'],
        'merchant_shops'=>['email'=>'merchant.shops@couponhub.test','name'=>'Quản lý sàn thương mại [DEMO]','role'=>'MERCHANT','password'=>'MerchantDemo!2026','phone'=>'0900000002'],
        'merchant_services'=>['email'=>'merchant.services@couponhub.test','name'=>'Quản lý di chuyển và ăn uống [DEMO]','role'=>'MERCHANT','password'=>'MerchantDemo!2026','phone'=>'0900000003'],
        'merchant_lifestyle'=>['email'=>'merchant.lifestyle@couponhub.test','name'=>'Quản lý du lịch và phong cách [DEMO]','role'=>'MERCHANT','password'=>'MerchantDemo!2026','phone'=>'0900000004'],
        'user_demo'=>['email'=>'user.demo@couponhub.test','name'=>'Người dùng trải nghiệm [DEMO]','role'=>'USER','password'=>'UserDemo!2026','phone'=>'0900000005'],
        'user_community'=>['email'=>'user.community@couponhub.test','name'=>'Thành viên cộng đồng [DEMO]','role'=>'USER','password'=>'UserDemo!2026','phone'=>'0900000006'],
    ],
    'categories'=>[
        'mua-sam'=>'Mua sắm', 'an-uong'=>'Ăn uống', 'du-lich'=>'Du lịch', 'cong-nghe'=>'Công nghệ',
        'giai-tri'=>'Giải trí', 'lam-dep'=>'Làm đẹp', 'dich-vu'=>'Dịch vụ', 'thoi-trang'=>'Thời trang',
    ],
    'merchants'=>[
        'shopee'=>['name'=>'Shopee','owner'=>'merchant_shops','category'=>'mua-sam','offers'=>[
            $offer('DEMO_SHOPEE_20','Shopee giảm 20%, tối đa 60.000đ','PERCENTAGE',20,200000,60000,'ONLINE',3),
            $offer('DEMO_SHOPEE_30K','Shopee giảm 30.000đ cho đơn từ 150.000đ','FIXED_AMOUNT',30000,150000),
        ]],
        'lazada'=>['name'=>'Lazada','owner'=>'merchant_shops','category'=>'mua-sam','offers'=>[
            $offer('DEMO_LAZADA_50K','Lazada giảm 50.000đ cho đơn từ 300.000đ','FIXED_AMOUNT',50000,300000),
            $offer('DEMO_LAZADA_SHIP','Lazada miễn phí vận chuyển từ 99.000đ','FREE_SHIPPING',null,99000),
        ]],
        'grab'=>['name'=>'Grab','owner'=>'merchant_services','category'=>'dich-vu','offers'=>[
            $offer('DEMO_GRAB_25','Grab giảm 25%, tối đa 30.000đ','PERCENTAGE',25,50000,30000,'ONLINE',5),
            $offer('DEMO_GRAB_20K','Grab giảm 20.000đ cho chuyến từ 80.000đ','FIXED_AMOUNT',20000,80000),
        ]],
        'be'=>['name'=>'be','owner'=>'merchant_services','category'=>'dich-vu','offers'=>[
            $offer('DEMO_BE_15K','be giảm 15.000đ cho chuyến từ 50.000đ','FIXED_AMOUNT',15000,50000),
            $offer('DEMO_BE_20','be giảm 20%, tối đa 25.000đ','PERCENTAGE',20,60000,25000),
        ]],
        'tiki'=>['name'=>'Tiki','owner'=>'merchant_shops','category'=>'mua-sam','offers'=>[
            $offer('DEMO_TIKI_40K','Tiki giảm 40.000đ cho đơn từ 250.000đ','FIXED_AMOUNT',40000,250000),
            $offer('DEMO_TIKI_15','Tiki giảm 15%, tối đa 100.000đ','PERCENTAGE',15,500000,100000),
        ]],
        'shopeefood'=>['name'=>'ShopeeFood','owner'=>'merchant_services','category'=>'an-uong','offers'=>[
            $offer('DEMO_FOOD_30','ShopeeFood giảm 30%, tối đa 40.000đ','PERCENTAGE',30,100000,40000),
            $offer('DEMO_FOOD_SHIP','ShopeeFood miễn phí vận chuyển từ 70.000đ','FREE_SHIPPING',null,70000),
        ]],
        'traveloka'=>['name'=>'Traveloka','owner'=>'merchant_lifestyle','category'=>'du-lich','offers'=>[
            $offer('DEMO_TRAVEL_10','Traveloka giảm 10%, tối đa 200.000đ','PERCENTAGE',10,1000000,200000),
            $offer('DEMO_TRAVEL_150K','Traveloka giảm 150.000đ cho đặt phòng từ 1,5 triệu','FIXED_AMOUNT',150000,1500000),
        ]],
        'cgv'=>['name'=>'CGV','owner'=>'merchant_lifestyle','category'=>'giai-tri','offers'=>[
            $offer('DEMO_CGV_25K','CGV giảm 25.000đ cho giao dịch từ 100.000đ','FIXED_AMOUNT',25000,100000,null,'BOTH'),
            $offer('DEMO_CGV_COMBO','CGV mua hai vé, tặng một phần bắp mẫu','BUY_X_GET_Y',null,120000,null,'OFFLINE'),
        ]],
        'hasaki'=>['name'=>'Hasaki','owner'=>'merchant_lifestyle','category'=>'lam-dep','offers'=>[
            $offer('DEMO_HASAKI_15','Hasaki giảm 15%, tối đa 80.000đ','PERCENTAGE',15,300000,80000,'BOTH'),
            $offer('DEMO_HASAKI_GIFT','Hasaki tặng bộ quà mẫu cho đơn từ 500.000đ','GIFT',null,500000,null,'BOTH'),
        ]],
        'fpt-shop'=>['name'=>'FPT Shop','owner'=>'merchant_lifestyle','category'=>'cong-nghe','offers'=>[
            $offer('DEMO_FPT_5','FPT Shop giảm 5%, tối đa 300.000đ','PERCENTAGE',5,3000000,300000,'BOTH'),
            $offer('DEMO_FPT_CASHBACK','FPT Shop hoàn 3%, tối đa 150.000đ','CASHBACK',3,1000000,150000,'ONLINE'),
        ]],
        'coolmate'=>['name'=>'Coolmate','owner'=>'merchant_shops','category'=>'thoi-trang','offers'=>[
            $offer('DEMO_COOLMATE_20','Coolmate giảm 20%, tối đa 100.000đ','PERCENTAGE',20,300000,100000),
            $offer('DEMO_COOLMATE_40K','Coolmate giảm 40.000đ cho đơn từ 250.000đ','FIXED_AMOUNT',40000,250000),
        ]],
        'highlands'=>['name'=>'Highlands Coffee','owner'=>'merchant_services','category'=>'an-uong','offers'=>[
            $offer('DEMO_HIGHLANDS_15','Highlands giảm 15%, tối đa 20.000đ','PERCENTAGE',15,80000,20000,'OFFLINE'),
            $offer('DEMO_HIGHLANDS_2GET1','Highlands mua hai, tặng một đồ uống mẫu','BUY_X_GET_Y',null,100000,null,'BOTH'),
        ]],
    ],
    // Các nhánh nghiệp vụ bổ sung, không tạo bản sao đè lên mã đang hoạt động.
    'scenarios'=>[
        ['platform'=>'shopee','code'=>'DEMO_COMMUNITY_SHOPEE','title'=>'Cộng đồng chia sẻ mã Shopee giảm 10%','author'=>'user_demo','source'=>'COMMUNITY','state'=>'ACTIVE'],
        ['platform'=>'lazada','code'=>'DEMO_COMMUNITY_LAZADA','title'=>'Cộng đồng chia sẻ mã Lazada giảm 10%','author'=>'user_community','source'=>'COMMUNITY','state'=>'ACTIVE'],
        ['platform'=>'shopee','code'=>'DEMO_SHOPEE_PENDING','title'=>'Shopee giảm 10% đang chờ Admin duyệt','source'=>'OFFICIAL','state'=>'PENDING'],
        ['platform'=>'grab','code'=>'DEMO_GRAB_PENDING','title'=>'Grab giảm 10% đang chờ Admin duyệt','source'=>'OFFICIAL','state'=>'PENDING'],
        ['platform'=>'be','code'=>'DEMO_BE_REJECTED','title'=>'be giảm 10% đã bị từ chối để kiểm thử','source'=>'OFFICIAL','state'=>'REJECTED'],
        ['platform'=>'traveloka','code'=>'DEMO_TRAVEL_SCHEDULED','title'=>'Traveloka giảm 10% có hiệu lực sau bảy ngày','source'=>'OFFICIAL','state'=>'SCHEDULED'],
        ['platform'=>'cgv','code'=>'DEMO_CGV_EXPIRED','title'=>'CGV giảm 10% đã hết hạn để kiểm thử','source'=>'OFFICIAL','state'=>'EXPIRED'],
        ['platform'=>'hasaki','code'=>'DEMO_HASAKI_DISABLED','title'=>'Hasaki giảm 10% đang tạm tắt','source'=>'OFFICIAL','state'=>'DISABLED'],
    ],
];
