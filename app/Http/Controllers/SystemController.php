<?php

namespace App\Http\Controllers;

use App\Models\HealthCheck;
use App\Services\DockerCommandService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class SystemController extends Controller
{
    public function __invoke(DockerCommandService $docker): View
    {
        return view('pages.system', [
            'docker' => $docker->dockerPs(),
            'load' => $this->run(['uptime']),
            'disk' => $this->run(['df', '-h', '/', '/var/lib/docker']),
            'memory' => $this->run(['free', '-h']),
            'health' => HealthCheck::latest('checked_at')->take(10)->get(),
            'dbOk' => $this->dbOk(),
        ]);
    }

    private function run(array $args): string
    {
        $process = new Process($args);
        $process->setTimeout(10);
        $process->run();
        return trim($process->getOutput().$process->getErrorOutput());
    }

    private function dbOk(): bool
    {
        try {
            DB::select('select 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
