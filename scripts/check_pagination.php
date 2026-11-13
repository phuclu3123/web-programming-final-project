<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/Pagination.php';

use App\Core\Pagination;

function check(bool $condition, string $name): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $name);
    }
    echo 'PASS: ', $name, PHP_EOL;
}

$cases = [
    ['empty result', 0, 1, 20, 1, 1, 0],
    ['first page', 41, 1, 20, 1, 3, 0],
    ['middle page', 41, 2, 20, 2, 3, 20],
    ['last page', 41, 3, 20, 3, 3, 40],
    ['requested page exceeds range', 41, 9999, 20, 3, 3, 40],
    ['requested page is negative', 41, -4, 20, 1, 3, 0],
    ['page size larger than result count', 2, 1, 50, 1, 1, 0],
];

foreach ($cases as [$name, $total, $requestedPage, $pageSize, $expectedPage, $expectedPages, $expectedOffset]) {
    $result = Pagination::fromTotal($total, $requestedPage, $pageSize);
    check(
        $result['total'] === $total &&
            $result['page'] === $expectedPage &&
            $result['pages'] === $expectedPages &&
            $result['pageSize'] === $pageSize &&
            $result['offset'] === $expectedOffset,
        $name
    );
}

try {
    Pagination::fromTotal(10, 1, 0);
    throw new RuntimeException('FAIL: zero page size must be rejected');
} catch (InvalidArgumentException) {
    echo 'PASS: zero page size rejected', PHP_EOL;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function renderPagination(array $pageNav): string
{
    ob_start();
    try {
        require __DIR__ . '/../app/views/partials/pagination-links.php';
        return (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
}

$example = [
    'page' => 2,
    'pages' => 4,
    'ariaLabel' => 'Trang chiến dịch nguồn',
    'makeUrl' => static fn(int $page): string => '/admin/integrations/campaigns?' . http_build_query(['page' => $page, 'state' => 'PENDING']),
];
$html = renderPagination($example);
check(str_contains($html, 'aria-current="page"') && str_contains($html, 'page=3&amp;state=PENDING'), 'partial keeps active page and filtered links');
check(substr_count($html, 'href=') === 4, 'partial displays bounded page window');
check(renderPagination($example) === $html, 'partial can be required multiple times');
check(renderPagination([...$example, 'pages' => 1]) === '', 'partial hidden on a single page');