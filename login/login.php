<?php
require_once __DIR__ . '/../config/bootstrap.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistem Pengurusan Program Perpustakaan</title>

  <link rel="stylesheet" href="<?= app_path('/login/css/login.css') ?>">
</head>

<body>

<div class="login-container">
  <div class="login-card">
    <div class="login-header">
      <div class="logo">PR</div>
      <h1>Sistem Pengurusan Program Perpustakaan</h1>
      <p>Please sign in to continue</p>
    </div>

    <div class="login-body">
         <?php
              if (isset($_SESSION['error'])) {
                  echo "<div class='error-message'>" . htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') . "</div>";
                  unset($_SESSION['error']);
              }
        ?>
      <div class="success-message" id="success-message">Login successful!</div>

    <form id="login-form" method="POST" action="login_process.php">
        <div class="form-group">
            <label>User ID</label>
            <div class="input-wrapper">
                <input type="text" id="user-id" name="username" autocomplete="username" required>
            </div>
        </div>
    
        <div class="form-group">
            <label>Password</label>
            <div class="input-wrapper">
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                <button type="button" class="password-toggle" id="toggle-password">Show</button>
            </div>
        </div>
    
        <button class="login-button" id="login-btn">Sign In</button>
    </form>

    </div>
  </div>
</div>

<script>
  // Password toggle only
  document.getElementById('toggle-password').onclick = () => {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
  };
</script>

</body>
</html>
