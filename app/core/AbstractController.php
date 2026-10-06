<?php
declare(strict_types=1);

/** Shared controller behavior; concrete controllers provide each page action. */
abstract class AbstractController
{
    public function __construct(
        protected readonly Database $db,
        protected readonly array $config,
        protected readonly Request $request,
        private readonly View $viewEngine
    ) {}

    abstract public function handle(): void;

    protected function render(string $template, array $data = []): void
    {
        $this->viewEngine->render($template, $data);
    }

    protected function requireMethod(string ...$allowed): string
    {
        $method = $this->request->method();
        if (!in_array($method, $allowed, true)) {
            header('Allow: ' . implode(', ', $allowed));
            throw new HttpError(405, 'Phương thức không được hỗ trợ.');
        }
        return $method;
    }

    protected function redirect(string $url): never
    {
        header('Location: ' . $url, true, 303);
        exit;
    }
}
