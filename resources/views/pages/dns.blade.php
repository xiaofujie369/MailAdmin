@extends('layouts.app')
@section('title', 'DNS 信誉检查')
@section('content')
<section class="panel">
  <form class="toolbar" method="post" action="{{ route('dns.check') }}">@csrf
    <input name="domain" value="{{ $domain }}" placeholder="example.com">
    <button>检查 DNS</button>
  </form>
</section>
<section class="panel">
  <div class="panel-head"><h2>历史记录</h2><span>保存每次检查结果</span></div>
  <table><thead><tr><th>域名</th><th>评分</th><th>MX</th><th>SPF</th><th>DKIM</th><th>DMARC</th><th>PTR</th><th>时间</th></tr></thead><tbody>
    @foreach($latest as $row)
    <tr><td>{{ $row->domain }}</td><td>{{ $row->score }}/100</td><td><pre>{{ $row->mx_result }}</pre></td><td><pre>{{ $row->spf_result }}</pre></td><td><pre>{{ $row->dkim_mail_result ?: $row->dkim_default_result }}</pre></td><td><pre>{{ $row->dmarc_result }}</pre></td><td><pre>{{ $row->ptr_result }}</pre></td><td>{{ $row->checked_at }}</td></tr>
    @endforeach
  </tbody></table>{{ $latest->links() }}
</section>
@endsection
