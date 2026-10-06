<?php

declare(strict_types=1);

use App\Controllers\Admin\CouponController as AdminCouponController;
use App\Controllers\Admin\ReportController as AdminReportController;
use App\Controllers\AuthController;
use App\Controllers\CouponController;
use App\Controllers\CouponInteractionController;
use App\Controllers\HomeController;
use App\Core\Router;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index']);
$router->get('/dashboard', [HomeController::class, 'dashboard']);
$router->get('/admin', [HomeController::class, 'admin']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

// Coupon Core.
$router->get('/coupons', [CouponController::class, 'index']);
$router->get('/coupons/create', [CouponController::class, 'create']);
$router->post('/coupons', [CouponController::class, 'store']);
$router->get('/my-coupons', [CouponController::class, 'myCoupons']);
$router->get('/coupons/{id}', [CouponController::class, 'show']);

// AJAX / JSON endpoints.
$router->get('/api/health', [HomeController::class, 'apiHealth']);
$router->get('/api/coupons/search', [CouponController::class, 'apiSearch']);
$router->post('/api/coupons/{id}/favorite', [CouponInteractionController::class, 'favorite']);
$router->post('/api/coupons/{id}/vote', [CouponInteractionController::class, 'vote']);
$router->post('/api/coupons/{id}/comments', [CouponInteractionController::class, 'comment']);
$router->post('/api/coupons/{id}/reports', [CouponInteractionController::class, 'report']);

// Admin moderation / reports.
$router->get('/admin/coupons', [AdminCouponController::class, 'index']);
$router->post('/admin/coupons/{id}/moderate', [AdminCouponController::class, 'moderate']);
$router->get('/admin/reports', [AdminReportController::class, 'index']);
$router->post('/admin/reports/{id}/resolve', [AdminReportController::class, 'resolve']);
