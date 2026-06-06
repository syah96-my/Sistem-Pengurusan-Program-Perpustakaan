<?php
require __DIR__ . '/../login/require_login.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Program Type Analytics</title>
    <script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
    <link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/program_table_css.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/program_modal_css.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">

  <link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/assets/vendor/datatables/datatables.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/stat_program_type.css') ?>">
</head>

<body>

  <div class="top-banner">
    <div class="top-banner-content">
      <h1 class="system-name" id="system-name">Sistem Pengurusan Program Perpustakaan</h1>
    </div>
  </div>

  <?php require __DIR__ . '/header/navbar.php';?>

  <main class="main-content page-padded">

    <div class="welcome-card section-spaced">
      <h3>Program Type Analytics</h3>
      <p class="muted-note">Number of programs by type across all libraries.</p>
    </div>

    <!-- FILTERS -->
    <div class="filter-row">
      <select id="filter-parent"><option value="">Parent Library</option></select>
      <select id="filter-type"><option value="">Library Type</option></select>
      <input id="search-library" type="text" placeholder="Search library name...">
        <div class="date-group">
        <label for="filter-date-from">From</label>
        <input id="filter-date-from" type="date">

        <label for="filter-date-to">Until</label>
        <input id="filter-date-to" type="date">
        </div>

      <button id="btn-refresh" class="btn btn-primary">Refresh</button>
    </div>

    <!-- SUMMARY BADGES (DYNAMIC) -->
    <div class="badge-box" id="summary-badges"></div>

    <div id="loadingBox">Loading...</div>

    <!-- TABLE -->
    <table id="typeTable" class="display nowrap table-full">
      <thead><tr id="table-header-row"></tr></thead>
      <tbody id="table-body"></tbody>
    </table>

  </main>

  <script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
  <script src="<?= app_path('/assets/vendor/datatables/datatables.min.js') ?>"></script>
  <script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/pages/stat_program_type.js') ?>"></script>


</body>
</html>



