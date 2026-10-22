<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\HolidayService;
use App\Core\Database;
use App\Repositories\CampaignRepository;

final class HolidayController extends Controller
{
    public function index(Request $request): void
    {
        $year = positiveId($request->query('year', date('Y')));
        $month = positiveId($request->query('month', '0') ?: '0', true);
        if ($year < 2020 || $year > 2035 || $month > 12) throw new \App\Core\HttpError(422, 'Tháng hoặc năm không hợp lệ.');
        $holidays = (new HolidayService())->upcoming($year);
        if ($month) $holidays = array_values(array_filter($holidays, static fn($day) => (int)substr($day['date'],5,2) === $month));
        $campaigns=(new CampaignRepository(Database::connection()))->publicCalendar($year,$month);
        $this->view('campaigns/calendar', ['pageTitle' => 'Lịch ưu đãi', 'holidays' => $holidays, 'year' => $year, 'selectedYear'=>$year, 'selectedMonth'=>$month, 'campaigns'=>$campaigns]);
    }
}
