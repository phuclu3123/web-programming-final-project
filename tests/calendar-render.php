<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
\App\Core\App::init(['app'=>['base_url'=>'','timezone'=>'Asia/Ho_Chi_Minh']]);
function calendarCheck(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException('FAIL: '.$label);
    echo 'PASS: '.$label."\n";
}
function renderCalendar(int $year, int $month, array $rows): string {
    $selectedYear=$year; $selectedMonth=$month; $campaigns=$rows; $holidays=[];
    ob_start(); require __DIR__.'/../app/views/campaigns/calendar.php'; return (string)ob_get_clean();
}
$rows=[['id'=>1,'name'=>'Midnight <script>alert(1)</script>','starts_at'=>'2026-02-02 17:00:00','ends_at'=>'2026-02-03 17:00:00','merchant_name'=>'Fixture']];
$html=renderCalendar(2026,2,$rows);
calendarCheck(preg_match_all('/<details class="calendar-day(?:\s|")/', $html)===35,'February ending on Sunday has five complete weeks, no extra week');
calendarCheck(str_contains($html,'03/02/2026, 1 chiến dịch') && str_contains($html,'04/02/2026, 0 chiến dịch'),'UTC conversion and exclusive midnight end do not add an extra day');
calendarCheck(str_contains($html,'&lt;script&gt;') && !str_contains($html,'<script>alert(1)'),'campaign text remains escaped in calendar');
$rows[0]['starts_at']='1000-01-01 00:00:00'; $rows[0]['ends_at']='9999-12-31 23:59:59';
$html=renderCalendar(2026,2,$rows);
calendarCheck(substr_count($html,'1 chiến dịch</')>=35 && preg_match_all('/<details class="calendar-day(?:\s|")/', $html)===35,'long campaigns are bounded to the visible grid');
calendarCheck(!str_contains(renderCalendar(2020,1,[]),'year=2019') && !str_contains(renderCalendar(2035,12,[]),'year=2036'),'month arrows stay within the supported year range');
$sanitize=new ReflectionMethod(\App\Services\HolidayService::class,'sanitize');
$holidays=$sanitize->invoke(new \App\Services\HolidayService(),[
    ['date'=>'2026-02-30','name'=>'Invalid date'], ['date'=>'2026-02-28','name'=>['bad']],
    ['date'=>['bad'],'name'=>'Invalid shape'], ['date'=>'2026-02-28','name'=>'Wrong country','countryCode'=>'US'],
    ['date'=>'2026-02-28','name'=>'Valid date','countryCode'=>'VN'],
]);
calendarCheck(count($holidays)===1 && $holidays[0]['name']==='Valid date','malformed external holiday rows cannot crash date rendering');
