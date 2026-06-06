<?php
require __DIR__ . '/../login/require_login.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Program Status Analytics</title>

    <script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
    <link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/program_table_css.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/program_modal_css.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">
<link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/stat_status.css') ?>">
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
      <h3>Program Status Overview</h3>
      <p class="muted-note">
        Status distribution of programs across libraries.
      </p>
    </div>

    <!-- =========================== FILTER BAR =========================== -->
    <div class="filter-row">

      <select id="filter-parent">
        <option value="">Parent Library</option>
      </select>

      <select id="filter-library">
        <option value="">Library</option>
      </select>

      <select id="filter-type">
        <option value="">Library Type</option>
        <?php

          $stmt = $pdo->query("
              SELECT id, type_name
              FROM gm_library_types
              WHERE id > 1
              ORDER BY id ASC
          ");

          while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo '<option value="'.$r['id'].'">'.$r['type_name'].'</option>';
          }
        ?>
      </select>
      <input id="search-library" type="text" placeholder="Search library name...">
      <div class="date-group">
        <label for="filter-date-from">From</label>
        <input id="filter-date-from" type="date">

        <label for="filter-date-to">Until</label>
        <input id="filter-date-to" type="date">
      </div>

      

      <button id="btn-refresh" class="btn btn-primary">Refresh</button>
    </div>

    <!-- =========================== RESULT BOX =========================== -->
    <div class="page-box">
      <div id="status-grid" class="stats-grid">
        <div class="stat-card"><h3>Incomplete</h3><div class="value" id="val-incomplete">0</div></div>
        <div class="stat-card"><h3>Pending</h3><div class="value" id="val-pending">0</div></div>
        <div class="stat-card"><h3>Verified</h3><div class="value" id="val-verified">0</div></div>
        <div class="stat-card"><h3>Rejected</h3><div class="value" id="val-rejected">0</div></div>
      </div>
      
        <div class="status-table-wrapper">
          <table id="status-by-type-table" class="status-table">
            <thead>
              <tr>
                <th>Program Type</th>
                <th>Incomplete</th>
                <th>Pending</th>
                <th>Verified</th>
                <th>Rejected</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="5" class="text-center-muted">No data</td>
              </tr>
            </tbody>
          </table>
        </div>
    </div>

  </main>

  <script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
  <script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>

 <script src="<?= app_path('/admin/javascript/pages/stat_status.js') ?>"></script>


</body>
</html>



