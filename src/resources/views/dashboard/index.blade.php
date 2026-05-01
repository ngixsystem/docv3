@extends('layouts.app')
@section('page-title', 'Дашборд')

@php
  $heroTotal = max($stats['total'], 1);
  $typeTotal = max($documentTypeStats->sum('count'), 1);
  $statusMax = max($documentStatusStats->max('count'), 1);
  $taskStatusMax = max($taskStatusStats->max('count'), 1);
  $activityMax = max($activityDays->map(fn ($day) => max($day['documents'], $day['tasks']))->max(), 1);
  $activityDocPeak = max($activityDays->max('documents'), 1);
  $activityTaskPeak = max($activityDays->max('tasks'), 1);
  $activityDocTotal = $activityDays->sum('documents');
  $activityTaskTotal = $activityDays->sum('tasks');
  $activityCombinedMax = max($activityDays->map(fn ($day) => $day['documents'] + $day['tasks'])->max(), 1);

  $typePalette = [
    'incoming' => ['#2563eb', '#93c5fd'],
    'outgoing' => ['#16a34a', '#86efac'],
    'memo' => ['#9333ea', '#d8b4fe'],
    'internal' => ['#ea580c', '#fdba74'],
  ];

  $statusPalette = [
    'draft' => '#64748b',
    'registered' => '#2563eb',
    'review' => '#f97316',
    'approved' => '#16a34a',
    'rejected' => '#dc2626',
    'archive' => '#94a3b8',
  ];

  $taskPalette = [
    'new' => '#64748b',
    'in_progress' => '#2563eb',
    'paused' => '#f97316',
    'done' => '#16a34a',
  ];

  $docPoints = $activityDays->values()->map(function ($day, $index) use ($activityMax) {
    $x = 30 + ($index * 15.86);
    $y = 160 - (($day['documents'] / $activityMax) * 118);
    return round($x, 2) . ',' . round($y, 2);
  })->implode(' ');

  $taskPoints = $activityDays->values()->map(function ($day, $index) use ($activityMax) {
    $x = 30 + ($index * 15.86);
    $y = 160 - (($day['tasks'] / $activityMax) * 118);
    return round($x, 2) . ',' . round($y, 2);
  })->implode(' ');

  $docAreaPoints = '30,160 ' . $docPoints . ' 490,160';
  $taskAreaPoints = '30,160 ' . $taskPoints . ' 490,160';
  $yAxisTicks = collect(range(0, 4))->map(function ($step) use ($activityMax) {
    return [
      'value' => round(($activityMax / 4) * (4 - $step)),
      'y' => 42 + ($step * 30),
    ];
  });

  $activityBars = $activityDays->values()->map(function ($day, $index) use ($activityCombinedMax) {
    $x = 2 + ($index * 17);
    $height = max(round((($day['documents'] + $day['tasks']) / $activityCombinedMax) * 170), 6);

    return [
      'x' => $x,
      'y' => 184 - $height,
      'height' => $height,
      'total' => $day['documents'] + $day['tasks'],
      'label' => $day['label'],
    ];
  });

@endphp

@push('styles')
<style>
.dashboard-shell { display:flex; flex-direction:column; gap:22px; }
.dashboard-shell {
  --dash-panel-bg: linear-gradient(180deg, rgba(255,255,255,.94) 0%, rgba(248,251,255,.92) 100%);
  --dash-panel-strong: linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(245,249,255,.95) 100%);
  --dash-panel-warm: linear-gradient(145deg, #fff7ed 0%, #ffffff 50%, #f5f3ff 100%);
  --dash-track: #edf2f7;
  --dash-grid: #e5edf7;
  --dash-point-stroke: #ffffff;
  --dash-task-card: #ffffff;
  --dash-soft-shadow: 0 8px 20px rgba(15,23,42,.04);
}
html[data-theme="dark"] .dashboard-shell {
  --dash-panel-bg: linear-gradient(180deg, rgba(16,16,16,.97) 0%, rgba(12,12,12,.95) 100%);
  --dash-panel-strong: linear-gradient(180deg, rgba(20,20,20,.99) 0%, rgba(15,15,15,.97) 100%);
  --dash-panel-warm: linear-gradient(145deg, rgba(22,10,10,.97) 0%, rgba(16,16,16,.97) 52%, rgba(18,12,12,.97) 100%);
  --dash-track: #252525;
  --dash-grid: #2e2e2e;
  --dash-point-stroke: #080808;
  --dash-task-card: #141414;
  --dash-soft-shadow: 0 12px 26px rgba(0,0,0,.45);
}
.hero-grid { display:grid; grid-template-columns:1.4fr .9fr; gap:20px; }
.hero-panel {
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(circle at 8% 15%, rgba(255,255,255,.07), transparent 28%),
    radial-gradient(circle at 86% 82%, rgba(185,28,28,.34), transparent 44%),
    linear-gradient(135deg, #040404 0%, #110202 28%, #2b0505 58%, #6b0f0f 100%);
  border-radius: 22px;
  padding: 28px;
  color: #fff;
  box-shadow: 0 24px 60px rgba(0,0,0,.58), 0 0 0 1px rgba(185,28,28,.16);
}
.hero-panel::after {
  content: '';
  position: absolute;
  width: 240px;
  height: 240px;
  right: -90px;
  top: -70px;
  border-radius: 50%;
  background: rgba(255,255,255,.08);
}
.hero-kicker { font-size: 12px; letter-spacing: .12em; text-transform: uppercase; opacity: .8; margin-bottom: 12px; }
.hero-title { font-size: 30px; line-height: 1.1; font-weight: 800; max-width: 520px; margin-bottom: 12px; }
.hero-sub { max-width: 560px; color: rgba(255,255,255,.78); line-height: 1.6; font-size: 14px; }
.hero-strip { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-top: 24px; }
.hero-chip {
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 16px;
  padding: 14px 16px;
  backdrop-filter: blur(8px);
}
.hero-chip-label { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; opacity: .7; margin-bottom: 6px; }
.hero-chip-value { font-size: 24px; font-weight: 800; }
.hero-chip-sub { font-size: 12px; opacity: .78; margin-top: 4px; }
.donut-panel {
  background:
    radial-gradient(circle at top right, rgba(155,28,28,.1), transparent 32%),
    var(--dash-panel-bg);
  border-radius: 22px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  padding: 22px;
}
.panel-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom: 18px; }
.panel-title { font-size: 16px; font-weight: 700; }
.panel-sub { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.donut-wrap { display:grid; grid-template-columns:160px 1fr; gap:18px; align-items:center; }
.donut-center { text-align:center; }
.donut-number { font-size: 30px; font-weight: 800; line-height: 1; }
.donut-caption { font-size: 12px; color: var(--text-muted); margin-top: 6px; }
.legend-list { display:flex; flex-direction:column; gap:10px; }
.legend-item { display:grid; grid-template-columns:12px 1fr auto; gap:10px; align-items:center; font-size:13px; }
.legend-dot { width: 12px; height: 12px; border-radius: 999px; }
.dashboard-stats { display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; }
.metric-card {
  position: relative;
  overflow: hidden;
  isolation: isolate;
  border-radius: 24px;
  padding: 22px 22px 20px;
  min-height: 142px;
  background:
    linear-gradient(180deg, rgba(255,255,255,.96) 0%, rgba(246,249,255,.94) 100%);
  border: 1px solid rgba(148,163,184,.18);
  box-shadow:
    0 18px 34px rgba(15,23,42,.08),
    inset 0 1px 0 rgba(255,255,255,.72);
}
.metric-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at top right, rgba(255,255,255,.48), transparent 24%),
    linear-gradient(135deg, color-mix(in srgb, var(--accent-tone) 10%, transparent) 0%, transparent 48%);
  pointer-events: none;
}
.metric-card::after {
  content: '';
  position: absolute;
  inset: auto -10px -28px auto;
  width: 136px;
  height: 136px;
  border-radius: 50%;
  background: var(--card-glow);
  opacity: .22;
  filter: blur(6px);
}
.metric-card > * {
  position: relative;
  z-index: 1;
}
.metric-top { display:flex; justify-content:space-between; align-items:flex-start; gap:14px; }
.metric-copy {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.metric-icon {
  width: 54px;
  height: 54px;
  border-radius: 18px;
  display:flex;
  align-items:center;
  justify-content:center;
  flex-shrink: 0;
  color: var(--icon-color, var(--value-color));
  background:
    linear-gradient(180deg, rgba(255,255,255,.88) 0%, color-mix(in srgb, var(--icon-bg) 82%, white) 100%);
  border: 1px solid rgba(255,255,255,.72);
  box-shadow:
    0 10px 20px rgba(15,23,42,.08),
    inset 0 1px 0 rgba(255,255,255,.65);
}
.metric-icon svg {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  display: block;
}
.metric-value {
  font-size: 40px;
  font-weight: 800;
  letter-spacing: -.03em;
  line-height: .95;
  margin: 0;
  color: var(--value-color);
  text-shadow: 0 8px 22px color-mix(in srgb, var(--value-color) 22%, transparent);
}
.metric-label {
  font-size: 14px;
  color: color-mix(in srgb, var(--text) 86%, var(--text-muted));
  font-weight: 600;
}
.metric-trend {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid rgba(148,163,184,.14);
}
html[data-theme="dark"] .metric-card {
  background:
    radial-gradient(circle at top right, rgba(185,28,28,.06), transparent 30%),
    linear-gradient(180deg, rgba(18,18,18,.99) 0%, rgba(13,13,13,.97) 100%);
  border-color: rgba(255,255,255,.07);
  box-shadow:
    0 20px 38px rgba(0,0,0,.5),
    inset 0 1px 0 rgba(255,255,255,.04);
}
html[data-theme="dark"] .metric-card::before {
  background:
    radial-gradient(circle at top right, rgba(255,255,255,.03), transparent 24%),
    linear-gradient(135deg, color-mix(in srgb, var(--accent-tone) 14%, transparent) 0%, transparent 52%);
}
html[data-theme="dark"] .metric-card::after {
  opacity: .14;
}
html[data-theme="dark"] .metric-icon {
  background: linear-gradient(180deg, #2a2a2a 0%, #222222 100%);
  border-color: rgba(255,255,255,.07);
  color: var(--icon-color);
  box-shadow:
    0 10px 22px rgba(0,0,0,.4),
    inset 0 1px 0 rgba(255,255,255,.05);
}
html[data-theme="dark"] .metric-label {
  color: #d4d4d4;
}
html[data-theme="dark"] .metric-trend {
  border-top-color: rgba(255,255,255,.07);
  color: #6b6b6b;
}
.insight-grid { display:grid; grid-template-columns:1.2fr .8fr; gap:20px; }
.chart-panel, .stack-panel {
  background: var(--dash-panel-strong);
  border-radius: 22px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  padding: 22px;
}
.grafana-panel {
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(circle at top right, rgba(96,165,250,.08), transparent 28%),
    radial-gradient(circle at bottom left, rgba(233,69,96,.08), transparent 32%),
    linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(244,248,255,.96) 100%);
}
.grafana-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(37,99,235,.035) 1px, transparent 1px),
    linear-gradient(90deg, rgba(37,99,235,.03) 1px, transparent 1px);
  background-size: 100% 34px, 48px 100%;
  opacity: .45;
  pointer-events: none;
}
html[data-theme="dark"] .grafana-panel {
  background:
    radial-gradient(circle at top right, rgba(185,28,28,.07), transparent 30%),
    radial-gradient(circle at bottom left, rgba(185,28,28,.04), transparent 34%),
    linear-gradient(180deg, rgba(18,18,18,.99) 0%, rgba(13,13,13,.99) 100%);
}
html[data-theme="dark"] .grafana-panel::before {
  background-image:
    linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
  opacity: .6;
}
.mini-chart { width: 100%; height: 220px; display:block; }
.activity-widget {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.activity-widget-toggle {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 12px;
  border-radius: 999px;
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--card-solid) 78%, transparent);
  color: var(--text);
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all var(--transition);
}
.activity-widget-toggle:hover {
  border-color: var(--accent);
  color: var(--accent);
}
.activity-widget-toggle::before {
  content: '\25A6';
  font-size: 14px;
  line-height: 1;
}
.activity-widget[data-mode="chart"] .activity-widget-toggle::before {
  content: '\223F';
}
.activity-widget[data-mode="blocks"] .activity-block-grid,
.activity-widget[data-mode="chart"] .grafana-chart {
  display: block;
}
.activity-widget[data-mode="blocks"] .grafana-chart,
.activity-widget[data-mode="chart"] .activity-block-grid {
  display: none;
}
.grafana-chart { width: 100%; height: 250px; margin-top: 6px; }
.activity-bar {
  fill: #9b1c1c;
  rx: 3;
  transition: transform .16s ease, opacity .16s ease, fill .16s ease;
  transform-origin: center bottom;
}
.activity-bar:hover {
  fill: #ef4444;
  opacity: .95;
  transform: scaleY(1.03);
}
.activity-bar-grid {
  stroke: rgba(185,28,28,.32);
  stroke-width: 1;
}
html[data-theme="dark"] .activity-bar-grid {
  stroke: rgba(185,28,28,.22);
}
.chart-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: var(--text);
  background: color-mix(in srgb, var(--card-solid) 72%, transparent);
  border: 1px solid var(--border);
}
.chart-axis-label,
.chart-axis-value {
  font-size: 10px;
  fill: var(--text-muted);
}
.chart-hover-band {
  opacity: 0;
  transition: opacity .16s ease;
}
.chart-node {
  transition: transform .16s ease;
  transform-origin: center;
}
.chart-day:hover .chart-hover-band {
  opacity: 1;
}
.chart-day:hover .chart-node {
  transform: scale(1.14);
}
.chart-summary {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}
.chart-summary-card {
  border-radius: 16px;
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--card-solid) 78%, transparent);
  padding: 12px 14px;
}
.chart-summary-kicker {
  font-size: 11px;
  color: var(--text-muted);
  margin-bottom: 6px;
}
.chart-summary-value {
  font-size: 24px;
  font-weight: 800;
  line-height: 1;
}
.chart-summary-note {
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 6px;
}
.chart-legend { display:flex; gap:16px; flex-wrap:wrap; margin-top: 8px; }
.chart-legend-item { display:flex; align-items:center; gap:8px; font-size:12px; color: var(--text-muted); }
.chart-legend-line { width: 18px; height: 3px; border-radius: 999px; }
.activity-block-grid {
  display:grid;
  grid-template-columns:repeat(6, minmax(0, 1fr));
  gap:12px;
  margin-top: 8px;
}
.activity-day-card {
  position: relative;
  overflow: hidden;
  border-radius: 18px;
  border: 1px solid var(--border);
  background:
    linear-gradient(180deg, rgba(255,255,255,.08) 0%, rgba(255,255,255,.02) 100%),
    color-mix(in srgb, var(--card-solid) 78%, transparent);
  padding: 14px;
  min-height: 122px;
  box-shadow: var(--dash-soft-shadow);
}
.activity-day-card::before {
  content: '';
  position: absolute;
  inset: auto -18px -28px auto;
  width: 88px;
  height: 88px;
  border-radius: 50%;
  background: rgba(255,255,255,.06);
}
.activity-day-card.peak-docs::after,
.activity-day-card.peak-tasks::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  pointer-events: none;
}
.activity-day-card.peak-docs::after { box-shadow: inset 0 0 0 1px rgba(37,99,235,.45); }
.activity-day-card.peak-tasks::after { box-shadow: inset 0 0 0 1px rgba(233,69,96,.45); }
.activity-day-top,
.activity-day-metrics,
.activity-day-total {
  position: relative;
  z-index: 1;
}
.activity-day-top {
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:8px;
  margin-bottom: 12px;
}
.activity-day-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--text);
}
.activity-day-index {
  font-size: 10px;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--text-muted);
}
.activity-day-metrics { display:grid; gap:10px; }
.activity-day-stat {
  display:grid;
  grid-template-columns:auto 1fr auto;
  gap:8px;
  align-items:center;
}
.activity-day-dot {
  width:10px;
  height:10px;
  border-radius:999px;
}
.activity-day-dot.docs { background:#2563eb; box-shadow: 0 0 0 4px rgba(37,99,235,.14); }
.activity-day-dot.tasks { background:#e94560; box-shadow: 0 0 0 4px rgba(233,69,96,.14); }
.activity-day-name { font-size:12px; color: var(--text-muted); }
.activity-day-value {
  font-size: 18px;
  font-weight: 800;
  line-height: 1;
}
.activity-day-value.docs { color:#60a5fa; }
.activity-day-value.tasks { color:#fb7185; }
.activity-day-total {
  margin-top: 14px;
  padding-top: 10px;
  border-top: 1px solid rgba(148,163,184,.14);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
}
.activity-day-total-label { font-size:11px; color: var(--text-muted); }
.activity-day-total-value { font-size:13px; font-weight:700; color: var(--text); }
.activity-empty {
  border-radius: 18px;
  border: 1px dashed var(--border);
  padding: 20px;
  text-align: center;
  color: var(--text-muted);
}
.bar-stack { display:flex; flex-direction:column; gap:14px; }
.bar-row { display:grid; grid-template-columns:110px 1fr 36px; gap:12px; align-items:center; }
.bar-label { font-size: 12px; color: var(--text-muted); }
.bar-track { height: 12px; background: var(--dash-track); border-radius: 999px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 999px; }
.activity-grid { display:grid; grid-template-columns:1.1fr .9fr; gap:20px; }
.list-panel {
  background: var(--dash-panel-strong);
  border-radius: 22px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  overflow: hidden;
}
.list-head { display:flex; justify-content:space-between; align-items:center; padding: 20px 22px 14px; }
.task-stack { padding: 0 14px 14px; display:flex; flex-direction:column; gap:10px; }
.task-spotlight {
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 14px;
  background: var(--dash-task-card);
  box-shadow: var(--dash-soft-shadow);
}
.task-spotlight.overdue { border-left: 4px solid #dc2626; }
.task-meta-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom: 8px; }
.recent-table { padding: 0 6px 12px; }
.heat-panel {
  background: var(--dash-panel-warm);
  border-radius: 22px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  padding: 22px;
}
.type-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; margin-top: 18px; }
.type-card {
  border-radius: 18px;
  padding: 16px;
  color: #fff;
  min-height: 130px;
  position: relative;
  overflow: hidden;
}
.type-card::after {
  content: '';
  position: absolute;
  right: -26px;
  bottom: -34px;
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: rgba(255,255,255,.14);
}
.type-card-value { font-size: 34px; font-weight: 800; line-height: 1; margin: 14px 0 8px; }
.type-card-label { font-size: 14px; font-weight: 700; }
.type-card-sub { font-size: 12px; opacity: .86; }
html[data-theme="dark"] .hero-panel { box-shadow: 0 20px 50px rgba(0,0,0,.26); }
html[data-theme="dark"] .hero-chip {
  background: rgba(255,255,255,.08);
  border-color: rgba(255,255,255,.09);
}
html[data-theme="dark"] .activity-day-card {
  background:
    radial-gradient(circle at top right, rgba(255,255,255,.03), transparent 30%),
    linear-gradient(180deg, rgba(20,20,20,.97) 0%, rgba(15,15,15,.95) 100%);
  border-color: rgba(255,255,255,.07);
}
html[data-theme="dark"] .activity-day-total {
  border-top-color: rgba(255,255,255,.07);
}
html[data-theme="dark"] .metric-card,
html[data-theme="dark"] .donut-panel,
html[data-theme="dark"] .chart-panel,
html[data-theme="dark"] .stack-panel,
html[data-theme="dark"] .list-panel,
html[data-theme="dark"] .heat-panel {
  box-shadow: 0 16px 40px rgba(0,0,0,.55);
}
html[data-theme="dark"] .bar-row strong,
html[data-theme="dark"] .legend-item strong,
html[data-theme="dark"] .panel-title,
html[data-theme="dark"] .donut-number {
  color: var(--text);
}
@media (max-width: 1200px) {
  .hero-grid, .insight-grid, .activity-grid { grid-template-columns:1fr; }
  .dashboard-stats { grid-template-columns:repeat(2, 1fr); }
  .activity-block-grid { grid-template-columns:repeat(4, minmax(0, 1fr)); }
}
@media (max-width: 760px) {
  .dashboard-stats, .type-grid, .hero-strip, .donut-wrap, .chart-summary, .activity-block-grid { grid-template-columns:1fr; }
  .hero-title { font-size: 24px; }
  .bar-row { grid-template-columns:1fr; }
}

/* ═══════════════════════════════════════════════════════
   DASHBOARD  ·  PREMIUM RED-BLACK ENHANCEMENTS
   ═══════════════════════════════════════════════════════ */

/* Hero panel — mesh grid overlay */
.hero-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 22px;
  background-image:
    linear-gradient(rgba(255,255,255,.024) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.024) 1px, transparent 1px);
  background-size: 38px 38px;
  pointer-events: none;
}

/* Hero panel typography */
.hero-title {
  font-weight: 900 !important;
  letter-spacing: -.025em !important;
  line-height: 1.08 !important;
}
.hero-kicker {
  letter-spacing: .14em;
  font-weight: 600;
}

/* Hero chips — deeper glass */
.hero-chip {
  background: rgba(0,0,0,.34) !important;
  border-color: rgba(255,255,255,.12) !important;
  backdrop-filter: blur(14px) !important;
  transition: all .22s ease;
}
.hero-chip:hover {
  border-color: rgba(185,28,28,.4) !important;
  background: rgba(185,28,28,.13) !important;
}

/* Metric cards — cinematic pulse (dark) */
html[data-theme="dark"] .metric-card {
  animation: metricPulse 5s ease-in-out infinite;
}
@keyframes metricPulse {
  0%,100% { box-shadow: 0 20px 38px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.04); }
  50%     { box-shadow: 0 20px 38px rgba(0,0,0,.56), 0 0 50px rgba(185,28,28,.04), inset 0 1px 0 rgba(255,255,255,.05); }
}

/* Panel glass — dark */
html[data-theme="dark"] .donut-panel,
html[data-theme="dark"] .chart-panel,
html[data-theme="dark"] .stack-panel,
html[data-theme="dark"] .heat-panel,
html[data-theme="dark"] .list-panel {
  background: linear-gradient(145deg, rgba(14,14,14,.98) 0%, rgba(10,10,10,.99) 100%);
  border-color: rgba(255,255,255,.07);
}

/* Grafana panel grid — red tint */
html[data-theme="dark"] .grafana-panel::before {
  background-image:
    linear-gradient(rgba(185,28,28,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(185,28,28,.03) 1px, transparent 1px);
}

/* Chart summary cards glass */
html[data-theme="dark"] .chart-summary-card {
  background: linear-gradient(145deg, rgba(20,20,20,.96) 0%, rgba(14,14,14,.97) 100%);
  border-color: rgba(255,255,255,.07);
}

/* Activity day cards hover */
html[data-theme="dark"] .activity-day-card {
  transition: all .22s cubic-bezier(0.16,1,.3,1);
}
html[data-theme="dark"] .activity-day-card:hover {
  border-color: rgba(185,28,28,.24);
  box-shadow: 0 8px 24px rgba(0,0,0,.62), 0 0 0 1px rgba(185,28,28,.1);
  transform: translateY(-2px);
}

/* Activity dots — vivid colors */
.activity-day-dot.docs  { background: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
.activity-day-dot.tasks { background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.15); }
.activity-day-value.tasks { color: #f87171; }

/* Task spotlight cards glass */
html[data-theme="dark"] .task-spotlight {
  background: linear-gradient(135deg, rgba(18,18,18,.99) 0%, rgba(12,12,12,.97) 100%);
  border-color: rgba(255,255,255,.07);
  box-shadow: 0 4px 14px rgba(0,0,0,.42), inset 0 1px 0 rgba(255,255,255,.04);
  transition: all .22s ease;
}
html[data-theme="dark"] .task-spotlight:hover {
  border-color: rgba(185,28,28,.22);
  box-shadow: 0 8px 24px rgba(0,0,0,.62);
}
html[data-theme="dark"] .task-spotlight.overdue {
  border-left-color: #ef4444;
  background: linear-gradient(135deg, rgba(185,28,28,.07) 0%, rgba(18,18,18,.99) 32%);
  box-shadow: -4px 0 20px rgba(185,28,28,.16), 0 4px 14px rgba(0,0,0,.42);
}

/* Type cards — hover lift */
.type-card { transition: all .22s cubic-bezier(0.16,1,.3,1); }
.type-card:hover {
  transform: translateY(-3px) scale(1.015);
  box-shadow: 0 14px 34px rgba(0,0,0,.48);
}

/* Bar fill shine */
.bar-fill { position: relative; overflow: hidden; }

/* Panel title refinement */
.panel-title { letter-spacing: -.01em; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const widget = document.getElementById('activityWidget');
  const toggle = document.getElementById('activityWidgetToggle');

  if (!widget || !toggle) {
    return;
  }

  const storageKey = 'docv3-dashboard-activity-mode';
  const applyMode = function (mode) {
    const nextMode = mode === 'chart' ? 'chart' : 'blocks';
    widget.setAttribute('data-mode', nextMode);
    toggle.textContent = nextMode === 'chart' ? 'Развернуть' : 'Свернуть';
    localStorage.setItem(storageKey, nextMode);
  };

  applyMode(localStorage.getItem(storageKey) || 'blocks');

  toggle.addEventListener('click', function () {
    const currentMode = widget.getAttribute('data-mode') === 'chart' ? 'chart' : 'blocks';
    applyMode(currentMode === 'chart' ? 'blocks' : 'chart');
  });
});
</script>
@endpush

@section('content')
<div class="dashboard-shell">
  <div class="hero-grid">
    <section class="hero-panel">
      <div class="hero-kicker">Операционная аналитика</div>
      <div class="hero-title">Центр управления документами, задачами и согласованием</div>
      <div class="hero-sub">
        Здесь мы видим общую нагрузку, скорость движения документов и точки внимания по задачам.
        Дашборд собран из живых данных текущего доступа пользователя.
      </div>

      <div class="hero-strip">
        <div class="hero-chip">
          <div class="hero-chip-label">Реестр</div>
          <div class="hero-chip-value">{{ $stats['total'] }}</div>
          <div class="hero-chip-sub">документов под контролем</div>
        </div>
        <div class="hero-chip">
          <div class="hero-chip-label">Рассмотрение</div>
          <div class="hero-chip-value">{{ $stats['review'] }}</div>
          <div class="hero-chip-sub">ожидают решения</div>
        </div>
        <div class="hero-chip">
          <div class="hero-chip-label">Срочные риски</div>
          <div class="hero-chip-value">{{ $stats['overdue'] }}</div>
          <div class="hero-chip-sub">задач с истекшим сроком</div>
        </div>
      </div>
    </section>

    <section class="donut-panel">
      <div class="panel-head">
        <div>
          <div class="panel-title">Структура реестра</div>
          <div class="panel-sub">Распределение документов по типам</div>
        </div>
        <span class="status-pill status-active">{{ $documentTypeStats->sum('count') }} всего</span>
      </div>

      <div class="donut-wrap">
        <div class="donut-center">
          <svg width="160" height="160" viewBox="0 0 160 160" aria-hidden="true">
            <defs>
              @foreach($documentTypeStats as $item)
                <linearGradient id="grad-{{ $item['key'] }}" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="{{ $typePalette[$item['key']][0] ?? '#2563eb' }}" />
                  <stop offset="100%" stop-color="{{ $typePalette[$item['key']][1] ?? '#93c5fd' }}" />
                </linearGradient>
              @endforeach
            </defs>
            <circle cx="80" cy="80" r="54" fill="none" stroke="var(--dash-track)" stroke-width="18" />
            @php $offset = 0; $circ = 339.292; @endphp
            @foreach($documentTypeStats as $item)
              @php
                $segment = $circ * ($item['count'] / $typeTotal);
                $dashOffset = $circ - $offset;
              @endphp
              @if($item['count'] > 0)
                <circle
                  cx="80"
                  cy="80"
                  r="54"
                  fill="none"
                  stroke="url(#grad-{{ $item['key'] }})"
                  stroke-width="18"
                  stroke-linecap="round"
                  stroke-dasharray="{{ round($segment, 3) }} {{ round($circ - $segment, 3) }}"
                  stroke-dashoffset="{{ round($dashOffset, 3) }}"
                  transform="rotate(-90 80 80)"
                />
              @endif
              @php $offset += $segment; @endphp
            @endforeach
          </svg>
          <div class="donut-number">{{ $stats['total'] }}</div>
          <div class="donut-caption">документов в зоне видимости</div>
        </div>

        <div class="legend-list">
          @foreach($documentTypeStats as $item)
            <div class="legend-item">
              <span class="legend-dot" style="background: {{ $typePalette[$item['key']][0] ?? '#2563eb' }};"></span>
              <span>{{ $item['label'] }}</span>
              <strong>{{ $item['count'] }}</strong>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  </div>

  <div class="dashboard-stats">
    <article class="metric-card" style="--icon-bg:#e8e8e8; --icon-color:#444444; --value-color:#9b1c1c; --accent-tone:#9b1c1c; --card-glow:radial-gradient(circle, rgba(155,28,28,.2) 0%, rgba(155,28,28,.06) 42%, transparent 74%);">
      <div class="metric-top">
        <div class="metric-copy">
          <div class="metric-label">Всего документов</div>
          <div class="metric-value">{{ $stats['total'] }}</div>
        </div>
        <div class="metric-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
            <line x1="10" y1="9" x2="8" y2="9"/>
          </svg>
        </div>
      </div>
      <div class="metric-trend">{{ $stats['review'] }} сейчас на рассмотрении</div>
    </article>

    <article class="metric-card" style="--icon-bg:#e8e8e8; --icon-color:#555555; --value-color:#c2410c; --accent-tone:#c2410c; --card-glow:radial-gradient(circle, rgba(194,65,12,.2) 0%, rgba(194,65,12,.06) 42%, transparent 74%);">
      <div class="metric-top">
        <div class="metric-copy">
          <div class="metric-label">В работе</div>
          <div class="metric-value">{{ $stats['review'] }}</div>
        </div>
        <div class="metric-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
      </div>
      <div class="metric-trend">ключевой поток согласований</div>
    </article>

    <article class="metric-card" style="--icon-bg:#e8e8e8; --icon-color:#444444; --value-color:#15803d; --accent-tone:#16a34a; --card-glow:radial-gradient(circle, rgba(22,163,74,.18) 0%, rgba(22,163,74,.05) 42%, transparent 74%);">
      <div class="metric-top">
        <div class="metric-copy">
          <div class="metric-label">Активных задач</div>
          <div class="metric-value">{{ $stats['tasks'] }}</div>
        </div>
        <div class="metric-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 11l3 3L22 4"/>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
          </svg>
        </div>
      </div>
      <div class="metric-trend">{{ $taskStatusStats->firstWhere('key', 'in_progress')['count'] ?? 0 }} в активной работе</div>
    </article>

    <article class="metric-card" style="--icon-bg:#e8e8e8; --icon-color:#9b1c1c; --value-color:#dc2626; --accent-tone:#dc2626; --card-glow:radial-gradient(circle, rgba(220,38,38,.2) 0%, rgba(220,38,38,.06) 42%, transparent 74%);">
      <div class="metric-top">
        <div class="metric-copy">
          <div class="metric-label">Просроченных</div>
          <div class="metric-value">{{ $stats['overdue'] }}</div>
        </div>
        <div class="metric-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
        </div>
      </div>
      <div class="metric-trend">задач требуют немедленного внимания</div>
    </article>
  </div>

  <div class="insight-grid">
    <section class="chart-panel grafana-panel activity-widget" id="activityWidget" data-mode="blocks">
      <div class="panel-head">
        <div>
          <div class="panel-title">Активность за 30 дней</div>
          <div class="panel-sub">Блоки по каждому дню: сколько создано документов и задач за последний месяц</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
          <span class="status-pill status-active">30 дней</span>
          <button type="button" class="activity-widget-toggle" id="activityWidgetToggle">Свернуть</button>
        </div>
      </div>

      <div class="activity-block-grid">
        @foreach($activityDays as $index => $day)
          <article class="activity-day-card {{ $day['documents'] === $activityDays->max('documents') && $day['documents'] > 0 ? 'peak-docs' : '' }} {{ $day['tasks'] === $activityDays->max('tasks') && $day['tasks'] > 0 ? 'peak-tasks' : '' }}">
            <div class="activity-day-top">
              <div class="activity-day-label">{{ $day['label'] }}</div>
              <div class="activity-day-index">День {{ $index + 1 }}</div>
            </div>
            <div class="activity-day-metrics">
              <div class="activity-day-stat">
                <span class="activity-day-dot docs"></span>
                <span class="activity-day-name">Документы</span>
                <span class="activity-day-value docs">{{ $day['documents'] }}</span>
              </div>
              <div class="activity-day-stat">
                <span class="activity-day-dot tasks"></span>
                <span class="activity-day-name">Задачи</span>
                <span class="activity-day-value tasks">{{ $day['tasks'] }}</span>
              </div>
            </div>
            <div class="activity-day-total">
              <span class="activity-day-total-label">Итого за день</span>
              <span class="activity-day-total-value">{{ $day['documents'] + $day['tasks'] }}</span>
            </div>
          </article>
        @endforeach
      </div>

      <svg class="grafana-chart" viewBox="0 0 520 210" preserveAspectRatio="none" aria-hidden="true">
        <line class="activity-bar-grid" x1="0" y1="35" x2="520" y2="35" />
        <line class="activity-bar-grid" x1="0" y1="95" x2="520" y2="95" />
        <line class="activity-bar-grid" x1="0" y1="155" x2="520" y2="155" />

        @foreach($activityBars as $bar)
          <rect
            class="activity-bar"
            x="{{ $bar['x'] }}"
            y="{{ $bar['y'] }}"
            width="13"
            height="{{ $bar['height'] }}"
            rx="3"
            ry="3"
          >
            <title>{{ $bar['label'] }}: {{ $bar['total'] }}</title>
          </rect>
        @endforeach
      </svg>

      <div class="chart-legend">
        <div class="chart-legend-item">
          <span class="chart-legend-line" style="background:#2563eb;"></span>
          <span>Документы</span>
        </div>
        <div class="chart-legend-item">
          <span class="chart-legend-line" style="background:#e94560;"></span>
          <span>Задачи</span>
        </div>
      </div>
      <div class="chart-summary">
        <div class="chart-summary-card">
          <div class="chart-summary-kicker">Пик по документам</div>
          <div class="chart-summary-value">{{ $activityDocPeak }}</div>
          <div class="chart-summary-note">максимум и сумма {{ $activityDocTotal }} за последние 30 дней</div>
        </div>
        <div class="chart-summary-card">
          <div class="chart-summary-kicker">Пик по задачам</div>
          <div class="chart-summary-value">{{ $activityTaskPeak }}</div>
          <div class="chart-summary-note">нагрузка и сумма {{ $activityTaskTotal }} за последние 30 дней</div>
        </div>
      </div>
    </section>

    <section class="stack-panel">
      <div class="panel-head">
        <div>
          <div class="panel-title">Воронка документов</div>
          <div class="panel-sub">Текущее распределение по статусам</div>
        </div>
      </div>

      <div class="bar-stack">
        @foreach($documentStatusStats as $item)
          <div class="bar-row">
            <div class="bar-label">{{ $item['label'] }}</div>
            <div class="bar-track">
              <div class="bar-fill" style="width: {{ ($item['count'] / $statusMax) * 100 }}%; background: {{ $statusPalette[$item['key']] ?? '#94a3b8' }};"></div>
            </div>
            <strong>{{ $item['count'] }}</strong>
          </div>
        @endforeach
      </div>

      <div style="height:18px;"></div>

      <div class="panel-title" style="font-size:14px; margin-bottom:12px;">Состояние задач</div>
      <div class="bar-stack">
        @foreach($taskStatusStats as $item)
          <div class="bar-row">
            <div class="bar-label">{{ $item['label'] }}</div>
            <div class="bar-track">
              <div class="bar-fill" style="width: {{ ($item['count'] / $taskStatusMax) * 100 }}%; background: {{ $taskPalette[$item['key']] ?? '#94a3b8' }};"></div>
            </div>
            <strong>{{ $item['count'] }}</strong>
          </div>
        @endforeach
      </div>
    </section>
  </div>

  <div class="activity-grid">
    <section class="list-panel">
      <div class="list-head">
        <div>
          <div class="panel-title">Последние документы</div>
          <div class="panel-sub">Последние движения по реестру</div>
        </div>
        <a href="{{ route('documents.index') }}" class="btn btn-sm btn-secondary">Все</a>
      </div>

      <div class="recent-table">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Номер</th>
                <th>Тип</th>
                <th>Тема</th>
                <th>Дата</th>
                <th>Статус</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentDocs as $doc)
                <tr>
                  <td><a href="{{ route('documents.show', $doc) }}" class="td-link" style="font-family:monospace;">{{ $doc->number }}</a></td>
                  <td><span class="badge type-{{ $doc->type }}">{{ $doc->type_name }}</span></td>
                  <td><a href="{{ route('documents.show', $doc) }}" class="td-link">{{ \Illuminate\Support\Str::limit($doc->subject, 46) }}</a></td>
                  <td style="font-size:12px; color:var(--text-muted);">{{ $doc->doc_date->format('d.m.Y') }}</td>
                  <td><span class="badge status-{{ $doc->status }}">{{ $doc->status_name }}</span></td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" style="text-align:center; color:var(--text-muted); padding:26px;">Пока нет документов для отображения</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="heat-panel">
      <div class="panel-head">
        <div>
          <div class="panel-title">Документы по типам</div>
          <div class="panel-sub">Быстрый срез текущей структуры</div>
        </div>
      </div>

      <div class="type-grid">
        @foreach($documentTypeStats as $item)
          <div class="type-card" style="background: linear-gradient(135deg, {{ $typePalette[$item['key']][0] ?? '#2563eb' }} 0%, {{ $typePalette[$item['key']][1] ?? '#93c5fd' }} 100%);">
            <div class="type-card-label">{{ $item['label'] }}</div>
            <div class="type-card-value">{{ $item['count'] }}</div>
            <div class="type-card-sub">
              {{ round(($item['count'] / $typeTotal) * 100) }}% от реестра
            </div>
          </div>
        @endforeach
      </div>
    </section>
  </div>

  <section class="list-panel">
    <div class="list-head">
      <div>
        <div class="panel-title">Задачи в фокусе</div>
        <div class="panel-sub">Приоритетные и срочные поручения</div>
      </div>
      <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-secondary">Канбан</a>
    </div>

    <div class="task-stack">
      @forelse($urgentTasks as $task)
        <div class="task-spotlight {{ $task->is_overdue ? 'overdue' : '' }}">
          <div class="task-meta-row">
            <span class="badge priority-{{ $task->priority }}">{{ $task->priority_name }}</span>
            @if($task->is_overdue)
              <span class="overdue-badge">Просрочена</span>
            @endif
            @if($task->deadline)
              <span style="margin-left:auto; font-size:12px; color:{{ $task->is_overdue ? '#dc2626' : 'var(--text-muted)' }};">
                {{ $task->deadline->format('d.m.Y') }}
              </span>
            @endif
          </div>
          <div style="font-size:14px; font-weight:700; margin-bottom:8px;">{{ $task->title }}</div>
          <div style="display:flex; align-items:center; gap:8px; color:var(--text-muted); font-size:12px;">
            <div class="avatar avatar-sm" style="background: {{ avatarColor($task->assignee->name) }};">{{ $task->assignee->initials }}</div>
            <span>{{ $task->assignee->short_name }}</span>
            @if($task->document)
              <span>·</span>
              <span>{{ $task->document->number }}</span>
            @endif
          </div>
        </div>
      @empty
        <div style="padding:18px 22px; color:var(--text-muted);">Активных задач сейчас нет.</div>
      @endforelse
    </div>
  </section>
</div>
@endsection
