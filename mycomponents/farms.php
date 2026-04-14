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
    $farmName = trim($_POST["farm_name"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $areaAcres = trim($_POST["area_acres"] ?? "");

    if ($farmName === "") {
        $errors[] = "Farm name is required.";
    }

    if ($areaAcres !== "" && !is_numeric($areaAcres)) {
        $errors[] = "Area must be a number.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO farms (user_id, farm_name, location, area_acres)
            VALUES (:user_id, :farm_name, :location, :area_acres)
        ");
        $stmt->execute([
            ":user_id" => $userId,
            ":farm_name" => $farmName,
            ":location" => $location !== "" ? $location : null,
            ":area_acres" => $areaAcres !== "" ? $areaAcres : null
        ]);
        $success = "Farm added successfully.";
    }
}

// Fetch farms for logged-in user
$listStmt = $pdo->prepare("
    SELECT id, farm_name, location, area_acres, created_at
    FROM farms
    WHERE user_id = :user_id
    ORDER BY id DESC
");
$listStmt->execute([":user_id" => $userId]);
$farms = $listStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Farms</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold text-slate-800">My Farms</h1>
      <div class="space-x-2">
        <a href="./dashboard.php" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">Dashboard</a>
        <a href="../backend/logout.php" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">Logout</a>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
      <section class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Add Farm</h2>

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

        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Farm Name *</label>
            <input type="text" name="farm_name" class="w-full border rounded px-3 py-2" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Location</label>
            <input type="text" name="location" class="w-full border rounded px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Area (Acres)</label>
            <input type="text" name="area_acres" class="w-full border rounded px-3 py-2" />
          </div>
          <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
            Save Farm
          </button>
        </form>
      </section>

      <section class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Farm List</h2>

        <?php if (empty($farms)): ?>
          <p class="text-slate-600">No farms added yet.</p>
        <?php else: ?>
          <div class="overflow-auto">
            <table class="w-full text-sm border">
              <thead class="bg-slate-100">
                <tr >
                  <th class="p-2 border text-left">Name</th>
                  <th class="p-2 border text-left">Location</th>
                  <th class="p-2 border text-left">Area</th>
                  <th class="p-2 border text-left">Created</th>
                  <th class="p-2 border text-left">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($farms as $farm): ?>
                  <tr>
                    <td class="p-2 border"><?php echo htmlspecialchars($farm["farm_name"]); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($farm["location"] ?? "-"); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($farm["area_acres"] ?? "-"); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($farm["created_at"]); ?></td>
                    <td class="p-2 border">
                      <a href="./plots.php?farm_id=<?php echo (int)$farm['id']; ?>" class="text-blue-600 hover:underline">Manage Plots</a>
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
