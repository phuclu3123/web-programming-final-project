<?php

declare(strict_types=1);

use App\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\CouponController as AdminCouponController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\MerchantController as AdminMerchantController;
use App\Controllers\Admin\ReportController as AdminReportController;
use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\CampaignController;
use App\Controllers\CouponController;
use App\Controllers\CouponInteractionController;
use App\Controllers\HomeController;
use App\Controllers\MerchantController;
use App\Core\Router;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index']);
$router->get('/dashboard', [HomeController::class, 'dashboard']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

// Public coupon + campaign.
$router->get('/coupons', [CouponController::class, 'index']);
$router->get('/coupons/create', [CouponController::class, 'create']);
$router->post('/coupons', [CouponController::class, 'store']);
$router->get('/my-coupons', [CouponController::class, 'myCoupons']);
$router->get('/coupons/{id}', [CouponController::class, 'show']);
$router->get('/campaigns/{id}', [CampaignController::class, 'show']);

// AJAX / JSON endpoints.
$router->get('/api/health', [HomeController::class, 'apiHealth']);
$router->get('/api/coupons/search', [CouponController::class, 'apiSearch']);
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
$router->post('/admin/merchants/{id}/review', [AdminMerchantController::class, 'review']);
$router->get('/admin/categories', [AdminCategoryController::class, 'index']);
$router->post('/admin/categories', [AdminCategoryController::class, 'store']);
$router->post('/admin/categories/{id}/toggle', [AdminCategoryController::class, 'toggle']);
$router->get('/admin/campaigns', [AdminCampaignController::class, 'index']);
$router->get('/admin/campaigns/{id}', [AdminCampaignController::class, 'show']);
$router->post('/admin/campaigns/{id}/attach', [AdminCampaignController::class, 'attach']);
$router->post('/admin/campaigns/{id}/coupons/{couponId}/detach', [AdminCampaignController::class, 'detach']);
$router->post('/admin/campaigns', [AdminCampaignController::class, 'store']);
$router->post('/admin/campaigns/{id}/status', [AdminCampaignController::class, 'status']);
$router->get('/admin/coupons', [AdminCouponController::class, 'index']);
$router->post('/admin/coupons/{id}/moderate', [AdminCouponController::class, 'moderate']);
$router->get('/admin/reports', [AdminReportController::class, 'index']);
$router->post('/admin/reports/{id}/resolve', [AdminReportController::class, 'resolve']);
