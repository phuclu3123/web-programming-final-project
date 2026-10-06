<?php
declare(strict_types=1);

/** Resolves templates from one fixed directory and exposes only assigned data. */
final class View
{
    public function __construct(private readonly string $basePath) {}

    public function render(string $template, array $data = []): void
    {
        $file = $this->resolve($template);
        extract($data, EXTR_SKIP);
        require $file;
    }

    private function resolve(string $template): string
    {
        if (!preg_match('/\A[a-z0-9\/_-]+\z/i', $template)) {
            throw new LogicException('Tên view không hợp lệ.');
        }

        $file = $this->basePath . DIRECTORY_SEPARATOR . $template . '.php';
        if (!is_file($file)) {
            throw new LogicException('Không tìm thấy view: ' . $template);
        }
        return $file;
    }
}
