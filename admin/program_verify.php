<?php
require __DIR__ . '/../login/require_login.php';

$program_id = 0;
$type_name = 'All Program Types';
if (!empty($_GET['t'])) {
    $decryptedProgramTypeId = gm_decrypt($_GET['t']);
    if ($decryptedProgramTypeId !== false && ctype_digit((string)$decryptedProgramTypeId)) {
        $program_id = (int)$decryptedProgramTypeId;
        $stmt = $pdo->prepare("SELECT type_name FROM gm_program_types WHERE id = ? LIMIT 1");
        $stmt->execute([$program_id]);
        $type_name = $stmt->fetchColumn() ?: $type_name;
    }
}
?>

<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pendaftaran Program</title>
  <script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
  <link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/program_table_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/program_modal_css.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">
<link rel="stylesheet" href="<?= app_path('/assets/vendor/datatables/datatables.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/program_verify.css') ?>">
</head>
 <body>
  <div class="top-banner">
   <div class="top-banner-content">
    <h1 class="system-name" id="system-name">Sistem Pengurusan Program Perpustakaan</h1>
   </div>
  </div>

  <?php require __DIR__ . '/header/navbar.php';?>

  <main class="main-content">
   <div class="welcome-card">
    <h3>
    <?= htmlspecialchars(strtoupper($type_name), ENT_QUOTES, 'UTF-8') ?></h3>
        <h3>
    <?php
    echo date('Y');
    ?></h3>
   </div>

   <div class="crud-section">
    <div class="section-header">
     <h2>Data Management</h2>
    </div>

<div class="tabs-container">
    <div class="tabs">
        <button class="tab-button active" data-tab="pending">Pending</button>
        <button class="tab-button" data-tab="approved">Approved</button>
        <button class="tab-button" data-tab="rejected">Rejected</button>
    </div>

    <div class="tab-content">

        <!-- ================= PENDING ================= -->
        <div class="tab-pane active" id="pending">

            <div class="bulk-actions">
                <button class="btn-bulk btn-bulk-approve js-bulk-approve">Bulk Approve</button>
                <button class="btn-bulk btn-bulk-reject js-bulk-reject">Bulk Reject</button>
                <button class="btn-bulk btn-bulk-remove js-bulk-remove">Bulk Remove</button>
            </div>

            <div class="table-container">
                <table class="data-table" id="table-pending">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="check-all"></th>
                            <th>ID</th>
                            <th>Program</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>

        <!-- ================= APPROVED ================= -->
        <div class="tab-pane" id="approved">

            <div class="bulk-actions">
                <button class="btn-bulk btn-bulk-reject js-bulk-reject">Bulk Reject</button>
                <button class="btn-bulk btn-bulk-remove js-bulk-remove">Bulk Remove</button>
            </div>

            <div class="table-container">
                <table class="data-table" id="table-approved">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="check-all"></th>
                            <th>ID</th>
                            <th>Program</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>

        <!-- ================= REJECTED ================= -->
        <div class="tab-pane" id="rejected">

            <div class="bulk-actions">
                <button class="btn-bulk btn-bulk-approve js-bulk-approve">Bulk Approve</button>
                <button class="btn-bulk btn-bulk-remove js-bulk-remove">Bulk Remove</button>
            </div>

            <div class="table-container">
                <table class="data-table" id="table-rejected">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="check-all"></th>
                            <th>ID</th>
                            <th>Program</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>

    </div>
</div>




    </div>

    

  </main>

  <!-- Program Modal (keeps your include) -->
  <?php require __DIR__ . '/modal/program.php';?>

  <!-- libs -->
  <script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
  <script src="<?= app_path('/assets/vendor/datatables/datatables.min.js') ?>"></script>
  <script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-verify/verification_config.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-verify/verification_utils.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-verify/verification_notes.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-verify/verification_table.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-verify/verification_modal.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-verify/verification_action_single.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-verify/verification_action_bulk.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-verify/verification_init.js') ?>"></script>
 </body>
</html>



