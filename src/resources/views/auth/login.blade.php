<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Вход — DocV3 СЭД</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Fira+Code:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --red:        #b91c1c;
  --red-strong: #991b1b;
  --red-soft:   rgba(185,28,28,.14);
  --red-glow:   rgba(185,28,28,.28);
  --bg:         #060607;
  --card-bg:    rgba(16,16,18,.92);
  --border:     rgba(255,255,255,.08);
  --text:       #e5e5e5;
  --muted:      #616161;
  --input-bg:   rgba(255,255,255,.05);
  --radius:     14px;
  --transition: .22s cubic-bezier(0.16,1,.3,1);
}

body {
  min-height: 100vh;
  display: grid;
  place-items: center;
  background: var(--bg);
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
  color: var(--text);
  -webkit-font-smoothing: antialiased;
  overflow: hidden;
  padding: 16px;
}

/* ─── Animated background ───────────────────────────── */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,.022) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.022) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none;
  z-index: 0;
}

/* Ambient red blob - top left */
body::after {
  content: '';
  position: fixed;
  width: 600px; height: 600px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(185,28,28,.14) 0%, rgba(185,28,28,.05) 40%, transparent 68%);
  top: -200px; left: -200px;
  pointer-events: none;
  z-index: 0;
  animation: blobDrift 18s ease-in-out infinite;
}

/* Second ambient blob - bottom right */
.blob2 {
  position: fixed;
  width: 500px; height: 500px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(185,28,28,.1) 0%, rgba(185,28,28,.04) 44%, transparent 68%);
  bottom: -180px; right: -160px;
  pointer-events: none;
  z-index: 0;
  animation: blobDrift2 22s ease-in-out infinite;
}

@keyframes blobDrift {
  0%,100% { transform: translate(0,0) scale(1); }
  40%     { transform: translate(30px, 40px) scale(1.06); }
  70%     { transform: translate(-15px, 18px) scale(.97); }
}

@keyframes blobDrift2 {
  0%,100% { transform: translate(0,0) scale(1); }
  35%     { transform: translate(-25px, -35px) scale(1.05); }
  70%     { transform: translate(12px, -16px) scale(.96); }
}

/* ─── Login card ─────────────────────────────────────── */
.login-wrap {
  position: relative;
  z-index: 1;
  width: min(440px, 100%);
  animation: cardIn .45s cubic-bezier(0.16,1,.3,1) both;
}

@keyframes cardIn {
  from { opacity: 0; transform: translateY(22px) scale(.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

.card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 22px;
  padding: 36px 32px 32px;
  box-shadow:
    0 32px 80px rgba(0,0,0,.75),
    0 0 0 1px rgba(255,255,255,.04),
    inset 0 1px 0 rgba(255,255,255,.06);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
}

/* ─── Brand ──────────────────────────────────────────── */
.brand {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 28px;
}

.logo-icon {
  width: 46px; height: 46px;
  border-radius: 14px;
  background: linear-gradient(135deg, #9b1c1c 0%, #5f0d0d 100%);
  color: #fff;
  display: grid;
  place-items: center;
  font-family: 'Fira Code', monospace;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
  border: 1px solid rgba(255,255,255,.1);
  box-shadow: 0 8px 24px rgba(155,28,28,.52), inset 0 1px 0 rgba(255,255,255,.12);
}

.brand-name {
  font-family: 'Fira Code', monospace;
  font-size: 20px;
  font-weight: 700;
  color: #fff;
  letter-spacing: -.02em;
  line-height: 1;
}

.brand-sub {
  font-size: 12px;
  color: var(--muted);
  margin-top: 3px;
  letter-spacing: .01em;
}

/* ─── Divider ────────────────────────────────────────── */
.divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--border) 30%, var(--border) 70%, transparent);
  margin-bottom: 28px;
}

/* ─── Form ───────────────────────────────────────────── */
.form-heading {
  font-size: 15px;
  font-weight: 700;
  color: #fff;
  margin-bottom: 6px;
  letter-spacing: -.01em;
}

.form-sub {
  font-size: 13px;
  color: var(--muted);
  margin-bottom: 24px;
  line-height: 1.5;
}

.form-group { margin-bottom: 16px; }

label {
  display: block;
  margin-bottom: 7px;
  font-size: 12px;
  font-weight: 600;
  color: #a3a3a3;
  letter-spacing: .04em;
  text-transform: uppercase;
}

input[type="text"],
input[type="password"] {
  width: 100%;
  padding: 11px 14px;
  border-radius: 10px;
  border: 1.5px solid var(--border);
  background: var(--input-bg);
  color: var(--text);
  font-size: 14px;
  font-family: inherit;
  transition: border-color var(--transition), box-shadow var(--transition), background var(--transition);
  outline: none;
}

input[type="text"]:hover,
input[type="password"]:hover {
  border-color: rgba(255,255,255,.14);
}

input[type="text"]:focus,
input[type="password"]:focus {
  border-color: rgba(185,28,28,.55);
  background: rgba(185,28,28,.05);
  box-shadow: 0 0 0 3px rgba(185,28,28,.13);
}

input::placeholder { color: #484848; }

/* ─── Submit button ──────────────────────────────────── */
.btn-submit {
  width: 100%;
  padding: 13px 20px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #c42020 0%, #8b1515 100%);
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  font-family: inherit;
  letter-spacing: .02em;
  cursor: pointer;
  margin-top: 8px;
  box-shadow: 0 4px 18px rgba(185,28,28,.4), inset 0 1px 0 rgba(255,255,255,.1);
  transition: all var(--transition);
  position: relative;
  overflow: hidden;
}

.btn-submit::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,.08) 0%, transparent 60%);
  pointer-events: none;
}

.btn-submit:hover {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  box-shadow: 0 6px 24px rgba(185,28,28,.55), inset 0 1px 0 rgba(255,255,255,.1);
  transform: translateY(-1px);
}

.btn-submit:active {
  transform: translateY(0);
  box-shadow: 0 3px 12px rgba(185,28,28,.35);
}

/* ─── Error ──────────────────────────────────────────── */
.error {
  background: rgba(185,28,28,.1);
  border: 1px solid rgba(185,28,28,.28);
  border-radius: 10px;
  padding: 12px 14px;
  margin-bottom: 20px;
  font-size: 13px;
  color: #fca5a5;
  backdrop-filter: blur(8px);
}

/* ─── Hint ───────────────────────────────────────────── */
.hint {
  margin-top: 20px;
  padding-top: 18px;
  border-top: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 8px;
}

.hint-icon {
  width: 28px; height: 28px;
  border-radius: 8px;
  background: rgba(255,255,255,.05);
  border: 1px solid var(--border);
  display: grid;
  place-items: center;
  font-size: 13px;
  flex-shrink: 0;
}

.hint-text {
  font-size: 12px;
  color: var(--muted);
  line-height: 1.5;
}

.hint-text strong {
  color: #a3a3a3;
  font-family: 'Fira Code', monospace;
  font-size: 11px;
}

/* ─── Footer badge ───────────────────────────────────── */
.footer-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-top: 22px;
  font-size: 11px;
  color: #383838;
  letter-spacing: .05em;
}

.footer-dot {
  width: 4px; height: 4px;
  border-radius: 50%;
  background: rgba(185,28,28,.5);
}

/* ─── Scrollbar ──────────────────────────────────────── */
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(185,28,28,.35); border-radius: 999px; }
</style>
</head>
<body>
  <div class="blob2"></div>

  <div class="login-wrap">
    <div class="card">

      <div class="brand">
        <div class="logo-icon">СЭД</div>
        <div>
          <div class="brand-name">DocV3</div>
          <div class="brand-sub">Система электронного документооборота</div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="form-heading">Добро пожаловать</div>
      <div class="form-sub">Введите учётные данные для входа в систему</div>

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
          <input id="login" name="login" type="text"
                 value="{{ old('login') }}"
                 placeholder="Введите логин"
                 required autofocus autocomplete="username">
        </div>

        <div class="form-group">
          <label for="password">Пароль</label>
          <input id="password" name="password" type="password"
                 placeholder="••••••••"
                 required autocomplete="current-password">
        </div>

        <button type="submit" class="btn-submit">Войти в систему</button>
      </form>

      <div class="hint">
        <div class="hint-icon">💡</div>
        <div class="hint-text">
          Тестовая учётная запись:
          <strong>admin</strong> / <strong>admin123</strong>
        </div>
      </div>
    </div>

    <div class="footer-badge">
      <span class="footer-dot"></span>
      DocV3 СЭД · Защищённая система
      <span class="footer-dot"></span>
    </div>
  </div>
</body>
</html>
