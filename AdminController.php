<?php

require_once "Coupon.php";

class AdminController
{
    private $couponModel;


    public function __construct()
    {
        $this->couponModel = new Coupon();
    }


    /*
     * Hiển thị danh sách coupon
     */
    public function index()
    {
        return [
            "coupons" =>
                $this->couponModel
                     ->getPendingCoupons()
        ];
    }


    /*
     * Hiển thị chi tiết coupon
     */
    public function detail($id)
    {
        return $this->couponModel
                    ->getById($id);
    }


    /*
     * Phê duyệt coupon
     */
    public function approve($id)
    {
        return $this->couponModel
                    ->approve($id);
    }
}