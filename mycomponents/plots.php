<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.html");
    exit();
}

require_once __DIR__ . "/../backend/config/db.php";

$userId = (int)$_SESSION["user_id"];
$errors = [];
$success = "";

// Fetch farms of logged-in user
$farmStmt = $pdo->prepare("SELECT id, farm_name FROM farms WHERE user_id = :user_id ORDER BY farm_name ASC");
$farmStmt->execute([":user_id" => $userId]);
$farms = $farmStmt->fetchAll();

// Selected farm from query string (optional)
$selectedFarmId = isset($_GET["farm_id"]) ? (int)$_GET["farm_id"] : 0;

// Validate selected farm belongs to user
if ($selectedFarmId > 0) {
    $checkFarm = $pdo->prepare("SELECT id FROM farms WHERE id = :farm_id AND user_id = :user_id LIMIT 1");
    $checkFarm->execute([
        ":farm_id" => $selectedFarmId,
        ":user_id" => $userId
    ]);
    if (!$checkFarm->fetch()) {
        $selectedFarmId = 0;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $farmId = (int)($_POST["farm_id"] ?? 0);
    $plotName = trim($_POST["plot_name"] ?? "");
    $cropType = trim($_POST["crop_type"] ?? "");
    $sowingDate = trim($_POST["sowing_date"] ?? "");
    $irrigationType = trim($_POST["irrigation_type"] ?? "");

    if ($farmId <= 0) {
        $errors[] = "Please select a farm.";
    } else {
        $farmCheckStmt = $pdo->prepare("SELECT id FROM farms WHERE id = :farm_id AND user_id = :user_id LIMIT 1");
        $farmCheckStmt->execute([
            ":farm_id" => $farmId,
            ":user_id" => $userId
        ]);
        if (!$farmCheckStmt->fetch()) {
            $errors[] = "Invalid farm selected.";
        }
    }

    if ($plotName === "") {
        $errors[] = "Plot name is required.";
    }

    if ($sowingDate !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sowingDate)) {
        $errors[] = "Invalid sowing date format.";
    }

    if (empty($errors)) {
        $insert = $pdo->prepare("
            INSERT INTO plots (farm_id, plot_name, crop_type, sowing_date, irrigation_type)
            VALUES (:farm_id, :plot_name, :crop_type, :sowing_date, :irrigation_type)
        ");
        $insert->execute([
            ":farm_id" => $farmId,
            ":plot_name" => $plotName,
            ":crop_type" => $cropType !== "" ? $cropType : null,
            ":sowing_date" => $sowingDate !== "" ? $sowingDate : null,
            ":irrigation_type" => $irrigationType !== "" ? $irrigationType : null
        ]);

        $success = "Plot added successfully.";
        $selectedFarmId = $farmId;
    }
}

// Fetch plots (all user farms OR specific selected farm)
if ($selectedFarmId > 0) {
    $plotStmt = $pdo->prepare("
        SELECT p.id, p.plot_name, p.crop_type, p.sowing_date, p.irrigation_type, p.created_at, f.farm_name
        FROM plots p
        INNER JOIN farms f ON p.farm_id = f.id
        WHERE f.user_id = :user_id AND f.id = :farm_id
        ORDER BY p.id DESC
    ");
    $plotStmt->execute([
        ":user_id" => $userId,
        ":farm_id" => $selectedFarmId
    ]);
} else {
    $plotStmt = $pdo->prepare("
        SELECT p.id, p.plot_name, p.crop_type, p.sowing_date, p.irrigation_type, p.created_at, f.farm_name
        FROM plots p
        INNER JOIN farms f ON p.farm_id = f.id
        WHERE f.user_id = :user_id
        ORDER BY p.id DESC
    ");
    $plotStmt->execute([":user_id" => $userId]);
}
$plots = $plotStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Manage your farm plots on AgroSense IoT — track crops, sowing dates, and irrigation types.">
  <title>Farm Plots — AgroSense IoT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../css/styles.css">
  <script>
    tailwind.config = {
      theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'], outfit: ['Outfit', 'sans-serif'] } } }
    }
  </script>
</head>
<body class="mgmt-page">
  <div class="mgmt-container animate-fade-in">

    <!-- Header -->
    <div class="mgmt-header">
      <div class="flex items-center gap-3 flex-wrap">
        <a href="./dashboard.php" class="back-btn">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          Dashboard
        </a>
        <a href="./farms.php" class="back-btn">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          Farms
        </a>
        <h1 class="flex items-center gap-2">
          <span class="text-2xl">🗺️</span> Farm Plots
        </h1>
      </div>
      <div class="mgmt-actions">
        <a href="./alerts.php" class="btn-secondary text-sm py-2 px-4">⚠️ Alerts</a>
        <a href="./irrigation.php" class="btn-secondary text-sm py-2 px-4">💧 Irrigation</a>
        <a href="../backend/logout.php" class="btn-danger text-sm py-2 px-4">Logout</a>
      </div>
    </div>

    <!-- Content Grid -->
    <div class="mgmt-grid">

      <!-- Add Plot Card -->
      <div class="mgmt-card animate-fade-in-up">
        <h2>➕ Add Plot</h2>

        <?php if (!empty($errors)): ?>
          <div class="msg-error">
            <ul class="list-disc pl-5 text-sm">
              <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
          <div class="msg-success text-sm"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (empty($farms)): ?>
          <div class="text-center py-6">
            <span class="text-4xl block mb-2">🌾</span>
            <p class="text-slate-500 text-sm">No farms available. <a href="./farms.php" class="text-emerald-600 font-medium hover:underline">Create a farm first</a>.</p>
          </div>
        <?php else: ?>
          <form method="POST" class="space-y-4">
            <div>
              <label class="form-label">Farm *</label>
              <select name="farm_id" class="form-input" required>
                <option value="">Select farm</option>
                <?php foreach ($farms as $farm): ?>
                  <option value="<?php echo (int)$farm["id"]; ?>" <?php echo ($selectedFarmId === (int)$farm["id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($farm["farm_name"]); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Plot Name *</label>
              <input type="text" name="plot_name" class="form-input" placeholder="e.g. North Field A" required />
            </div>
            <div>
              <label class="form-label">Crop Type</label>
              <input type="text" name="crop_type" class="form-input" placeholder="Wheat / Rice / Maize" />
            </div>
            <div>
              <label class="form-label">Sowing Date</label>
              <input type="date" name="sowing_date" class="form-input" />
            </div>
            <div>
              <label class="form-label">Irrigation Type</label>
              <input type="text" name="irrigation_type" class="form-input" placeholder="Drip / Sprinkler / Flood" />
            </div>
            <button type="submit" class="btn-primary w-full py-2.5">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
              Save Plot
            </button>
          </form>
        <?php endif; ?>
      </div>

      <!-- Plot List Card -->
      <div class="mgmt-card animate-fade-in-up" style="animation-delay: 0.1s;">
        <h2>📋 Plot List</h2>

        <?php if (empty($plots)): ?>
          <div class="text-center py-8">
            <span class="text-5xl block mb-3">🗺️</span>
            <p class="text-slate-500">No plots added yet.</p>
          </div>
        <?php else: ?>
          <div class="overflow-auto rounded-xl border border-slate-100">
            <table class="mgmt-table">
              <thead>
                <tr>
                  <th>Farm</th>
                  <th>Plot</th>
                  <th>Crop</th>
                  <th>Sowing</th>
                  <th>Irrigation</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($plots as $plot): ?>
                  <tr>
                    <td class="font-medium text-slate-800"><?php echo htmlspecialchars($plot["farm_name"]); ?></td>
                    <td><?php echo htmlspecialchars($plot["plot_name"]); ?></td>
                    <td><?php echo htmlspecialchars($plot["crop_type"] ?? "—"); ?></td>
                    <td class="text-xs"><?php echo htmlspecialchars($plot["sowing_date"] ?? "—"); ?></td>
                    <td><?php echo htmlspecialchars($plot["irrigation_type"] ?? "—"); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
