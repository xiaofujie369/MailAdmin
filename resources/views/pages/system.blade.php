@extends('layouts.app')
@section('title', '系统')
@section('content')
<div class="metric-grid">
  <article class="metric {{ $dbOk ? 'good' : 'bad' }}"><span>MySQL</span><strong>{{ $dbOk ? '正常' : '异常' }}</strong></article>
  <article class="metric"><span>Redis</span><strong>{{ config('database.redis.default.host') }}</strong></article>
  <article class="metric"><span>邮件容器</span><strong>{{ config('mailadmin.container') }}</strong></article>
</div>
<div class="two-col">
  <section class="panel"><div class="panel-head"><h2>Docker 容器</h2></div><pre class="terminal">{{ $docker }}</pre></section>
  <section class="panel"><div class="panel-head"><h2>负载</h2></div><pre class="terminal">{{ $load }}</pre></section>
  <section class="panel"><div class="panel-head"><h2>磁盘</h2></div><pre class="terminal">{{ $disk }}</pre></section>
  <section class="panel"><div class="panel-head"><h2>内存</h2></div><pre class="terminal">{{ $memory }}</pre></section>
</div>
@endsection
