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
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { inter: ['Inter', 'sans-serif'] }
        }
      }
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-900 via-emerald-700 to-lime-600 font-inter">
  <div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-green-700/80 text-white flex flex-col p-6 space-y-6">
      <h1 class="text-2xl font-bold">Crop Dashboard</h1>
      <p class="text-sm">Hi, <?php echo htmlspecialchars($_SESSION["name"] ?? $_SESSION["username"]); ?></p>
      <nav class="flex-1 space-y-3">
        <a href="#current" class="block hover:bg-green-600 px-4 py-2 rounded-lg">Current Conditions</a>
        <a href="#alerts" class="block hover:bg-green-600 px-4 py-2 rounded-lg">Alerts</a>
        <a href="#history" class="block hover:bg-green-600 px-4 py-2 rounded-lg">Historical Data</a>
        <a href="./farms.php" class="block hover:bg-green-600 px-4 py-2 rounded-lg">Farms</a>
        <a href="./plots.php" class="block hover:bg-green-600 px-4 py-2 rounded-lg">Plots</a>
        <a href="./alerts.php" class="block hover:bg-green-600 px-4 py-2 rounded-lg">Alerts Manager</a>
      </nav>
      <button onclick="window.location.href='../backend/logout.php'" class="mt-auto bg-red-600 hover:bg-red-700 py-2 rounded-lg text-center">Logout</button>
    </aside>

    <main class="flex-1 overflow-y-auto p-8 space-y-10">
      <header class="bg-white/90 backdrop-blur rounded-2xl p-5 shadow-lg flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
          <h2 class="text-3xl font-bold text-emerald-900">Smart Crop Monitoring</h2>
          <p class="text-sm text-slate-600">Welcome back, <?php echo htmlspecialchars($_SESSION["name"] ?? $_SESSION["username"]); ?></p>
        </div>
        <div class="flex gap-3 text-sm">
          <span class="px-3 py-2 rounded-full bg-slate-100 text-slate-700">Last updated: <span id="last-updated">--:--</span></span>
          <span class="px-3 py-2 rounded-full bg-red-100 text-red-700 font-semibold">Unread Alerts: <span id="unread-count">0</span></span>
        </div>
      </header>
      <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white/95 rounded-2xl shadow p-5 border-l-4 border-emerald-500">
          <p class="text-sm text-slate-500">Temperature</p>
          <p class="text-3xl font-bold text-emerald-700" id="temperature">-- °C</p>
        </div>
        <div class="bg-white/95 rounded-2xl shadow p-5 border-l-4 border-sky-500">
          <p class="text-sm text-slate-500">Humidity</p>
          <p class="text-3xl font-bold text-sky-700" id="humidity">-- %</p>
        </div>
        <div class="bg-white/95 rounded-2xl shadow p-5 border-l-4 border-amber-500">
          <p class="text-sm text-slate-500">Soil Moisture</p>
          <p class="text-3xl font-bold text-amber-700" id="moisture">-- %</p>
        </div>
      </section>
      <section id="alerts" class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-semibold mb-4">Alerts</h3>
        <ul class="list-disc pl-5 text-red-600" id="alerts-list">
          <li>No alerts currently.</li>
        </ul>
      </section>

      <section id="history" class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-semibold mb-4">Historical Data</h3>
        <div class="flex flex-col md:flex-row gap-4">
          <input type="date" id="startDate" class="border p-2 rounded">
          <input type="date" id="endDate" class="border p-2 rounded">
          <button onclick="fetchHistoricalData()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">View</button>
        </div>
        <div id="historical-data" class="mt-4 text-sm text-gray-700"></div>
      </section>
    </main>
  </div>

  <script>
    async function fetchSensorData() {
      try {
        const [sensorRes, summaryRes] = await Promise.all([
          fetch('../backend/api/sensor_latest.php'),
          fetch('../backend/api/dashboard_summary.php')
        ]);

        const sensorData = await sensorRes.json();
        const summaryData = await summaryRes.json();

        // Sensor values
        if (!sensorData.error) {
          document.getElementById('temperature').textContent =
            sensorData.temperature !== null ? `${sensorData.temperature} °C` : '-- °C';
          document.getElementById('humidity').textContent =
            sensorData.humidity !== null ? `${sensorData.humidity} %` : '-- %';
          document.getElementById('moisture').textContent =
            sensorData.moisture !== null ? `${sensorData.moisture} %` : '-- %';
        }

        document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();

        // Alert summary
        if (!summaryData.error) {
          document.getElementById('unread-count').textContent = summaryData.unread_count ?? 0;

          const list = document.getElementById('alerts-list');
          if (!summaryData.alerts || summaryData.alerts.length === 0) {
            list.innerHTML = '<li>No unread alerts.</li>';
          } else {
            list.innerHTML = summaryData.alerts.map(a =>
              `<li><strong>[${a.severity.toUpperCase()}]</strong> ${a.farm_name}/${a.plot_name}: ${a.message}</li>`
            ).join('');
          }
        }
      } catch (err) {
        console.error('Dashboard fetch failed', err);
      }
    }

    async function fetchHistoricalData() {
      const start = document.getElementById('startDate').value;
      const end = document.getElementById('endDate').value;

      if (!start || !end) {
        document.getElementById('historical-data').textContent = 'Please select both dates.';
        return;
      }

      try {
        const res = await fetch(`../backend/api/sensor_history.php?start=${start}&end=${end}`);
        const data = await res.json();

        if (data.error) {
          document.getElementById('historical-data').textContent = data.error;
          return;
        }

        if (!data.length) {
          document.getElementById('historical-data').textContent = 'No data found for selected range.';
          return;
        }

        let html = '<ul class="list-disc pl-5">';
        data.forEach(item => {
          html += `<li>${item.recorded_at} | Temp: ${item.temperature} °C, Humidity: ${item.humidity} %, Moisture: ${item.moisture} %</li>`;
        });
        html += '</ul>';
        document.getElementById('historical-data').innerHTML = html;
      } catch (err) {
        document.getElementById('historical-data').textContent = 'Failed to fetch historical data.';
      }
    }

    fetchSensorData();
    setInterval(fetchSensorData, 5000);
  </script>

</body>
</html>
