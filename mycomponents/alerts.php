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

// Mark alert as read
if (isset($_GET["mark_read"])) {
    $alertId = (int)$_GET["mark_read"];
    if ($alertId > 0) {
        $markStmt = $pdo->prepare("
            UPDATE alerts a
            INNER JOIN plots p ON a.plot_id = p.id
            INNER JOIN farms f ON p.farm_id = f.id
            SET a.is_read = 1
            WHERE a.id = :alert_id AND f.user_id = :user_id
        ");
        $markStmt->execute([
            ":alert_id" => $alertId,
            ":user_id" => $userId
        ]);
    }
    header("Location: ./alerts.php");
    exit();
}

// Add alert
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $plotId = (int)($_POST["plot_id"] ?? 0);
    $severity = trim($_POST["severity"] ?? "low");
    $message = trim($_POST["message"] ?? "");

    if ($plotId <= 0) $errors[] = "Please select a plot.";
    if (!in_array($severity, ["low", "medium", "high"], true)) $errors[] = "Invalid severity.";
    if ($message === "") $errors[] = "Message is required.";

    if (empty($errors)) {
        $checkPlot = $pdo->prepare("
            SELECT p.id
            FROM plots p
            INNER JOIN farms f ON p.farm_id = f.id
            WHERE p.id = :plot_id AND f.user_id = :user_id
            LIMIT 1
        ");
        $checkPlot->execute([
            ":plot_id" => $plotId,
            ":user_id" => $userId
        ]);

        if (!$checkPlot->fetch()) {
            $errors[] = "Invalid plot selected.";
        } else {
            $insert = $pdo->prepare("
                INSERT INTO alerts (plot_id, severity, message, is_read)
                VALUES (:plot_id, :severity, :message, 0)
            ");
            $insert->execute([
                ":plot_id" => $plotId,
                ":severity" => $severity,
                ":message" => $message
            ]);
            $success = "Alert added successfully.";
        }
    }
}

// Fetch plots for dropdown
$plotStmt = $pdo->prepare("
    SELECT p.id, p.plot_name, f.farm_name
    FROM plots p
    INNER JOIN farms f ON p.farm_id = f.id
    WHERE f.user_id = :user_id
    ORDER BY f.farm_name, p.plot_name
");
$plotStmt->execute([":user_id" => $userId]);
$plots = $plotStmt->fetchAll();

// Fetch alerts
$alertStmt = $pdo->prepare("
    SELECT a.id, a.severity, a.message, a.is_read, a.created_at, p.plot_name, f.farm_name
    FROM alerts a
    INNER JOIN plots p ON a.plot_id = p.id
    INNER JOIN farms f ON p.farm_id = f.id
    WHERE f.user_id = :user_id
    ORDER BY a.id DESC
");
$alertStmt->execute([":user_id" => $userId]);
$alerts = $alertStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Manage crop alerts on AgroSense IoT — create, view, and manage severity-based notifications.">
  <title>Alerts — AgroSense IoT</title>
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
          <span class="text-2xl">⚠️</span> Alerts Manager
        </h1>
      </div>
      <div class="mgmt-actions">
        <a href="./farms.php" class="btn-secondary text-sm py-2 px-4">🌾 Farms</a>
        <a href="./plots.php" class="btn-secondary text-sm py-2 px-4">🗺️ Plots</a>
        <a href="../backend/logout.php" class="btn-danger text-sm py-2 px-4">Logout</a>
      </div>
    </div>

    <!-- Content Grid -->
    <div class="mgmt-grid">

      <!-- Add Alert Card -->
      <div class="mgmt-card animate-fade-in-up">
        <h2>🔔 Add Alert</h2>

        <?php if (!empty($errors)): ?>
          <div class="msg-error">
            <?php foreach ($errors as $e): ?><div class="text-sm"><?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="msg-success text-sm"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
          <div>
            <label class="form-label">Plot</label>
            <select name="plot_id" class="form-input" required>
              <option value="">Select plot</option>
              <?php foreach ($plots as $plot): ?>
                <option value="<?php echo (int)$plot["id"]; ?>">
                  <?php echo htmlspecialchars($plot["farm_name"] . " — " . $plot["plot_name"]); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Severity</label>
            <select name="severity" class="form-input">
              <option value="low">🟢 Low</option>
              <option value="medium">🟡 Medium</option>
              <option value="high">🔴 High</option>
            </select>
          </div>
          <div>
            <label class="form-label">Message</label>
            <textarea name="message" class="form-input" rows="3" placeholder="Describe the alert condition..." required></textarea>
          </div>
          <button type="submit" class="btn-primary w-full py-2.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Save Alert
          </button>
        </form>
      </div>

      <!-- Alert List Card -->
      <div class="mgmt-card animate-fade-in-up" style="animation-delay: 0.1s;">
        <h2>📋 Alert List</h2>
        <?php if (empty($alerts)): ?>
          <div class="text-center py-8">
            <span class="text-5xl block mb-3">✅</span>
            <p class="text-slate-500">No alerts yet. All clear!</p>
          </div>
        <?php else: ?>
          <div class="overflow-auto rounded-xl border border-slate-100">
            <table class="mgmt-table">
              <thead>
                <tr>
                  <th>Farm / Plot</th>
                  <th>Severity</th>
                  <th>Message</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($alerts as $a): ?>
                  <tr>
                    <td class="font-medium text-slate-800"><?php echo htmlspecialchars($a["farm_name"] . " / " . $a["plot_name"]); ?></td>
                    <td>
                      <?php
                        $sev = strtolower($a["severity"]);
                        $badgeClass = "badge-low";
                        if ($sev === "high") $badgeClass = "badge-high";
                        elseif ($sev === "medium") $badgeClass = "badge-medium";
                      ?>
                      <span class="badge <?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars(strtoupper($a["severity"])); ?>
                      </span>
                    </td>
                    <td class="text-sm max-w-xs"><?php echo htmlspecialchars($a["message"]); ?></td>
                    <td>
                      <?php if ($a["is_read"]): ?>
                        <span class="text-xs text-slate-400 font-medium">Read ✓</span>
                      <?php else: ?>
                        <span class="badge badge-medium">Unread</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!$a["is_read"]): ?>
                        <a class="text-emerald-600 hover:text-emerald-800 font-medium text-sm transition-colors" href="./alerts.php?mark_read=<?php echo (int)$a["id"]; ?>">Mark Read</a>
                      <?php else: ?>
                        <span class="text-slate-300">—</span>
                      <?php endif; ?>
                    </td>
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
