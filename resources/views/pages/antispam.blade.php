@extends('layouts.app')
@section('title', '反垃圾')
@section('content')
<div class="metric-grid">
  <article class="metric good"><span>7 日成功</span><strong>{{ $sent }}</strong></article>
  <article class="metric bad"><span>7 日退信</span><strong>{{ $bounced }}</strong></article>
  <article class="metric warn"><span>7 日延迟</span><strong>{{ $deferred }}</strong></article>
  <article class="metric warn"><span>队列压力</span><strong>{{ $queueCount }}</strong></article>
</div>
<section class="panel"><div class="panel-head"><h2>风险提示</h2></div><ul>@foreach($risks as $risk)<li>{{ $risk }}</li>@endforeach</ul></section>
<section class="panel">
  <div class="panel-head"><h2>Rspamd / Postfix / DNS 健康检查</h2></div>
  <table><thead><tr><th>项目</th><th>状态</th><th>评分</th><th>消息</th><th>原始输出</th></tr></thead><tbody>
    @foreach($health as $item)<tr><td>{{ $item->check_type }}</td><td>{{ $item->status }}</td><td>{{ $item->score }}</td><td>{{ $item->message }}</td><td><pre class="terminal">{{ $item->raw_output }}</pre></td></tr>@endforeach
  </tbody></table>
</section>
@endsection
