<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Вход - DocV3</title>
<style>
body {
  margin: 0;
  min-height: 100vh;
  display: grid;
  place-items: center;
  background: radial-gradient(circle at top, #22315f 0%, #151a33 45%, #0d1022 100%);
  font-family: 'Segoe UI', system-ui, sans-serif;
  color: #111827;
}
.card {
  width: min(420px, calc(100vw - 32px));
  background: rgba(255,255,255,.97);
  border-radius: 20px;
  padding: 32px;
  box-shadow: 0 24px 80px rgba(0,0,0,.25);
}
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
}
.logo {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: #e94560;
  color: #fff;
  display: grid;
  place-items: center;
  font-weight: 700;
}
h1 { margin: 0; font-size: 24px; }
p { margin: 4px 0 0; color: #6b7280; font-size: 14px; }
.form-group { margin-bottom: 16px; }
label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #475569; }
input {
  width: 100%;
  padding: 11px 13px;
  border-radius: 10px;
  border: 1px solid #dbe2ea;
  background: #f8fafc;
  font-size: 14px;
  box-sizing: border-box;
}
button {
  width: 100%;
  border: none;
  border-radius: 10px;
  background: #e94560;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  padding: 12px 16px;
  cursor: pointer;
}
.error {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  border-radius: 10px;
  padding: 12px 14px;
  margin-bottom: 16px;
  font-size: 13px;
}
.hint {
  margin-top: 14px;
  font-size: 12px;
  color: #64748b;
  text-align: center;
}
</style>
</head>
<body>
  <div class="card">
    <div class="brand">
      <div class="logo">СЭД</div>
      <div>
        <h1>DocV3</h1>
        <p>Вход в систему электронного документооборота</p>
      </div>
    </div>

    @if($errors->any())
      <div class="error">
        @foreach($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
      @csrf
      <div class="form-group">
        <label for="login">Логин</label>
        <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus>
      </div>

      <div class="form-group">
        <label for="password">Пароль</label>
        <input id="password" name="password" type="password" required>
      </div>

      <button type="submit">Войти</button>
    </form>

    <div class="hint">Тестовая учетная запись: <strong>admin</strong> / <strong>admin123</strong></div>
  </div>
</body>
</html>
