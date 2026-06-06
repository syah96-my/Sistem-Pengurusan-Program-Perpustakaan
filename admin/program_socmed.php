<?php
require __DIR__ . '/../login/require_login.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Social Media Activities</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
    <link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/program_table_css.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/program_modal_css.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">

    <link rel="stylesheet" href="<?= app_path('/assets/vendor/datatables/datatables.min.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">

  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/program_socmed.css') ?>">
</head>

<body>

<div class="top-banner">
    <div class="top-banner-content">
        <h1 class="system-name">
            Sistem Pengurusan Program Perpustakaan - Aktiviti Media Sosial
        </h1>
    </div>
</div>

<?php require __DIR__ . '/header/navbar.php';?>

<main class="main-content">

    <div class="crud-section">
        <div class="section-header">
            <h2>Social Media Activities</h2>
            <div class="button-group">
                <button class="btn-primary" id="add-btn">+ New Activity</button>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="filter-tags">
            <select id="filter-platform">
                <option value="">All Platforms</option>
            </select>

            <select id="filter-year">
                <option value="">All Years</option>
                <?php for ($y = date("Y"); $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- TABLE -->
        <div class="table-container">
            <table class="data-table" id="socmed-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Activity</th>
                    <th>Platform</th>
                    <th>Date</th>
                    <th>Link</th>
                    <th class="actions-col">Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>
</main>

<!-- MODAL -->
<?php require __DIR__ . '/modal/socmed.php'; ?>

<script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= app_path('/assets/vendor/datatables/datatables.min.js') ?>"></script>
<script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/modals/socmed.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/pages/program_socmed.js') ?>"></script>

</body>
</html>



