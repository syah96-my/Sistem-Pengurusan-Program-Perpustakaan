<?php
require __DIR__ . '/../login/require_login.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Program CSV Export</title>

<script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
<link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
<link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/stat_download.css') ?>">
</head>

<body>

<div class="top-banner">
  <div class="top-banner-content">
    <h1 class="system-name">
      Sistem Pengurusan Program Perpustakaan
    </h1>
  </div>
</div>

<?php require __DIR__ . '/header/navbar.php';?>

<main class="main-content page-padded">

<div class="export-card">

<h3>Export Programs (CSV)</h3>
<p class="align-right-muted">

</p>

<form method="GET" action="programs_csv.php">

<div class="filter-row">

  <select id="filter-parent" name="parent_library_id">
    <option value="">Parent Library</option>
  </select>

  <select id="filter-library" name="library_id">
    <option value="">Library</option>
  </select>

  <select id="filter-type" name="library_type_id">
    <option value="">Library Type</option>
  </select>


  <input type="date" id="filter-date-from" name="date_from">
  <input type="date" id="filter-date-to"   name="date_to">

</div>

<button type="submit" class="btn btn-primary">
Download CSV
</button>

</form>

</div>
</main>

<script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>

<script src="<?= app_path('/admin/javascript/pages/stat_download.js') ?>"></script>

</body>
</html>



