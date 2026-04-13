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
<body class="bg-[url('../img/image5.jpg')] bg-cover bg-center h-screen overflow-hidden font-inter">
  <div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-green-700/80 text-white flex flex-col p-6 space-y-6">
      <h1 class="text-2xl font-bold">Crop Dashboard</h1>
      <p class="text-sm">Hi, <?php echo htmlspecialchars($_SESSION["name"] ?? $_SESSION["username"]); ?></p>
      <nav class="flex-1 space-y-3">
        <a href="#current" class="block hover:bg-green-600 px-4 py-2 rounded-lg">Current Conditions</a>
        <a href="#alerts" class="block hover:bg-green-600 px-4 py-2 rounded-lg">Alerts</a>
        <a href="#history" class="block hover:bg-green-600 px-4 py-2 rounded-lg">Historical Data</a>
      </nav>
      <button onclick="window.location.href='../backend/logout.php'" class="mt-auto bg-red-600 hover:bg-red-700 py-2 rounded-lg text-center">Logout</button>
    </aside>

    <main class="flex-1 overflow-y-auto p-8 space-y-10">
      <header class="flex justify-between items-center">
        <h2 class="text-3xl font-semibold text-yellow-900">Smart Crop Monitoring</h2>
        <span class="text-sm text-gray-700">Last updated: <span id="last-updated">--:--</span></span>
      </header>

      <section id="current" class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-semibold mb-4">Current Crop Conditions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
          <div class="bg-green-100 p-4 rounded-lg">
            <p class="text-lg">Temperature</p>
            <p class="text-3xl font-bold" id="temperature">-- °C</p>
          </div>
          <div class="bg-blue-100 p-4 rounded-lg">
            <p class="text-lg">Humidity</p>
            <p class="text-3xl font-bold" id="humidity">-- %</p>
          </div>
          <div class="bg-yellow-100 p-4 rounded-lg">
            <p class="text-lg">Soil Moisture</p>
            <p class="text-3xl font-bold" id="moisture">-- %</p>
          </div>
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
        const res = await fetch('../backend/api/sensor_latest.php');
        const data = await res.json();

        if (data.error) {
          return;
        }

        document.getElementById('temperature').textContent =
          data.temperature !== null ? `${data.temperature} °C` : '-- °C';
        document.getElementById('humidity').textContent =
          data.humidity !== null ? `${data.humidity} %` : '-- %';
        document.getElementById('moisture').textContent =
          data.moisture !== null ? `${data.moisture} %` : '-- %';

        document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();

        if (data.moisture !== null && Number(data.moisture) < 30) {
          document.getElementById('alerts-list').innerHTML = '<li>Soil moisture is low. Irrigate now.</li>';
        } else {
          document.getElementById('alerts-list').innerHTML = '<li>All good.</li>';
        }
      } catch (err) {
        console.error('Failed to fetch sensor data', err);
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
