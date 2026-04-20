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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $plotId = (int)($_POST["plot_id"] ?? 0);
    $startedAt = trim($_POST["started_at"] ?? "");
    $endedAt = trim($_POST["ended_at"] ?? "");
    $method = trim($_POST["method"] ?? "");
    $waterLiters = trim($_POST["water_liters"] ?? "");
    $notes = trim($_POST["notes"] ?? "");

    if ($plotId <= 0) $errors[] = "Select a plot.";
    if ($startedAt === "") $errors[] = "Start time is required.";
    if ($waterLiters !== "" && !is_numeric($waterLiters)) $errors[] = "Water liters must be numeric.";

    if (empty($errors)) {
        $check = $pdo->prepare("
            SELECT p.id
            FROM plots p
            INNER JOIN farms f ON p.farm_id = f.id
            WHERE p.id = :plot_id AND f.user_id = :user_id
            LIMIT 1
        ");
        $check->execute([":plot_id" => $plotId, ":user_id" => $userId]);

        if (!$check->fetch()) {
            $errors[] = "Invalid plot.";
        } else {
            $ins = $pdo->prepare("
                INSERT INTO irrigation_logs (plot_id, started_at, ended_at, method, water_liters, notes)
                VALUES (:plot_id, :started_at, :ended_at, :method, :water_liters, :notes)
            ");
            $ins->execute([
                ":plot_id" => $plotId,
                ":started_at" => $startedAt,
                ":ended_at" => $endedAt !== "" ? $endedAt : null,
                ":method" => $method !== "" ? $method : null,
                ":water_liters" => $waterLiters !== "" ? $waterLiters : null,
                ":notes" => $notes !== "" ? $notes : null
            ]);
            $success = "Irrigation log added.";
        }
    }
}

$plotsStmt = $pdo->prepare("
    SELECT p.id, p.plot_name, f.farm_name
    FROM plots p
    INNER JOIN farms f ON p.farm_id = f.id
    WHERE f.user_id = :user_id
    ORDER BY f.farm_name, p.plot_name
");
$plotsStmt->execute([":user_id" => $userId]);
$plots = $plotsStmt->fetchAll();

$listStmt = $pdo->prepare("
    SELECT l.id, l.started_at, l.ended_at, l.method, l.water_liters, l.notes, p.plot_name, f.farm_name
    FROM irrigation_logs l
    INNER JOIN plots p ON l.plot_id = p.id
    INNER JOIN farms f ON p.farm_id = f.id
    WHERE f.user_id = :user_id
    ORDER BY l.id DESC
");
$listStmt->execute([":user_id" => $userId]);
$logs = $listStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Track irrigation history on AgroSense IoT — log watering schedules, methods, and water usage.">
  <title>Irrigation Logs — AgroSense IoT</title>
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
      <div class="flex items-center gap-4">
        <a href="./dashboard.php" class="back-btn">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          Dashboard
        </a>
        <h1 class="flex items-center gap-2">
          <span class="text-2xl">💧</span> Irrigation Logs
        </h1>
      </div>
      <div class="mgmt-actions">
        <a href="./farms.php" class="btn-secondary text-sm py-2 px-4">🌾 Farms</a>
        <a href="./plots.php" class="btn-secondary text-sm py-2 px-4">🗺️ Plots</a>
        <a href="./alerts.php" class="btn-secondary text-sm py-2 px-4">⚠️ Alerts</a>
        <a href="../backend/logout.php" class="btn-danger text-sm py-2 px-4">Logout</a>
      </div>
    </div>

    <!-- Content Grid -->
    <div class="mgmt-grid">

      <!-- Add Irrigation Log Card -->
      <div class="mgmt-card animate-fade-in-up">
        <h2>➕ Add Irrigation Log</h2>

        <?php if ($errors): ?>
          <div class="msg-error">
            <?php foreach ($errors as $e): ?><div class="text-sm"><?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="msg-success text-sm"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
          <div>
            <label class="form-label">Plot *</label>
            <select name="plot_id" class="form-input" required>
              <option value="">Select Plot</option>
              <?php foreach ($plots as $p): ?>
                <option value="<?php echo (int)$p["id"]; ?>"><?php echo htmlspecialchars($p["farm_name"] . " — " . $p["plot_name"]); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Start Time *</label>
            <input type="datetime-local" name="started_at" class="form-input" required>
          </div>
          <div>
            <label class="form-label">End Time</label>
            <input type="datetime-local" name="ended_at" class="form-input">
          </div>
          <div>
            <label class="form-label">Method</label>
            <input type="text" name="method" class="form-input" placeholder="Drip / Sprinkler / Flood">
          </div>
          <div>
            <label class="form-label">Water Used (Liters)</label>
            <input type="text" name="water_liters" class="form-input" placeholder="e.g. 500">
          </div>
          <div>
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-input" rows="2" placeholder="Optional notes..."></textarea>
          </div>
          <button type="submit" class="btn-primary w-full py-2.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Save Log
          </button>
        </form>
      </div>

      <!-- Irrigation History Card -->
      <div class="mgmt-card animate-fade-in-up" style="animation-delay: 0.1s;">
        <h2>📋 Irrigation History</h2>

        <?php if (empty($logs)): ?>
          <div class="text-center py-8">
            <span class="text-5xl block mb-3">💧</span>
            <p class="text-slate-500">No irrigation logs yet.</p>
          </div>
        <?php else: ?>
          <div class="overflow-auto rounded-xl border border-slate-100">
            <table class="mgmt-table">
              <thead>
                <tr>
                  <th>Farm / Plot</th>
                  <th>Start</th>
                  <th>End</th>
                  <th>Method</th>
                  <th>Liters</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($logs as $l): ?>
                  <tr>
                    <td class="font-medium text-slate-800"><?php echo htmlspecialchars($l["farm_name"]." / ".$l["plot_name"]); ?></td>
                    <td class="text-xs"><?php echo htmlspecialchars($l["started_at"]); ?></td>
                    <td class="text-xs"><?php echo htmlspecialchars($l["ended_at"] ?? "—"); ?></td>
                    <td><?php echo htmlspecialchars($l["method"] ?? "—"); ?></td>
                    <td><?php echo htmlspecialchars($l["water_liters"] ?? "—"); ?></td>
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
