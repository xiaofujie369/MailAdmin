@extends('layouts.app')
@section('title', '名单管理')
@section('content')
<div class="notice">第一版只在后台管理名单，不会默认写入 Postfix 或 Rspamd，避免误伤正常邮件。</div>
@foreach(['suppressions'=>'退订名单','blocklists'=>'黑名单','allowlists'=>'白名单'] as $table=>$title)
<section class="panel">
  <div class="panel-head"><h2>{{ $title }}</h2><div><a class="button secondary" href="{{ route('lists.export',$table) }}">导出 CSV</a></div></div>
  <form class="toolbar" method="post" action="{{ route('lists.store',$table) }}">@csrf
    <select name="type"><option value="email">邮箱</option><option value="domain">域名</option>@if($table!=='suppressions')<option value="ip">IP</option><option value="cidr">CIDR</option>@endif</select>
    <input name="value" placeholder="user@example.com / example.com / 1.2.3.4">
    @if($table==='blocklists')<select name="action"><option value="reject">reject</option><option value="discard">discard</option><option value="hold">hold</option><option value="tag">tag</option></select>@endif
    <input name="reason" placeholder="原因">
    <button>添加</button>
  </form>
  <form class="toolbar import" method="post" enctype="multipart/form-data" action="{{ route('lists.import',$table) }}" onsubmit="return doubleConfirm('确认导入 CSV？')">@csrf
    <input type="file" name="csv" accept=".csv,text/csv">
    <input name="confirm_phrase" placeholder="IMPORT">
    <button class="secondary">导入 CSV</button>
  </form>
  @php($rows = $$table)
  <table><thead><tr><th>ID</th><th>类型</th><th>值</th><th>动作</th><th>原因</th><th>状态</th><th>操作</th></tr></thead><tbody>
    @foreach($rows as $row)
      <tr><td>{{ $row->id }}</td><td>{{ $row->type }}</td><td><code>{{ $row->value }}</code></td><td>{{ $row->action ?? '-' }}</td><td>{{ $row->reason }}</td><td>{{ $row->is_active ? '启用' : '禁用' }}</td><td class="actions">
        <form method="post" action="{{ route('lists.toggle',[$table,$row->id]) }}">@csrf @method('PATCH')<button class="secondary">{{ $row->is_active ? '禁用' : '启用' }}</button></form>
        <form method="post" action="{{ route('lists.destroy',[$table,$row->id]) }}" onsubmit="return doubleConfirm('确认删除这条名单？')">@csrf @method('DELETE')<input name="confirm_phrase" placeholder="DELETE"><button class="danger">删除</button></form>
      </td></tr>
    @endforeach
  </tbody></table>{{ $rows->links() }}
</section>
@endforeach
@endsection
