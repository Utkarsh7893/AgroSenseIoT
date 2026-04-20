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
  <title>Irrigation Logs</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen p-6">
  <div class="max-w-7xl mx-auto grid md:grid-cols-2 gap-6">
    <section class="bg-white rounded-xl shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Add Irrigation Log</h2>
      <?php if ($errors): ?><div class="mb-3 text-red-700"><?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>
      <?php if ($success): ?><div class="mb-3 text-green-700"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

      <form method="POST" class="space-y-3">
        <select name="plot_id" class="w-full border rounded p-2" required>
          <option value="">Select Plot</option>
          <?php foreach ($plots as $p): ?>
            <option value="<?php echo (int)$p["id"]; ?>"><?php echo htmlspecialchars($p["farm_name"] . " - " . $p["plot_name"]); ?></option>
          <?php endforeach; ?>
        </select>
        <input type="datetime-local" name="started_at" class="w-full border rounded p-2" required>
        <input type="datetime-local" name="ended_at" class="w-full border rounded p-2">
        <input type="text" name="method" class="w-full border rounded p-2" placeholder="Drip / Sprinkler / Flood">
        <input type="text" name="water_liters" class="w-full border rounded p-2" placeholder="Water used (liters)">
        <textarea name="notes" class="w-full border rounded p-2" placeholder="Notes"></textarea>
        <button class="w-full bg-blue-600 text-white rounded p-2">Save</button>
      </form>
    </section>

    <section class="bg-white rounded-xl shadow p-6 overflow-auto">
      <h2 class="text-xl font-semibold mb-4">Irrigation History</h2>
      <table class="w-full text-sm border">
        <thead class="bg-slate-100">
          <tr>
            <th class="p-2 border text-left">Farm/Plot</th>
            <th class="p-2 border text-left">Start</th>
            <th class="p-2 border text-left">End</th>
            <th class="p-2 border text-left">Method</th>
            <th class="p-2 border text-left">Liters</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $l): ?>
            <tr>
              <td class="p-2 border"><?php echo htmlspecialchars($l["farm_name"]." / ".$l["plot_name"]); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($l["started_at"]); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($l["ended_at"] ?? "-"); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($l["method"] ?? "-"); ?></td>
              <td class="p-2 border"><?php echo htmlspecialchars($l["water_liters"] ?? "-"); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </div>
</body>
</html>
