<?php
declare(strict_types=1);

namespace App\Core;



/** Immutable wrapper around one HTTP request. */
final class Request
{
    private readonly array $query;
    private readonly array $post;
    private readonly array $server;
    private ?array $json = null;
    public function __construct(
        ?array $query = null,
        ?array $post = null,
        ?array $server = null
    ) {
        $this->query = $query ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->server = $server ?? $_SERVER;
    }

    public static function fromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function query(string $name, string $default = ''): string
    {
        return $this->stringValue($this->query, $name, $default, 'Tham số URL');
    }

    public function post(string $name, string $default = ''): string
    {
        return $this->stringValue($this->post, $name, $default, 'Dữ liệu biểu mẫu');
    }

    public function isAjax(): bool
    {
        return strtolower((string) ($this->server['HTTP_X_REQUESTED_WITH'] ?? '')) === 'couponhub';
    }

    // ---- ROUTER QUANG: cùng một request cho route, form và JSON ----
    public function path(): string
    {
        $path = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = parse_url((string) config('app.base_url', ''), PHP_URL_PATH) ?: '';
        $base = rtrim($base, '/');
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) $path = substr($path, strlen($base));
        if ($path === '/index.php') $path = '/';
        return rtrim('/' . ltrim($path, '/'), '/') ?: '/';
    }
    public function all(): array
    {
        if (!$this->isJson()) return array_merge($this->query, $this->post);
        if ($this->json === null) {
            $decoded = json_decode(file_get_contents('php://input') ?: '', true);
            if (!is_array($decoded)) throw new HttpError(400, 'JSON không hợp lệ.');
            $this->json = $decoded;
        }
        return array_merge($this->query, $this->json);
    }
    public function input(string $name, mixed $default = null): mixed { return $this->all()[$name] ?? $default; }
    public function queryString(): string { return http_build_query($this->query, '', '&', PHP_QUERY_RFC3986); }
    public function isJson(): bool { return str_contains(strtolower($this->server['CONTENT_TYPE'] ?? ''), 'application/json'); }
    public function expectsJson(): bool
    {
        return str_contains(strtolower($this->server['HTTP_ACCEPT'] ?? ''), 'application/json')
            || in_array(strtolower($this->server['HTTP_X_REQUESTED_WITH'] ?? ''), ['xmlhttprequest', 'couponhub'], true)
            || str_starts_with($this->path(), '/api/');
    }

    private function stringValue(array $source, string $name, string $default, string $label): string
    {
        $value = $source[$name] ?? $default;
        if (!is_string($value)) {
            throw new HttpError(422, $label . ' không hợp lệ.');
        }
        return trim($value);
    }
}
