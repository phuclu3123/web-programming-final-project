<?php
declare(strict_types=1);
if (getenv('COUPONHUB_DB') !== 'couponhub_merge_test' || getenv('COUPONHUB_PORT') !== '13379') exit("SKIP: isolated test DB required.\n");
require dirname(__DIR__).'/app/bootstrap.php';
session_save_path(sys_get_temp_dir());
\App\Core\Session::start();
$db = \App\Core\Database::connection();
$check = static function(bool $ok, string $label): void { if (!$ok) throw new RuntimeException($label); };
$campaigns = new \App\Repositories\CampaignRepository($db);
$users = new \App\Repositories\UserRepository($db);
$notifications = new \App\Services\NotificationService(new \App\Repositories\NotificationRepository($db));
$admin = $users->findByLogin('admin_demo');
$user = $users->findByLogin('user_demo');
$other = $users->findByLogin('user_community');
$platform = (int)$db->query("SELECT id FROM campaigns WHERE slug='demo-couponhub-week'")->fetchColumn();
$shop = (int)$db->query("SELECT id FROM campaigns WHERE slug='demo-shopee-official'")->fetchColumn();
$shopping = (int)$db->query("SELECT id FROM categories WHERE slug='mua-sam'")->fetchColumn();
$beauty = (int)$db->query("SELECT id FROM categories WHERE slug='lam-dep'")->fetchColumn();
$both = (int)$db->query("SELECT id FROM coupons WHERE code='DEMO_HASAKI_15'")->fetchColumn();
if (!$both) $both = (int)$db->query("SELECT id FROM coupons WHERE usage_channel='BOTH' AND category_id=$beauty LIMIT 1")->fetchColumn();
$db->beginTransaction();
try {
    $check($both > 0, 'Fixture BOTH coupon');
    $db->prepare('INSERT INTO campaign_coupons(campaign_id,coupon_id,added_by) VALUES(?,?,?)')->execute([$platform,$both,(int)$admin['id']]);
    $listing = $campaigns->publicFilteredListing('ALL', $beauty, 'OFFLINE', 999);
    $check($listing['total']===1 && $listing['page']===1 && (int)$listing['items'][0]['id']===$platform, 'Campaign filter matches category and includes BOTH in OFFLINE');
    $check((int)$listing['items'][0]['coupon_count']===1, 'Filtered campaign count agrees with coupon results');
    $items = $campaigns->publicCoupons($platform,$beauty,'OFFLINE');
    $check(count($items)===1 && (int)$items[0]['id']===$both, 'Preview/detail filters agree');
    $check(count($campaigns->publicCoupons($platform,$beauty,'ONLINE'))===1, 'BOTH available online too');
    $check(count($campaigns->publicCoupons($platform,$beauty,'BOTH'))===1, 'Exact BOTH filter');
    $check($campaigns->publicCoupons($shop,$beauty,'OFFLINE')===[], 'No unrelated shop coupons');
    $paged = $campaigns->publicFilteredListing('ALL',0,'ALL',2,1);
    $check($paged['pages']===2 && $paged['page']===2 && count($paged['items'])===1, 'Filtered listing pagination');
    $db->prepare("UPDATE campaigns SET status='DRAFT' WHERE id=?")->execute([$platform]);
    $check($campaigns->publicCoupons($platform)===[], 'Draft cannot expose coupon list');
    $check($campaigns->publicFilteredListing('ALL',$beauty,'OFFLINE',1)['total']===0, 'Draft excluded from filtered listing');
    $db->prepare("UPDATE campaigns SET status='PUBLISHED',starts_at=UTC_TIMESTAMP()+INTERVAL 2 DAY WHERE id=?")->execute([$platform]);
    $check($campaigns->publicFilteredListing('UPCOMING',0,'ALL',1)['total']===1, 'Upcoming phase');
    $check($campaigns->publicFilteredListing('ACTIVE',0,'ALL',1)['total']===1, 'Active phase');
    $db->prepare("UPDATE campaigns SET ends_at=UTC_TIMESTAMP()-INTERVAL 1 DAY WHERE id=?")->execute([$platform]);
    $check($campaigns->publicCoupons($platform)===[], 'Expired parent excluded');
    $shopOwner = (int)$db->query("SELECT owner_merchant_id FROM campaigns WHERE id=$shop")->fetchColumn();
    $db->prepare("UPDATE merchants SET status='LOCKED' WHERE id=?")->execute([$shopOwner]);
    $check($campaigns->findPublic($shop)===null && $campaigns->publicCoupons($shop)===[], 'Locked shop campaign excluded');
    $db->prepare("UPDATE merchants SET status='APPROVED' WHERE id=?")->execute([$shopOwner]);
    // A UTC date crossing midnight is selected by its local Vietnam month.
    $db->prepare("UPDATE campaigns SET starts_at='2030-01-31 18:00:00',ends_at='2030-02-02 18:00:00' WHERE id=?")->execute([$platform]);
    $check(in_array($platform,array_map('intval',array_column($campaigns->publicCalendar(2030,2),'id')),true), 'Calendar uses Vietnam month boundaries');
    $check(!in_array($platform,array_map('intval',array_column($campaigns->publicCalendar(2030,1),'id')),true), 'No campaign before Vietnam month starts');

    $feed = $notifications->feed($user);
    $check(count($feed)>0 && count(array_filter($feed,static fn($n)=>$n['unread']))>0, 'Real review notifications exist');
    $otherKeys = array_column($notifications->feed($other),'event_key');
    $check(!array_intersect(array_column($feed,'event_key'),$otherKeys), 'Notification identity isolated per user');
    $notifications->markAllRead($user);
    $check(!array_filter($notifications->feed($user),static fn($n)=>$n['unread']), 'Read receipts survive repeated feed reads');
    $check((bool)array_filter($notifications->feed($other),static fn($n)=>$n['unread']), 'Read receipts do not affect other users');
    $adminFeed = $notifications->feed($admin);
    $check((bool)array_filter($adminFeed,static fn($n)=>$n['path']==='/admin/reports'), 'Admin report notification from actual records');
} finally {
    $db->rollBack();
    \App\Core\Session::logout();
}
echo "PASS: campaign filters/counts/pagination, BOTH channels, parent privacy, calendar UTC boundaries and actor-scoped notifications/read receipts.\n";
