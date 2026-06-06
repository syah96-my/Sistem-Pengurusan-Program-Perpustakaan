<?php
require __DIR__ . '/../login/require_login.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
<link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
<link rel="stylesheet" href="<?= app_path('/admin/css/main-dashboard.css') ?>">
<link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/index.css') ?>">
</head>

<body>

<!-- =========================================================
     TOP BANNER (KEEP)
========================================================= -->
<div class="top-banner">
  <div class="top-banner-content">
    <h1 class="system-name">
      Sistem Pengurusan Program Perpustakaan
    </h1>
  </div>
</div>

<!-- =========================================================
     NAVBAR (KEEP)
========================================================= -->
<?php require __DIR__ . '/header/navbar.php';?>

<!-- =========================================================
     MAIN CONTENT (EMPTY FOR NOW)
========================================================= -->
<main class="main-content page-padded">
<!-- ===================== DASHBOARD CARDS ===================== -->
<section class="dash-section">
  <div class="dash-cards">
    <div class="dash-card">
      <div class="dash-card-title">Total Programs</div>
      <div class="dash-card-value" id="total-program">-</div>
    </div>

    <div class="dash-card warn">
      <div class="dash-card-title">Pending Verification</div>
      <div class="dash-card-value" id="total-pending">-</div>
    </div>

    <div class="dash-card danger">
      <div class="dash-card-title">Rejected</div>
      <div class="dash-card-value" id="total-rejected">-</div>
    </div>
  </div>
</section>

<!-- ===================== MONTHLY TREND ===================== -->
<section class="dash-section">
  <h2 class="dash-title">Programs This Month</h2>

    <div class="chart-card chart-card-tall">
    <canvas id="monthlyChart"></canvas>
    </div>
</section>

<!-- ===================== RECENT PROGRAMS ===================== -->
<section class="dash-section">
  <h2 class="dash-title">Latest Programs</h2>

  <div class="table-card">
    <table class="dash-table">
      <thead>
        <tr>
          <th>Program Name</th>
          <th>Library</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>
</section>

</main>

<!-- =========================================================
     JS LIBRARIES
========================================================= -->
<script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
<script src="<?= app_path('/assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>
<!-- =========================================================
     Dashboard
========================================================= -->

<script src="<?= app_path('/admin/javascript/pages/index.js') ?>"></script>


<!-- =========================================================
     FORCE PASSWORD CHANGE (KEEP)
========================================================= -->
<?php if (!empty($_SESSION['force_change_password'])): ?>
<script src="<?= app_path('/admin/javascript/common/change-password.js') ?>"></script>
<?php endif; ?>

</body>
</html>



