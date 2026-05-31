@extends('layouts.app')
@section('title', '日志')
@section('content')
<section class="panel">
  <form class="toolbar" method="get">
    <select name="tail"><option value="500" @selected($tail===500)>最近 500 行</option><option value="2000" @selected($tail===2000)>最近 2000 行</option><option value="5000" @selected($tail===5000)>最近 5000 行</option></select>
    <input name="keyword" value="{{ $keyword }}" placeholder="sent / bounced / deferred / reject / 邮箱 / 域名">
    <button>筛选</button>
  </form>
  <pre class="terminal tall">{{ $logs }}</pre>
</section>
@endsection
