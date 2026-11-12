<?php
declare(strict_types=1);

namespace App\Services;

/** Email-safe markup shared by transactional mail transports. No remote assets or tracking. */
final class PasswordResetEmail
{
    /** @return array{subject:string,text:string,html:string} */
    public static function otp(string $code): array
    {
        if (!preg_match('/\A[0-9]{6}\z/D', $code)) throw new \InvalidArgumentException('Invalid recovery code format.');
        $text = "Sử dụng mã dưới đây để đặt lại mật khẩu tài khoản CouponHub của bạn.\n\n"
            . $code . "\n\nMã có hiệu lực trong 10 phút và chỉ sử dụng một lần.\n"
            . "Không chia sẻ mã này với bất kỳ ai.\n\n"
            . "Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này. Mật khẩu của bạn sẽ không thay đổi.\n\nCouponHub";
        $content = '<p style="margin:0 0 24px;color:#475569;font-size:16px;line-height:26px">Sử dụng mã dưới đây để đặt lại mật khẩu tài khoản CouponHub của bạn.</p>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 20px"><tr><td align="center" bgcolor="#eff3ff" style="border:1px solid #dbe4ff;border-radius:12px;padding:24px 12px">'
            . '<p style="margin:0 0 10px;color:#475569;font-size:12px;line-height:18px;font-weight:700;letter-spacing:1px">MÃ XÁC MINH</p>'
            . '<p style="margin:0;color:#304fc4;font-family:Consolas,monospace;font-size:36px;line-height:46px;font-weight:700;letter-spacing:6px">' . $code . '</p></td></tr></table>'
            . '<p style="margin:0 0 20px;color:#475569;font-size:14px;line-height:23px">Mã có hiệu lực trong <strong>10 phút</strong> và chỉ sử dụng một lần. Không chia sẻ mã này với bất kỳ ai.</p>';
        return ['subject' => 'Mã đặt lại mật khẩu CouponHub', 'text' => $text,
            'html' => self::layout('Mã xác minh đặt lại mật khẩu CouponHub. Có hiệu lực trong 10 phút.', $content)];
    }

    /** @return array{subject:string,text:string,html:string} */
    public static function link(string $resetUrl): array
    {
        if (!filter_var($resetUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($resetUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Invalid recovery URL.');
        }
        $link = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = "Bạn đã yêu cầu đặt lại mật khẩu tài khoản CouponHub.\n\nMở liên kết sau để tạo mật khẩu mới:\n"
            . $resetUrl . "\n\nLiên kết có hiệu lực trong 30 phút và chỉ sử dụng một lần.\n\n"
            . "Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này. Mật khẩu của bạn sẽ không thay đổi.\n\nCouponHub";
        $content = '<p style="margin:0 0 24px;color:#475569;font-size:16px;line-height:26px">Bạn đã yêu cầu đặt lại mật khẩu tài khoản CouponHub. Chọn nút bên dưới để tạo mật khẩu mới.</p>'
            . '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 24px"><tr><td bgcolor="#3454cd" style="border-radius:10px;text-align:center">'
            . '<a href="' . $link . '" style="display:inline-block;padding:16px 26px;color:#ffffff;text-decoration:none;font-size:16px;font-weight:700">Đặt lại mật khẩu</a></td></tr></table>'
            . '<p style="margin:0 0 20px;color:#475569;font-size:14px;line-height:23px">Liên kết có hiệu lực trong <strong>30 phút</strong> và chỉ sử dụng một lần.</p>'
            . '<p style="margin:0 0 8px;color:#64748b;font-size:13px;line-height:21px">Nếu nút không hoạt động, hãy sao chép liên kết sau vào trình duyệt:</p>'
            . '<p style="margin:0 0 24px;font-size:13px;line-height:21px;word-break:break-all;overflow-wrap:anywhere"><a href="' . $link . '" style="color:#304fc4">' . $link . '</a></p>';
        return ['subject' => 'Đặt lại mật khẩu CouponHub', 'text' => $text,
            'html' => self::layout('Đặt lại mật khẩu tài khoản CouponHub của bạn.', $content)];
    }

    private static function layout(string $preview, string $content): string
    {
        $preview = htmlspecialchars($preview, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CouponHub</title></head>'
            . '<body style="margin:0;padding:0;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#202733">'
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all">' . $preview . '</div>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" bgcolor="#f4f6fa"><tr><td align="center" style="padding:32px 16px">'
            . '<table role="presentation" width="560" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="width:100%;max-width:560px;border:1px solid #e2e8f0;border-radius:16px">'
            . '<tr><td style="padding:28px 28px 22px;border-bottom:1px solid #edf0f5"><span style="color:#202733;font-size:25px;line-height:32px;font-weight:700">Coupon<span style="color:#3454cd">Hub.</span></span></td></tr>'
            . '<tr><td style="padding:28px"><p style="margin:0 0 10px;color:#3454cd;font-size:12px;line-height:18px;font-weight:700;letter-spacing:1px">BẢO MẬT TÀI KHOẢN</p>'
            . '<h1 style="margin:0 0 18px;color:#202733;font-size:26px;line-height:34px">Đặt lại mật khẩu</h1>' . $content
            . '<p style="margin:0;padding-top:20px;border-top:1px solid #edf0f5;color:#64748b;font-size:14px;line-height:23px">Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này. Mật khẩu của bạn sẽ không thay đổi.</p></td></tr></table>'
            . '<p style="margin:20px 0 0;color:#64748b;font-size:12px;line-height:20px">Email tự động từ CouponHub. Vui lòng không trả lời.</p>'
            . '</td></tr></table></body></html>';
    }
}
