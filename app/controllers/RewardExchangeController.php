<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpError;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\RewardExchangeRepository;
use DomainException;

final class RewardExchangeController extends Controller
{
    private function repository(): RewardExchangeRepository
    {
        return new RewardExchangeRepository(Database::connection());
    }

    public function index(Request $request): void
    {
        $actor = Auth::requireRole(['USER','MERCHANT']);
        $repository = $this->repository();
        $this->view('rewards/index', [
            'pageTitle'=>'Đổi CouponPoint | CouponHub',
            'actor'=>$actor,
            'catalog'=>$repository->catalog(),
            'wallet'=>$repository->wallet((int)$actor['id']),
            'history'=>$repository->history((int)$actor['id']),
        ]);
    }

    public function redeem(Request $request): void
    {
        $actor = Auth::requireRole(['USER','MERCHANT']);
        $item = $request->input('reward_id','');
        $key = $request->input('idempotency_key','');
        if (!is_string($item) || !preg_match('/\A[1-9][0-9]{0,17}\z/D',$item)
            || filter_var($item,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) === false
            || !is_string($key)) throw new HttpError(422,'Yêu cầu đổi thưởng không hợp lệ.');
        try {
            $id = $this->repository()->redeem((int)$actor['id'],(int)$item,$key);
            Session::flash('success','Đã nhận yêu cầu đổi thưởng #'.$id.'. Điểm đã được giữ cho đến khi Admin xác minh.');
        } catch (DomainException $error) {
            Session::flash('error',$error->getMessage());
        }
        Response::redirect(url('/rewards'),303);
    }
}
