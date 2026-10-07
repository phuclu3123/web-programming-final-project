<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CampaignRepository;
use App\Repositories\CouponRepository;
use App\Repositories\MerchantRepository;
use App\Repositories\UserRepository;
use Throwable;

final class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $pdo = Database::connection();
        $campaigns = new CampaignRepository($pdo);
        $coupons = new CouponRepository($pdo);
        $merchants = new MerchantRepository($pdo);

        $this->view('home/index', [
            'pageTitle' => 'CouponHub',
            'currentCampaigns' => $campaigns->publicCurrent(2),
            'upcomingCampaigns' => $campaigns->publicUpcoming(4),
            'hotDeals' => $coupons->hotDeals(6),
            'popularMerchants' => $merchants->popular(6),
        ]);
    }

    public function dashboard(Request $request): void
    {
        Auth::requireAuth();
        $users = new UserRepository(Database::connection());
        $userId = (int) Auth::id();

        $this->view('dashboard/index', [
            'pageTitle' => 'Tài khoản của tôi',
            'rewardSummary' => $users->rewardSummary($userId),
            'pointHistory' => $users->pointHistory($userId),
        ]);
    }

    public function apiHealth(Request $request): void
    {
        try {
            $pdo = Database::connection();
            $pdo->query('SELECT 1')->fetchColumn();
            $collation = $pdo->query('SELECT @@collation_connection')->fetchColumn();
            Response::json([
                'success' => true,
                'data' => [
                    'app' => (string) config('app.name', 'CouponHub'),
                    'database' => 'connected',
                    'collationConnection' => (string) $collation,
                    'serverTimeUtc' => gmdate('c'),
                ],
            ]);
        } catch (Throwable $e) {
            Response::json(['success' => false, 'message' => 'Database connection failed.'], 503);
        }
    }
}
