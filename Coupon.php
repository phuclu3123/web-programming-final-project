<?php

class Coupon
{
    private $coupons;


    public function __construct()
    {
        //Dữ liệu demo thay cho Database
        $this->coupons = [
            [
                "id" => 1,
                "title" => "Giảm 50% cho đơn hàng",
                "code" => "SALE50",
                "shop" => "Shop ABC",
                "description" => "Giảm 50% cho khách hàng mới.",
                "discount" => "50%",
                "starts_at" => "2026-10-05 08:00",
                "ends_at" => "2026-10-30 23:59",
                "status" => "PENDING"
            ],

            [
                "id" => 2,
                "title" => "Giảm 20.000đ",
                "code" => "SAVE20",
                "shop" => "Shop XYZ",
                "description" => "Giảm 20.000đ cho đơn hàng.",
                "discount" => "20.000đ",
                "starts_at" => "2026-10-05 08:00",
                "ends_at" => "2026-10-25 23:59",
                "status" => "PENDING"
            ],

            [
                "id" => 3,
                "title" => "Giảm 30% cuối tuần",
                "code" => "WEEKEND30",
                "shop" => "Shop ABC",
                "description" => "Ưu đãi cuối tuần.",
                "discount" => "30%",
                "starts_at" => "2026-10-10 00:00",
                "ends_at" => "2026-11-10 23:59",
                "status" => "PENDING"
            ]
        ];
    }


    //Lấy tất cả coupon

    public function getAll()
    {
        return $this->coupons;
    }


    //Lấy coupon theo ID
    public function getById($id)
    {
        foreach ($this->coupons as $coupon) {

            if ($coupon["id"] == $id) {
                return $coupon;
            }
        }

        return null;
    }


    //Lấy các coupon đang chờ duyệt
    public function getPendingCoupons()
    {
        $result = [];

        foreach ($this->coupons as $coupon) {

            if ($coupon["status"] === "PENDING") {

                $result[] = $coupon;
            }
        }

        return $result;
    }


    //Phê duyệt coupon PENDING → APPROVED
     
    public function approve($id)
    {
        foreach ($this->coupons as $index => $coupon) {

            if ($coupon["id"] == $id) {

                //Chỉ được duyệt coupon PENDING

                if ($coupon["status"] !== "PENDING") {

                    return [
                        "success" => false,
                        "message" =>
                            "Coupon này đã được xử lý."
                    ];
                }


                //Chuyển trạng thái
                $this->coupons[$index]["status"] =
                    "APPROVED";


                return [
                    "success" => true,
                    "message" =>
                        "Phê duyệt coupon thành công."
                ];
            }
        }


        return [
            "success" => false,
            "message" =>
                "Không tìm thấy coupon."
        ];
    }
}