@extends('layouts.app')
@section('title', '操作审计')
@section('content')
<section class="panel">
  <table><thead><tr><th>ID</th><th>用户</th><th>动作</th><th>目标</th><th>IP</th><th>时间</th></tr></thead><tbody>
    @foreach($logs as $log)<tr><td>{{ $log->id }}</td><td>{{ $log->user_id }}</td><td>{{ $log->action }}</td><td>{{ $log->target_type }} {{ $log->target_id }}</td><td>{{ $log->ip }}</td><td>{{ $log->created_at }}</td></tr>@endforeach
  </tbody></table>{{ $logs->links() }}
</section>
@endsection
