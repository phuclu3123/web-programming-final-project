<?php
declare(strict_types=1);

/** Immutable wrapper around one HTTP request. */
final class Request
{
    public function __construct(
        private readonly array $query,
        private readonly array $post,
        private readonly array $server
    ) {}

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

    private function stringValue(array $source, string $name, string $default, string $label): string
    {
        $value = $source[$name] ?? $default;
        if (!is_string($value)) {
            throw new HttpError(422, $label . ' không hợp lệ.');
        }
        return trim($value);
    }
}
