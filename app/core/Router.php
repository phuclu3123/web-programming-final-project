<?php
declare(strict_types=1);
namespace App\Core;

/** ---- ROUTER THỐNG NHẤT: match route, guard trước constructor, trả 404/405 đúng ---- */
final class Router
{
    private array $routes = [];
    public function get(string $path, array $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, array $handler): void { $this->add('POST', $path, $handler); }
    public function add(string $method, string $path, array $handler): void
    {
        $path = rtrim('/' . trim($path, '/'), '/') ?: '/';
        foreach ($this->routes as $route) if ($route['method'] === strtoupper($method) && $route['path'] === $path) throw new \LogicException('Route trùng: ' . $path);
        $this->routes[] = ['method'=>strtoupper($method), 'path'=>$path, 'handler'=>$handler];
    }
    public function routes(): array { return $this->routes; }
    public function dispatch(Request $request): void
    {
        RequestSecurity::headers();
        try {
            Session::start();
            $allowed = [];
            foreach ($this->routes as $route) {
                $params = $this->match($route['path'], $request->path());
                if ($params === null) continue;
                $allowed[] = $route['method'];
                if ($route['method'] !== $request->method()) continue;
                // Không mở kết nối DB trong controller trước khi biết khách có phiên.
                $this->guard($request->path(), $request->method());
                if ($request->method() !== 'GET') {
                    RequestSecurity::checkWrite();
                    $input = $request->csrfToken();
                    if (!is_string($input)) throw new HttpError(403, 'Token không hợp lệ.');
                    Session::checkCsrf($input);
                    // All current forms/APIs have flat fields. Reject foo[]=... for
                    // every role before an array can be cast into a different record ID.
                    foreach ($request->all() as $value) {
                        if (($value!==null && !is_scalar($value)) || (is_string($value) && !mb_check_encoding($value,'UTF-8'))) throw new HttpError(422, 'Dữ liệu biểu mẫu không hợp lệ.');
                    }
                    RequestSecurity::limitWrite();
                    TurnstileGuard::check($request);
                }
                [$class, $action] = $route['handler'];
                (new $class())->{$action}($request, ...array_values($params));
                return;
            }
            if ($allowed) {
                header('Allow: ' . implode(', ', array_unique($allowed)));
                throw new HttpError(405, 'Phương thức không được hỗ trợ.');
            }
            throw new HttpError(404, 'Không tìm thấy trang yêu cầu.');
        } catch (\Throwable $error) {
            $businessError = $error instanceof \mysqli_sql_exception && $error->getCode() === 1644;
            $status = $error instanceof HttpError ? $error->status : ($businessError ? 422 : 503);
            if ($status === 503) error_log('CouponHub request failed: '.get_class($error).' status=503'); // No request bodies, secrets or exception arguments in logs.
            if ($status === 401 && $request->method() === 'GET' && !$request->expectsJson()) {
                $return = parse_url(url($request->path()), PHP_URL_PATH) ?: '/';
                if ($request->queryString() !== '') $return .= '?' . $request->queryString();
                Response::redirect(url('/login') . '?return=' . rawurlencode($return), 303);
            }
            $message = $error instanceof HttpError || $businessError ? $error->getMessage() : 'Chưa thể xử lý. Kiểm tra MySQL và SQL tích hợp trong database/README.md.';
            if ($request->expectsJson() || str_starts_with($request->path(), '/api/')) Response::json(['success'=>false, 'message'=>$message], $status);
            // View lỗi tối giản không truy vấn DB/Auth lần nữa khi DB đang hỏng.
            http_response_code($status);
            require dirname(__DIR__).'/views/errors/standalone.php';
        }
    }
    private function guard(string $path, string $method): void
    {
        $roles = null;
        if ($path === '/admin.php' || $path === '/admin' || str_starts_with($path, '/admin/')) $roles = ['ADMIN'];
        elseif ($path === '/merchant/apply') $roles = ['USER','MERCHANT'];
        elseif ($path === '/shop.php' || $path === '/merchant' || str_starts_with($path, '/merchant/')) $roles = ['MERCHANT'];
        elseif ($path === '/coupons' && $method === 'POST') $roles = ['USER','MERCHANT'];
        elseif (in_array($path, ['/notifications','/notifications/read','/dashboard','/account','/account/password','/my-coupons','/saved-coupons','/coupons/create'], true)
            || preg_match('~\A/(?:saved-coupons/[0-9]+/(?:remove|save)|api/coupons/[0-9]+/(?:favorite|vote|comments|reports))\z~', $path)) $roles = ['USER','MERCHANT','ADMIN'];
        if ($roles !== null) Auth::requireRole($roles);
    }
    private function match(string $route, string $path): ?array
    {
        $pattern = preg_quote($route, '~');
        $pattern = preg_replace('~\\\\\{([A-Za-z_][A-Za-z0-9_]*)\\\\\}~', '(?P<$1>[^/]+)', $pattern);
        if (!preg_match('~\A' . $pattern . '\z~D', $path, $matches)) return null;
        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}
