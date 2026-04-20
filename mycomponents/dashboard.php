<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AgroSense IoT Dashboard — Monitor your crop conditions, alerts, and historical data in real time.">
  <title>Dashboard — AgroSense IoT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../css/styles.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            inter: ['Inter', 'sans-serif'],
            outfit: ['Outfit', 'sans-serif'],
          }
        }
      }
    }
  </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-900 via-emerald-700 to-lime-600 font-inter">
  <div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed md:static inset-y-0 left-0 z-40 w-64 glass-sidebar text-white flex flex-col p-6 space-y-6 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
      <div class="flex items-center gap-3 mb-2">
        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl">🌱</div>
        <h1 class="text-xl font-bold font-outfit">AgroSense IoT</h1>
      </div>
      <div class="px-4 py-2 rounded-lg bg-white/10 text-sm">
        <span class="text-white/60">Welcome,</span>
        <span class="font-semibold block"><?php echo htmlspecialchars($_SESSION["name"] ?? $_SESSION["username"]); ?></span>
      </div>
      <nav class="flex-1 space-y-1">
        <a href="#current" class="flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-white/15 transition-all text-sm font-medium group">
          <span class="text-lg group-hover:scale-110 transition-transform">🌡️</span> Current Conditions
        </a>
        <a href="#alerts" class="flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-white/15 transition-all text-sm font-medium group">
          <span class="text-lg group-hover:scale-110 transition-transform">🔔</span> Alerts
        </a>
        <a href="#history" class="flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-white/15 transition-all text-sm font-medium group">
          <span class="text-lg group-hover:scale-110 transition-transform">📊</span> Historical Data
        </a>
        <div class="border-t border-white/10 my-3"></div>
        <a href="./farms.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-white/15 transition-all text-sm font-medium group">
          <span class="text-lg group-hover:scale-110 transition-transform">🌾</span> Farms
        </a>
        <a href="./plots.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-white/15 transition-all text-sm font-medium group">
          <span class="text-lg group-hover:scale-110 transition-transform">🗺️</span> Plots
        </a>
        <a href="./alerts.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-white/15 transition-all text-sm font-medium group">
          <span class="text-lg group-hover:scale-110 transition-transform">⚠️</span> Alerts Manager
        </a>
        <a href="./irrigation.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-white/15 transition-all text-sm font-medium group">
          <span class="text-lg group-hover:scale-110 transition-transform">💧</span> Irrigation Logs
        </a>
      </nav>
      <button onclick="window.location.href='../backend/logout.php'" class="btn-danger w-full py-2.5 text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Logout
      </button>
      <button id="closeSidebarBtn" class="md:hidden self-end bg-white/20 px-3 py-1 rounded text-sm">Close</button>
    </aside>

    <!-- Sidebar Backdrop -->
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/40 z-30 hidden md:hidden"></div>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-8">
      <!-- Mobile menu button -->
      <button id="openSidebarBtn" class="md:hidden mb-2 bg-white/90 text-emerald-900 px-4 py-2 rounded-xl shadow font-medium text-sm flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        Menu
      </button>

      <!-- Header -->
      <header class="glass-card-strong p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3 animate-fade-in-down">
        <div>
          <h2 class="text-2xl md:text-3xl font-bold font-outfit text-emerald-900">Smart Crop Monitoring</h2>
          <p class="text-sm text-slate-500 mt-1">Welcome back, <?php echo htmlspecialchars($_SESSION["name"] ?? $_SESSION["username"]); ?> 👋</p>
        </div>
        <div class="flex flex-wrap gap-3 text-sm">
          <span class="px-4 py-2 rounded-full bg-slate-50 text-slate-600 border border-slate-200 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span id="last-updated">--:--</span>
          </span>
          <span class="px-4 py-2 rounded-full bg-red-50 text-red-600 border border-red-200 font-semibold flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            Alerts: <span id="unread-count">0</span>
          </span>
        </div>
      </header>

      <!-- Current Conditions -->
      <section id="current" class="grid grid-cols-1 md:grid-cols-3 gap-5 stagger-children">
        <div class="glass-card-strong p-6 border-l-4 border-emerald-500 hover-lift">
          <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-slate-500 font-medium">Temperature</p>
            <span class="text-2xl">🌡️</span>
          </div>
          <p class="text-3xl font-bold font-outfit text-emerald-700" id="temperature">-- °C</p>
        </div>
        <div class="glass-card-strong p-6 border-l-4 border-sky-500 hover-lift">
          <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-slate-500 font-medium">Humidity</p>
            <span class="text-2xl">💧</span>
          </div>
          <p class="text-3xl font-bold font-outfit text-sky-700" id="humidity">-- %</p>
        </div>
        <div class="glass-card-strong p-6 border-l-4 border-amber-500 hover-lift">
          <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-slate-500 font-medium">Soil Moisture</p>
            <span class="text-2xl">🌱</span>
          </div>
          <p class="text-3xl font-bold font-outfit text-amber-700" id="moisture">-- %</p>
        </div>
      </section>

      <!-- Trend Chart -->
      <section class="glass-card-strong p-6 animate-fade-in-up" style="animation-delay:0.2s;">
        <h3 class="text-lg font-bold font-outfit text-slate-800 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
          Moisture Trend (Recent)
        </h3>
        <canvas id="miniTrendChart" height="90"></canvas>
      </section>

      <!-- Alerts -->
      <section id="alerts" class="glass-card-strong p-6 animate-fade-in-up" style="animation-delay:0.3s;">
        <h3 class="text-lg font-bold font-outfit text-slate-800 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
          Alerts
        </h3>
        <ul class="space-y-2" id="alerts-list">
          <li class="text-slate-500 text-sm">No alerts currently.</li>
        </ul>
      </section>

      <!-- Historical Data -->
      <section id="history" class="glass-card-strong p-6 animate-fade-in-up" style="animation-delay:0.4s;">
        <h3 class="text-lg font-bold font-outfit text-slate-800 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Historical Data
        </h3>
        <div class="flex flex-col md:flex-row gap-3 mb-4">
          <input type="date" id="startDate" class="form-input max-w-xs">
          <input type="date" id="endDate" class="form-input max-w-xs">
          <button onclick="fetchHistoricalData()" class="btn-primary py-2 px-6 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            View Data
          </button>
        </div>
        <div id="historical-data" class="text-sm text-gray-700 overflow-auto"></div>
      </section>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    let miniChart;

    function setupSidebarToggle() {
      const sidebar = document.getElementById('sidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      const openBtn = document.getElementById('openSidebarBtn');
      const closeBtn = document.getElementById('closeSidebarBtn');

      openBtn?.addEventListener('click', () => {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
      });

      const closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
      };

      closeBtn?.addEventListener('click', closeSidebar);
      backdrop?.addEventListener('click', closeSidebar);
    }

    async function fetchSensorData() {
      try {
        const [sensorRes, summaryRes] = await Promise.all([
          fetch('../backend/api/sensor_latest.php'),
          fetch('../backend/api/dashboard_summary.php')
        ]);

        const sensorData = await sensorRes.json();
        const summaryData = await summaryRes.json();

        if (!sensorData.error) {
          const tempEl = document.getElementById('temperature');
          const humEl = document.getElementById('humidity');
          const moistEl = document.getElementById('moisture');

          if (sensorData.temperature !== null) {
            tempEl.textContent = `${sensorData.temperature} °C`;
            tempEl.style.transition = 'all 0.5s ease';
          }
          if (sensorData.humidity !== null) {
            humEl.textContent = `${sensorData.humidity} %`;
            humEl.style.transition = 'all 0.5s ease';
          }
          if (sensorData.moisture !== null) {
            moistEl.textContent = `${sensorData.moisture} %`;
            moistEl.style.transition = 'all 0.5s ease';
          }
        }

        document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();

        if (!summaryData.error) {
          document.getElementById('unread-count').textContent = summaryData.unread_count ?? 0;
          const list = document.getElementById('alerts-list');
          if (!summaryData.alerts || summaryData.alerts.length === 0) {
            list.innerHTML = '<li class="text-slate-500 text-sm">No unread alerts. ✅</li>';
          } else {
            list.innerHTML = summaryData.alerts.map(a => {
              const sev = (a.severity || '').toLowerCase();
              let badgeClass = 'badge-low';
              if (sev === 'high') badgeClass = 'badge-high';
              else if (sev === 'medium') badgeClass = 'badge-medium';

              return `
                <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                  <span class="badge ${badgeClass} mt-0.5">${sev.toUpperCase()}</span>
                  <span class="text-sm text-slate-700">${a.farm_name}/${a.plot_name}: ${a.message}</span>
                </li>
              `;
            }).join('');
          }
        }
      } catch (err) {
        console.error('Dashboard fetch failed', err);
      }
    }

    async function fetchTrendData() {
      try {
        const res = await fetch('../backend/api/sensor_trend.php');
        const data = await res.json();
        if (data.error || !Array.isArray(data)) return;

        const labels = data.map(item => new Date(item.recorded_at).toLocaleTimeString());
        const moisture = data.map(item => Number(item.moisture));

        const ctx = document.getElementById('miniTrendChart').getContext('2d');
        if (miniChart) miniChart.destroy();

        miniChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Soil Moisture (%)',
              data: moisture,
              borderColor: '#16a34a',
              backgroundColor: 'rgba(22,163,74,0.1)',
              fill: true,
              tension: 0.4,
              pointBackgroundColor: '#16a34a',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              pointRadius: 4,
              pointHoverRadius: 6,
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                display: true,
                labels: {
                  font: { family: 'Inter', weight: '500' },
                  usePointStyle: true,
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: { font: { family: 'Inter', size: 11 } }
              },
              x: {
                grid: { display: false },
                ticks: { font: { family: 'Inter', size: 11 } }
              }
            }
          }
        });
      } catch (err) {
        console.error('Trend fetch failed', err);
      }
    }

    async function fetchHistoricalData() {
      const start = document.getElementById('startDate').value;
      const end = document.getElementById('endDate').value;

      if (!start || !end) {
        document.getElementById('historical-data').innerHTML = '<p class="text-amber-600">Please select both dates.</p>';
        return;
      }

      try {
        const res = await fetch(`../backend/api/sensor_history.php?start=${start}&end=${end}`);
        const data = await res.json();

        if (data.error) {
          document.getElementById('historical-data').innerHTML = `<p class="text-red-600">${data.error}</p>`;
          return;
        }

        if (!data.length) {
          document.getElementById('historical-data').innerHTML = '<p class="text-slate-500">No data found for selected range.</p>';
          return;
        }

        let html = `
          <div class="overflow-auto rounded-xl border border-slate-100">
            <table class="mgmt-table">
              <thead>
                <tr>
                  <th>Recorded At</th>
                  <th>Temp (°C)</th>
                  <th>Humidity (%)</th>
                  <th>Moisture (%)</th>
                </tr>
              </thead>
              <tbody>
        `;

        data.forEach(item => {
          html += `
            <tr>
              <td>${item.recorded_at}</td>
              <td>${item.temperature}</td>
              <td>${item.humidity}</td>
              <td>${item.moisture}</td>
            </tr>
          `;
        });

        html += '</tbody></table></div>';
        document.getElementById('historical-data').innerHTML = html;
      } catch (err) {
        document.getElementById('historical-data').innerHTML = '<p class="text-red-600">Failed to fetch historical data.</p>';
      }
    }

    setupSidebarToggle();
    fetchSensorData();
    fetchTrendData();
    setInterval(fetchSensorData, 5000);
    setInterval(fetchTrendData, 15000);
  </script>
</body>
</html>
