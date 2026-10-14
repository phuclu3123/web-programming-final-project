<?php
$pages = [
    'about' => [
        'lead' => 'CouponHub giúp cộng đồng khám phá, chia sẻ và kiểm chứng các ưu đãi từ nhiều Merchant.',
        'sections' => [
            ['Coupon cộng đồng', 'Người dùng chia sẻ ưu đãi; nội dung được quản trị viên xem xét trước khi công khai.'],
            ['Merchant Center', 'Người dùng có quyền quản lý Shop đã được phê duyệt có thể quản lý ưu đãi Official và Campaign cho Shop đó.'],
            ['CouponPoint', 'Điểm thưởng và xếp hạng dựa trên các quy tắc của hệ thống; một số lượt lưu và xác nhận không được tính khi có xung đột lợi ích.'],
        ],
    ],
    'terms' => [
        'lead' => 'Thông tin sử dụng nền tảng CouponHub (nội dung dự thảo cho dự án học tập).',
        'sections' => [
            ['Tài khoản', 'Người dùng chịu trách nhiệm bảo mật thông tin đăng nhập và sử dụng tài khoản đúng mục đích.'],
            ['Nội dung ưu đãi', 'Nội dung coupon do cộng đồng đóng góp cần được kiểm tra điều kiện, thời hạn và mức độ chính xác trước khi đăng. Việc được công khai không bảo đảm Merchant chấp nhận mã trong mọi tình huống.'],
            ['Quyền quản lý Merchant', 'Việc gửi yêu cầu quản lý Shop không tự động cấp quyền; Admin có quyền kiểm tra, phê duyệt, từ chối hoặc thu hồi quyền.'],
            ['Xử lý vi phạm', 'Hệ thống có thể từ chối, ẩn nội dung hoặc khóa tài khoản vi phạm quy tắc cộng đồng, tùy theo chính sách quản trị.'],
        ],
    ],
    'privacy' => [
        'lead' => 'Mô tả ở mức tổng quan dữ liệu được dùng trong bản demo CouponHub; chưa phải tài liệu pháp lý chính thức.',
        'sections' => [
            ['Dữ liệu tài khoản', 'Hệ thống lưu các thông tin người dùng cung cấp khi đăng ký như họ tên, email và mật khẩu ở dạng băm để phục vụ xác thực.'],
            ['Dữ liệu hoạt động', 'Coupon được đăng, bình luận, lượt lưu, lượt xác nhận và lịch sử điểm có thể được lưu để vận hành chức năng và phòng chống gian lận.'],
            ['Quyền truy cập', 'Một số dữ liệu phục vụ kiểm duyệt chỉ được hiển thị cho vai trò quản trị hoặc người có quyền phù hợp. Không công khai mã đặt lại mật khẩu và thông tin đăng nhập.'],
            ['Lưu ý', 'Không đăng tải hồ sơ cá nhân, chứng từ nhạy cảm hay thông tin thanh toán trong phần nội dung công khai hoặc bằng chứng đăng ký Shop.'],
        ],
    ],
    'community' => [
        'lead' => 'Cùng xây dựng nguồn coupon hữu ích, rõ ràng và đáng tin cậy.',
        'sections' => [
            ['Đăng coupon', 'Chỉ đăng thông tin đúng với ưu đãi đã kiểm tra; mô tả rõ mã, điều kiện, mức giảm và thời gian hiệu lực.'],
            ['Xác nhận hiệu lực', 'Chỉ bình chọn “Dùng được”, “Không dùng được” hoặc “Điều kiện thay đổi” khi có căn cứ từ trải nghiệm thực tế.'],
            ['Bình luận và báo cáo', 'Không spam, xúc phạm, giả mạo thương hiệu hoặc chia sẻ dữ liệu cá nhân nhạy cảm. Dùng chức năng báo cáo khi phát hiện thông tin sai.'],
            ['Minh bạch', 'Người đăng và người đang quản lý Merchant liên quan không được tự tạo xác nhận để tăng Trust hoặc CouponPoint.'],
        ],
    ],
    'contact' => [
        'lead' => 'Thông tin liên hệ cho người sử dụng bản demo CouponHub.',
        'sections' => [
            ['Báo lỗi hoặc phản hồi', 'Bạn có thể chuẩn bị ảnh chụp màn hình, URL trang phát sinh lỗi và bước tái hiện để gửi cho nhóm phát triển theo kênh liên lạc của lớp/dự án.'],
            ['Báo cáo coupon', 'Để phản ánh một coupon cụ thể, sử dụng nút “Báo cáo” ở trang chi tiết coupon.'],
        ],
    ],
];
$page = $pages[$infoType] ?? $pages['about'];
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <nav aria-label="Breadcrumb" class="mb-3">
            <ol class="breadcrumb bg-transparent px-0 py-1 mb-0">
                <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>">Trang chủ</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($pageTitle) ?></li>
            </ol>
        </nav>
        <article class="card shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-3"><?= e($pageTitle) ?></h1>
                <p class="lead text-muted"><?= e($page['lead']) ?></p>
                <?php foreach ($page['sections'] as [$heading, $text]): ?>
                    <section class="mt-4">
                        <h2 class="h5"><?= e($heading) ?></h2>
                        <p class="mb-0"><?= e($text) ?></p>
                    </section>
                <?php endforeach; ?>
                <?php if ($infoType === 'contact'): ?>
                    <?php if ($supportEmail !== '' && filter_var($supportEmail, FILTER_VALIDATE_EMAIL)): ?>
                        <p class="mt-4"><strong>Email hỗ trợ:</strong> <a
                                href="mailto:<?= e($supportEmail) ?>"><?= e($supportEmail) ?></a></p>
                    <?php else: ?>
                        <p class="small text-muted mt-4">Email hỗ trợ chưa được cấu hình. Chủ dự án có thể bổ sung
                            <code>support_email</code> trong <code>config/app.php</code>.
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
                <hr class="mt-4">
                <p class="small text-muted mb-0">CouponHub là dự án phục vụ học tập. Nội dung chính sách này là bản dự
                    thảo và cần được rà soát trước khi triển khai thực tế.</p>
            </div>
        </article>
    </div>
</div>