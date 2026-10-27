<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\RewardExchangePolicy as Policy;

$checks=0;
function same(mixed $actual, mixed $expected, string $label): void {
    global $checks;
    if ($actual !== $expected) throw new RuntimeException($label . ': unexpected result ' . var_export($actual,true));
    $checks++;
}
function fails(callable $fn, string $label): void {
    global $checks;
    try { $fn(); } catch (DomainException $e) { $checks++; return; }
    throw new RuntimeException($label . ': expected DomainException');
}

same(Policy::balance(0,0),0,'empty wallet');
same(Policy::balance(515,0),515,'earned balance');
same(Policy::balance(515,500),15,'pending holds points');
same(Policy::balance(1000,500),500,'fulfilled spend');
same(Policy::balance(1000,0),1000,'cancelled release');
same(Policy::balance(10,15),0,'negative display clamped');
Policy::assertCanRedeem(500,0,500); $checks++;
Policy::assertCanRedeem(1010,500,500); $checks++;
fails(fn()=>Policy::assertCanRedeem(499,0,500),'insufficient');
fails(fn()=>Policy::assertCanRedeem(550,60,500),'reserved counts');
fails(fn()=>Policy::assertCanRedeem(500,0,0),'zero cost');
fails(fn()=>Policy::balance(-1,0),'negative earned');
fails(fn()=>Policy::balance(10,-1),'negative reserved');
same(Policy::idempotencyKey(str_repeat('a',32)),str_repeat('a',32),'safe token');
fails(fn()=>Policy::idempotencyKey(''), 'missing token');
fails(fn()=>Policy::idempotencyKey(str_repeat('A',32)), 'uppercase token');
fails(fn()=>Policy::idempotencyKey(str_repeat('a',31)."'"),'SQL injection token');
same(Policy::adminNote('  Đã chuyển voucher thành công  '),'Đã chuyển voucher thành công','admin note trim');
fails(fn()=>Policy::adminNote('short'),'note too short');
fails(fn()=>Policy::adminNote(str_repeat('x',501)),'note too long');

// Static wiring contract: request requires CSRF/role guard; wallet is not the Level source.
$root=dirname(__DIR__);
$routes=file_get_contents($root.'/config/routes.php');
$guard=file_get_contents($root.'/app/core/Router.php');
$migration=file_get_contents($root.'/database/migrations/phase10_reward_exchange.sql');
$repository=file_get_contents($root.'/app/repositories/RewardExchangeRepository.php');
$pointsSchema=file_get_contents($root.'/database/schema.sql');
foreach (['/rewards/redeem','/admin/rewards/{id}/review'] as $route) {
    if (!str_contains($routes,$route)) throw new RuntimeException('Missing route: '.$route);
    $checks++;
}
foreach (["$".'path === \'/rewards/redeem\'', "str_starts_with($".'path, \'/admin/\')'] as $rule) {
    if (!str_contains($guard,$rule)) throw new RuntimeException('Missing role guard: '.$rule);
    $checks++;
}
foreach (['FOR UPDATE','beginTransaction()','idempotency_key','PENDING','FULFILLED','CANCELLED'] as $feature) {
    if (!str_contains($repository,$feature)) throw new RuntimeException('Missing locking/ledger: '.$feature);
    $checks++;
}
foreach (['uq_reward_redeem_once','idx_reward_redemptions_wallet','fk_reward_redemption_user'] as $feature) {
    if (!str_contains($migration,$feature)) throw new RuntimeException('Missing schema: '.$feature);
    $checks++;
}
if (!str_contains($pointsSchema,'VIEW `v_user_level`') || !str_contains($pointsSchema,'`p`.`earned_points`')) {
    throw new RuntimeException('Level basis changed unexpectedly.');
}
$checks++;
echo "Phase 10 Reward Exchange: {$checks} checks PASS (without MySQL).\n";
