<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    /** @param array<string,mixed> $data */
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewFile = dirname(__DIR__) . '/views/' . $view . '.php';
        $layoutFile = dirname(__DIR__) . '/views/layouts/' . $layout . '.php';

        if (!is_file($viewFile) || !is_file($layoutFile)) {
            throw new \RuntimeException('View hoặc layout không tồn tại.');
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();
        require $layoutFile;
    }

    public function renderError(int $status, string $message): void
    {
        http_response_code($status);
        $this->view('errors/http', [
            'pageTitle' => 'Lỗi ' . $status,
            'status' => $status,
            'message' => $message,
        ]);
    }
}
