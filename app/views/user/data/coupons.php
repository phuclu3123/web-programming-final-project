<?php

/**
 * Danh sách tất cả Danh mục hệ thống
 * TOP 3 Danh mục (Thời trang, Ăn uống, Làm đẹp) được đánh dấu is_featured = true để hiển thị trên Trang chủ
 */
function allCategories(): array
{
    return [
        [
            'id' => 'thoi-trang',
            'number' => '01',
            'name' => 'Thời trang',
            'subtitle' => 'Mặc chất riêng. Săn giá hời.',
            'image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1200&q=85',
            'is_featured' => true,
            'order' => 1
        ],
        [
            'id' => 'an-uong',
            'number' => '02',
            'name' => 'Ăn uống',
            'subtitle' => 'Ăn ngon, bớt tiền.',
            'image' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=900&q=85',
            'is_featured' => true,
            'order' => 2
        ],
        [
            'id' => 'lam-dep',
            'number' => '03',
            'name' => 'Làm đẹp',
            'subtitle' => 'Yêu bản thân theo cách tiết kiệm.',
            'image' => 'https://images.unsplash.com/photo-1601049541289-9b1b7bbbfe19?auto=format&fit=crop&w=900&q=85',
            'is_featured' => true,
            'order' => 3
        ],
        [
            'id' => 'cong-nghe',
            'number' => '04',
            'name' => 'Công nghệ & Phần mềm',
            'subtitle' => 'Nâng cấp trải nghiệm, tối ưu chi phí.',
            'image' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=900&q=85',
            'is_featured' => false,
            'order' => 4
        ],
        [
            'id' => 'du-lich',
            'number' => '05',
            'name' => 'Di chuyển & Du lịch',
            'subtitle' => 'Đi nhiều hơn, chi ít hơn.',
            'image' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=900&q=85',
            'is_featured' => false,
            'order' => 5
        ],
        [
            'id' => 'dich-vu-khac',
            'number' => '06',
            'name' => 'Dịch vụ khác',
            'subtitle' => 'Làm mới không gian sống & giải trí.',
            'image' => 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=900&q=85',
            'is_featured' => false,
            'order' => 6
        ]
    ];
}

/**
 * Lấy duy nhất TOP 3 Danh mục Nổi bật cho Trang chủ
 */
function featuredCategories(): array
{
    $all = allCategories();
    $featured = array_filter($all, function ($cat) {
        return !empty($cat['is_featured']);
    });

    usort($featured, function ($a, $b) {
        return $a['order'] <=> $b['order'];
    });

    return array_slice($featured, 0, 3);
}

/**
 * Lấy danh sách Tên danh mục phục vụ bộ lọc
 */
function couponCategories(): array
{
    return array_column(allCategories(), 'name');
}

/**
 * Logo các thương hiệu lớn
 */
function storeLogos(): array
{
    return [
        'Shopee' => 'https://cdn.simpleicons.org/shopee/EE4D2D',
        'Lazada' => 'https://cdn.simpleicons.org/lazada/0F146D',
        'Tiki' => 'https://cdn.simpleicons.org/tiki/1A94FF',
        'GrabFood' => 'https://cdn.simpleicons.org/grab/00B14F',
        'Spotify' => 'https://cdn.simpleicons.org/spotify/1ED760',
        'Traveloka' => 'https://cdn.simpleicons.org/traveloka/0194F3',
        'Agoda' => 'https://cdn.simpleicons.org/agoda/30386B'
    ];
}

/**
 * Bộ dữ liệu Coupon phong phú đầy đủ tất cả danh mục
 */
function allCoupons(): array
{
    return [
        // --- 1. THỜI TRANG ---
        'SHOPEE30' => [
            'store' => 'Shopee',
            'category' => 'Thời trang',
            'discount' => 'GIẢM 30%',
            'title' => 'Giảm 30% cho đơn hàng thời trang nam nữ',
            'code' => 'SHOPEE30',
            'expiry' => '2026-12-31',
            'description' => 'Áp dụng cho các sản phẩm thời trang, giày dép, phụ kiện thuộc gian hàng Shopee Mall.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Giảm tối đa 50.000đ cho đơn từ 150.000đ.',
                'Áp dụng khi thanh toán qua ShopeePay hoặc Ví SPayLater.'
            ]
        ],
        'LAZADA100' => [
            'store' => 'Lazada',
            'category' => 'Thời trang',
            'discount' => 'GIẢM 100K',
            'title' => 'Voucher 100.000đ thời trang thương hiệu LazMall',
            'code' => 'LAZADA100',
            'expiry' => '2026-11-30',
            'description' => 'Ưu đãi dành riêng cho bộ sưu tập áo quần, túi xách cao cấp.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Đơn hàng tối thiểu từ 500.000đ.',
                'Không áp dụng đồng thời với mã giảm giá vận chuyển.'
            ]
        ],
        'UNIQLO15' => [
            'store' => 'Uniqlo',
            'category' => 'Thời trang',
            'discount' => 'GIẢM 15%',
            'title' => 'Giảm 15% cho thành viên mới đặt mua online',
            'code' => 'UNIQLO15',
            'expiry' => '2026-10-25',
            'description' => 'Áp dụng cho tất cả các mặt hàng nguyên giá tại website/app Uniqlo.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Chỉ áp dụng cho tài khoản đăng ký lần đầu.',
                'Áp dụng cho 1 đơn hàng duy nhất.'
            ]
        ],

        // --- 2. ĂN UỐNG ---
        'COFFEE20' => [
            'store' => 'Highlands Coffee',
            'category' => 'Ăn uống',
            'discount' => 'GIẢM 20%',
            'title' => 'Ưu đãi 20% PhinDi & Trà Sen Vàng',
            'code' => 'COFFEE20',
            'expiry' => '2026-10-20',
            'description' => 'Thưởng thức cà phê và trà đậm đà vị Highlands với mức giá tiết kiệm.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Áp dụng cho đồ uống size M và L.',
                'Khung giờ áp dụng: 13:00 - 18:00 các ngày trong tuần.'
            ]
        ],
        'GRABFOOD50' => [
            'store' => 'GrabFood',
            'category' => 'Ăn uống',
            'discount' => 'GIẢM 50K',
            'title' => 'Giảm 50.000đ đặt món ngon Quán Obathan',
            'code' => 'GRABFOOD50',
            'expiry' => '2026-11-10',
            'description' => 'Thỏa thích đặt đồ ăn, trà sữa giao tận nơi siêu tốc.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Đơn hàng từ 120.000đ.',
                'Số lượng mã có hạn theo từng khung giờ trong ngày.'
            ]
        ],
        'SHOPEEFOOD30' => [
            'store' => 'ShopeeFood',
            'category' => 'Ăn uống',
            'discount' => 'GIẢM 30K',
            'title' => 'Mã giảm 30K khao bạn thân ăn trưa',
            'code' => 'SHOPEEFOOD30',
            'expiry' => '2026-12-15',
            'description' => 'Áp dụng cho danh sách các quán ăn đối tác yêu thích.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Đơn từ 80.000đ trở lên.',
                'Chỉ áp dụng cho hình thức Giao tận nơi.'
            ]
        ],

        // --- 3. LÀM ĐẸP ---
        'TIKI50' => [
            'store' => 'Tiki',
            'category' => 'Làm đẹp',
            'discount' => 'GIẢM 50K',
            'title' => 'Giảm 50.000đ mỹ phẩm & Skincare chính hãng',
            'code' => 'TIKI50',
            'expiry' => '2026-12-20',
            'description' => 'Săm son môi, kem chống nắng, serum dưỡng da giao nhanh TikiNOW.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Đơn hàng từ 250.000đ.',
                'Áp dụng cho gian hàng chính hãng TikiTrading.'
            ]
        ],
        'HASAKI10' => [
            'store' => 'Hasaki',
            'category' => 'Làm đẹp',
            'discount' => 'GIẢM 10%',
            'title' => 'Giảm 10% tối đa 100K cho phụ kiện trang điểm',
            'code' => 'HASAKI10',
            'expiry' => '2026-11-05',
            'description' => 'Áp dụng tại hệ thống cửa hàng và đặt hàng qua website Hasaki.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Đơn hàng tối thiểu từ 200.000đ.',
                'Không áp dụng với các sản phẩm đang xả kho.'
            ]
        ],

        // --- 4. CÔNG NGHỆ & PHẦN MỀM ---
        'FPT1000K' => [
            'store' => 'FPT Shop',
            'category' => 'Công nghệ & Phần mềm',
            'discount' => 'GIẢM 1TR',
            'title' => 'Giảm ngay 1.000.000đ khi mua Laptop Học sinh - Sinh viên',
            'code' => 'FPT1000K',
            'expiry' => '2026-11-30',
            'description' => 'Sắm laptop văn phòng, đồ họa, gaming với ưu đãi cực hời.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Áp dụng cho dòng Laptop trên 12.000.000đ.',
                'Yêu cầu xác minh thẻ học sinh / sinh viên còn hiệu lực.'
            ]
        ],
        'CANVA50' => [
            'store' => 'Canva',
            'category' => 'Công nghệ & Phần mềm',
            'discount' => 'GIẢM 50%',
            'title' => 'Ưu đãi 50% gói nâng cấp Canva Pro 1 năm',
            'code' => 'CANVA50',
            'expiry' => '2026-12-31',
            'description' => 'Mở khóa toàn bộ mẫu thiết kế, hình ảnh premium và công cụ AI.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Áp dụng khi đăng ký gói Canva Pro theo năm.',
                'Thanh toán qua thẻ Visa/Mastercard hoặc ZaloPay.'
            ]
        ],
        'SPOTIFY50' => [
            'store' => 'Spotify',
            'category' => 'Công nghệ & Phần mềm',
            'discount' => 'GIẢM 50%',
            'title' => 'Giảm 50% gói Spotify Premium cho Sinh viên',
            'code' => 'SPOTIFY50',
            'expiry' => '2026-12-10',
            'description' => 'Nghe nhạc không quảng cáo, tải nhạc ngoại tuyến chất lượng cao.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Cần xác minh danh tính qua hệ thống SheerID.',
                'Gia hạn tự động hàng tháng.'
            ]
        ],

        // --- 5. DI CHUYỂN & DU LỊCH ---
        'TRAVELOKA300' => [
            'store' => 'Traveloka',
            'category' => 'Di chuyển & Du lịch',
            'discount' => 'GIẢM 300K',
            'title' => 'Giảm 300.000đ đặt vé máy bay nội địa & quốc tế',
            'code' => 'TRAVELOKA300',
            'expiry' => '2026-11-20',
            'description' => 'Sẵn sàng cho chuyến du lịch cùng vé máy bay giá cực nét.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Đơn đặt vé từ 2.000.000đ.',
                'Áp dụng cho các hãng hàng không Vietnam Airlines, Vietjet, Bamboo.'
            ]
        ],
        'AGODA200' => [
            'store' => 'Agoda',
            'category' => 'Di chuyển & Du lịch',
            'discount' => 'GIẢM 200K',
            'title' => 'Giảm 200.000đ khi đặt phòng khách sạn, Resort 4*',
            'code' => 'AGODA200',
            'expiry' => '2026-12-15',
            'description' => 'Tận hưởng kỳ nghỉ dưỡng tuyệt vời tại Đà Nẵng, Nha Trang, Phú Quốc.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Áp dụng cho đơn đặt phòng từ 1.500.000đ.',
                'Thanh toán trực tiếp trực tuyến qua app Agoda.'
            ]
        ],
        'BEFLIGHT50' => [
            'store' => 'Be',
            'category' => 'Di chuyển & Du lịch',
            'discount' => 'GIẢM 50%',
            'title' => 'Giảm 50% di chuyển BeCar ra sân bay',
            'code' => 'BEFLIGHT50',
            'expiry' => '2026-10-31',
            'description' => 'Đi sân bay Tân Sơn Nhất / Nội Bài an toàn, tiện lợi.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Giảm tối đa 60.000đ.',
                'Điểm đi hoặc điểm đến là khu vực Sân bay.'
            ]
        ],

        // --- 6. DỊCH VỤ KHÁC ---
        'CLEAN20' => [
            'store' => 'JupViec',
            'category' => 'Dịch vụ khác',
            'discount' => 'GIẢM 20%',
            'title' => 'Giảm 20% dịch vụ giúp việc theo giờ & vệ sinh máy lạnh',
            'code' => 'CLEAN20',
            'expiry' => '2026-11-15',
            'description' => 'Thảnh thơi tận hưởng không gian sống sạch sẽ, thơm tho.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Áp dụng cho ca làm việc từ 3 giờ trở lên.',
                'Áp dụng tại TP.HCM và Hà Nội.'
            ]
        ],
        'UNICA40' => [
            'store' => 'Unica',
            'category' => 'Dịch vụ khác',
            'discount' => 'GIẢM 40%',
            'title' => 'Ưu đãi 40% toàn bộ khóa học Online kỹ năng & Marketing',
            'code' => 'UNICA40',
            'expiry' => '2026-12-25',
            'description' => 'Học mọi lúc mọi nơi với hàng ngàn khóa học chất lượng.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Nhập mã trực tiếp tại bước thanh toán khóa học.',
                'Sở hữu khóa học trọn đời.'
            ]
        ],
        'CGV50K' => [
            'store' => 'CGV Cinemas',
            'category' => 'Dịch vụ khác',
            'discount' => 'GIẢM 50K',
            'title' => 'Đồng giá 50.000đ/vé xem phim rạp CGV ngày Thứ 4',
            'code' => 'CGV50K',
            'expiry' => '2026-12-31',
            'description' => 'Trải nghiệm những bộ phim bom tấn mới nhất trên màn ảnh rộng.',
            'approval_status' => 'APPROVED',
            'availability_status' => 'ACTIVE',
            'conditions' => [
                'Áp dụng cho vé 2D ghế Standard/VIP.',
                'Mỗi thành viên mua tối đa 2 vé/ngày.'
            ]
        ]
    ];
}

function couponStatus(array $coupon): string
{
    if (($coupon['approval_status'] ?? '') !== 'APPROVED' || ($coupon['availability_status'] ?? '') !== 'ACTIVE') {
        return 'Không khả dụng';
    }

    $expiry = strtotime($coupon['expiry'] ?? '');
    if ($expiry === false || $expiry < strtotime(date('Y-m-d'))) {
        return 'Hết hạn';
    }

    $daysLeft = floor(($expiry - strtotime(date('Y-m-d'))) / 86400);
    return ($daysLeft <= 7) ? 'Sắp hết hạn' : 'Còn hạn';
}

function publicCoupons(): array
{
    $coupons = allCoupons();
    $result = [];
    foreach ($coupons as $code => $coupon) {
        if ($coupon['approval_status'] === 'APPROVED' && $coupon['availability_status'] === 'ACTIVE') {
            $coupon['status'] = couponStatus($coupon);
            $result[$code] = $coupon;
        }
    }
    return $result;
}

function findCoupon(string $code): ?array
{
    $coupons = publicCoupons();
    return $coupons[strtoupper(trim($code))] ?? null;
}

function couponMatches(array $coupon, string $query): bool
{
    if ($query === '') return true;
    $fields = [$coupon['store'], $coupon['category'], $coupon['discount'], $coupon['title'], $coupon['code'], $coupon['description']];
    foreach ($fields as $field) {
        if (mb_stripos($field, $query, 0, 'UTF-8') !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Danh sách tất cả các Chiến dịch khuyến mãi
 */
function allCampaigns(): array
{
    return [
        'sale-hang-thang' => [
            'id' => 'sale-hang-thang',
            'title' => 'Siêu Sale Giữa Tháng - Giảm 50%',
            'description' => 'Hàng ngàn mã giảm giá độc quyền từ Shopee, Lazada, Tiki đang diễn ra.',
            'badge' => '🔥 Đang diễn ra',
            'start_date' => '10/10/2026',
            'end_date' => '20/10/2026',
            'banner' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=1200&q=80',
            'status' => 'active',
            'coupons' => ['SHOPEE30', 'LAZADA100', 'UNIQLO15', 'TIKI50'] // Mã thuộc chiến dịch này
        ],
        'food-fest' => [
            'id' => 'food-fest',
            'title' => 'Lễ Hội Ẩm Thực - Đồng Giá 1K',
            'description' => 'Tập hợp mã freeship và giảm 20% - 50% cho Highlands, GrabFood, ShopeeFood.',
            'badge' => 'Sắp kết thúc',
            'start_date' => '01/10/2026',
            'end_date' => '16/10/2026',
            'banner' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1200&q=80',
            'status' => 'expiring',
            'coupons' => ['COFFEE20', 'GRABFOOD50', 'SHOPEEFOOD30'] // Chỉ hiện mã ăn uống
        ],
        'tech-week' => [
            'id' => 'tech-week',
            'title' => 'Tuần Lễ Công Nghệ & Phần Mềm',
            'description' => 'Săn voucher giảm giá mua laptop, phụ kiện và phần mềm bản quyền.',
            'badge' => 'Sắp diễn ra',
            'start_date' => '01/11/2026',
            'end_date' => '10/11/2026',
            'banner' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1200&q=80',
            'status' => 'upcoming',
            'coupons' => ['FPT1000K', 'CANVA50', 'SPOTIFY50'] // Chỉ hiện mã công nghệ
        ]
    ];
}

function findCampaign(string $id): ?array
{
    $campaigns = allCampaigns();
    return $campaigns[$id] ?? null;
}

/**
 * Lấy ra danh sách Coupon thuộc về 1 chiến dịch cụ thể
 */
function getCampaignCoupons(string $campaignId): array
{
    $campaign = findCampaign($campaignId);
    if (!$campaign) return [];

    $allPublic = publicCoupons();
    $result = [];
    foreach ($campaign['coupons'] as $code) {
        if (isset($allPublic[$code])) {
            $result[$code] = $allPublic[$code];
        }
    }
    return $result;
}