<?php

class Coupon
{
    private $db;

    /*
     * Khởi tạo kết nối database
     */
    public function __construct()
    {
        $host = "100.106.206.24";
        $dbname = "dbcouponshubweb";
        $username = "root";
        $password = "";

        try {
            $this->db = new PDO(
                "mysql:host=" . $host . ";dbname=" . $dbname . ";charset=utf8mb4",
                $username,
                $password
            );

            $this->db->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

        } catch (PDOException $e) {
            die("Không thể kết nối database: " . $e->getMessage());
        }
    }

    /*
     * Lấy danh sách coupon đang chờ duyệt
     */
    public function getPendingCoupons()
    {
        $sql = "
            SELECT
                c.id,
                c.title,
                c.code,
                c.discount_type,
                c.discount_value,
                c.starts_at,
                c.ends_at,
                c.moderation_status,
                c.publication_status,
                m.name AS merchant_name,
                cat.name AS category_name
            FROM tbl_coupons c
            INNER JOIN tbl_merchants m
                ON m.id = c.merchant_id
            INNER JOIN tbl_categories cat
                ON cat.id = c.category_id
            WHERE c.deleted_at IS NULL
              AND c.moderation_status = 'PENDING'
            ORDER BY c.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     * Lấy chi tiết một coupon
     */
    public function getById($couponId)
    {
        $sql = "
            SELECT
                c.*,
                m.name AS merchant_name,
                cat.name AS category_name
            FROM tbl_coupons c
            INNER JOIN tbl_merchants m
                ON m.id = c.merchant_id
            INNER JOIN tbl_categories cat
                ON cat.id = c.category_id
            WHERE c.id = :coupon_id
              AND c.deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":coupon_id", $couponId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
     * Phê duyệt coupon
     *
     * Gọi Stored Procedure:
     * ch_approve_coupon(p_actor_id, p_coupon_id)
     */
    public function approve($actorId, $couponId)
    {
        try {
            $sql = "CALL ch_approve_coupon(:actor_id, :coupon_id)";

            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(
                ":actor_id",
                $actorId,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ":coupon_id",
                $couponId,
                PDO::PARAM_INT
            );

            $stmt->execute();

            /*
             * Procedure trả về:
             */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            /*
             * Đóng result set của CALL
             * để có thể tiếp tục gọi procedure khác.
             */
            while ($stmt->nextRowset()) {
            }

            return $result;

        } catch (PDOException $e) {

            /*
             * Lấy message lỗi từ SIGNAL
             * của ch_approve_coupon.
             */
            throw new Exception($e->getMessage());
        }
    }

    /*
     * Lấy số lượng coupon theo trạng thái
     */
    public function countByStatus($status)
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM tbl_coupons
            WHERE moderation_status = :status
              AND deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ":status",
            $status,
            PDO::PARAM_STR
        );

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$row["total"];
    }
}