<?php

namespace App\Http\Controllers;

use App\Models\DnsCheck;
use App\Services\AuditLogger;
use App\Services\DnsCheckService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DnsController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.dns', [
            'latest' => DnsCheck::latest('checked_at')->paginate(20),
            'domain' => $request->string('domain', config('mailadmin.domain'))->toString(),
            'result' => null,
        ]);
    }

    public function check(Request $request, DnsCheckService $service, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['domain' => ['required', 'string', 'max:253']]);
        $result = $service->check($request->string('domain')->toString());
        $audit->write('DNS 检查', 'dns_check', (string) $result->id, ['domain' => $result->domain, 'score' => $result->score]);
        return redirect()->route('dns.index', ['domain' => $result->domain])->with('status', "DNS 检查完成，评分 {$result->score}/100");
    }
}
