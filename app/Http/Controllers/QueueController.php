<?php

namespace App\Http\Controllers;

use App\Models\QueueItem;
use App\Services\AuditLogger;
use App\Services\DockerCommandService;
use App\Services\InputValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function index(Request $request): View
    {
        $query = QueueItem::query()->latest();
        foreach (['queue_id', 'sender', 'recipient', 'recipient_domain', 'reason'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, 'like', '%'.$request->string($field)->toString().'%');
            }
        }
        return view('pages.queue', ['items' => $query->paginate(30)->withQueryString()]);
    }

    public function cancel(Request $request, DockerCommandService $docker, InputValidator $validator, AuditLogger $audit): RedirectResponse
    {
        $this->ensureOperator($request);
        $request->validate(['queue_id' => ['required'], 'confirm_phrase' => ['required', 'in:DELETE']]);
        $queueId = $validator->queueId($request->string('queue_id')->toString());
        $output = $docker->postsuperDelete($queueId);
        QueueItem::where('queue_id', $queueId)->delete();
        $audit->write('取消队列邮件', 'queue_item', $queueId, ['output' => $output]);
        return back()->with('status', '已提交取消发送命令');
    }

    public function bulkCancel(Request $request, DockerCommandService $docker, InputValidator $validator, AuditLogger $audit): RedirectResponse
    {
        $this->ensureOperator($request);
        $request->validate(['queue_ids' => ['required', 'array'], 'confirm_phrase' => ['required', 'in:DELETE']]);
        foreach ($request->input('queue_ids', []) as $queueId) {
            $safe = $validator->queueId((string) $queueId);
            $docker->postsuperDelete($safe);
            QueueItem::where('queue_id', $safe)->delete();
        }
        $audit->write('批量取消队列邮件', 'queue_item', null, ['count' => count($request->input('queue_ids', []))]);
        return back()->with('status', '已提交批量取消命令');
    }

    public function flush(Request $request, DockerCommandService $docker, AuditLogger $audit): RedirectResponse
    {
        $this->ensureOperator($request);
        $request->validate(['confirm_phrase' => ['required', 'in:FLUSH']]);
        $output = $docker->flushQueue();
        $audit->write('重试队列投递', 'queue', null, ['output' => $output]);
        return back()->with('status', '已提交重试投递命令');
    }

    public function clear(Request $request, DockerCommandService $docker, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, '只有管理员可以清空队列');
        $request->validate(['confirm_phrase' => ['required', 'in:CLEAR']]);
        $output = $docker->postsuperDeleteAll();
        QueueItem::truncate();
        $audit->write('清空全部队列', 'queue', 'ALL', ['output' => $output]);
        return back()->with('status', '已提交清空队列命令');
    }

    private function ensureOperator(Request $request): void
    {
        abort_unless($request->user()->canOperate(), 403, '当前角色没有操作权限');
    }
}
