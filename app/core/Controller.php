<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /** null giữ nguyên các template đã có đủ head/body; các trang khác dùng layout chung. */
    protected function view(string $view, array $data = [], ?string $layout = 'main'): void
    {
        $viewFile = dirname(__DIR__) . '/views/' . $view . '.php';
        $layoutFile = $layout === null ? null : dirname(__DIR__) . '/views/layouts/' . $layout . '.php';

        if (!is_file($viewFile) || ($layoutFile !== null && !is_file($layoutFile))) {
            throw new \RuntimeException('View hoặc layout không tồn tại.');
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $viewFile;
            $content = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
        if ($layoutFile === null) echo $content;
        else require $layoutFile;
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
