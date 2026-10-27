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
use App\Repositories\DealRequestRepository;
use App\Services\DealRequestValidator;
use DomainException;

final class DealRequestController extends Controller
{
    private function repository(): DealRequestRepository
    {
        return new DealRequestRepository(Database::connection());
    }

    private static function id(string $raw): int
    {
        if (!preg_match('/\A[1-9][0-9]{0,17}\z/D', $raw)
            || filter_var($raw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) === false) {
            throw new HttpError(404, 'Yêu cầu không tồn tại.');
        }
        return (int)$raw;
    }

    public function index(Request $request): void
    {
        $status = strtoupper($request->query('status', 'OPEN'));
        if (!in_array($status, ['OPEN','CLOSED','ALL'], true)) throw new HttpError(422, 'Bộ lọc không hợp lệ.');
        $rawPage = $request->query('page', '1');
        $page = preg_match('/\A[1-9][0-9]{0,5}\z/D', $rawPage) ? (int)$rawPage : 1;
        $this->view('deal-requests/index', [
            'pageTitle'=>'Cộng đồng tìm mã | CouponHub',
            'listing'=>$this->repository()->page($status, $page),
            'status'=>$status,
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requireRole(['USER','MERCHANT']);
        $this->view('deal-requests/create', ['pageTitle'=>'Đăng yêu cầu tìm coupon | CouponHub']);
    }

    public function store(Request $request): void
    {
        $actor = Auth::requireRole(['USER','MERCHANT']);
        $title = $request->input('title', '');
        $body = $request->input('body', '');
        if (!is_string($title) || !is_string($body)) throw new HttpError(422, 'Biểu mẫu không hợp lệ.');
        try {
            $values = DealRequestValidator::request($title, $body);
            $id = $this->repository()->create((int)$actor['id'], $values['title'], $values['body']);
            Session::flash('success', 'Yêu cầu tìm coupon đã được đăng.');
            Response::redirect(url('/deal-requests/' . $id), 303);
        } catch (DomainException $error) {
            Session::flash('error', $error->getMessage());
            Response::redirect(url('/deal-requests/create'), 303);
        }
    }

    public function show(Request $request, string $id): void
    {
        $id = self::id($id);
        $repo = $this->repository();
        $dealRequest = $repo->find($id);
        if ($dealRequest === null) throw new HttpError(404, 'Yêu cầu không tồn tại.');
        $this->view('deal-requests/show', [
            'pageTitle'=>(string)$dealRequest['title'] . ' | CouponHub',
            'dealRequest'=>$dealRequest,
            'replies'=>$repo->replies($id),
        ]);
    }

    public function reply(Request $request, string $id): void
    {
        $actor = Auth::requireRole(['USER','MERCHANT']);
        $requestId = self::id($id);
        $couponId = $request->input('coupon_id', '');
        $note = $request->input('note', '');
        if (!is_string($couponId) || !is_string($note)) throw new HttpError(422, 'Biểu mẫu không hợp lệ.');
        try {
            $values = DealRequestValidator::reply($couponId, $note);
            $this->repository()->addReply($requestId, (int)$actor['id'], $values['coupon_id'], $values['note']);
            Session::flash('success', 'Đã gửi coupon gợi ý cho người đăng yêu cầu.');
        } catch (DomainException $error) {
            Session::flash('error', $error->getMessage());
        }
        Response::redirect(url('/deal-requests/' . $requestId) . '#answers', 303);
    }

    public function close(Request $request, string $id): void
    {
        $actor = Auth::requireRole(['USER','MERCHANT']);
        $requestId = self::id($id);
        if (!$this->repository()->close($requestId, (int)$actor['id'])) {
            throw new HttpError(403, 'Chỉ người tạo mới được đóng yêu cầu đang mở.');
        }
        Session::flash('success', 'Đã đóng yêu cầu.');
        Response::redirect(url('/deal-requests/' . $requestId), 303);
    }
}
