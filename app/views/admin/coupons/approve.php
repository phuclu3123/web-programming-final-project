<?php

require_once __DIR__ . "/../../../controllers/admin/CouponController.php";

$controller = new CouponController();

/*
 * Demo actor Admin
 */
$actorId = 3;

$message = "";
$messageType = "";

/*
 * Xử lý POST - Phê duyệt coupon
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $couponId = isset($_POST["coupon_id"])
        ? (int)$_POST["coupon_id"]
        : 0;

    if ($couponId <= 0) {

        $message = "Coupon ID không hợp lệ.";
        $messageType = "error";

    } else {

        try {

            $result = $controller->approve(
                $actorId,
                $couponId
            );

            if (
                $result &&
                isset($result["moderation_status"]) &&
                $result["moderation_status"] === "APPROVED"
            ) {

                $message = "Phê duyệt coupon thành công.";
                $messageType = "success";

            } else {

                $message = "Không thể phê duyệt coupon.";
                $messageType = "error";
            }

        } catch (Exception $e) {

            /*
             * Hiển thị lỗi do SIGNAL
             * từ ch_approve_coupon trả về.
             */
            $message = $e->getMessage();
            $messageType = "error";
        }
    }
}

/*
 * Lấy danh sách coupon PENDING
 */
$coupons = $controller->getPendingCoupons();

/*
 * Lấy thống kê
 */
$stats = $controller->getDashboardStats();

/*
 * Coupon đang được xem chi tiết
 */
$selectedCoupon = null;

if (isset($_GET["id"])) {

    $couponId = (int)$_GET["id"];

    if ($couponId > 0) {
        $selectedCoupon = $controller->getCoupon($couponId);
    }
}

/*
 * Hàm escape HTML
 */
function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin - Kiểm duyệt Coupon</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #222;
        }

        .header {
            background: #222;
            color: white;
            padding: 20px 40px;
        }

        .header h1 {
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .stat-card h3 {
            margin-top: 0;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .success {
            background: #dff6e5;
            color: #176b2c;
        }

        .error {
            background: #ffe1e1;
            color: #a40000;
        }

        .content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .panel {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .coupon {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .coupon h3 {
            margin-top: 0;
        }

        .coupon-info {
            margin: 8px 0;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            background: #fff0c2;
            color: #805d00;
            font-size: 13px;
        }

        .btn {
            display: inline-block;
            padding: 9px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-detail {
            background: #e9ecef;
            color: #222;
        }

        .btn-approve {
            background: #198754;
            color: white;
        }

        .btn-approve:hover {
            background: #157347;
        }

        .detail-row {
            margin-bottom: 12px;
        }

        .detail-row strong {
            display: inline-block;
            min-width: 130px;
        }

        @media (max-width: 800px) {

            .stats {
                grid-template-columns: 1fr;
            }

            .content {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

    <div class="header">

        <h1>Admin - Kiểm duyệt Coupon</h1>

    </div>

    <div class="container">

        <?php if ($message !== ""): ?>

            <div class="message <?= e($messageType) ?>">

                <?= e($message) ?>

            </div>

        <?php endif; ?>


        <!-- =========================
             DASHBOARD STATISTICS
        ========================== -->

        <div class="stats">

            <div class="stat-card">

                <h3>Chờ duyệt</h3>

                <div class="stat-number">
                    <?= e($stats["pending"]) ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>Đã duyệt</h3>

                <div class="stat-number">
                    <?= e($stats["approved"]) ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>Từ chối</h3>

                <div class="stat-number">
                    <?= e($stats["rejected"]) ?>
                </div>

            </div>

        </div>


        <div class="content">

            <!-- =========================
                 PENDING QUEUE
            ========================== -->

            <div class="panel">

                <h2>Hàng chờ duyệt</h2>

                <?php if (empty($coupons)): ?>

                    <p>
                        Hiện không có coupon nào đang chờ duyệt.
                    </p>

                <?php else: ?>

                    <?php foreach ($coupons as $coupon): ?>

                        <div class="coupon">

                            <h3>
                                <?= e($coupon["title"]) ?>
                            </h3>

                            <div class="coupon-info">

                                <strong>Merchant:</strong>

                                <?= e($coupon["merchant_name"]) ?>

                            </div>

                            <div class="coupon-info">

                                <strong>Code:</strong>

                                <?= e($coupon["code"]) ?>

                            </div>

                            <div class="coupon-info">

                                <strong>Giảm:</strong>

                                <?= e($coupon["discount_type"]) ?>

                                -

                                <?= e($coupon["discount_value"]) ?>

                            </div>

                            <div class="coupon-info">

                                <strong>Trạng thái:</strong>

                                <span class="status">
                                    <?= e($coupon["moderation_status"]) ?>
                                </span>

                            </div>

                            <br>

                            <a
                                class="btn btn-detail"
                                href="admin.php?id=<?= e($coupon["id"]) ?>"
                            >
                                Xem chi tiết
                            </a>

                            <form
                                method="POST"
                                style="display:inline;"
                                onsubmit="return confirm('Bạn có chắc muốn phê duyệt coupon này?');"
                            >

                                <input
                                    type="hidden"
                                    name="coupon_id"
                                    value="<?= e($coupon["id"]) ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-approve"
                                >
                                    Phê duyệt
                                </button>

                            </form>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>


            <!-- =========================
                 COUPON DETAIL
            ========================== -->

            <div class="panel">

                <h2>Chi tiết coupon</h2>

                <?php if ($selectedCoupon === null): ?>

                    <p>
                        Chọn một coupon để xem chi tiết.
                    </p>

                <?php else: ?>

                    <div class="detail-row">

                        <strong>ID:</strong>

                        <?= e($selectedCoupon["id"]) ?>

                    </div>

                    <div class="detail-row">

                        <strong>Tên:</strong>

                        <?= e($selectedCoupon["title"]) ?>

                    </div>

                    <div class="detail-row">

                        <strong>Merchant:</strong>

                        <?= e($selectedCoupon["merchant_name"]) ?>

                    </div>

                    <div class="detail-row">

                        <strong>Category:</strong>

                        <?= e($selectedCoupon["category_name"]) ?>

                    </div>

                    <div class="detail-row">

                        <strong>Code:</strong>

                        <?= e($selectedCoupon["code"]) ?>

                    </div>

                    <div class="detail-row">

                        <strong>Mô tả:</strong>

                        <?= e($selectedCoupon["description"]) ?>

                    </div>

                    <div class="detail-row">

                        <strong>Điều kiện:</strong>

                        <?= e($selectedCoupon["terms"]) ?>

                    </div>

                    <div class="detail-row">

                        <strong>Loại giảm:</strong>

                        <?= e($selectedCoupon["discount_type"]) ?>

                    </div>

                    <div class="detail-row">

                        <strong>Giá trị:</strong>

                        <?= e($selectedCoupon["discount_value"]) ?>

                    </div>

                    <div class="detail-row">

                        <strong>Bắt đầu:</strong>

                        <?= e($selectedCoupon["starts_at"]) ?>

                    </div>

                    <div class="detail-row">

                        <strong>Kết thúc:</strong>

                        <?= e($selectedCoupon["ends_at"]) ?>

                    </div>

                    <div class="detail-row">

                        <strong>Moderation:</strong>

                        <?= e($selectedCoupon["moderation_status"]) ?>

                    </div>

                    <br>

                    <?php if ($selectedCoupon["moderation_status"] === "PENDING"): ?>

                        <form
                            method="POST"
                            onsubmit="return confirm('Bạn có chắc muốn phê duyệt coupon này?');"
                        >

                            <input
                                type="hidden"
                                name="coupon_id"
                                value="<?= e($selectedCoupon["id"]) ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-approve"
                            >
                                Phê duyệt coupon
                            </button>

                        </form>

                    <?php else: ?>

                        <p>
                            Coupon này đã được xử lý.
                        </p>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

        </div>

    </div>

</body>

</html>