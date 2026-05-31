@extends('layouts.app')
@section('title', '邮件队列')
@section('content')
<div class="notice">已经 status=sent 的邮件无法撤回；这里只能取消仍在 Postfix 队列中的邮件。</div>
<form class="panel toolbar" method="get">
  <input name="queue_id" value="{{ request('queue_id') }}" placeholder="队列 ID">
  <input name="sender" value="{{ request('sender') }}" placeholder="发件人">
  <input name="recipient" value="{{ request('recipient') }}" placeholder="收件人">
  <input name="recipient_domain" value="{{ request('recipient_domain') }}" placeholder="域名">
  <input name="reason" value="{{ request('reason') }}" placeholder="原因">
  <button>搜索</button>
</form>
<section class="panel">
  <div class="panel-head"><h2>当前队列</h2><span>{{ $items->total() }} 封</span></div>
  <form method="post" action="{{ route('queue.bulkCancel') }}" onsubmit="return doubleConfirm('确认批量取消选中的队列邮件？')">
  @csrf
  <table><thead><tr><th>选择</th><th>ID</th><th>发件人</th><th>收件人</th><th>大小</th><th>原因</th><th>操作</th></tr></thead><tbody>
  @foreach($items as $item)
    <tr>
      <td><input type="checkbox" name="queue_ids[]" value="{{ $item->queue_id }}"></td>
      <td><code>{{ $item->queue_id }}</code></td><td>{{ $item->sender }}</td><td>{{ $item->recipient }}</td><td>{{ $item->size }}</td><td>{{ $item->reason }}</td>
      <td>勾选后可取消单封或批量取消</td>
    </tr>
  @endforeach
  </tbody></table>
  <div class="toolbar" style="margin-top:12px"><input name="confirm_phrase" placeholder="DELETE"><button class="danger">批量取消选中邮件</button></div>
  </form>
  {{ $items->links() }}
</section>
<section class="panel danger-zone">
  <div class="panel-head"><h2>危险操作</h2><span>必须输入确认短语</span></div>
  <div class="toolbar">
    <form method="post" action="{{ route('queue.cancel') }}" onsubmit="return doubleConfirm('确认取消这封队列邮件？')">@csrf<input name="queue_id" placeholder="队列 ID"><input name="confirm_phrase" placeholder="DELETE"><button class="danger">取消单封</button></form>
    <form method="post" action="{{ route('queue.flush') }}" onsubmit="return doubleConfirm('确认重试投递？')">@csrf<input name="confirm_phrase" placeholder="FLUSH"><button class="warn">立即重试投递</button></form>
    <form method="post" action="{{ route('queue.clear') }}" onsubmit="return doubleConfirm('确认清空全部队列？')">@csrf<input name="confirm_phrase" placeholder="CLEAR"><button class="danger">清空全部队列</button></form>
  </div>
</section>
@endsection
