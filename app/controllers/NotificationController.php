<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Controller, Database, Request, Response};
use App\Repositories\NotificationRepository;
use App\Services\NotificationService;

// Lấy và đánh dấu thông báo của actor hiện tại, không nhận user ID từ client.
final class NotificationController extends Controller
{
    private function service(): NotificationService
    {
        return new NotificationService(new NotificationRepository(Database::connection()));
    }

    public function index(Request $request): void
    {
        $actor = Auth::requireAuth();
        $this->view('notifications/index', ['pageTitle' => 'Thông báo', 'notifications' => $this->service()->feed($actor)]);
    }

    public function read(Request $request): void
    {
        $actor=Auth::requireAuth();$key=$request->input('event_key','');
        if(!is_string($key)||strlen($key)>191)throw new \App\Core\HttpError(422,'Thông báo không hợp lệ.');
        if($key!=='')$this->service()->markOneRead($actor,$key);else $this->service()->markAllRead($actor);
        if ($request->expectsJson()) Response::json(['success' => true]);
        Response::redirect(url('/notifications'), 303);
    }
    public function feed(Request $request):void
    {
        $items=$this->service()->feed(Auth::requireAuth());
        foreach($items as &$item){$item['href']=url($item['path']);$item['occurred_at_display']=format_db_datetime($item['occurred_at']);}
        unset($item);Response::json(['success'=>true,'data'=>['items'=>$items,'unread_count'=>count(array_filter($items,static fn(array $item):bool=>$item['unread']))]]);
    }
}
