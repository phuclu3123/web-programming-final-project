<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\HolidayService;

final class HolidayController extends Controller
{
    public function index(Request $request): void
    {
        $year = (int)date('Y');
        $holidays = (new HolidayService())->upcoming($year);
        $this->view('campaigns/calendar', ['pageTitle' => 'Lịch ngày lễ cho Campaign', 'holidays' => $holidays, 'year' => $year]);
    }
}
