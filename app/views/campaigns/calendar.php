<?php
$calendarYear = (int)($selectedYear ?? date('Y'));
$calendarMonth = (int)($selectedMonth ?? 0);
$calendarOpen = (($_GET['calendarOpen'] ?? '') === '1');
$displayMonth = $calendarMonth ?: (int)date('n');
$calendarZone = new DateTimeZone((string)config('app.timezone', 'Asia/Ho_Chi_Minh'));
$monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $calendarYear, $displayMonth), $calendarZone);
$monthEnd = $monthStart->modify('first day of next month');
$gridStart = $monthStart->modify('monday this week');
$gridEnd = $monthEnd->modify('sunday this week');
$today = new DateTimeImmutable('today', $calendarZone);
$campaigns = is_array($campaigns ?? null) ? $campaigns : [];
$campaignColorIndex = [];
$campaignsByDay = [];

foreach ($campaigns as $campaign) {
    $campaignColorIndex[(int)$campaign['id']] = ((int)$campaign['id'] * 137) % 360;
    try {
        $startsAt = (new DateTimeImmutable((string)$campaign['starts_at'], new DateTimeZone('UTC')))->setTimezone($calendarZone);
        $endsAt = (new DateTimeImmutable((string)$campaign['ends_at'], new DateTimeZone('UTC')))->setTimezone($calendarZone);
    } catch (Throwable) {
        continue;
    }
    $firstDay = $startsAt->setTime(0, 0);
    $lastDay = $endsAt->setTime(0, 0);
    for ($day = $firstDay; $day <= $lastDay; $day = $day->modify('+1 day')) {
        $key = $day->format('Y-m-d');
        $campaignsByDay[$key][] = $campaign;
    }
}
$previousMonth = $monthStart->modify('-1 month');
$nextMonth = $monthStart->modify('+1 month');
$monthNames = [1=>'Tháng 1',2=>'Tháng 2',3=>'Tháng 3',4=>'Tháng 4',5=>'Tháng 5',6=>'Tháng 6',7=>'Tháng 7',8=>'Tháng 8',9=>'Tháng 9',10=>'Tháng 10',11=>'Tháng 11',12=>'Tháng 12'];
$weekdayNames = [1=>'Thứ hai',2=>'Thứ ba',3=>'Thứ tư',4=>'Thứ năm',5=>'Thứ sáu',6=>'Thứ bảy',7=>'Chủ nhật'];
?>
<div class="discovery-page editorial-calendar-page">
    <div class="member-breadcrumb">
        <a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><span>Lịch ưu đãi</span>
    </div>

    <header class="calendar-hero">
        <div class="calendar-hero-copy">
            <span class="eyebrow">LÊN KẾ HOẠCH MUA SẮM</span>
            <h1>Lịch ưu đãi <?= $calendarYear ?></h1>
            <p>Theo dõi các chiến dịch đang diễn ra và sắp bắt đầu — chọn một ngày để xem chi tiết.</p>
        </div>
        <a href="<?= e(url('/campaigns')) ?>" class="calendar-campaign-link">Xem danh sách chiến dịch <?= icon('arrow') ?></a>
    </header>

    <p class="calendar-feedback" data-calendar-feedback role="status" aria-live="polite" hidden></p>

    <details class="calendar-disclosure" <?= $calendarOpen ? 'open' : '' ?>>
        <summary class="calendar-disclosure-trigger">
            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
            <span>Xem lịch</span>
        </summary>
    <section class="calendar-shell" aria-label="Lịch chiến dịch">
        <div class="calendar-toolbar">
            <div class="calendar-period" aria-live="polite">
                <span class="calendar-period-month"><?= e($monthNames[$displayMonth]) ?></span>
                <span class="calendar-period-year"><?= $calendarYear ?></span>
            </div>
            <div class="calendar-controls" aria-label="Điều hướng lịch">
                <a class="calendar-icon-button" href="<?= e(url('/campaign-calendar?year='.$previousMonth->format('Y').'&month='.$previousMonth->format('n').'&calendarOpen=1')) ?>" aria-label="Tháng trước" title="Tháng trước">‹</a>
                <a class="calendar-today-button" href="<?= e(url('/campaign-calendar?year='.$today->format('Y').'&month='.$today->format('n').'&calendarOpen=1')) ?>">Hôm nay</a>
                <a class="calendar-icon-button" href="<?= e(url('/campaign-calendar?year='.$nextMonth->format('Y').'&month='.$nextMonth->format('n').'&calendarOpen=1')) ?>" aria-label="Tháng sau" title="Tháng sau">›</a>
            </div>
        </div>

        <form class="calendar-filter-bar" method="GET" action="<?= e(url('/campaign-calendar')) ?>">
            <input type="hidden" name="calendarOpen" value="1">
            <label class="calendar-filter-field" for="filter-month"><span>Tháng</span>
                <select id="filter-month" name="month" class="form-select">
                    <option value="">Cả năm</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $displayMonth === $m && $calendarMonth !== 0 ? 'selected' : '' ?>><?= e($monthNames[$m]) ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <label class="calendar-filter-field" for="filter-year"><span>Năm</span>
                <select id="filter-year" name="year" class="form-select">
                    <?php for ($y = 2020; $y <= 2035; $y++): ?>
                        <option value="<?= $y ?>" <?= $calendarYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <div class="calendar-filter-actions">
                <button type="submit" class="btn-calendar-filter">Áp dụng</button>
                <?php if ($calendarMonth !== 0 || $calendarYear !== (int)date('Y')): ?>
                    <a href="<?= e(url('/campaign-calendar?calendarOpen=1')) ?>" class="btn-calendar-reset">Đặt lại</a>
                <?php endif; ?>
            </div>
            <div class="calendar-legend" aria-label="Chú giải trạng thái">
                <span><i class="calendar-legend-dot is-running"></i>Đang diễn ra</span>
                <span><i class="calendar-legend-dot is-upcoming"></i>Sắp diễn ra</span>
            </div>
        </form>

        <div class="calendar-weekdays" aria-hidden="true">
            <?php foreach (['Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7','Chủ nhật'] as $weekday): ?><span><?= e($weekday) ?></span><?php endforeach; ?>
        </div>
        <div class="calendar-grid" role="group" aria-label="Các ngày trong tháng">
            <?php for ($day = $gridStart; $day <= $gridEnd; $day = $day->modify('+1 day')):
                $dayKey = $day->format('Y-m-d');
                $dayCampaigns = $campaignsByDay[$dayKey] ?? [];
                $inMonth = $day->format('n') === (string)$displayMonth;
                $isToday = $day->format('Y-m-d') === $today->format('Y-m-d');
            ?>
                <details class="calendar-day<?= $inMonth ? '' : ' is-outside' ?><?= $isToday ? ' is-today' : '' ?>" <?= $dayCampaigns ? '' : 'data-empty-day="true"' ?>>
                    <summary aria-label="<?= e($day->format('d/m/Y')) ?>, <?= count($dayCampaigns) ?> chiến dịch">
                        <span class="calendar-day-number"><?= $day->format('j') ?></span>
                        <?php if ($isToday): ?><span class="calendar-today-label">Hôm nay</span><?php endif; ?>
                        <?php foreach (array_slice($dayCampaigns, 0, 2) as $campaign): $upcoming = campaignTiming($campaign)['upcoming']; $campaignHue = $campaignColorIndex[(int)$campaign['id']] ?? 215; ?>
                            <span class="calendar-event-chip <?= $upcoming ? 'is-upcoming' : 'is-running' ?>" style="--campaign-accent:hsl(<?= $campaignHue ?> 58% 43%)"><?= e($campaign['name']) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($dayCampaigns) > 2): ?><span class="calendar-more">+<?= count($dayCampaigns) - 2 ?> ưu đãi</span><?php endif; ?>
                    </summary>
                    <div class="calendar-day-detail">
                        <div class="calendar-day-detail-heading">
                            <strong><?= e($weekdayNames[(int)$day->format('N')].', '.$day->format('d/m/Y')) ?></strong>
                            <span><?= count($dayCampaigns) ?> chiến dịch</span>
                        </div>
                        <?php if ($dayCampaigns): foreach ($dayCampaigns as $campaign): $upcoming = campaignTiming($campaign)['upcoming']; $campaignHue = $campaignColorIndex[(int)$campaign['id']] ?? 215; ?>
                            <a class="calendar-detail-event" href="<?= e(url('/campaigns/'.(int)$campaign['id'])) ?>">
                                <i class="calendar-legend-dot <?= $upcoming ? 'is-upcoming' : 'is-running' ?>" style="--campaign-accent:hsl(<?= $campaignHue ?> 58% 43%)"></i>
                                <span><strong><?= e($campaign['name']) ?></strong><small><?= e($campaign['merchant_name'] ?? 'CouponHub') ?> · <?= e(format_db_datetime($campaign['starts_at']  )) ?> – <?= e(format_db_datetime($campaign['ends_at'])) ?></small></span>
                                <?= icon('arrow') ?>
                            </a>
                        <?php endforeach; else: ?>
                            <p class="calendar-day-empty">Chưa có chiến dịch trong ngày này.</p>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endfor; ?>
        </div>
        <p class="calendar-selection-hint">Chọn một ngày để xem các chiến dịch và thời gian áp dụng.</p>
    </section>
    </details>

    <section class="calendar-campaigns" aria-labelledby="calendar-campaigns-title">
        <div class="calendar-section-heading">
            <div>
                <span class="eyebrow">THEO DÕI ƯU ĐÃI</span>
                <h2 id="calendar-campaigns-title">Đang diễn ra &amp; Sắp tới</h2>
            </div>
            <span class="calendar-section-count"><?= count($campaigns) ?> chiến dịch</span>
        </div>
        <?php if ($campaigns): ?>
            <div class="calendar-campaign-list">
                <?php foreach ($campaigns as $campaign): $upcoming = campaignTiming($campaign)['upcoming']; $campaignHue = $campaignColorIndex[(int)$campaign['id']] ?? 215; ?>
                    <article class="calendar-campaign-card <?= $upcoming ? 'is-upcoming' : 'is-running' ?>" style="--campaign-accent:hsl(<?= $campaignHue ?> 58% 43%)">
                        <div class="calendar-campaign-date" aria-label="Bắt đầu ngày <?= e(format_db_datetime($campaign['starts_at'], 'd/m/Y')) ?>">
                            <strong><?= e(format_db_datetime($campaign['starts_at'], 'd')) ?></strong>
                            <span><?= e($monthNames[(int)format_db_datetime($campaign['starts_at'], 'n')]) ?></span>
                        </div>
                        <div class="calendar-campaign-content">
                            <div class="calendar-campaign-meta">
                                <span class="calendar-status-pill <?= $upcoming ? 'is-upcoming' : 'is-running' ?>"><?= $upcoming ? 'Sắp diễn ra' : 'Đang diễn ra' ?></span>
                                <span class="calendar-merchant-pill"><?= e($campaign['merchant_name'] ?? 'CouponHub') ?></span>
                            </div>
                            <h3><?= e($campaign['name']) ?></h3>
                            <p><?= e(format_db_datetime($campaign['starts_at'])) ?> – <?= e(format_db_datetime($campaign['ends_at'])) ?></p>
                        </div>
                        <a class="calendar-detail-button" href="<?= e(url('/campaigns/'.(int)$campaign['id'])) ?>">Chi tiết <span aria-hidden="true">→</span></a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="calendar-campaign-empty">
                <span class="calendar-empty-icon" aria-hidden="true"><i class="fa-regular fa-calendar"></i></span>
                <strong>Chưa có chiến dịch trong khoảng thời gian này</strong>
                <p>Thử chọn tháng hoặc năm khác để khám phá các ưu đãi.</p>
            </div>
        <?php endif; ?>
    </section>

    <section class="calendar-holidays" aria-labelledby="calendar-holidays-title">
        <h2 id="calendar-holidays-title">Những ngày đáng lưu ý</h2>
        <p>Ngày lễ tham khảo, không phải cam kết có chương trình giảm giá.</p>
        <?php if ($holidays): ?><div class="calendar-holiday-list">
            <?php foreach ($holidays as $holiday):
                $holidayDate = new DateTimeImmutable((string)$holiday['date'], $calendarZone);
                $holidayName = (string)$holiday['name'];
                $holidayIcon = 'fa-solid fa-calendar-day';
                if (str_contains(mb_strtolower($holidayName), 'tết')) $holidayIcon = 'fa-solid fa-gift';
                elseif (str_contains(mb_strtolower($holidayName), 'quốc khánh')) $holidayIcon = 'fa-solid fa-flag';
            ?>
                <article class="calendar-holiday-card">
                    <time datetime="<?= e($holidayDate->format('Y-m-d')) ?>"><?= e($holidayDate->format('d/m')) ?></time>
                    <span class="calendar-holiday-icon" aria-hidden="true"><i class="<?= e($holidayIcon) ?>"></i></span>
                    <strong><?= e($holidayName) ?></strong>
                </article>
            <?php endforeach; ?>
        </div><?php else: ?>
            <div class="calendar-holiday-empty">Chưa có dữ liệu ngày lễ cho khoảng thời gian này.</div>
        <?php endif; ?>
    </section>
</div>
<script src="<?= e(asset('/js/deal-calendar.js')) ?>" defer></script>
