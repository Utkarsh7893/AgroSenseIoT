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
  <title>Plots</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold text-slate-800">Farm Plots</h1>
      <div class="space-x-2">
        <a href="./dashboard.php" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">Dashboard</a>
        <a href="./farms.php" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Farms</a>
        <a href="../backend/logout.php" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">Logout</a>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
      <section class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Add Plot</h2>

        <?php if (!empty($errors)): ?>
          <div class="mb-4 rounded bg-red-100 text-red-700 px-4 py-3">
            <ul class="list-disc pl-5">
              <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
          <div class="mb-4 rounded bg-green-100 text-green-700 px-4 py-3">
            <?php echo htmlspecialchars($success); ?>
          </div>
        <?php endif; ?>

        <?php if (empty($farms)): ?>
          <p class="text-slate-600">No farms available. Create a farm first.</p>
        <?php else: ?>
          <form method="POST" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Farm *</label>
              <select name="farm_id" class="w-full border rounded px-3 py-2" required>
                <option value="">Select farm</option>
                <?php foreach ($farms as $farm): ?>
                  <option value="<?php echo (int)$farm["id"]; ?>" <?php echo ($selectedFarmId === (int)$farm["id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($farm["farm_name"]); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Plot Name *</label>
              <input type="text" name="plot_name" class="w-full border rounded px-3 py-2" required />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Crop Type</label>
              <input type="text" name="crop_type" class="w-full border rounded px-3 py-2" placeholder="Wheat / Rice / Maize" />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Sowing Date</label>
              <input type="date" name="sowing_date" class="w-full border rounded px-3 py-2" />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Irrigation Type</label>
              <input type="text" name="irrigation_type" class="w-full border rounded px-3 py-2" placeholder="Drip / Sprinkler / Flood" />
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
              Save Plot
            </button>
          </form>
        <?php endif; ?>
      </section>

      <section class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Plot List</h2>

        <?php if (empty($plots)): ?>
          <p class="text-slate-600">No plots added yet.</p>
        <?php else: ?>
          <div class="overflow-auto">
            <table class="w-full text-sm border">
              <thead class="bg-slate-100">
                <tr>
                  <th class="p-2 border text-left">Farm</th>
                  <th class="p-2 border text-left">Plot</th>
                  <th class="p-2 border text-left">Crop</th>
                  <th class="p-2 border text-left">Sowing</th>
                  <th class="p-2 border text-left">Irrigation</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($plots as $plot): ?>
                  <tr>
                    <td class="p-2 border"><?php echo htmlspecialchars($plot["farm_name"]); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($plot["plot_name"]); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($plot["crop_type"] ?? "-"); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($plot["sowing_date"] ?? "-"); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($plot["irrigation_type"] ?? "-"); ?></td>
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
