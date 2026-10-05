<?php

require_once __DIR__ . "/../../models/Coupon.php";

class CouponController
{
    private $coupon;

    public function __construct()
    {
        $this->coupon = new Coupon();
    }

    /*
     * Lấy danh sách coupon đang chờ duyệt
     */
    public function getPendingCoupons()
    {
        return $this->coupon->getPendingCoupons();
    }

    /*
     * Lấy chi tiết coupon
     */
    public function getCoupon($couponId)
    {
        return $this->coupon->getById($couponId);
    }

    /*
     * Phê duyệt coupon
     */
    public function approve($actorId, $couponId)
    {
        return $this->coupon->approve(
            $actorId,
            $couponId
        );
    }

    /*
     * Lấy số liệu tổng quan
     */
    public function getDashboardStats()
    {
        return array(
            "pending" => $this->coupon->countByStatus("PENDING"),
            "approved" => $this->coupon->countByStatus("APPROVED"),
            "rejected" => $this->coupon->countByStatus("REJECTED")
        );
    }
}