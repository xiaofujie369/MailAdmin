<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\InputValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListController extends Controller
{
    private array $tables = ['suppressions', 'blocklists', 'allowlists'];

    public function index(Request $request): View
    {
        return view('pages.lists', [
            'suppressions' => DB::table('suppressions')->latest()->paginate(10, ['*'], 'supp_page'),
            'blocklists' => DB::table('blocklists')->latest()->paginate(10, ['*'], 'block_page'),
            'allowlists' => DB::table('allowlists')->latest()->paginate(10, ['*'], 'allow_page'),
        ]);
    }

    public function store(string $table, Request $request, InputValidator $validator, AuditLogger $audit): RedirectResponse
    {
        $this->guardTable($table);
        $types = $table === 'suppressions' ? ['email', 'domain'] : ['email', 'domain', 'ip', 'cidr'];
        $data = $request->validate([
            'type' => ['required', Rule::in($types)],
            'value' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', Rule::in(['reject', 'discard', 'hold', 'tag'])],
        ]);
        $data['value'] = $validator->listValue($data['type'], $data['value']);
        if ($table === 'suppressions') {
            $data['source'] = 'manual';
        }
        if ($table !== 'blocklists') {
            unset($data['action']);
        } else {
            $data['action'] ??= 'reject';
        }
        $data['is_active'] = true;
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table($table)->updateOrInsert(['value' => $data['value']], $data);
        $audit->write('添加名单', $table, $data['value'], $data);
        return back()->with('status', '名单已保存');
    }

    public function import(string $table, Request $request, InputValidator $validator, AuditLogger $audit): RedirectResponse
    {
        $this->guardTable($table);
        $request->validate(['csv' => ['required', 'file'], 'confirm_phrase' => ['required', 'in:IMPORT']]);
        $rows = array_map('str_getcsv', file($request->file('csv')->getRealPath()) ?: []);
        $headers = array_map('trim', array_shift($rows) ?: []);
        $count = 0;
        foreach ($rows as $row) {
            $item = array_combine($headers, $row) ?: [];
            if (! isset($item['type'], $item['value'])) {
                continue;
            }
            $type = trim($item['type']);
            if ($table === 'suppressions' && ! in_array($type, ['email', 'domain'], true)) {
                continue;
            }
            $value = $validator->listValue($type, trim($item['value']));
            $data = [
                'type' => $type,
                'value' => $value,
                'reason' => $item['reason'] ?? '',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($table === 'suppressions') {
                $data['source'] = 'csv';
            }
            if ($table === 'blocklists') {
                $action = $item['action'] ?? 'reject';
                $data['action'] = in_array($action, ['reject', 'discard', 'hold', 'tag'], true) ? $action : 'reject';
            }
            DB::table($table)->updateOrInsert(['value' => $value], $data);
            $count++;
        }
        $audit->write('导入 CSV', $table, null, ['count' => $count]);
        return back()->with('status', "已导入 {$count} 条记录");
    }

    public function export(string $table): StreamedResponse
    {
        $this->guardTable($table);
        return response()->streamDownload(function () use ($table): void {
            $out = fopen('php://output', 'w');
            $headers = $table === 'blocklists' ? ['type', 'value', 'action', 'reason'] : ['type', 'value', 'reason'];
            fputcsv($out, $headers);
            DB::table($table)->orderBy('id')->each(function ($row) use ($out, $headers): void {
                fputcsv($out, array_map(fn ($key) => $row->{$key} ?? '', $headers));
            });
        }, $table.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function toggle(string $table, int $id, AuditLogger $audit): RedirectResponse
    {
        $this->guardTable($table);
        $row = DB::table($table)->where('id', $id)->first();
        abort_unless($row, 404);
        DB::table($table)->where('id', $id)->update(['is_active' => ! $row->is_active, 'updated_at' => now()]);
        $audit->write('启用/禁用名单', $table, (string) $id);
        return back();
    }

    public function destroy(string $table, int $id, Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->guardTable($table);
        $request->validate(['confirm_phrase' => ['required', 'in:DELETE']]);
        DB::table($table)->where('id', $id)->delete();
        $audit->write('删除名单', $table, (string) $id);
        return back()->with('status', '记录已删除');
    }

    private function guardTable(string $table): void
    {
        abort_unless(in_array($table, $this->tables, true), 404);
    }
}
