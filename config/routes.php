<?php

declare(strict_types=1);

use App\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\CouponController as AdminCouponController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\MerchantController as AdminMerchantController;
use App\Controllers\Admin\MerchantAccessController;
use App\Controllers\Admin\ArticleController as AdminArticleController;
use App\Controllers\ArticleController;
use App\Controllers\AccountController;
use App\Controllers\HolidayController;
use App\Controllers\Admin\ReportController as AdminReportController;
use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\OAuthController;
use App\Controllers\CampaignController;
use App\Controllers\CouponController;
use App\Controllers\DealCalculatorController;
use App\Controllers\CouponDiscoveryToolsController;
use App\Controllers\DealRequestController;
use App\Controllers\RewardExchangeController;
use App\Controllers\Admin\RewardExchangeController as AdminRewardExchangeController;
use App\Controllers\CouponInteractionController;
use App\Controllers\CouponEventController;
use App\Controllers\PortalController;
use App\Controllers\CatalogController;
use App\Controllers\CategoryController;
use App\Controllers\ShopController;
use App\Controllers\Admin\ReviewController;
use App\Controllers\InfoController;
use App\Controllers\MerchantController;
use App\Controllers\SavedCouponController;
use App\Core\Router;

/** @var Router $router */
$router->get('/login.php', [AuthController::class, 'showLogin']);
$router->post('/logout.php', [AuthController::class, 'logout']);
$router->post('/api/login.php', [AuthController::class, 'login']);
$router->post('/api/register.php', [AuthController::class, 'register']);
$router->post('/api/oauth/prepare.php', [OAuthController::class, 'prepare']);
$router->post('/auth/oauth/{provider}', [OAuthController::class, 'start']);
$router->get('/api/oauth/google-callback.php', [OAuthController::class, 'google']);
$router->get('/api/oauth/github-callback.php', [OAuthController::class, 'github']);
$router->get('/index.php', [CatalogController::class, 'index']);
$router->get('/admin.php', [ReviewController::class, 'index']);
$router->post('/admin.php', [ReviewController::class, 'index']);
$router->get('/shop.php', [ShopController::class, 'index']);
$router->post('/shop.php', [ShopController::class, 'index']);
$router->get('/', [CatalogController::class, 'index']);
$router->get('/portal', [PortalController::class, 'index']);
$router->get('/dashboard', [PortalController::class, 'dashboard']);
$router->get('/notifications', [\App\Controllers\NotificationController::class, 'index']);
$router->post('/notifications/read', [\App\Controllers\NotificationController::class, 'read']);
$router->get('/account', [AccountController::class, 'index']);
$router->post('/account', [AccountController::class, 'update']);
$router->post('/account/password', [AccountController::class, 'changePassword']);
$router->get('/about', [InfoController::class, 'about']);
$router->get('/terms', [InfoController::class, 'terms']);
$router->get('/privacy', [InfoController::class, 'privacy']);
$router->get('/community-guidelines', [InfoController::class, 'communityGuidelines']);
$router->get('/contact', [InfoController::class, 'contact']);
$router->get('/articles', [ArticleController::class, 'index']);
$router->get('/guides/{slug}', [ArticleController::class, 'guide']);
$router->get('/articles/{slug}', [ArticleController::class, 'show']);
$router->get('/campaign-calendar', [HolidayController::class, 'index']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/auth/reset-otp', [AuthController::class, 'resetOtp']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

// Public coupon + campaign.
$router->get('/categories', [CategoryController::class, 'index']);
$router->get('/campaigns', [CampaignController::class, 'index']);
$router->get('/coupons', [CouponController::class, 'index']);
$router->get('/tools/best-deal', [DealCalculatorController::class, 'index']);
$router->get('/tools/match', [CouponDiscoveryToolsController::class, 'match']);
$router->get('/tools/stack-checker', [CouponDiscoveryToolsController::class, 'stack']);
$router->get('/rewards', [RewardExchangeController::class, 'index']);
$router->post('/rewards/redeem', [RewardExchangeController::class, 'redeem']);
$router->get('/deal-requests', [DealRequestController::class, 'index']);
$router->get('/deal-requests/create', [DealRequestController::class, 'create']);
$router->post('/deal-requests', [DealRequestController::class, 'store']);
$router->get('/deal-requests/{id}', [DealRequestController::class, 'show']);
$router->post('/deal-requests/{id}/reply', [DealRequestController::class, 'reply']);
$router->post('/deal-requests/{id}/close', [DealRequestController::class, 'close']);
$router->get('/coupons/create', [CouponController::class, 'create']);
$router->post('/coupons', [CouponController::class, 'store']);
$router->get('/my-coupons', [CouponController::class, 'myCoupons']);
$router->get('/saved-coupons', [SavedCouponController::class, 'index']);
$router->post('/saved-coupons/{id}/save', [SavedCouponController::class, 'save']);
$router->post('/saved-coupons/{id}/remove', [SavedCouponController::class, 'remove']);
$router->get('/coupons/{id}', [CouponController::class, 'show']);
$router->get('/campaigns/{id}', [CampaignController::class, 'show']);

// AJAX / JSON endpoints.
$router->get('/api/health', [PortalController::class, 'apiHealth']);
$router->get('/api/coupons/search', [CouponController::class, 'apiSearch']);
$router->post('/api/coupons/{id}/events', [CouponEventController::class, 'record']);
$router->post('/api/coupons/{id}/favorite', [CouponInteractionController::class, 'favorite']);
$router->post('/api/coupons/{id}/vote', [CouponInteractionController::class, 'vote']);
$router->post('/api/coupons/{id}/comments', [CouponInteractionController::class, 'comment']);
$router->post('/api/coupons/{id}/reports', [CouponInteractionController::class, 'report']);

// Merchant onboarding + Merchant Center.
$router->get('/merchant/apply', [MerchantController::class, 'apply']);
$router->post('/merchant/apply', [MerchantController::class, 'submitApplication']);
$router->get('/merchant', [MerchantController::class, 'dashboard']);
$router->get('/merchant/profile/{id}', [MerchantController::class, 'profile']);
$router->post('/merchant/profile/{id}', [MerchantController::class, 'updateProfile']);
$router->get('/merchant/coupons', [MerchantController::class, 'coupons']);
$router->get('/merchant/coupons/create', [MerchantController::class, 'createCoupon']);
$router->post('/merchant/coupons', [MerchantController::class, 'storeCoupon']);
$router->get('/merchant/coupons/{id}/edit', [MerchantController::class, 'editCoupon']);
$router->post('/merchant/coupons/{id}/edit', [MerchantController::class, 'updateCoupon']);
$router->post('/merchant/coupons/{id}/disable', [MerchantController::class, 'disableCoupon']);
$router->get('/merchant/campaigns', [CampaignController::class, 'merchantIndex']);
$router->get('/merchant/campaigns/create', [CampaignController::class, 'merchantCreate']);
$router->post('/merchant/campaigns', [CampaignController::class, 'merchantStore']);
$router->get('/merchant/campaigns/{id}', [CampaignController::class, 'merchantShow']);
$router->get('/merchant/campaigns/{id}/edit', [CampaignController::class, 'merchantEdit']);
$router->post('/merchant/campaigns/{id}/edit', [CampaignController::class, 'merchantUpdate']);
$router->post('/merchant/campaigns/{id}/attach', [CampaignController::class, 'attach']);
$router->post('/merchant/campaigns/{id}/coupons/{couponId}/detach', [CampaignController::class, 'detach']);

// Admin.
$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/users', [AdminUserController::class, 'index']);
$router->post('/admin/users/{id}/status', [AdminUserController::class, 'status']);
$router->get('/admin/merchants', [AdminMerchantController::class, 'index']);
$router->post('/admin/merchants/{id}/status', [AdminMerchantController::class, 'shopStatus']);
$router->post('/admin/merchants/{id}/review', [AdminMerchantController::class, 'review']);
$router->get('/admin/categories', [AdminCategoryController::class, 'index']);
$router->post('/admin/categories', [AdminCategoryController::class, 'store']);
$router->post('/admin/categories/{id}/toggle', [AdminCategoryController::class, 'toggle']);
$router->post('/admin/categories/{id}/edit', [AdminCategoryController::class, 'update']);
$router->post('/admin/categories/{id}/delete', [AdminCategoryController::class, 'delete']);
$router->get('/admin/campaigns', [AdminCampaignController::class, 'index']);
$router->get('/admin/campaigns/{id}', [AdminCampaignController::class, 'show']);
$router->post('/admin/campaigns/{id}/attach', [AdminCampaignController::class, 'attach']);
$router->post('/admin/campaigns/{id}/coupons/{couponId}/detach', [AdminCampaignController::class, 'detach']);
$router->post('/admin/campaigns', [AdminCampaignController::class, 'store']);
$router->post('/admin/campaigns/{id}/status', [AdminCampaignController::class, 'status']);
$router->get('/admin/coupons', [AdminCouponController::class, 'index']);
$router->post('/admin/coupons/{id}/moderate', [AdminCouponController::class, 'moderate']);
$router->get('/admin/coupons/{id}/edit', [AdminCouponController::class, 'edit']);
$router->post('/admin/coupons/{id}/edit', [AdminCouponController::class, 'update']);
$router->post('/admin/coupons/{id}/expire', [AdminCouponController::class, 'expire']);
$router->post('/admin/coupons/{id}/delete', [AdminCouponController::class, 'delete']);
$router->get('/admin/rewards', [AdminRewardExchangeController::class, 'index']);
$router->post('/admin/rewards/{id}/review', [AdminRewardExchangeController::class, 'review']);
$router->get('/admin/reports', [AdminReportController::class, 'index']);
$router->post('/admin/reports/{id}/resolve', [AdminReportController::class, 'resolve']);

// Phase 5: independent User–Shop requests and revocation.
$router->get('/admin/merchant-requests', [MerchantAccessController::class, 'index']);
$router->post('/admin/merchant-requests/{id}/review', [MerchantAccessController::class, 'review']);
$router->post('/admin/merchant-memberships/{userId}/{merchantId}/revoke', [MerchantAccessController::class, 'revoke']);
// Admin articles use existing articles table; public users can only see PUBLISHED articles.
$router->get('/admin/articles', [AdminArticleController::class, 'index']);
$router->get('/admin/articles/create', [AdminArticleController::class, 'create']);
$router->post('/admin/articles', [AdminArticleController::class, 'store']);
$router->get('/admin/articles/{id}/edit', [AdminArticleController::class, 'edit']);
$router->post('/admin/articles/{id}/edit', [AdminArticleController::class, 'update']);
