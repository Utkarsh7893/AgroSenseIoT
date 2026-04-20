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
  <meta name="description" content="Manage your farms on AgroSense IoT — add new farms and view your farm portfolio.">
  <title>My Farms — AgroSense IoT</title>
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
          <span class="text-2xl">🌾</span> My Farms
        </h1>
      </div>
      <div class="mgmt-actions">
        <a href="./plots.php" class="btn-secondary text-sm py-2 px-4">🗺️ Plots</a>
        <a href="./alerts.php" class="btn-secondary text-sm py-2 px-4">⚠️ Alerts</a>
        <a href="../backend/logout.php" class="btn-danger text-sm py-2 px-4">Logout</a>
      </div>
    </div>

    <!-- Content Grid -->
    <div class="mgmt-grid">

      <!-- Add Farm Card -->
      <div class="mgmt-card animate-fade-in-up">
        <h2>➕ Add New Farm</h2>

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

        <form method="POST" class="space-y-4">
          <div>
            <label class="form-label">Farm Name *</label>
            <input type="text" name="farm_name" class="form-input" placeholder="e.g. Green Valley Farm" required />
          </div>
          <div>
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-input" placeholder="e.g. Rajasthan, India" />
          </div>
          <div>
            <label class="form-label">Area (Acres)</label>
            <input type="text" name="area_acres" class="form-input" placeholder="e.g. 12.5" />
          </div>
          <button type="submit" class="btn-primary w-full py-2.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Save Farm
          </button>
        </form>
      </div>

      <!-- Farm List Card -->
      <div class="mgmt-card animate-fade-in-up" style="animation-delay: 0.1s;">
        <h2>📋 Farm List</h2>

        <?php if (empty($farms)): ?>
          <div class="text-center py-8">
            <span class="text-5xl block mb-3">🌱</span>
            <p class="text-slate-500">No farms added yet. Create your first farm!</p>
          </div>
        <?php else: ?>
          <div class="overflow-auto rounded-xl border border-slate-100">
            <table class="mgmt-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Location</th>
                  <th>Area</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($farms as $farm): ?>
                  <tr>
                    <td class="font-medium text-slate-800"><?php echo htmlspecialchars($farm["farm_name"]); ?></td>
                    <td><?php echo htmlspecialchars($farm["location"] ?? "—"); ?></td>
                    <td><?php echo htmlspecialchars($farm["area_acres"] ?? "—"); ?></td>
                    <td class="text-xs"><?php echo htmlspecialchars($farm["created_at"]); ?></td>
                    <td>
                      <a href="./plots.php?farm_id=<?php echo (int)$farm['id']; ?>" class="text-emerald-600 hover:text-emerald-800 font-medium text-sm transition-colors">
                        Manage Plots →
                      </a>
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
