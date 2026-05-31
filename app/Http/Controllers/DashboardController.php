<?php

namespace App\Http\Controllers;

use App\Models\HealthCheck;
use App\Models\MailDailyStat;
use App\Models\MailMonthlyStat;
use App\Models\QueueItem;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = MailDailyStat::where('date', today()->toDateString())->first();
        $seven = MailDailyStat::orderBy('date')->where('date', '>=', today()->subDays(6))->get();
        $thirty = MailDailyStat::orderBy('date')->where('date', '>=', today()->subDays(29))->get();
        $months = MailMonthlyStat::orderBy('month')->take(12)->get();
        $health = HealthCheck::latest('checked_at')->take(6)->get();

        return view('pages.dashboard', [
            'today' => $today,
            'seven' => $seven,
            'thirty' => $thirty,
            'months' => $months,
            'queueCount' => QueueItem::count(),
            'health' => $health,
        ]);
    }
}
