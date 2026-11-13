<?php require __DIR__.'/../partials/calendar-display-data.php'; ?>
<div class="discovery-page editorial-calendar-page campaign-planner-page" data-calendar-view="<?= $calendarView ?>" data-calendar-now="<?= $calendarNow ?>">
    <div class="member-breadcrumb"><a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><span>Lịch ưu đãi</span></div>
    <header class="calendar-hero">
        <div class="calendar-hero-copy"><h1>Lịch ưu đãi</h1><p>Tìm chiến dịch, chọn ngày ưu đãi.</p></div>
        <a href="<?= e(url('/campaigns')) ?>" class="calendar-campaign-link">Khám phá chiến dịch <?= icon('arrow') ?></a>
    </header>
    <nav class="calendar-view-switch" aria-label="Cách xem ưu đãi">
        <a id="calendar-view-quick" data-calendar-navigate href="<?= e($calendarLink(['view'=>'quick'])) ?>" <?= $calendarView === 'quick' ? 'aria-current="page"' : '' ?>>Xem nhanh</a>
        <a id="calendar-view-month" data-calendar-navigate href="<?= e($calendarLink(['view'=>'month','month'=>$displayMonth])) ?>" <?= $calendarView === 'month' ? 'aria-current="page"' : '' ?>>Lịch tháng</a>
    </nav>
    <p class="calendar-feedback" data-calendar-feedback role="status" aria-live="polite" hidden></p>
    <p class="visually-hidden" data-calendar-selection-status role="status" aria-live="polite"></p>
    <div class="calendar-period-tools">
        <div class="calendar-toolbar">
            <div class="calendar-period"><span class="calendar-period-month"><?= e($calendarMonth ? $monthNames[$displayMonth] : 'Cả năm') ?></span><span class="calendar-period-year"><?= $calendarYear ?></span></div>
            <div class="calendar-controls" aria-label="Điều hướng tháng">
                <?php if ((int)$previousMonth->format('Y') >= 2020): ?><a href="<?= e($calendarLink(['year'=>(int)$previousMonth->format('Y'),'month'=>$calendarMonth ? (int)$previousMonth->format('n') : 0])) ?>" aria-label="<?= $calendarMonth ? 'Tháng trước' : 'Năm trước' ?>">‹</a><?php else: ?><span aria-disabled="true" aria-label="Đã đến đầu khoảng lịch">‹</span><?php endif; ?>
                <a class="calendar-today-button" href="<?= e($calendarLink(['year'=>(int)$today->format('Y'),'month'=>(int)$today->format('n')])) ?>">Hôm nay</a>
                <?php if ((int)$nextMonth->format('Y') <= 2035): ?><a href="<?= e($calendarLink(['year'=>(int)$nextMonth->format('Y'),'month'=>$calendarMonth ? (int)$nextMonth->format('n') : 0])) ?>" aria-label="<?= $calendarMonth ? 'Tháng sau' : 'Năm sau' ?>">›</a><?php else: ?><span aria-disabled="true" aria-label="Đã đến cuối khoảng lịch">›</span><?php endif; ?>
            </div>
        </div>
        <form class="calendar-filter-bar" method="GET" action="<?= e(url('/campaign-calendar')) ?>">
            <input type="hidden" name="view" value="<?= $calendarView ?>"><input type="hidden" name="status" value="<?= $quickFilter ?>">
            <label class="calendar-filter-field" for="filter-month"><span>Tháng</span><select id="filter-month" name="month" class="form-select">
                <?php if ($calendarView === 'quick'): ?><option value="0" <?= $calendarMonth === 0 ? 'selected' : '' ?>>Cả năm</option><?php endif; ?>
                <?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $displayMonth === $m && ($calendarMonth !== 0 || $calendarView === 'month') ? 'selected' : '' ?>><?= e($monthNames[$m]) ?></option><?php endfor; ?>
            </select></label>
            <label class="calendar-filter-field" for="filter-year"><span>Năm</span><select id="filter-year" name="year" class="form-select">
                <?php for ($y=2020;$y<=2035;$y++): ?><option value="<?= $y ?>" <?= $calendarYear === $y ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?>
            </select></label>
            <div class="calendar-filter-actions"><button type="submit" class="btn-calendar-filter">Áp dụng</button></div>
        </form>
    </div>
    <?php if ($calendarView === 'quick'): ?>
    <section class="calendar-quick-view" aria-labelledby="calendar-quick-heading">
        <nav class="calendar-quick-filters" aria-label="Lọc trạng thái chiến dịch">
            <?php foreach ($quickLabels as $key=>$label): ?><a id="calendar-status-<?= $key ?>" data-calendar-navigate href="<?= e($calendarLink(['status'=>$key])) ?>" <?= $quickFilter === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?><span><?= count($quickGroups[$key]) ?></span></a><?php endforeach; ?>
        </nav>
        <div class="calendar-quick-heading"><div><h2 id="calendar-quick-heading"><?= count($quickGroups[$quickFilter]) ?> chiến dịch</h2><p><?= e($quickNotes[$quickFilter]) ?></p></div></div>
        <?php if ($quickGroups[$quickFilter]): ?><div class="calendar-quick-list">
            <?php foreach ($quickGroups[$quickFilter] as $rowCampaign): $rowCompact=false; require __DIR__.'/../partials/calendar-campaign-row.php'; endforeach; ?>
        </div><?php else: ?>
            <div class="calendar-campaign-empty"><strong><?= $quickFilter === 'ending' ? 'Không có chiến dịch kết thúc trong 48 giờ tới.' : ($quickFilter === 'upcoming' ? 'Chưa có chiến dịch sắp bắt đầu trong khoảng đã chọn.' : 'Không có chiến dịch đang diễn ra trong khoảng đã chọn.') ?></strong><p>Khoảng đang xem: <?= e($scopeLabel) ?>.</p><a data-calendar-navigate href="<?= e($calendarLink(['view'=>'month','month'=>$displayMonth])) ?>">Xem lịch tháng <?= icon('arrow') ?></a></div>
        <?php endif; ?>
    </section>
    <?php else: ?>
    <div class="calendar-workspace">
        <section class="calendar-shell" aria-label="Lịch chiến dịch">
            <div class="calendar-month-foot"><span><span class="calendar-legend-start">↗ Bắt đầu</span><span class="calendar-legend-end">↘ Kết thúc</span></span><span>Giờ Việt Nam · UTC+7</span></div>
            <div class="calendar-weekdays" aria-hidden="true"><?php foreach (['Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7','CN'] as $weekday): ?><span><?= e($weekday) ?></span><?php endforeach; ?></div>
            <div class="calendar-grid" role="group" aria-label="Các ngày trong tháng">
                <?php for ($day=$gridStart;$day<=$gridEnd;$day=$day->modify('+1 day')):
                    $dayKey=$day->format('Y-m-d'); $dayCampaigns=$campaignsByDay[$dayKey] ?? [];
                    $milestones=$milestonesByDay[$dayKey] ?? [];
                    $inMonth=$day->format('n') === (string)$displayMonth; $isToday=$dayKey === $today->format('Y-m-d');
                    $startCount=count(array_filter($milestones,static fn($item)=>$item['kind']==='start')); $endCount=count($milestones)-$startCount;
                ?>
                <details class="calendar-day<?= $inMonth ? '' : ' is-outside' ?><?= $isToday ? ' is-today' : '' ?>" data-calendar-day="<?= e($dayKey) ?>">
                    <summary aria-label="<?= e($day->format('d/m/Y')) ?>, <?= count($dayCampaigns) ?> chiến dịch, <?= $startCount ?> bắt đầu, <?= $endCount ?> kết thúc" aria-controls="calendar-selected-day">
                        <span class="calendar-day-number"><?= $day->format('j') ?></span><span class="calendar-day-total"><?= count($dayCampaigns) ?> <span class="calendar-day-unit">ưu đãi</span></span>
                        <?php if ($milestones): ?><span class="calendar-milestones">
                            <?php foreach (array_slice($milestones,0,2) as $milestone): ?><span class="calendar-milestone is-<?= $milestone['kind'] ?>" title="<?= e(($milestone['campaign']['merchant_name'] ?: 'CouponHub').' · '.($milestone['kind']==='start' ? 'Bắt đầu' : 'Kết thúc')) ?>"><span aria-hidden="true"><?= $milestone['kind']==='start' ? '↗' : '↘' ?></span><span class="calendar-milestone-name"><?= e($milestone['campaign']['merchant_name'] ?: 'CouponHub') ?></span></span><?php endforeach; ?>
                            <?php if (count($milestones)>2): ?><span class="calendar-milestone-more">+<?= count($milestones)-2 ?></span><?php endif; ?>
                        </span><?php endif; ?>
                    </summary>
                    <div class="calendar-day-detail"><?php require __DIR__.'/../partials/calendar-day-agenda.php'; ?></div>
                </details>
                <?php endfor; ?>
            </div>
        </section>
        <aside class="calendar-selected-day" id="calendar-selected-day" data-calendar-selected-day data-initial-day="<?= e($selectedCalendarDay->format('Y-m-d')) ?>" aria-label="Chiến dịch của ngày được chọn">
            <?php $day=$selectedCalendarDay; $dayCampaigns=$campaignsByDay[$day->format('Y-m-d')] ?? []; require __DIR__.'/../partials/calendar-day-agenda.php'; ?>
        </aside>
    </div>
    <?php endif; ?>
    <details class="calendar-holidays"><summary>Ngày lễ tham khảo</summary>

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
    </details>
</div>
<script src="<?= e(asset('/js/deal-calendar.js')) ?>?v=<?= filemtime(__DIR__.'/../../../public/js/deal-calendar.js') ?>" defer></script>
