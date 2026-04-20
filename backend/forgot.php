<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars(trim($_POST["email"]));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $status = "error";
        $message = "Invalid email address. Please try again.";
    } else {
        require_once __DIR__ . "/config/db.php";
        try {
            $stmt = $pdo->prepare("INSERT INTO forgot_password_logs (email) VALUES (:email)");
            $stmt->execute([":email" => $email]);
            $status = "success";
            $message = "A password reset link has been sent to <strong>$email</strong>.";
        } catch (PDOException $e) {
            $status = "error";
            $message = "Database error. Please try again later.";
        }
    }
} else {
    $status = "error";
    $message = "Invalid request.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $status === 'success' ? 'Reset Link Sent' : 'Error'; ?> — AgroSense IoT</title>
  <?php if ($status === 'success'): ?>
    <meta http-equiv="refresh" content="6; url=../index.html">
  <?php endif; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../css/styles.css">
  <script>
    tailwind.config = {
      theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'], outfit: ['Outfit', 'sans-serif'] } } }
    }
  </script>
</head>
<body class="auth-bg">

  <!-- Three.js Canvas Background -->
  <canvas id="nature-canvas"></canvas>

  <!-- Back Button -->
  <div class="fixed top-6 left-6 z-50 animate-fade-in">
    <a href="../index.html" class="back-btn">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Login
    </a>
  </div>

  <div class="flex items-center justify-center min-h-screen px-4 py-12">
    <div class="glass-card p-8 md:p-10 w-full max-w-md text-center animate-fade-in-up">

      <?php if ($status === 'success'): ?>
        <!-- Success State -->
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full shadow-lg mb-6 animate-scale-in">
          <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"/>
          </svg>
        </div>
        <h1 class="text-2xl font-bold font-outfit text-green-800 mb-3">Check Your Email</h1>
        <p class="text-gray-600 mb-6"><?php echo $message; ?></p>
        
        <!-- Countdown timer -->
        <div class="bg-emerald-50 rounded-xl p-4 mb-6 border border-emerald-100">
          <p class="text-sm text-emerald-700">
            <span class="animate-pulse-gentle inline-block">⏳</span>
            Redirecting to login in <span id="countdown" class="font-bold">6</span> seconds...
          </p>
        </div>

        <a href="../index.html" class="btn-primary w-full py-3 text-base">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
          Go to Login Now
        </a>

      <?php else: ?>
        <!-- Error State -->
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-red-400 to-red-600 rounded-full shadow-lg mb-6 animate-scale-in">
          <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
        </div>
        <h1 class="text-2xl font-bold font-outfit text-red-700 mb-3">Something Went Wrong</h1>
        <p class="text-gray-600 mb-6"><?php echo $message; ?></p>
        
        <div class="space-y-3">
          <a href="../mycomponents/forgot.html" class="btn-primary w-full py-3 text-base">
            Try Again
          </a>
          <a href="../index.html" class="block text-sm text-green-700 font-medium hover:text-green-900 transition-colors">← Back to Login</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Three.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <script src="../js/nature-bg.js"></script>

  <?php if ($status === 'success'): ?>
  <script>
    let seconds = 6;
    const countdownEl = document.getElementById('countdown');
    setInterval(() => {
      seconds--;
      if (seconds >= 0) countdownEl.textContent = seconds;
    }, 1000);
  </script>
  <?php endif; ?>
</body>
</html>
