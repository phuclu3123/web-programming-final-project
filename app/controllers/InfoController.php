<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

final class InfoController extends Controller
{
    public function about(Request $request): void { $this->show('Giới thiệu CouponHub', 'about'); }
    public function terms(Request $request): void { $this->show('Điều khoản sử dụng', 'terms'); }
    public function privacy(Request $request): void { $this->show('Chính sách bảo mật', 'privacy'); }
    public function communityGuidelines(Request $request): void { $this->show('Quy tắc cộng đồng', 'community'); }
    public function contact(Request $request): void { $this->show('Liên hệ', 'contact'); }

    private function show(string $pageTitle, string $type): void
    {
        $this->view('info/page', [
            'pageTitle' => $pageTitle,
            'infoType' => $type,
            'supportEmail' => trim((string) config('app.support_email', '')),
        ]);
    }
}
