<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'СЭД DocV3') - СЭД</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --sidebar-w: 272px;
  --sidebar-w-collapsed: 92px;
  --radius: 16px;
  --radius-lg: 22px;
  --transition: .22s ease;
}

html[data-theme="light"] {
  /* Акцент — тёмно-красный */
  --accent: #9b1c1c;
  --accent-strong: #7f1d1d;
  --accent-soft: rgba(155,28,28,.1);
  /* Основной контент — светлый */
  --bg: #f2f2f2;
  --bg-elevated: #fafafa;
  --card: rgba(255,255,255,.92);
  --card-solid: #ffffff;
  --surface-soft: #f6f6f6;
  --border: #e2e2e2;
  --border-strong: #cccccc;
  --text: #111111;
  --text-muted: #666666;
  --shadow: 0 8px 28px rgba(0,0,0,.1);
  --topbar-bg: rgba(255,255,255,.88);
  --table-head: #f6f6f6;
  --input-bg: #ffffff;
  --chip-bg: #eeeeee;
  /* Сайдбар — всегда тёмный */
  --sidebar-text: #cccccc;
  --sidebar-text-muted: #555555;
  --sidebar-icon-bg: rgba(255,255,255,.05);
  --sidebar-divider: rgba(255,255,255,.07);
  --sidebar-hover: rgba(155,28,28,.12);
  --sidebar-active-bg: linear-gradient(90deg, rgba(155,28,28,.32) 0%, rgba(155,28,28,.14) 100%);
  --sidebar-active-color: #f87171;
  --sidebar-surface:
    radial-gradient(ellipse at top left, rgba(155,28,28,.1) 0%, transparent 50%),
    linear-gradient(180deg, #0e0e0e 0%, #0a0a0a 100%);
  --sidebar-shadow: 16px 0 48px rgba(0,0,0,.5);
}

html[data-theme="dark"] {
  --accent: #b91c1c;
  --accent-strong: #991b1b;
  --accent-soft: rgba(185,28,28,.15);
  --bg: #080808;
  --bg-elevated: #0e0e0e;
  --card: rgba(14,14,14,.97);
  --card-solid: #111111;
  --surface-soft: #161616;
  --border: #252525;
  --border-strong: #333333;
  --text: #e5e5e5;
  --text-muted: #636363;
  --shadow: 0 18px 44px rgba(0,0,0,.75);
  --topbar-bg: rgba(8,8,8,.95);
  --table-head: #111111;
  --input-bg: #0f0f0f;
  --chip-bg: #181818;
  --sidebar-text: #c8c8c8;
  --sidebar-text-muted: #4a4a4a;
  --sidebar-icon-bg: rgba(255,255,255,.04);
  --sidebar-divider: rgba(255,255,255,.06);
  --sidebar-hover: rgba(185,28,28,.13);
  --sidebar-active-bg: linear-gradient(90deg, rgba(185,28,28,.36) 0%, rgba(185,28,28,.16) 100%);
  --sidebar-active-color: #fca5a5;
  --sidebar-surface:
    radial-gradient(ellipse at top left, rgba(185,28,28,.09) 0%, transparent 48%),
    linear-gradient(180deg, #070707 0%, #050505 100%);
  --sidebar-shadow: 16px 0 48px rgba(0,0,0,.85);
}

body {
  font-family: 'Segoe UI', system-ui, sans-serif;
  background: var(--bg);
  color: var(--text);
  display: flex;
  min-height: 100vh;
  overflow-x: hidden;
}

body.has-custom-bg {
  background:
    linear-gradient(rgba(0,0,0,.5), rgba(0,0,0,.5)),
    var(--custom-bg-image) center / cover fixed no-repeat;
}

.sidebar {
  width: var(--sidebar-w);
  background: var(--sidebar-surface);
  color: var(--sidebar-text);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  transition: width var(--transition);
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  z-index: 100;
  overflow: hidden;
  box-shadow: var(--sidebar-shadow);
}

.sidebar.collapsed { width: var(--sidebar-w-collapsed); }

.sidebar::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(185,28,28,.04) 0%, transparent 30%);
  pointer-events: none;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 20px 18px 18px;
  border-bottom: 1px solid var(--sidebar-divider);
  position: relative;
  z-index: 1;
}

.sidebar.collapsed .sidebar-logo {
  justify-content: center;
  padding: 16px 10px 14px;
  min-height: 68px;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.logo-icon {
  width: 42px;
  height: 42px;
  background: linear-gradient(135deg, #9b1c1c 0%, #6b1212 100%);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 14px;
  color: #fff;
  font-weight: 800;
  letter-spacing: -.5px;
  border: 1px solid rgba(255,255,255,.08);
  box-shadow: 0 8px 24px rgba(155,28,28,.45), inset 0 1px 0 rgba(255,255,255,.08);
}

.logo-copy { min-width: 0; transition: opacity var(--transition), transform var(--transition); }
.logo-text { font-size: 15px; font-weight: 800; color: #fff; white-space: nowrap; }
.logo-sub { font-size: 12px; color: var(--sidebar-text-muted); white-space: nowrap; }

.sidebar.collapsed .logo-copy {
  opacity: 0;
  width: 0;
  transform: translateX(-8px);
  overflow: hidden;
}

.sidebar.collapsed .sidebar-brand {
  display: none;
}

.sidebar-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-shrink: 0;
}

.sidebar.collapsed .sidebar-actions {
  justify-content: center;
}

.icon-btn {
  width: 36px;
  height: 36px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.08);
  background: rgba(255,255,255,.06);
  color: #d7def5;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  line-height: 1;
  transition: all var(--transition);
}

.icon-btn:hover {
  background: rgba(255,255,255,.11);
  transform: translateY(-1px);
}

.theme-btn .theme-dark { display: none; }
html[data-theme="dark"] .theme-btn .theme-light { display: none; }
html[data-theme="dark"] .theme-btn .theme-dark { display: inline; }

#sidebarToggle {
  font-size: 0;
}

#sidebarToggle::before {
  content: '\2630';
  font-size: 18px;
  line-height: 1;
}

.sidebar-nav .nav-icon svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  display: block;
}

.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 16px 12px 10px;
  position: relative;
  z-index: 1;
}

.sidebar.collapsed .sidebar-nav {
  padding: 10px 8px 10px;
}

.nav-section {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .16em;
  text-transform: uppercase;
  color: rgba(155,28,28,.75);
  padding: 18px 14px 8px;
  white-space: nowrap;
  overflow: hidden;
  transition: opacity var(--transition), transform var(--transition);
}

.sidebar.collapsed .nav-section {
  opacity: 0;
  transform: translateX(-8px);
  height: 0;
  padding: 0 14px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  color: var(--sidebar-text);
  text-decoration: none;
  border-radius: 18px;
  margin: 4px 0;
  transition: background var(--transition), color var(--transition), transform var(--transition), box-shadow var(--transition), padding var(--transition);
  white-space: nowrap;
  overflow: hidden;
  position: relative;
  border: 1px solid transparent;
}

.nav-item:hover {
  background: var(--sidebar-hover);
  color: #fff;
  border-color: rgba(255,255,255,.05);
  transform: translateX(2px);
}

.nav-item.active {
  background: var(--sidebar-active-bg);
  color: var(--sidebar-active-color);
  border-color: rgba(255,255,255,.05);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.05);
}

.nav-item.active::before {
  content: '';
  position: absolute;
  left: -1px;
  top: 10px;
  bottom: 10px;
  width: 3px;
  background: linear-gradient(180deg, #ef4444 0%, #7f1d1d 100%);
  border-radius: 0 4px 4px 0;
  box-shadow: 0 0 14px rgba(185,28,28,.6);
}

.nav-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.06);
  transition: all var(--transition);
  color: rgba(200,208,234,.7);
}

.nav-item:hover .nav-icon {
  background: rgba(255,255,255,.1);
  color: #fff;
  border-color: rgba(255,255,255,.1);
}

.nav-item.active .nav-icon {
  background: linear-gradient(135deg, rgba(185,28,28,.35) 0%, rgba(155,28,28,.18) 100%);
  border-color: rgba(185,28,28,.35);
  color: var(--sidebar-active-color);
  box-shadow: 0 4px 14px rgba(185,28,28,.28);
}

.nav-label {
  font-size: 14px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  transition: opacity var(--transition), transform var(--transition);
}

.sidebar.collapsed .nav-item {
  width: 56px;
  height: 56px;
  min-height: 56px;
  padding: 10px;
  margin: 10px auto;
  gap: 0;
  justify-content: center;
  align-items: center;
  border-radius: 18px;
}

.sidebar.collapsed .nav-item:hover { transform: none; }

.sidebar.collapsed .nav-item.active::before {
  left: 50%;
  top: auto;
  bottom: 2px;
  transform: translateX(-50%);
  width: 24px;
  height: 3px;
  border-radius: 999px 999px 0 0;
  box-shadow: 0 0 10px rgba(185,28,28,.7);
}

.sidebar.collapsed .nav-item.active .nav-icon {
  transform: translateY(-2px);
}

.sidebar.collapsed .nav-icon {
  width: 36px;
  height: 36px;
  border-radius: 12px;
  margin: 0;
}

.sidebar.collapsed .nav-label {
  display: none;
}

.sidebar-user {
  padding: 16px;
  border-top: 1px solid var(--sidebar-divider);
  display: flex;
  flex-direction: column;
  gap: 12px;
  position: relative;
  z-index: 1;
  background: linear-gradient(180deg, rgba(255,255,255,.01) 0%, rgba(255,255,255,.03) 100%);
}

.user-card {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 12px;
  padding: 14px;
  border-radius: 18px;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.06);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
  min-width: 0;
}

.user-info { min-width: 0; flex: 1; transition: opacity var(--transition), transform var(--transition); }
.user-name { font-size: 13px; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 700; }
.user-role { font-size: 11px; color: var(--sidebar-text-muted); white-space: nowrap; }

.logout-form { display: flex; }
.logout-btn {
  width: 100%;
  background: linear-gradient(180deg, rgba(255,255,255,.08) 0%, rgba(255,255,255,.05) 100%);
  color: #fff;
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 14px;
  font-weight: 600;
}

.logout-btn:hover { background: rgba(255,255,255,.12); }

.sidebar.collapsed .sidebar-user {
  padding: 14px 8px 12px;
  align-items: center;
}

.sidebar.collapsed .user-card {
  width: 60px;
  height: 60px;
  min-height: 60px;
  justify-content: center;
  padding: 10px;
  border-radius: 18px;
}

.sidebar.collapsed .user-info {
  opacity: 0;
  width: 0;
  transform: translateX(-8px);
  overflow: hidden;
}

.sidebar.collapsed .logout-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.sidebar.collapsed .logout-btn::before {
  content: '\238B' !important;
  font-size: 16px;
  line-height: 1;
}

.sidebar.collapsed .logout-form { width: 60px; }
.sidebar.collapsed .logout-btn {
  min-height: 40px;
  padding-inline: 0;
  font-size: 0;
  position: relative;
  border-radius: 14px;
}

.sidebar.collapsed .logout-btn::before {
  content: '⎋';
  font-size: 16px;
}

.main {
  flex: 1;
  margin-left: var(--sidebar-w);
  transition: margin-left var(--transition);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main.collapsed { margin-left: var(--sidebar-w-collapsed); }

.topbar {
  background: var(--topbar-bg);
  border-bottom: 1px solid var(--border);
  padding: 14px 28px;
  display: flex;
  align-items: center;
  gap: 16px;
  position: sticky;
  top: 0;
  z-index: 50;
  backdrop-filter: blur(16px);
}

.topbar-title { font-size: 18px; font-weight: 700; color: var(--text); flex: 1; }
.topbar-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.page-content { padding: 28px; flex: 1; }

.topbar-clock {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 16px;
  border-radius: 16px;
  background: var(--card);
  border: 1px solid var(--border);
  backdrop-filter: blur(16px);
  box-shadow: var(--shadow);
  cursor: default;
  user-select: none;
}
.clock-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: var(--accent-soft);
  flex-shrink: 0;
  color: var(--accent);
}
.clock-icon svg { width: 16px; height: 16px; }
.clock-body {
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.clock-hm {
  font-size: 17px;
  font-weight: 800;
  color: var(--text);
  letter-spacing: -.03em;
  font-variant-numeric: tabular-nums;
  line-height: 1;
}
.clock-date {
  font-size: 11px;
  color: var(--text-muted);
  font-weight: 500;
  white-space: nowrap;
  letter-spacing: .01em;
}
@media (max-width: 640px) { .topbar-clock { display: none; } }

.theme-pill {
  width: 40px;
  height: 40px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  backdrop-filter: blur(12px);
  box-shadow: var(--shadow);
}

.bg-picker-btn {
  position: relative;
  overflow: hidden;
  font-size: 0;
}

.bg-picker-btn input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}

.bg-picker-btn::before {
  content: '\1F5BC';
  font-size: 17px;
  line-height: 1;
}

#resetBackgroundToggle {
  font-size: 0;
}

#resetBackgroundToggle::before {
  content: '\232B';
  font-size: 17px;
  line-height: 1;
}

#topbarThemeToggle .theme-light,
#topbarThemeToggle .theme-dark {
  font-size: 0;
  line-height: 1;
}

#topbarThemeToggle .theme-light::before {
  content: '\263E';
  font-size: 17px;
}

#topbarThemeToggle .theme-dark::before {
  content: '\2600';
  font-size: 17px;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 10px;
  font-size: 13.5px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all var(--transition);
  text-decoration: none;
}

.btn-primary { background: var(--accent); color: #fff; }
.btn-primary:hover { background: var(--accent-strong); }
.btn-secondary { background: var(--chip-bg); color: var(--text); }
.btn-secondary:hover { filter: brightness(.98); }
.btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--text); }
.btn-outline:hover { border-color: var(--accent); color: var(--accent); }
.btn-success { background: #198754; color: #fff; }
.btn-warning { background: #fd7e14; color: #fff; }
.btn-danger  { background: #dc3545; color: #fff; }
.btn-sm { padding: 5px 12px; font-size: 12px; }
.btn-icon { padding: 7px; border-radius: 8px; }

.card {
  background: var(--card);
  backdrop-filter: blur(16px);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
}

.card-header { padding: 18px 22px 14px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.card-title { font-size: 15px; font-weight: 600; }
.card-body { padding: 20px 22px; }

.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 28px; }
.stat-card { background: var(--card); border-radius: var(--radius); padding: 22px; box-shadow: var(--shadow); border: 1px solid var(--border); display: flex; align-items: flex-start; gap: 16px; }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.stat-label { font-size: 12px; color: var(--text-muted); margin-bottom: 4px; }
.stat-value { font-size: 28px; font-weight: 700; color: var(--text); line-height: 1; }
.stat-sub { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th {
  font-size: 11.5px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--text-muted);
  padding: 12px 16px;
  border-bottom: 2px solid var(--border);
  background: var(--table-head);
  white-space: nowrap;
}
td { padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 13.5px; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: color-mix(in srgb, var(--table-head) 80%, transparent); }
.td-link { color: var(--text); text-decoration: none; font-weight: 500; }
.td-link:hover { color: var(--accent); }

.badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 500; }
.badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
/* Бейджи — светлая тема (белые карточки) */
.status-draft { background: #f0f0f0; color: #555555; }
.status-registered { background: #dbeafe; color: #1d4ed8; }
.status-review { background: #ffedd5; color: #c2410c; }
.status-approved { background: #dcfce7; color: #15803d; }
.status-rejected { background: #fee2e2; color: #b91c1c; }
.status-archive { background: #f0f0f0; color: #777777; }
.priority-low { background: #f0f0f0; color: #555555; }
.priority-medium { background: #dbeafe; color: #1d4ed8; }
.priority-high { background: #ffedd5; color: #c2410c; }
.priority-urgent { background: #fee2e2; color: #b91c1c; }
.type-incoming { background: #dbeafe; color: #1d4ed8; }
.type-outgoing { background: #dcfce7; color: #15803d; }
.type-memo { background: #f3e8ff; color: #7e22ce; }
.type-internal { background: #ffedd5; color: #c2410c; }

/* Бейджи — тёмная тема (чёрные карточки) */
html[data-theme="dark"] .status-draft { background: #1e1e1e; color: #888888; }
html[data-theme="dark"] .status-registered { background: #161c2e; color: #7da8f0; }
html[data-theme="dark"] .status-review { background: #231610; color: #e07a40; }
html[data-theme="dark"] .status-approved { background: #111e16; color: #5eb87a; }
html[data-theme="dark"] .status-rejected { background: #1f1010; color: #e05a5a; }
html[data-theme="dark"] .status-archive { background: #1a1a1a; color: #666666; }
html[data-theme="dark"] .priority-low { background: #1e1e1e; color: #888888; }
html[data-theme="dark"] .priority-medium { background: #161c2e; color: #7da8f0; }
html[data-theme="dark"] .priority-high { background: #231610; color: #e07a40; }
html[data-theme="dark"] .priority-urgent { background: #1f1010; color: #e05a5a; }
html[data-theme="dark"] .type-incoming { background: #161c2e; color: #7da8f0; }
html[data-theme="dark"] .type-outgoing { background: #111e16; color: #5eb87a; }
html[data-theme="dark"] .type-memo { background: #1a1228; color: #b07ae0; }
html[data-theme="dark"] .type-internal { background: #231610; color: #e07a40; }

.filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 18px; }
.filter-tab {
  padding: 7px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border: 1.5px solid var(--border);
  background: var(--card);
  color: var(--text-muted);
  transition: all var(--transition);
  text-decoration: none;
}
.filter-tab:hover { border-color: var(--accent); color: var(--accent); }
.filter-tab.active { background: var(--accent); color: #fff; border-color: var(--accent); }

.user-combobox { position: relative; }
.user-combobox-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: var(--card-solid);
  border: 1.5px solid var(--border);
  border-radius: 10px;
  z-index: 300;
  max-height: 220px;
  overflow-y: auto;
  box-shadow: 0 8px 28px rgba(0,0,0,.22);
  display: none;
}
.user-combobox-dropdown.open { display: block; }
.user-combobox-option {
  padding: 9px 14px;
  cursor: pointer;
  font-size: 13px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  border-bottom: 1px solid var(--border);
  transition: background var(--transition), color var(--transition);
}
.user-combobox-option:last-child { border-bottom: none; }
.user-combobox-option:hover { background: var(--accent-soft); color: var(--accent); }
.user-combobox-option-dept { font-size: 11.5px; color: var(--text-muted); white-space: nowrap; }
.user-combobox-option:hover .user-combobox-option-dept { color: inherit; opacity: .75; }

.doc-type-filter {
  padding: 5px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  border: 1.5px solid var(--border);
  background: transparent;
  color: var(--text-muted);
  transition: all var(--transition);
  white-space: nowrap;
}
.doc-type-filter:hover {
  border-color: var(--accent);
  color: var(--accent);
}
.doc-type-filter.active {
  background: var(--accent);
  border-color: var(--accent);
  color: #fff;
}

.search-bar { display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; }
.search-input {
  flex: 1;
  padding: 9px 14px;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  font-size: 13.5px;
  background: var(--card-solid);
  color: var(--text);
}

.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); backdrop-filter: blur(4px); z-index: 500; align-items: center; justify-content: center; }
.modal-overlay.show { display: flex; }
.modal { background: var(--card-solid); border-radius: 16px; width: min(720px, 95vw); max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,.2); border: 1px solid var(--border); }
.modal-header { padding: 20px 24px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.modal-title { font-size: 17px; font-weight: 700; }
.modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-muted); line-height: 1; padding: 4px; }
.modal-body { padding: 20px 24px; overflow-y: auto; flex: 1; }
.modal-footer { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; }
.form-group { margin-bottom: 16px; }
.form-label { font-size: 12.5px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; display: block; }
.form-control {
  width: 100%;
  padding: 9px 13px;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  font-size: 13.5px;
  background: var(--input-bg);
  color: var(--text);
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
textarea.form-control { min-height: 90px; resize: vertical; }

.flash { padding: 12px 18px; border-radius: 12px; font-size: 13.5px; margin-bottom: 18px; }
.flash-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.flash-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
html[data-theme="dark"] .flash-success { background: #111e16; color: #6ed48e; border-color: #1f3828; }
html[data-theme="dark"] .flash-error { background: #1f1010; color: #f07070; border-color: #3d1818; }

.avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), #c0392b); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0; }
.avatar-sm { width: 28px; height: 28px; font-size: 11px; }
.overdue-badge { background: #fee2e2; color: #b91c1c; font-size: 10px; padding: 2px 7px; border-radius: 10px; animation: pulse 2s infinite; }
html[data-theme="dark"] .overdue-badge { background: #2a0e0e; color: #f87171; border: 1px solid #4a1515; }
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .6; } }

.dropzone { border: 2px dashed var(--border); border-radius: 10px; padding: 28px; text-align: center; cursor: pointer; transition: all var(--transition); }
.dropzone:hover, .dropzone.drag-over { border-color: var(--accent); background: var(--accent-soft); }
.file-list { margin-top: 12px; display: flex; flex-direction: column; gap: 6px; }
.file-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: var(--chip-bg); border-radius: 8px; font-size: 13px; }
.file-ext { font-weight: 700; width: 36px; text-align: center; padding: 2px 5px; border-radius: 4px; font-size: 10px; }
.ext-pdf { background: #fee2e2; color: #dc2626; }
.ext-docx, .ext-doc { background: #dbeafe; color: #1d4ed8; }
.ext-xlsx, .ext-xls { background: #dcfce7; color: #15803d; }
.ext-jpg, .ext-jpeg, .ext-png { background: #fef3c7; color: #b45309; }
.file-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.file-size { font-size: 11px; color: var(--text-muted); white-space: nowrap; }
.file-remove { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 16px; padding: 0 2px; }

.kanban { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; align-items: start; }
.kanban-col { background: var(--table-head); border-radius: var(--radius); padding: 0 0 12px; border: 1px solid var(--border); }
.kanban-header { padding: 14px 16px 12px; display: flex; align-items: center; gap: 8px; border-bottom: 2px solid var(--border); }
.kanban-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.kanban-title { font-size: 13px; font-weight: 700; flex: 1; }
.kanban-count { background: var(--border); color: var(--text-muted); font-size: 11px; padding: 2px 7px; border-radius: 10px; }
.kanban-cards { padding: 12px 10px; display: flex; flex-direction: column; gap: 10px; min-height: 60px; }
.task-card { background: var(--card-solid); border-radius: 10px; padding: 14px; border: 1px solid var(--border); box-shadow: 0 1px 4px rgba(0,0,0,.06); }
.task-card.overdue { border-left: 3px solid #9b1c1c; }
.task-title { font-size: 13.5px; font-weight: 600; margin-bottom: 8px; line-height: 1.4; }
.task-meta { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-bottom: 10px; }
.task-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 8px; }
.assignee-chip { display: flex; align-items: center; gap: 5px; font-size: 11.5px; color: var(--text-muted); }
.deadline-chip { font-size: 11px; color: var(--text-muted); }
.deadline-chip.late { color: #dc3545; font-weight: 600; }
.task-actions { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 10px; }

.role-badge { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.role-admin { background: #fee2e2; color: #991b1b; }
.role-manager { background: #dbeafe; color: #1d4ed8; }
.role-clerk { background: #dcfce7; color: #15803d; }
.role-employee { background: #f0f0f0; color: #555555; }
.status-pill { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.status-active { background: #dcfce7; color: #15803d; }
.status-inactive { background: #fee2e2; color: #b91c1c; }

html[data-theme="dark"] .role-admin { background: #1f1010; color: #f07070; border: 1px solid #3d1818; }
html[data-theme="dark"] .role-manager { background: #18223a; color: #93b8ff; border: 1px solid #1e2e4a; }
html[data-theme="dark"] .role-clerk { background: #152218; color: #6ed48e; border: 1px solid #1c3020; }
html[data-theme="dark"] .role-employee { background: #1e1e1e; color: #888888; border: 1px solid #2e2e2e; }
html[data-theme="dark"] .status-active { background: #152218; color: #6ed48e; border: 1px solid #1c3020; }
html[data-theme="dark"] .status-inactive { background: #1f1010; color: #f07070; border: 1px solid #3d1818; }

@media (max-width: 1100px) {
  .kanban { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 900px) {
  .main { margin-left: 0; }
  .sidebar { transform: translateX(-100%); transition: transform var(--transition); width: var(--sidebar-w); }
  .sidebar.collapsed { transform: translateX(0); width: var(--sidebar-w); }
  .main.collapsed { margin-left: 0; }
  .form-row { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
  .page-content { padding: 18px; }
  .topbar { padding: 12px 18px; flex-wrap: wrap; }
  .search-bar { flex-direction: column; }
  .kanban { grid-template-columns: 1fr; }
}
</style>
@stack('styles')
</head>
<body>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-brand">
      <div class="logo-icon">СЭД</div>
      <div class="logo-copy">
        <div class="logo-text">DocV3</div>
        <div class="logo-sub">Документооборот</div>
      </div>
    </div>
    <div class="sidebar-actions">
      <button class="icon-btn" type="button" id="sidebarToggle" aria-label="Свернуть меню">☰</button>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Главное</div>
    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Дашборд">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
          <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
        </svg>
      </span>
      <span class="nav-label">Дашборд</span>
    </a>

    <div class="nav-section">Документы</div>
    <a href="{{ route('documents.index') }}" class="nav-item {{ request()->routeIs('documents.*') ? 'active' : '' }}" title="Документы">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
          <line x1="10" y1="9" x2="8" y2="9"/>
        </svg>
      </span>
      <span class="nav-label">Документы</span>
    </a>

    <div class="nav-section">Задачи</div>
    <a href="{{ route('tasks.index') }}" class="nav-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}" title="Менеджер задач">
      <span class="nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 11 12 14 22 4"/>
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
        </svg>
      </span>
      <span class="nav-label">Менеджер задач</span>
    </a>

    @can('manage-users')
      <div class="nav-section">Администрирование</div>
      <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}" title="Пользователи">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </span>
        <span class="nav-label">Пользователи</span>
      </a>
    @endcan

    @can('manage-structure')
      <a href="{{ route('organization.index') }}" class="nav-item {{ request()->routeIs('organization.*') || request()->routeIs('departments.*') || request()->routeIs('groups.*') ? 'active' : '' }}" title="Отделы и группы">
        <span class="nav-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="8" y="1" width="8" height="5" rx="1"/>
            <rect x="1" y="14" width="6" height="5" rx="1"/>
            <rect x="9" y="14" width="6" height="5" rx="1"/>
            <rect x="17" y="14" width="6" height="5" rx="1"/>
            <path d="M4 14v-3h16v3"/>
            <line x1="12" y1="6" x2="12" y2="11"/>
          </svg>
        </span>
        <span class="nav-label">Отделы и группы</span>
      </a>
    @endcan
  </nav>

  <div class="sidebar-user">
    @if($currentUser)
      <a href="{{ route('profile.edit') }}" class="user-card" title="Мой профиль" style="text-decoration:none; color:inherit;">
        <div class="avatar" style="background: {{ avatarColor($currentUser->name) }};">{{ $currentUser->initials }}</div>
        <div class="user-info">
          <div class="user-name">{{ $currentUser->name }}</div>
          <div class="user-role">{{ $currentUser->role_name }}</div>
        </div>
      </a>
      <form method="POST" action="{{ route('logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="btn btn-sm logout-btn" title="Выйти">Выйти</button>
      </form>
    @endif
  </div>
</aside>

<div class="main" id="main">
  <header class="topbar">
    <div class="topbar-title">@yield('page-title', 'Система документооборота')</div>
    <div class="topbar-clock" id="topbarClock" aria-live="polite">
      <div class="clock-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <div class="clock-body">
        <div class="clock-hm" id="clockHM">00:00</div>
        <div class="clock-date" id="clockDate"></div>
      </div>
    </div>
    <div class="topbar-actions">
      <label class="icon-btn theme-pill bg-picker-btn" title="Выбрать фон JPG" aria-label="Выбрать фон JPG">
        <span>🖼</span>
        <input type="file" id="backgroundPicker" accept=".jpg,.jpeg,image/jpeg">
      </label>
      <button class="icon-btn theme-pill" type="button" id="resetBackgroundToggle" title="Сбросить фон" aria-label="Сбросить фон">⌫</button>
      <button class="icon-btn theme-btn theme-pill" type="button" id="topbarThemeToggle" aria-label="Переключить тему">
        <span class="theme-light">☾</span>
        <span class="theme-dark">☀</span>
      </button>
      @auth
        <a href="{{ route('notifications.index') }}" class="icon-btn theme-pill" style="position:relative;" title="Уведомления">
          <span style="font-size:17px; line-height:1;">🔔</span>
          @if($unreadCount > 0)
            <span style="position:absolute; top:4px; right:4px; width:16px; height:16px; background:var(--accent); border-radius:50%; font-size:9px; font-weight:700; color:#fff; display:flex; align-items:center; justify-content:center; line-height:1;">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
          @endif
        </a>
      @endauth
      @yield('topbar-actions')
      @if($currentUser && count($currentUser->allowedDocumentTypes()) > 0)
        <a href="{{ route('documents.create') }}" class="btn btn-primary">
          <span>+</span> Новый документ
        </a>
      @endif
    </div>
  </header>

  <div class="page-content">
    @if(session('success'))
      <div class="flash flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="flash flash-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
      <div class="flash flash-error">
        @foreach($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif
    @yield('content')
  </div>
</div>

<script>
const html = document.documentElement;
const sidebar = document.getElementById('sidebar');
const main = document.getElementById('main');
const body = document.body;
const backgroundPicker = document.getElementById('backgroundPicker');
const resetBackgroundToggle = document.getElementById('resetBackgroundToggle');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const backgroundUploadUrl = @json(route('profile.background.update'));
const backgroundDeleteUrl = @json(route('profile.background.destroy'));
const serverBackgroundImage = @json($currentUser?->background_image);

function applyCustomBackground(imageData) {
  if (imageData) {
    body.classList.add('has-custom-bg');
    body.style.setProperty('--custom-bg-image', `url("${imageData}")`);
  } else {
    body.classList.remove('has-custom-bg');
    body.style.removeProperty('--custom-bg-image');
  }
}

function applyTheme(theme) {
  html.setAttribute('data-theme', theme);
  localStorage.setItem('docv3-theme', theme);
}

function toggleTheme() {
  const nextTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  applyTheme(nextTheme);
}

function applySidebarState(collapsed) {
  sidebar.classList.toggle('collapsed', collapsed);
  main.classList.toggle('collapsed', collapsed);
  localStorage.setItem('docv3-sidebar-collapsed', collapsed ? '1' : '0');
}

function toggleSidebar() {
  applySidebarState(!sidebar.classList.contains('collapsed'));
}

function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

const savedTheme = localStorage.getItem('docv3-theme');
if (savedTheme === 'dark' || savedTheme === 'light') {
  applyTheme(savedTheme);
}

applyCustomBackground(serverBackgroundImage);

const sidebarCollapsed = localStorage.getItem('docv3-sidebar-collapsed') === '1';
if (window.innerWidth > 900) {
  applySidebarState(sidebarCollapsed);
}

document.getElementById('sidebarToggle')?.addEventListener('click', toggleSidebar);
document.getElementById('topbarThemeToggle')?.addEventListener('click', toggleTheme);
resetBackgroundToggle?.addEventListener('click', function () {
  fetch(backgroundDeleteUrl, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
    },
  })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('delete_failed');
      }

      return response.json();
    })
    .then(function () {
      applyCustomBackground(null);
      if (backgroundPicker) {
        backgroundPicker.value = '';
      }
    })
    .catch(function () {
      alert('Не удалось сбросить фон приложения.');
    });
});

backgroundPicker?.addEventListener('change', function (event) {
  const file = event.target.files?.[0];
  if (!file) {
    return;
  }

  const isJpg = ['image/jpeg', 'image/jpg'].includes(file.type) || /\.(jpe?g)$/i.test(file.name);
  if (!isJpg) {
    alert('Можно выбрать только JPG файл.');
    event.target.value = '';
    return;
  }

  const formData = new FormData();
  formData.append('background', file);

  fetch(backgroundUploadUrl, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
    },
    body: formData,
  })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('upload_failed');
      }

      return response.json();
    })
    .then(function (data) {
      applyCustomBackground(data.background_image || null);
      event.target.value = '';
    })
    .catch(function () {
      alert('Не удалось сохранить фон приложения.');
      event.target.value = '';
    });
});

window.addEventListener('resize', function () {
  if (window.innerWidth <= 900) {
    sidebar.classList.remove('collapsed');
    main.classList.remove('collapsed');
  } else {
    applySidebarState(localStorage.getItem('docv3-sidebar-collapsed') === '1');
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
  }
});

// Clock
(function () {
  const elHM   = document.getElementById('clockHM');
  const elDate = document.getElementById('clockDate');
  if (!elHM) return;

  const days   = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];
  const months = ['янв','фев','мар','апр','май','июн','июл','авг','сен','окт','ноя','дек'];

  function pad(n) { return String(n).padStart(2, '0'); }

  function tick() {
    const now = new Date();
    elHM.textContent  = pad(now.getHours()) + ':' + pad(now.getMinutes());
    elDate.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
  }

  tick();
  setInterval(tick, 1000);
})();
</script>
@stack('scripts')
</body>
</html>
