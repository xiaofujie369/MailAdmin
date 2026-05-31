<?php

namespace App\Http\Controllers;

use App\Services\DockerCommandService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogsController extends Controller
{
    public function __invoke(Request $request, DockerCommandService $docker): View
    {
        $tail = max(100, min((int) $request->integer('tail', 500), 5000));
        $keyword = trim($request->string('keyword')->toString());
        $logs = $docker->logs($tail);
        if ($keyword !== '') {
            $logs = collect(explode("\n", $logs))
                ->filter(fn ($line) => str_contains(mb_strtolower($line), mb_strtolower($keyword)))
                ->implode("\n");
        }

        return view('pages.logs', compact('logs', 'tail', 'keyword'));
    }
}
