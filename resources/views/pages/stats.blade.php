@extends('layouts.app')
@section('title', '统计分析')
@section('content')
<form class="toolbar panel" method="get">
  <select name="range">
    @foreach(['today'=>'今天','yesterday'=>'昨天','7d'=>'最近 7 天','30d'=>'最近 30 天','month'=>'本月','last_month'=>'上月'] as $key=>$label)
      <option value="{{ $key }}" @selected($range===$key)>{{ $label }}</option>
    @endforeach
  </select>
  <button>筛选</button>
</form>
<div class="metric-grid">
  <article class="metric good"><span>成功</span><strong>{{ $summary['sent'] }}</strong></article>
  <article class="metric bad"><span>退信</span><strong>{{ $summary['bounced'] }}</strong></article>
  <article class="metric warn"><span>延迟</span><strong>{{ $summary['deferred'] }}</strong></article>
  <article class="metric bad"><span>拒收</span><strong>{{ $summary['reject'] }}</strong></article>
  <article class="metric"><span>退信率</span><strong>{{ $summary['bounce_rate'] }}%</strong></article>
  <article class="metric"><span>延迟率</span><strong>{{ $summary['deferred_rate'] }}%</strong></article>
</div>
<section class="panel"><div class="panel-head"><h2>每日趋势</h2></div><canvas id="dailyChart" height="260"></canvas></section>
<section class="panel">
  <div class="panel-head"><h2>每日明细</h2></div>
  <table><thead><tr><th>日期</th><th>成功</th><th>退信</th><th>延迟</th><th>拒收</th><th>失败率</th></tr></thead><tbody>
  @foreach($daily as $row)
    <tr><td>{{ $row->date }}</td><td>{{ $row->sent_count }}</td><td>{{ $row->bounced_count }}</td><td>{{ $row->deferred_count }}</td><td>{{ $row->reject_count }}</td><td>{{ $row->failure_rate }}%</td></tr>
  @endforeach
  </tbody></table>
</section>
<div class="two-col">
@foreach(['Top 收件人'=>$topRecipients,'Top 发件人'=>$topSenders,'Top 收件域名'=>$topDomains,'Top 连接 IP'=>$topIps] as $title=>$rows)
  <section class="panel"><div class="panel-head"><h2>{{ $title }}</h2></div><table><tbody>
    @foreach($rows as $row)<tr><td><code>{{ $row->recipient ?? $row->sender ?? $row->recipient_domain ?? $row->client_ip }}</code></td><td>{{ $row->total }}</td></tr>@endforeach
  </tbody></table></section>
@endforeach
</div>
@push('scripts')
<script>renderTrend('dailyChart', @json($daily->map(fn($x)=>['label'=>$x->date,'sent'=>$x->sent_count,'bounced'=>$x->bounced_count,'deferred'=>$x->deferred_count])->values()));</script>
@endpush
@endsection
