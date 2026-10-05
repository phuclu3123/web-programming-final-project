<?php

require_once "AdminController.php";


/*
|--------------------------------------------------------------------------
| Khởi tạo Controller
|--------------------------------------------------------------------------
*/

$controller = new AdminController();


/*
|--------------------------------------------------------------------------
| Biến thông báo
|--------------------------------------------------------------------------
*/

$message = "";
$messageType = "";


/*
|--------------------------------------------------------------------------
| Xử lý APPROVE
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $couponId = isset($_POST["coupon_id"])
        ? (int)$_POST["coupon_id"]
        : 0;


    if ($couponId <= 0) {

        $message =
            "Coupon ID không hợp lệ.";

        $messageType = "error";

    } else {

        $result =
            $controller->approve($couponId);


        $message =
            $result["message"];


        if ($result["success"]) {

            $messageType = "success";

        } else {

            $messageType = "error";
        }
    }
}


/*
|--------------------------------------------------------------------------
| Lấy danh sách coupon
|--------------------------------------------------------------------------
*/

$data =
    $controller->index();

$coupons =
    $data["coupons"];


/*
|--------------------------------------------------------------------------
| Lấy coupon đang được xem
|--------------------------------------------------------------------------
*/

$selectedCoupon = null;


if (isset($_GET["id"])) {

    $id = (int)$_GET["id"];

    $selectedCoupon =
        $controller->detail($id);
}


/*
|--------------------------------------------------------------------------
| Escape HTML
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        $value,
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

    <title>
        CouponHub - Admin
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                Arial, Helvetica, sans-serif;

            background: #f5f6fa;

            color: #333;
        }


        .header {

            background: #343a40;

            color: white;

            padding: 20px 40px;
        }


        .header h1 {

            margin: 0 0 5px 0;

            font-size: 26px;
        }


        .header p {

            margin: 0;

            color: #dcdcdc;
        }


        .container {

            width: 90%;

            max-width: 1200px;

            margin: 30px auto;
        }


        /*Thông báo*/

        .message {

            padding: 14px 18px;

            margin-bottom: 20px;

            border-radius: 6px;

            font-weight: bold;
        }


        .success {

            background: #d4edda;

            color: #155724;
        }


        .error {

            background: #f8d7da;

            color: #721c24;
        }


        /*Dashboard*/

        .dashboard {

            display: flex;

            gap: 20px;

            margin-bottom: 25px;
        }


        .stat-card {

            flex: 1;

            background: white;

            padding: 20px;

            border-radius: 8px;

            box-shadow:
                0 2px 8px
                rgba(0, 0, 0, 0.08);
        }


        .stat-card h3 {

            margin: 0;

            color: #666;

            font-size: 15px;
        }


        .stat-number {

            font-size: 30px;

            font-weight: bold;

            margin-top: 10px;
        }


        /* Main content*/

        .content {

            display: flex;

            gap: 25px;

            align-items: flex-start;
        }


        .box {

            background: white;

            padding: 22px;

            border-radius: 8px;

            box-shadow:
                0 2px 8px
                rgba(0, 0, 0, 0.08);
        }


        .coupon-list {

            width: 55%;
        }


        .coupon-detail {

            width: 45%;
        }


        .box h2 {

            margin-top: 0;

            margin-bottom: 20px;
        }


        /*Coupon card*/

        .coupon-card {

            border:
                1px solid #ddd;

            border-radius: 7px;

            padding: 16px;

            margin-bottom: 15px;
        }


        .coupon-card h3 {

            margin:
                0 0 10px 0;
        }


        .coupon-card p {

            margin: 7px 0;
        }


        .status {

            display: inline-block;

            padding:
                5px 10px;

            border-radius: 5px;

            font-size: 13px;

            font-weight: bold;
        }


        .pending {

            background: #fff3cd;

            color: #856404;
        }


        .approved {

            background: #d4edda;

            color: #155724;
        }


        /*Buttons*/

        .btn {

            display: inline-block;

            padding:
                9px 15px;

            border: none;

            border-radius: 5px;

            text-decoration: none;

            cursor: pointer;

            font-size: 14px;
        }


        .btn-view {

            background: #007bff;

            color: white;
        }


        .btn-approve {

            width: 100%;

            background: #28a745;

            color: white;

            font-size: 16px;

            padding: 12px;
        }


        .btn-approve:hover {

            background: #218838;
        }


        /*Detail*/

        .detail-row {

            margin-bottom: 14px;

            line-height: 1.5;
        }


        .detail-label {

            display: inline-block;

            width: 120px;

            font-weight: bold;
        }


        .description {

            background: #f8f9fa;

            padding: 12px;

            border-radius: 5px;

            margin-top: 8px;
        }


        .empty {

            color: #777;

            text-align: center;

            padding: 30px 10px;
        }


        @media (max-width: 800px) {

            .content {

                flex-direction: column;
            }


            .coupon-list,
            .coupon-detail {

                width: 100%;
            }


            .dashboard {

                flex-direction: column;
            }
        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<div class="header">

    <h1>
        CouponHub Admin
    </h1>

    <p>
        Trang kiểm duyệt coupon
    </p>

</div>


<div class="container">


    <!-- =====================================================
         MESSAGE
    ====================================================== -->

    <?php if ($message !== ""): ?>

        <div
            class="message
            <?= $messageType === "success"
                ? "success"
                : "error" ?>"
        >

            <?= e($message) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         DASHBOARD
    ====================================================== -->

    <div class="dashboard">


        <div class="stat-card">

            <h3>
                Pending Coupons
            </h3>

            <div class="stat-number">

                <?= count($coupons) ?>

            </div>

        </div>


        <div class="stat-card">

            <h3>
                Admin
            </h3>

            <div class="stat-number">

                1

            </div>

        </div>


        <div class="stat-card">

            <h3>
                System
            </h3>

            <div class="stat-number">

                OK

            </div>

        </div>


    </div>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <div class="content">


        <!-- =================================================
             PENDING QUEUE
        ================================================== -->

        <div class="box coupon-list">

            <h2>
                Hàng chờ kiểm duyệt
            </h2>


            <?php if (empty($coupons)): ?>

                <div class="empty">

                    Không có coupon đang chờ duyệt.

                </div>

            <?php endif; ?>


            <?php foreach ($coupons as $coupon): ?>

                <div class="coupon-card">


                    <h3>

                        <?= e(
                            $coupon["title"]
                        ) ?>

                    </h3>


                    <p>

                        <strong>
                            Mã:
                        </strong>

                        <?= e(
                            $coupon["code"]
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Shop:
                        </strong>

                        <?= e(
                            $coupon["shop"]
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Giảm:
                        </strong>

                        <?= e(
                            $coupon["discount"]
                        ) ?>

                    </p>


                    <span
                        class="status pending"
                    >

                        <?= e(
                            $coupon["status"]
                        ) ?>

                    </span>


                    <br>
                    <br>


                    <a
                        href="admin.php?id=<?= e(
                            $coupon["id"]
                        ) ?>"
                        class="btn btn-view"
                    >

                        Xem chi tiết

                    </a>


                </div>

            <?php endforeach; ?>

        </div>


        <!-- =================================================
             COUPON DETAIL
        ================================================== -->

        <div class="box coupon-detail">

            <h2>
                Chi tiết coupon
            </h2>


            <?php if ($selectedCoupon === null): ?>

                <div class="empty">

                    Chọn một coupon bên trái
                    để xem chi tiết.

                </div>


            <?php else: ?>


                <div class="detail-row">

                    <span class="detail-label">
                        Tên:
                    </span>

                    <?= e(
                        $selectedCoupon["title"]
                    ) ?>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Mã:
                    </span>

                    <?= e(
                        $selectedCoupon["code"]
                    ) ?>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Shop:
                    </span>

                    <?= e(
                        $selectedCoupon["shop"]
                    ) ?>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Giảm:
                    </span>

                    <?= e(
                        $selectedCoupon["discount"]
                    ) ?>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Bắt đầu:
                    </span>

                    <?= e(
                        $selectedCoupon["starts_at"]
                    ) ?>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Kết thúc:
                    </span>

                    <?= e(
                        $selectedCoupon["ends_at"]
                    ) ?>

                </div>


                <div class="detail-row">

                    <strong>
                        Mô tả:
                    </strong>

                    <div class="description">

                        <?= e(
                            $selectedCoupon["description"]
                        ) ?>

                    </div>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Trạng thái:
                    </span>


                    <?php if (
                        $selectedCoupon["status"]
                        === "PENDING"
                    ): ?>

                        <span
                            class="status pending"
                        >

                            PENDING

                        </span>

                    <?php else: ?>

                        <span
                            class="status approved"
                        >

                            APPROVED

                        </span>

                    <?php endif; ?>

                </div>


                <br>


                <?php if (
                    $selectedCoupon["status"]
                    === "PENDING"
                ): ?>


                    <form
                        method="POST"
                        action="admin.php?id=<?= e(
                            $selectedCoupon["id"]
                        ) ?>"
                    >

                        <input
                            type="hidden"
                            name="coupon_id"
                            value="<?= e(
                                $selectedCoupon["id"]
                            ) ?>"
                        >


                        <button
                            type="submit"
                            class="btn btn-approve"
                            onclick="
                                return confirm(
                                    'Bạn có chắc muốn phê duyệt coupon này?'
                                );
                            "
                        >

                            Phê duyệt coupon

                        </button>

                    </form>


                <?php else: ?>


                    <div class="message success">

                        Coupon đã được phê duyệt.

                    </div>


                <?php endif; ?>


            <?php endif; ?>

        </div>


    </div>

</div>


</body>

</html>