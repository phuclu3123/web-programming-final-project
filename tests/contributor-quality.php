<?php

declare(strict_types=1);

require __DIR__ . '/../app/services/ContributorQualityService.php';

use App\Services\ContributorQualityService;

$cases = [
    'No submissions' => [['submitted_count'=>0,'approved_count'=>0,'rejected_count'=>0], ['reviewed'=>0,'pending'=>0,'approvalRate'=>null]],
    'Only pending' => [['submitted_count'=>7,'approved_count'=>0,'rejected_count'=>0], ['reviewed'=>0,'pending'=>7,'approvalRate'=>null]],
    'Brief example: 45 approvals of 50 reviewed' => [['submitted_count'=>50,'approved_count'=>45,'rejected_count'=>5], ['reviewed'=>50,'pending'=>0,'approvalRate'=>90]],
    'Pending omitted from approval denominator' => [['submitted_count'=>20,'approved_count'=>8,'rejected_count'=>2], ['reviewed'=>10,'pending'=>10,'approvalRate'=>80]],
    'All rejected' => [['submitted_count'=>3,'approved_count'=>0,'rejected_count'=>3], ['reviewed'=>3,'pending'=>0,'approvalRate'=>0]],
];

foreach ($cases as $name => [$input, $expected]) {
    $actual = ContributorQualityService::fromSummary($input);
    if ($actual !== $expected) throw new RuntimeException("FAIL: {$name}; " . var_export($actual, true));
}
echo 'PASS: reviewed-only approval rate and pending count (' . count($cases) . " cases, no database).\n";
