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
        $year = (int)date('Y');
        $holidays = (new HolidayService())->upcoming($year);
        $campaigns=(new CampaignRepository(Database::connection()))->publicListing('ALL',1,24)['items'];
        $this->view('campaigns/calendar', ['pageTitle' => 'Lịch ưu đãi', 'holidays' => $holidays, 'year' => $year, 'campaigns'=>$campaigns]);
    }
}
