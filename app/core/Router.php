<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int,array{method:string,path:string,handler:array{0:string,1:string}}> */
    private array $routes = [];

    /** @param array{0:string,1:string} $handler */
    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /** @param array{0:string,1:string} $handler */
    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /** @param array{0:string,1:string} $handler */
    public function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalize($path),
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            $params = $this->match($route['path'], $request->path());
            if ($params === null) {
                continue;
            }

            [$controllerClass, $action] = $route['handler'];
            if (!class_exists($controllerClass)) {
                throw new \RuntimeException('Controller không tồn tại: ' . $controllerClass);
            }

            $controller = new $controllerClass();
            if (!method_exists($controller, $action)) {
                throw new \RuntimeException('Action không tồn tại: ' . $action);
            }

            $controller->{$action}($request, ...array_values($params));
            return;
        }

        http_response_code(404);
        $controller = new class extends Controller {};
        $controller->renderError(404, 'Không tìm thấy trang bạn yêu cầu.');
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    /** @return array<string,string>|null */
    private function match(string $routePath, string $requestPath): ?array
    {
        if ($routePath === '/' && $requestPath === '/') {
            return [];
        }

        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $m): string {
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $routePath);

        $pattern = '#^' . $pattern . '$#';
        if (!preg_match($pattern, $requestPath, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = urldecode((string) $value);
            }
        }
        return $params;
    }
}
