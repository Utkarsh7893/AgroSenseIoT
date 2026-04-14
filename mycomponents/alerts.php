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
  <title>Alerts</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold text-slate-800">Alerts</h1>
      <div class="space-x-2">
        <a href="./dashboard.php" class="px-4 py-2 rounded bg-green-600 text-white">Dashboard</a>
        <a href="./plots.php" class="px-4 py-2 rounded bg-indigo-600 text-white">Plots</a>
        <a href="../backend/logout.php" class="px-4 py-2 rounded bg-red-600 text-white">Logout</a>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
      <section class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Add Alert</h2>

        <?php if (!empty($errors)): ?>
          <div class="mb-4 rounded bg-red-100 text-red-700 px-4 py-3">
            <?php foreach ($errors as $e): ?><div><?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="mb-4 rounded bg-green-100 text-green-700 px-4 py-3"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
          <div>
            <label class="block mb-1 text-sm font-medium">Plot</label>
            <select name="plot_id" class="w-full border rounded px-3 py-2" required>
              <option value="">Select plot</option>
              <?php foreach ($plots as $plot): ?>
                <option value="<?php echo (int)$plot["id"]; ?>">
                  <?php echo htmlspecialchars($plot["farm_name"] . " - " . $plot["plot_name"]); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium">Severity</label>
            <select name="severity" class="w-full border rounded px-3 py-2">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium">Message</label>
            <textarea name="message" class="w-full border rounded px-3 py-2" rows="3" required></textarea>
          </div>

          <button class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Save Alert</button>
        </form>
      </section>

      <section class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Alert List</h2>
        <?php if (empty($alerts)): ?>
          <p class="text-slate-600">No alerts yet.</p>
        <?php else: ?>
          <div class="overflow-auto">
            <table class="w-full text-sm border">
              <thead class="bg-slate-100">
                <tr>
                  <th class="p-2 border text-left">Farm/Plot</th>
                  <th class="p-2 border text-left">Severity</th>
                  <th class="p-2 border text-left">Message</th>
                  <th class="p-2 border text-left">Status</th>
                  <th class="p-2 border text-left">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($alerts as $a): ?>
                  <tr>
                    <td class="p-2 border"><?php echo htmlspecialchars($a["farm_name"] . " / " . $a["plot_name"]); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars(strtoupper($a["severity"])); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($a["message"]); ?></td>
                    <td class="p-2 border"><?php echo $a["is_read"] ? "Read" : "Unread"; ?></td>
                    <td class="p-2 border">
                      <?php if (!$a["is_read"]): ?>
                        <a class="text-blue-600 hover:underline" href="./alerts.php?mark_read=<?php echo (int)$a["id"]; ?>">Mark Read</a>
                      <?php else: ?>
                        <span class="text-slate-400">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>
</body>
</html>
