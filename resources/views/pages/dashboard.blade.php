@extends('layouts.app')

@section('title', '仪表盘')

@section('content')
@php
  $today = $today ?: (object)[
    'sent_count' => 0,
    'bounced_count' => 0,
    'deferred_count' => 0,
    'reject_count' => 0,
    'failure_rate' => 0,
  ];

  $sevenData = ($seven ?? collect())->map(function ($x) {
    return [
      'label' => $x->date ?? '-',
      'sent' => (int)($x->sent_count ?? 0),
      'bounced' => (int)($x->bounced_count ?? 0),
    ];
  })->values();

  $thirtyData = ($thirty ?? collect())->map(function ($x) {
    return [
      'label' => $x->date ?? '-',
      'sent' => (int)($x->sent_count ?? 0),
      'bounced' => (int)($x->bounced_count ?? 0),
    ];
  })->values();

  $monthData = ($months ?? collect())->map(function ($x) {
    return [
      'label' => $x->month ?? '-',
      'sent' => (int)($x->sent_count ?? 0),
      'bounced' => (int)($x->bounced_count ?? 0),
    ];
  })->values();
@endphp

<div class="metric-grid">
  <article class="metric good"><span>今日成功</span><strong>{{ $today->sent_count }}</strong></article>
  <article class="metric bad"><span>今日退信</span><strong>{{ $today->bounced_count }}</strong></article>
  <article class="metric warn"><span>今日延迟</span><strong>{{ $today->deferred_count }}</strong></article>
  <article class="metric bad"><span>今日拒收</span><strong>{{ $today->reject_count }}</strong></article>
  <article class="metric warn"><span>当前队列</span><strong>{{ $queueCount ?? 0 }}</strong></article>
  <article class="metric"><span>今日失败率</span><strong>{{ $today->failure_rate }}%</strong></article>
</div>

<div class="two-col">
  <section class="panel">
    <div class="panel-head"><h2>7 日发送趋势</h2></div>
    <canvas id="sevenChart" height="240"></canvas>
  </section>

  <section class="panel">
    <div class="panel-head"><h2>30 日发送趋势</h2></div>
    <canvas id="thirtyChart" height="240"></canvas>
  </section>
</div>

<div class="two-col">
  <section class="panel">
    <div class="panel-head"><h2>月度趋势</h2></div>
    <canvas id="monthChart" height="240"></canvas>
  </section>

  <section class="panel">
    <div class="panel-head"><h2>系统健康</h2><span>最近检查</span></div>
    <table>
      <thead>
        <tr>
          <th>项目</th>
          <th>状态</th>
          <th>评分</th>
          <th>消息</th>
        </tr>
      </thead>
      <tbody>
        @forelse($health ?? [] as $item)
          <tr>
            <td>{{ $item->check_type }}</td>
            <td>{{ $item->status }}</td>
            <td>{{ $item->score }}</td>
            <td>{{ $item->message }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4">暂无健康检查数据，请等待采集器运行。</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </section>
</div>

@push('scripts')
<script>
const sevenData = @json($sevenData);
const thirtyData = @json($thirtyData);
const monthData = @json($monthData);

if (typeof renderTrend === 'function') {
  renderTrend('sevenChart', sevenData);
  renderTrend('thirtyChart', thirtyData);
  renderTrend('monthChart', monthData);
}
</script>
@endpush
@endsection
