<?php
declare(strict_types=1);
use App\Core\Request;
use App\Core\Router;

// ---- ĐIỂM VÀO CHUNG: mọi URL động dùng cùng bootstrap và Router ----
require_once __DIR__ . '/app/bootstrap.php';
$router = new Router();
require __DIR__ . '/config/routes.php';
$router->dispatch(Request::fromGlobals());
