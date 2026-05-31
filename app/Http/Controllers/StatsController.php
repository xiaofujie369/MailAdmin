<?php

namespace App\Http\Controllers;

use App\Models\MailDailyStat;
use App\Models\MailEvent;
use App\Models\MailMonthlyStat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $range = $request->string('range', '30d')->toString();
        [$from, $to] = match ($range) {
            'today' => [today(), today()],
            'yesterday' => [today()->subDay(), today()->subDay()],
            '7d' => [today()->subDays(6), today()],
            'month' => [today()->startOfMonth(), today()],
            'last_month' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
            default => [today()->subDays(29), today()],
        };

        $daily = MailDailyStat::whereBetween('date', [$from->toDateString(), $to->toDateString()])->orderBy('date')->get();
        $monthly = MailMonthlyStat::orderBy('month')->take(12)->get();
        $summary = [
            'sent' => $daily->sum('sent_count'),
            'bounced' => $daily->sum('bounced_count'),
            'deferred' => $daily->sum('deferred_count'),
            'reject' => $daily->sum('reject_count'),
        ];
        $attempted = max(1, $summary['sent'] + $summary['bounced'] + $summary['deferred']);
        $summary['failure_rate'] = round(($summary['bounced'] + $summary['deferred']) * 100 / $attempted, 2);
        $summary['bounce_rate'] = round($summary['bounced'] * 100 / $attempted, 2);
        $summary['deferred_rate'] = round($summary['deferred'] * 100 / $attempted, 2);

        return view('pages.stats', [
            'range' => $range,
            'daily' => $daily,
            'monthly' => $monthly,
            'summary' => $summary,
            'topRecipients' => MailEvent::selectRaw('recipient, count(*) as total')->whereNotNull('recipient')->groupBy('recipient')->orderByDesc('total')->limit(10)->get(),
            'topSenders' => MailEvent::selectRaw('sender, count(*) as total')->whereNotNull('sender')->groupBy('sender')->orderByDesc('total')->limit(10)->get(),
            'topDomains' => MailEvent::selectRaw('recipient_domain, count(*) as total')->whereNotNull('recipient_domain')->groupBy('recipient_domain')->orderByDesc('total')->limit(10)->get(),
            'topIps' => MailEvent::selectRaw('client_ip, count(*) as total')->whereNotNull('client_ip')->groupBy('client_ip')->orderByDesc('total')->limit(10)->get(),
        ]);
    }
}
