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
  <main class="login-card">
    <h1>MailAdmin Pro</h1>
    <p>docker-mailserver 运维后台</p>
    @if($errors->any()) <div class="danger">{{ $errors->first() }}</div> @endif
    <form method="post" action="/login" class="stack">
      @csrf
      <input name="email" type="email" value="{{ old('email') }}" placeholder="管理员邮箱" required autofocus>
      <input name="password" type="password" placeholder="密码" required>
      <label class="check"><input type="checkbox" name="remember" value="1"> 记住登录</label>
      <button>登录</button>
    </form>
  </main>
</body>
</html>
