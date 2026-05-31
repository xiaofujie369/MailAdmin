<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>登录 - MailAdmin Pro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="login-body">
  <main class="login-shell">
    <section class="login-intro" aria-label="MailAdmin Pro">
      <div class="login-brand">
        <span class="brand-mark">M</span>
        <div>
          <strong>MailAdmin Pro</strong>
          <span>邮件运维后台</span>
        </div>
      </div>
      <div class="login-copy">
        <h1>统一管理邮件投递、队列与安全检查</h1>
        <p>面向 docker-mailserver 的生产运维控制台，集中查看日志采集、统计聚合、投递队列、DNS 信誉和系统健康状态。</p>
      </div>
      <small>仅限授权管理员访问</small>
    </section>

    <section class="login-panel">
      <div class="login-card">
        <h2>管理员登录</h2>
        <p>使用管理员账号进入控制台</p>
        @if($errors->any()) <div class="danger">{{ $errors->first() }}</div> @endif
        <form method="post" action="/login" class="stack">
          @csrf
          <input name="email" type="email" value="{{ old('email') }}" placeholder="管理员邮箱" required autofocus autocomplete="username">
          <input name="password" type="password" placeholder="密码" required autocomplete="current-password">
          <label class="check"><input type="checkbox" name="remember" value="1"> 记住登录</label>
          <button>登录</button>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
