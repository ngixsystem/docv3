<!DOCTYPE html>
<html lang="ru" data-theme="light">
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
  --accent: #e94560;
  --accent-strong: #cf3550;
  --accent-soft: rgba(233,69,96,.14);
  --bg: #eef2f8;
  --bg-elevated: #f8fbff;
  --card: rgba(255,255,255,.86);
  --card-solid: #ffffff;
  --surface-soft: #f8fafc;
  --border: #dbe5f1;
  --border-strong: #c9d6e6;
  --text: #172033;
  --text-muted: #6f7d95;
  --shadow: 0 14px 36px rgba(15,23,42,.08);
  --topbar-bg: rgba(255,255,255,.8);
  --table-head: #f5f8fc;
  --input-bg: #f7faff;
  --chip-bg: #eef4fb;
  --sidebar-text: #c8d0ea;
  --sidebar-text-muted: #8ea0d1;
  --sidebar-icon-bg: rgba(255,255,255,.045);
  --sidebar-divider: rgba(255,255,255,.09);
  --sidebar-hover: rgba(255,255,255,.06);
  --sidebar-active-bg: linear-gradient(90deg, rgba(233,69,96,.23) 0%, rgba(233,69,96,.12) 100%);
  --sidebar-active-color: #ff7f98;
  --sidebar-surface:
    radial-gradient(circle at top left, rgba(255,255,255,.09), transparent 26%),
    linear-gradient(180deg, #1b1d36 0%, #1a1c34 40%, #18192f 100%);
  --sidebar-shadow: 16px 0 40px rgba(10,12,28,.18);
}

html[data-theme="dark"] {
  --accent: #ff6b86;
  --accent-strong: #ff5473;
  --accent-soft: rgba(255,107,134,.18);
  --bg: #0f1725;
  --bg-elevated: #101a2d;
  --card: rgba(18,27,43,.88);
  --card-solid: #152034;
  --surface-soft: #1a2438;
  --border: #24334c;
  --border-strong: #30405c;
  --text: #eef4ff;
  --text-muted: #93a4bf;
  --shadow: 0 18px 44px rgba(0,0,0,.28);
  --topbar-bg: rgba(16,24,38,.84);
  --table-head: #182236;
  --input-bg: #121d30;
  --chip-bg: #162236;
  --sidebar-text: #d4dbf4;
  --sidebar-text-muted: #8ea0d1;
  --sidebar-icon-bg: rgba(255,255,255,.055);
  --sidebar-divider: rgba(255,255,255,.08);
  --sidebar-hover: rgba(255,255,255,.07);
  --sidebar-active-bg: linear-gradient(90deg, rgba(255,107,134,.24) 0%, rgba(255,107,134,.12) 100%);
  --sidebar-active-color: #ff9db1;
  --sidebar-surface:
    radial-gradient(circle at top left, rgba(255,255,255,.08), transparent 24%),
    linear-gradient(180deg, #131a2b 0%, #151c2e 40%, #111827 100%);
  --sidebar-shadow: 16px 0 40px rgba(0,0,0,.34);
}

body {
  font-family: 'Segoe UI', system-ui, sans-serif;
  background:
    radial-gradient(circle at top, rgba(255,255,255,.45), transparent 20%),
    linear-gradient(180deg, var(--bg-elevated) 0%, var(--bg) 100%);
  color: var(--text);
  display: flex;
  min-height: 100vh;
  overflow-x: hidden;
}

body.has-custom-bg {
  background:
    linear-gradient(rgba(10,16,26,.38), rgba(10,16,26,.38)),
    var(--custom-bg-image) center / cover fixed no-repeat;
}

html[data-theme="light"] body.has-custom-bg {
  background:
    linear-gradient(rgba(255,255,255,.32), rgba(255,255,255,.32)),
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
  background:
    linear-gradient(180deg, rgba(255,255,255,.03), transparent 22%),
    linear-gradient(90deg, rgba(255,255,255,.015) 1px, transparent 1px);
  background-size: auto, 24px 24px;
  pointer-events: none;
  opacity: .4;
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
  background: linear-gradient(135deg, #ff5478 0%, #ff6f61 100%);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 16px;
  color: #fff;
  font-weight: 800;
  box-shadow: 0 14px 28px rgba(233,69,96,.28);
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

.sidebar-nav .nav-icon {
  font-size: 0;
  font-family: 'Segoe UI Symbol', 'Segoe UI', sans-serif;
}

.sidebar-nav .nav-icon::before {
  font-size: 16px;
  line-height: 1;
}

.sidebar-nav .nav-item:nth-of-type(1) .nav-icon::before { content: '\2302'; }
.sidebar-nav .nav-item:nth-of-type(2) .nav-icon::before { content: '\25A4'; }
.sidebar-nav .nav-item:nth-of-type(3) .nav-icon::before { content: '\2713'; }
.sidebar-nav .nav-item:nth-of-type(4) .nav-icon::before { content: '\25D4'; }
.sidebar-nav .nav-item:nth-of-type(5) .nav-icon::before { content: '\25A6'; }

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
  color: rgba(142,160,209,.58);
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
  width: 4px;
  background: linear-gradient(180deg, #ff8fa6 0%, var(--accent) 100%);
  border-radius: 0 4px 4px 0;
  box-shadow: 0 0 16px rgba(233,69,96,.45);
}

.nav-icon {
  font-size: 16px;
  width: 30px;
  height: 30px;
  border-radius: 11px;
  text-align: center;
  line-height: 1;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--sidebar-icon-bg);
  transition: all var(--transition);
}

.nav-item.active .nav-icon {
  background: rgba(255,255,255,.08);
  color: var(--sidebar-active-color);
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
  width: 26px;
  height: 4px;
  border-radius: 999px 999px 0 0;
}

.sidebar.collapsed .nav-item.active .nav-icon {
  transform: translateY(-2px);
}

.sidebar.collapsed .nav-icon {
  width: 32px;
  height: 32px;
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
.status-draft { background: #f1f5f9; color: #64748b; }
.status-registered { background: #eff6ff; color: #1d4ed8; }
.status-review { background: #fff7ed; color: #c2410c; }
.status-approved { background: #f0fdf4; color: #15803d; }
.status-rejected { background: #fef2f2; color: #dc2626; }
.status-archive { background: #f8fafc; color: #94a3b8; }
.priority-low { background: #f1f5f9; color: #64748b; }
.priority-medium { background: #eff6ff; color: #1d4ed8; }
.priority-high { background: #fff7ed; color: #c2410c; }
.priority-urgent { background: #fef2f2; color: #dc2626; }
.type-incoming { background: #eff6ff; color: #1d4ed8; }
.type-outgoing { background: #f0fdf4; color: #15803d; }
.type-memo { background: #fdf4ff; color: #7e22ce; }
.type-internal { background: #fff7ed; color: #c2410c; }

html[data-theme="dark"] .status-draft,
html[data-theme="dark"] .priority-low,
html[data-theme="dark"] .role-employee { background: #1c2739; color: #aebbd1; }
html[data-theme="dark"] .status-registered,
html[data-theme="dark"] .priority-medium,
html[data-theme="dark"] .type-incoming,
html[data-theme="dark"] .role-manager { background: #16253d; color: #8cb5ff; }
html[data-theme="dark"] .status-review,
html[data-theme="dark"] .priority-high,
html[data-theme="dark"] .type-internal { background: #38261d; color: #ffba84; }
html[data-theme="dark"] .status-approved,
html[data-theme="dark"] .type-outgoing,
html[data-theme="dark"] .role-clerk { background: #182c23; color: #8de0b0; }
html[data-theme="dark"] .status-rejected,
html[data-theme="dark"] .priority-urgent { background: #351d23; color: #ff9aa9; }
html[data-theme="dark"] .type-memo,
html[data-theme="dark"] .role-admin { background: #271d34; color: #ddb5ff; }
html[data-theme="dark"] .status-archive { background: #1a2434; color: #9eb0c8; }

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
.flash-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
html[data-theme="dark"] .flash-success { background: #182c23; color: #8de0b0; border-color: #234d3b; }
html[data-theme="dark"] .flash-error { background: #351d23; color: #ffb5c0; border-color: #5a2732; }

.avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), #c0392b); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0; }
.avatar-sm { width: 28px; height: 28px; font-size: 11px; }
.overdue-badge { background: #fef2f2; color: #dc2626; font-size: 10px; padding: 2px 7px; border-radius: 10px; animation: pulse 2s infinite; }
html[data-theme="dark"] .overdue-badge { background: #351d23; color: #ffb5c0; }
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
.task-card.overdue { border-left: 3px solid #dc3545; }
.task-title { font-size: 13.5px; font-weight: 600; margin-bottom: 8px; line-height: 1.4; }
.task-meta { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-bottom: 10px; }
.task-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 8px; }
.assignee-chip { display: flex; align-items: center; gap: 5px; font-size: 11.5px; color: var(--text-muted); }
.deadline-chip { font-size: 11px; color: var(--text-muted); }
.deadline-chip.late { color: #dc3545; font-weight: 600; }
.task-actions { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 10px; }

.role-badge { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.role-admin { background: #fdf4ff; color: #7e22ce; }
.role-manager { background: #eff6ff; color: #1d4ed8; }
.role-clerk { background: #f0fdf4; color: #15803d; }
.role-employee { background: #f8fafc; color: #475569; }
.status-pill { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.status-active { background: #f0fdf4; color: #15803d; }
.status-inactive { background: #fef2f2; color: #dc2626; }

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
      <span class="nav-icon">⌂</span>
      <span class="nav-label">Дашборд</span>
    </a>

    <div class="nav-section">Документы</div>
    <a href="{{ route('documents.index') }}" class="nav-item {{ request()->routeIs('documents.*') ? 'active' : '' }}" title="Документы">
      <span class="nav-icon">▤</span>
      <span class="nav-label">Документы</span>
    </a>

    <div class="nav-section">Задачи</div>
    <a href="{{ route('tasks.index') }}" class="nav-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}" title="Менеджер задач">
      <span class="nav-icon">✓</span>
      <span class="nav-label">Менеджер задач</span>
    </a>

    @can('manage-users')
      <div class="nav-section">Администрирование</div>
      <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}" title="Пользователи">
        <span class="nav-icon">◔</span>
        <span class="nav-label">Пользователи</span>
      </a>
    @endcan

    @can('manage-structure')
      <a href="{{ route('organization.index') }}" class="nav-item {{ request()->routeIs('organization.*') || request()->routeIs('departments.*') || request()->routeIs('groups.*') ? 'active' : '' }}" title="Отделы и группы">
        <span class="nav-icon">▦</span>
        <span class="nav-label">Отделы и группы</span>
      </a>
    @endcan
  </nav>

  <div class="sidebar-user">
    @if($currentUser)
      <div class="user-card" title="{{ $currentUser->name }}">
        <div class="avatar" style="background: {{ avatarColor($currentUser->name) }};">{{ $currentUser->initials }}</div>
        <div class="user-info">
          <div class="user-name">{{ $currentUser->name }}</div>
          <div class="user-role">{{ $currentUser->role_name }}</div>
        </div>
      </div>
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
</script>
@stack('scripts')
</body>
</html>
