<?php require_once __DIR__.'/includes/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sentinel32 | IoT Network IDS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="bg-grid"></div>
<aside class="sidebar">
  <div class="brand"><div class="brand-mark">S32</div><div><b>SENTINEL32</b><small>IoT Network Defense</small></div></div>
  <nav>
    <a class="active" href="#overview">◈ <span>Overview</span></a>
    <a href="#traffic">⌁ <span>Live Traffic</span></a>
    <a href="#alerts">⚠ <span>Security Alerts</span></a>
    <a href="#sensor">◎ <span>Sensor Health</span></a>
  </nav>
  <div class="side-foot"><span class="pulse"></span> ESP32 monitoring service</div>
</aside>

<main>
<header>
  <div><p class="eyebrow">FINAL YEAR PROJECT / SECURITY OPERATIONS</p><h1>Network Defense Console</h1></div>
  <div class="clock"><span id="date"></span><b id="clock"></b></div>
</header>

<section id="overview" class="cards">
 <article class="metric"><span>Sensor Status</span><strong id="sensorStatus">CHECKING</strong><small><i class="dot"></i> ESP32-IDS-01</small></article>
 <article class="metric"><span>Packets / Second</span><strong id="pps">0.0</strong><small>Live wireless traffic</small></article>
 <article class="metric"><span>Devices Observed</span><strong id="devices">0</strong><small>Unique MAC addresses</small></article>
 <article class="metric danger"><span>Alerts Today</span><strong id="alertsToday">0</strong><small><b id="criticalToday">0</b> high / critical</small></article>
</section>

<section class="grid">
 <article id="traffic" class="panel chart-panel">
   <div class="panel-head"><div><span class="kicker">LIVE TELEMETRY</span><h2>Wireless Packet Rate</h2></div><span class="live"><i></i> LIVE</span></div>
   <div class="chart-wrap"><canvas id="trafficChart"></canvas></div>
 </article>
 <article id="sensor" class="panel health">
   <div class="panel-head"><div><span class="kicker">EDGE SENSOR</span><h2>ESP32 Health</h2></div></div>
   <div class="radar"><div class="radar-ring r1"></div><div class="radar-ring r2"></div><div class="radar-ring r3"></div><div class="radar-sweep"></div><div class="radar-core"></div></div>
   <div class="health-row"><span>Average RSSI</span><b id="rssi">-- dBm</b></div>
   <div class="health-row"><span>Wi-Fi Channel</span><b id="channel">--</b></div>
   <div class="health-row"><span>Management Frames</span><b id="mgmt">0</b></div>
   <div class="health-row"><span>Deauth Frames</span><b id="deauth">0</b></div>
 </article>
</section>

<section id="alerts" class="panel alerts-panel">
 <div class="panel-head"><div><span class="kicker">INCIDENT QUEUE</span><h2>Recent Security Alerts</h2></div><button onclick="loadData()">↻ Refresh</button></div>
 <div class="table-wrap">
 <table>
  <thead><tr><th>Time</th><th>Source</th><th>Attack</th><th>Severity</th><th>PPS</th><th>Status</th><th>Action</th></tr></thead>
  <tbody id="alertRows"><tr><td colspan="7" class="empty">Waiting for security telemetry…</td></tr></tbody>
 </table>
 </div>
</section>

<footer>Sentinel32 • ESP32 IoT Network Intrusion Detection & Alert System</footer>
</main>
<div id="toast" class="toast"></div>
<script src="assets/js/dashboard.js"></script>
</body>
</html>
