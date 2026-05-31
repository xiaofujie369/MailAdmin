<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.audit', [
            'logs' => AuditLog::latest('created_at')->paginate(50),
        ]);
    }
}
