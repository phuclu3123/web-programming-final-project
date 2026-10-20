<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CouponEventRepository;
use App\Services\CouponEventService;

/** JSON endpoint; CSRF/Origin checks are enforced by Router before dispatch. */
final class CouponEventController extends Controller
{
    public function record(Request $request, string $id): void
    {
        $couponId = filter_var($id, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $event = $request->input('event', '');
        if ($couponId === false || !is_string($event) || $event !== 'COPY') {
            Response::json(['success'=>false,'message'=>'Sự kiện coupon không hợp lệ.'],422);
        }
        $actorId = Auth::id(); // null for anonymous visitors; never trust a client-supplied ID.
        $service = new CouponEventService(new CouponEventRepository(Database::connection()));
        $recorded = $service->recordCopy($actorId, (int)$couponId);
        Response::json(['success'=>true,'recorded'=>$recorded]);
    }
}
