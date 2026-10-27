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
use App\Repositories\ArticleRepository;
use App\Repositories\SavedCouponRepository;
use Throwable;

final class PortalController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::connection();
        $campaigns = new CampaignRepository($db);
        $coupons = new CouponRepository($db);
        $merchants = new MerchantRepository($db);
        // Guests and non-USER accounts receive only the existing public sections.
        $viewer = Auth::user($db);
        $personalizedDeals = is_array($viewer) && ($viewer['role'] ?? '') === 'USER'
            ? $coupons->personalizedForUser((int) $viewer['id'], 6)
            : [];

        $this->view('home/index', [
            'pageTitle' => 'CouponHub',
            'currentCampaigns' => $campaigns->publicCurrent(2),
            'upcomingCampaigns' => $campaigns->publicUpcoming(4),
            'hotDeals' => $coupons->hotDeals(6),
            'trendingDeals' => $coupons->trendingPublic(6),
            'personalizedDeals' => $personalizedDeals,
            'popularMerchants' => $merchants->popular(6),
            'recentArticles' => (new ArticleRepository($db))->published(3),
        ]);
    }

    public function dashboard(Request $request): void
    {
        $actor = Auth::requireAuth();
        $users = new UserRepository(Database::connection());
        $userId = (int) Auth::id();
        $rewardSummary = $users->rewardSummary($userId);

        $this->view('dashboard/index', [
            'pageTitle' => 'Tài khoản của tôi',
            'actor' => $actor,
            'rewardSummary' => $rewardSummary,
            'contributorQuality' => \App\Services\ContributorQualityService::fromSummary($rewardSummary),
            'pointHistory' => $users->pointHistory($userId),
            'savedCount' => (new SavedCouponRepository(Database::connection()))->countForUser($userId),
            'savedPreview' => (new SavedCouponRepository(Database::connection()))->paginated($userId,
                ['keyword'=>'', 'category_id'=>0, 'status'=>'ALL', 'sort'=>'RECENT'], 1, 3)['items'],
        ]);
    }

    public function apiHealth(Request $request): void
    {
        try {
            $db = Database::connection();
            $db->query('SELECT 1')->fetchColumn();
            $collation = $db->query('SELECT @@collation_connection')->fetchColumn();
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
