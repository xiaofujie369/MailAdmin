@extends('layouts.app')

@section('title', '统计分析')

@section('content')
@php
  $summary = $summary ?? [
    'sent' => 0,
    'bounced' => 0,
    'deferred' => 0,
    'reject' => 0,
    'bounce_rate' => 0,
    'deferred_rate' => 0,
  ];

  $range = $range ?? '7d';
  $daily = $daily ?? collect();
  $topRecipients = $topRecipients ?? collect();
  $topSenders = $topSenders ?? collect();
  $topDomains = $topDomains ?? collect();
  $topIps = $topIps ?? collect();

  $dailyChartData = $daily->map(function ($x) {
    return [
      'label' => $x->date ?? '-',
      'sent' => (int)($x->sent_count ?? 0),
      'bounced' => (int)($x->bounced_count ?? 0),
      'deferred' => (int)($x->deferred_count ?? 0),
    ];
  })->values();

  $rangeOptions = [
    'today' => '今天',
    'yesterday' => '昨天',
    '7d' => '最近 7 天',
    '30d' => '最近 30 天',
    'month' => '本月',
    'last_month' => '上月',
  ];

  $topSections = [
    'Top 收件人' => $topRecipients,
    'Top 发件人' => $topSenders,
    'Top 收件域名' => $topDomains,
    'Top 连接 IP' => $topIps,
  ];
@endphp

<form class="toolbar panel" method="get">
  <select name="range">
    @foreach($rangeOptions as $key => $label)
      <option value="{{ $key }}" @selected($range === $key)>{{ $label }}</option>
    @endforeach
  </select>
  <button type="submit">筛选</button>
</form>

<div class="metric-grid">
  <article class="metric good"><span>成功</span><strong>{{ $summary['sent'] ?? 0 }}</strong></article>
  <article class="metric bad"><span>退信</span><strong>{{ $summary['bounced'] ?? 0 }}</strong></article>
  <article class="metric warn"><span>延迟</span><strong>{{ $summary['deferred'] ?? 0 }}</strong></article>
  <article class="metric bad"><span>拒收</span><strong>{{ $summary['reject'] ?? 0 }}</strong></article>
  <article class="metric"><span>退信率</span><strong>{{ $summary['bounce_rate'] ?? 0 }}%</strong></article>
  <article class="metric"><span>延迟率</span><strong>{{ $summary['deferred_rate'] ?? 0 }}%</strong></article>
</div>

<section class="panel">
  <div class="panel-head"><h2>每日趋势</h2></div>
  <canvas id="dailyChart" height="260"></canvas>
</section>

<section class="panel">
  <div class="panel-head"><h2>每日明细</h2></div>
  <table>
    <thead>
      <tr>
        <th>日期</th>
        <th>成功</th>
        <th>退信</th>
        <th>延迟</th>
        <th>拒收</th>
        <th>失败率</th>
      </tr>
    </thead>
    <tbody>
      @forelse($daily as $row)
        <tr>
          <td>{{ $row->date ?? '-' }}</td>
          <td>{{ $row->sent_count ?? 0 }}</td>
          <td>{{ $row->bounced_count ?? 0 }}</td>
          <td>{{ $row->deferred_count ?? 0 }}</td>
          <td>{{ $row->reject_count ?? 0 }}</td>
          <td>{{ $row->failure_rate ?? 0 }}%</td>
        </tr>
      @empty
        <tr>
          <td colspan="6">暂无统计数据。</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</section>

<div class="two-col">
  @foreach($topSections as $title => $rows)
    <section class="panel">
      <div class="panel-head"><h2>{{ $title }}</h2></div>
      <table>
        <tbody>
          @forelse($rows as $row)
            <tr>
              <td>
                <code>{{ $row->recipient ?? $row->sender ?? $row->recipient_domain ?? $row->client_ip ?? '-' }}</code>
              </td>
              <td>{{ $row->total ?? 0 }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="2">暂无数据。</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </section>
  @endforeach
</div>

@push('scripts')
<script>
if (typeof renderTrend === 'function') {
  renderTrend('dailyChart', @json($dailyChartData));
}
</script>
@endpush
@endsection
