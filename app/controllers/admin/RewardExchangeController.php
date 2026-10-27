<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

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
    public function index(Request $request): void
    {
        Auth::requireRole(['ADMIN']);
        $this->view('admin/rewards/index', [
            'pageTitle'=>'Yêu cầu đổi thưởng | Admin CouponHub',
            'requests'=>(new RewardExchangeRepository(Database::connection()))->adminQueue(),
        ]);
    }

    public function review(Request $request, string $id): void
    {
        $actor = Auth::requireRole(['ADMIN']);
        if (!preg_match('/\A[1-9][0-9]{0,17}\z/D',$id)
            || filter_var($id,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) === false) {
            throw new HttpError(404,'Không tìm thấy yêu cầu đổi thưởng.');
        }
        $action = $request->input('action','');
        $note = $request->input('note','');
        if (!is_string($action) || !is_string($note)) throw new HttpError(422,'Dữ liệu biểu mẫu không hợp lệ.');
        try {
            $updated = (new RewardExchangeRepository(Database::connection()))->review((int)$id,(int)$actor['id'],$action,$note);
            Session::flash($updated ? 'success' : 'warning',
                $updated ? 'Đã cập nhật yêu cầu. Nếu từ chối, số điểm đã được hoàn về ví.'
                    : 'Yêu cầu không tồn tại hoặc đã được xử lý trước đó.');
        } catch (DomainException $error) {
            Session::flash('error',$error->getMessage());
        }
        Response::redirect(url('/admin/rewards'),303);
    }
}
