<?php
declare(strict_types=1);

use App\Contracts\RoutineGateway;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Request;
use App\Core\Database;
use App\Core\HttpError;
use App\Core\OAuthPreparation;
use App\Repositories\CatalogRepository;
use App\Services\AuthService;

require __DIR__ . '/../app/bootstrap.php';
session_save_path(sys_get_temp_dir());
function checkRoute(bool $value, string $label): void { if (!$value) throw new RuntimeException('FAIL: ' . $label); }
$router = new App\Core\Router();
require __DIR__ . '/../config/routes.php';
$match = new ReflectionMethod($router, 'match'); $match->setAccessible(true);
checkRoute($match->invoke($router, '/coupons/{id}', '/coupons/12') === ['id'=>'12'], 'one route parameter');
checkRoute($match->invoke($router, '/admin/merchant-memberships/{userId}/{merchantId}/revoke', '/admin/merchant-memberships/2/3/revoke') === ['userId'=>'2','merchantId'=>'3'], 'two route parameters');
checkRoute($match->invoke($router, '/login.php', '/loginXphp') === null, 'literal dot escaped');
checkRoute($match->invoke($router, '/coupons/{id}', '/coupons/1/edit') === null, 'whole path only');
checkRoute((new Request([],[],['REQUEST_URI'=>'/coupons/12?x=1']))->path() === '/coupons/12', 'query outside route path');
checkRoute(is_a(new Request(), App\Core\Request::class), 'shared Request type');
foreach ($router->routes() as $route) {
    [$class,$action] = $route['handler'];
    checkRoute(class_exists($class) && method_exists($class,$action), 'route handler ' . $route['path']);
}
checkRoute(Auth::safeReturn('/admin/users', 'ADMIN') === '/admin/users', 'Admin routed return');
checkRoute(Auth::safeReturn('/admin/users', 'USER') === 'index.php', 'User cannot return to Admin');
checkRoute(Auth::safeReturn('//evil.example/admin', 'ADMIN') !== '//evil.example/admin', 'no external return');
// JSON/redirect responses end the request; HTTP checks exercise their guard policies.
echo "PASS: route reflection, parameter matching and shared Request.\n";
