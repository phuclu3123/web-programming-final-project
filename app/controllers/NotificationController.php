<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, Controller, Database, Request, Response};
use App\Repositories\NotificationRepository;
use App\Services\NotificationService;

final class NotificationController extends Controller
{
    private function service(): NotificationService
    {
        return new NotificationService(new NotificationRepository(Database::connection()));
    }

    public function index(Request $request): void
    {
        $actor = Auth::requireAuth();
        $this->view('notifications/index', ['pageTitle'=>'Thông báo', 'notifications'=>$this->service()->feed($actor)]);
    }

    public function read(Request $request): void
    {
        $this->service()->markAllRead(Auth::requireAuth());
        if ($request->expectsJson()) Response::json(['success'=>true]);
        Response::redirect(url('/notifications'), 303);
    }
}
