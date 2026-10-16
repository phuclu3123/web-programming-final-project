<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpError;
use App\Core\OAuthPreparation;
use App\Core\Request;
use App\Core\Response;

/** ---- OAUTH PREPARATION: cùng router, vẫn chưa đổi token/tạo tài khoản ---- */
final class OAuthController extends Controller
{
    private function preparation(): OAuthPreparation
    {
        return new OAuthPreparation(require __DIR__ . '/../../config/oauth.php');
    }
    public function prepare(Request $request): void
    {
        $preparation = $this->preparation();
        OAuthPreparation::requireSetupAccess();
        $mode = $request->input('mode', ''); $provider = $request->input('provider', '');
        if (!is_string($provider) || !in_array($mode, ['probe','provider'], true)) throw new HttpError(400, 'Chế độ thử không hợp lệ.');
        Response::json(['success'=>true] + $preparation->begin($provider, $mode === 'probe'));
    }
    private function callback(string $provider, Request $request): void
    {
        header('Referrer-Policy: no-referrer');
        Response::json($this->preparation()->consume($provider, $request->all()));
    }
    public function google(Request $request): void { $this->callback('google', $request); }
    public function github(Request $request): void { $this->callback('github', $request); }
}
