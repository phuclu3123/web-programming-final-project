<?php
declare(strict_types=1);
use App\Core\Request;
use App\Core\Router;

// Mọi URL động dùng cùng bootstrap và router để giữ chung phiên, quyền và xử lý lỗi.
require_once __DIR__ . '/app/bootstrap.php';
$router = new Router();
require __DIR__ . '/config/routes.php';
$router->dispatch(Request::fromGlobals());
