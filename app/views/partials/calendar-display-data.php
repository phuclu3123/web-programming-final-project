<?php
$calendarYear = (int)($selectedYear ?? date('Y'));
$calendarMonth = (int)($selectedMonth ?? 0);
$calendarView = ($_GET['view'] ?? '') === 'month' || (!isset($_GET['view']) && ($_GET['calendarOpen'] ?? '') === '1') ? 'month' : 'quick';
$quickFilter = is_string($_GET['status'] ?? null) && in_array($_GET['status'], ['active','ending','upcoming'], true) ? $_GET['status'] : 'active';
$displayMonth = $calendarMonth ?: (int)date('n');
if ($calendarView === 'month') $calendarMonth = $displayMonth;
$calendarZone = new DateTimeZone((string)config('app.timezone', 'Asia/Ho_Chi_Minh'));
$monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $calendarYear, $displayMonth), $calendarZone);
$monthEnd = $monthStart->modify('first day of next month');
$gridStart = $monthStart->modify('monday this week');
$gridEnd = $monthEnd->modify('-1 day')->modify('sunday this week');
$today = new DateTimeImmutable('today', $calendarZone);
$calendarNow = (int)round(microtime(true) * 1000);
$campaigns = is_array($campaigns ?? null) ? $campaigns : [];
$campaignsByDay = $milestonesByDay = [];
$quickGroups = ['active'=>[], 'ending'=>[], 'upcoming'=>[]];
foreach ($campaigns as $calendarCampaign) {
    try {
        $startsAt = (new DateTimeImmutable((string)$calendarCampaign['starts_at'], new DateTimeZone('UTC')))->setTimezone($calendarZone);
        $endsAt = (new DateTimeImmutable((string)$calendarCampaign['ends_at'], new DateTimeZone('UTC')))->setTimezone($calendarZone);
    } catch (Throwable) { continue; }
    if ($endsAt <= $startsAt) continue;
    $calendarCampaign['_start'] = $startsAt->getTimestamp() * 1000;
    $calendarCampaign['_end'] = $endsAt->getTimestamp() * 1000;
    if ($calendarCampaign['_start'] > $calendarNow) $quickGroups['upcoming'][] = $calendarCampaign;
    elseif ($calendarCampaign['_end'] > $calendarNow) {
        $quickGroups['active'][] = $calendarCampaign;
        if ($calendarCampaign['_end'] - $calendarNow <= 48 * 3600000) $quickGroups['ending'][] = $calendarCampaign;
    }
    foreach (['start'=>$startsAt, 'end'=>$endsAt] as $kind=>$instant) {
        $milestonesByDay[$instant->format('Y-m-d')][] = ['kind'=>$kind, 'campaign'=>$calendarCampaign];
    }
    // Preserve UTC conversion and the exclusive end of the existing overlap query.
    $firstDay = max($startsAt->setTime(0, 0), $gridStart);
    $lastDay = min($endsAt->modify('-1 microsecond')->setTime(0, 0), $gridEnd);
    for ($day = $firstDay; $day <= $lastDay; $day = $day->modify('+1 day')) $campaignsByDay[$day->format('Y-m-d')][] = $calendarCampaign;
}
foreach ($quickGroups as $kind=>&$group) {
    $sortField = $kind === 'upcoming' ? '_start' : '_end';
    usort($group, static fn($a,$b) => ($a[$sortField] <=> $b[$sortField]) ?: ((int)$a['id'] <=> (int)$b['id']));
}
unset($group);
foreach ($campaignsByDay as &$dayRows) usort($dayRows, static fn($a,$b) => ($a['_end'] <=> $b['_end']) ?: ((int)$a['id'] <=> (int)$b['id']));
unset($dayRows);
$previousMonth = $monthStart->modify($calendarMonth ? '-1 month' : '-1 year');
$nextMonth = $monthStart->modify($calendarMonth ? '+1 month' : '+1 year');
$monthNames = [1=>'Tháng 1',2=>'Tháng 2',3=>'Tháng 3',4=>'Tháng 4',5=>'Tháng 5',6=>'Tháng 6',7=>'Tháng 7',8=>'Tháng 8',9=>'Tháng 9',10=>'Tháng 10',11=>'Tháng 11',12=>'Tháng 12'];
$weekdayNames = [1=>'Thứ hai',2=>'Thứ ba',3=>'Thứ tư',4=>'Thứ năm',5=>'Thứ sáu',6=>'Thứ bảy',7=>'Chủ nhật'];
$selectedCalendarDay = $today->format('Y-m') === $monthStart->format('Y-m') ? $today : $monthStart;
$calendarLink = static fn(array $changes=[]) => url('/campaign-calendar?'.http_build_query(array_replace(['year'=>$calendarYear,'month'=>$calendarMonth,'view'=>$calendarView,'status'=>$quickFilter],$changes)));
$quickLabels = ['active'=>'Đang diễn ra', 'ending'=>'Sắp kết thúc', 'upcoming'=>'Sắp bắt đầu'];
$quickNotes = ['active'=>'Chiến dịch gần kết thúc được xếp trước.', 'ending'=>'Kết thúc trong 48 giờ tới, gần hết hạn xếp trước.', 'upcoming'=>'Chiến dịch gần bắt đầu được xếp trước.'];
$scopeLabel = $calendarMonth ? $monthNames[$displayMonth].' / '.$calendarYear : 'Cả năm '.$calendarYear;
