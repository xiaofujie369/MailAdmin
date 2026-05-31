<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', '仪表盘') - MailAdmin Pro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
  <aside class="sidebar">
    <div class="brand">
      <span class="brand-mark">M</span>
      <div><strong>MailAdmin Pro</strong><small>邮件运维后台</small></div>
    </div>
    <nav>
      <a @class(['active' => request()->routeIs('dashboard')]) href="{{ route('dashboard') }}">仪表盘</a>
      <a @class(['active' => request()->routeIs('stats')]) href="{{ route('stats') }}">统计分析</a>
      <a @class(['active' => request()->routeIs('queue.*')]) href="{{ route('queue.index') }}">邮件队列</a>
      <a @class(['active' => request()->routeIs('lists.*')]) href="{{ route('lists.index') }}">名单管理</a>
      <a @class(['active' => request()->routeIs('dns.*')]) href="{{ route('dns.index') }}">DNS 检查</a>
      <a @class(['active' => request()->routeIs('antispam')]) href="{{ route('antispam') }}">反垃圾</a>
      <a @class(['active' => request()->routeIs('logs')]) href="{{ route('logs') }}">日志</a>
      <a @class(['active' => request()->routeIs('system')]) href="{{ route('system') }}">系统</a>
      <a @class(['active' => request()->routeIs('audit')]) href="{{ route('audit') }}">审计</a>
    </nav>
  </aside>
  <main class="shell">
    <header class="topbar">
      <div>
        <h1>@yield('title', '仪表盘')</h1>
        <p>{{ config('mailadmin.container') }} · {{ config('app.locale') }} · 127.0.0.1:8095</p>
      </div>
      <form method="post" action="{{ route('logout') }}">
        @csrf
        <span class="pill">{{ auth()->user()->role ?? '' }}</span>
        <button class="secondary">退出</button>
      </form>
    </header>
    <section class="content">
      @if(session('status')) <div class="notice">{{ session('status') }}</div> @endif
      @if($errors->any()) <div class="danger">{{ $errors->first() }}</div> @endif
      @yield('content')
    </section>
  </main>
  <script src="/assets/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')
</body>
</html>
