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
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/program_input.css') ?>">
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
     <div class="button-group">
       <button class="btn-primary" id="add-btn">+ New Record</button>
       <button class="btn-primary" id="bulk-import-btn">Bulk Import</button>
     </div>
    </div>
<div class="tabs-container">
    <div class="tabs">
        <button class="tab-button active" data-status="incomplete">
            Incomplete <span class="tab-badge tab-warn" id="count-incomplete"></span>
        </button>

        <button class="tab-button" data-status="pending">
            Pending
        </button>

        <button class="tab-button" data-status="verified">
            Verified
        </button>

        <button class="tab-button" data-status="rejected">
            Rejected <span class="tab-badge tab-danger" id="count-rejected"></span>
        </button>

        <button class="tab-button" data-status="delete">
            Deleted
        </button>
    </div>


    <div class="tab-content">

        <!-- ================= STATUS TAB: INCOMPLETE ================= -->
        <div class="tab-pane active" id="status-incomplete">

            <!-- STAGE FILTERS -->
            <div class="filter-tags stage-filter" data-for="incomplete">
                <button class="stage-btn active" data-stage="">All</button>
                <button class="stage-btn" data-stage="pre_program">Pre Program</button>
                <button class="stage-btn" data-stage="completed">Completed</button>
                <button class="stage-btn" data-stage="cancelled">Cancelled</button>
            </div>

            <div class="table-container">
                <table class="data-table" id="table-incomplete">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Program</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        
         <!-- ================= STATUS TAB: PENDING ================= -->
        <div class="tab-pane" id="status-pending">

            <div class="filter-tags stage-filter" data-for="pending">
                <button class="stage-btn active" data-stage="">All</button>
                <button class="stage-btn" data-stage="pre_program">Pre Program</button>
                <button class="stage-btn" data-stage="completed">Completed</button>
                <button class="stage-btn" data-stage="cancelled">Cancelled</button>
            </div>

            <div class="table-container">
                <table class="data-table" id="table-pending">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Program</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        

        <!-- ================= STATUS TAB: VERIFIED ================= -->
        <div class="tab-pane" id="status-verified">

            <div class="filter-tags stage-filter" data-for="verified">
                <button class="stage-btn active" data-stage="">All</button>
                <button class="stage-btn" data-stage="pre_program">Pre Program</button>
                <button class="stage-btn" data-stage="completed">Completed</button>
                <button class="stage-btn" data-stage="cancelled">Cancelled</button>
            </div>

            <div class="table-container">
                <table class="data-table" id="table-verified">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Program</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- ================= STATUS TAB: REJECTED ================= -->
        <div class="tab-pane" id="status-rejected">

            <div class="filter-tags stage-filter" data-for="rejected">
                <button class="stage-btn active" data-stage="">All</button>
                <button class="stage-btn" data-stage="pre_program">Pre Program</button>
                <button class="stage-btn" data-stage="completed">Completed</button>
                <button class="stage-btn" data-stage="cancelled">Cancelled</button>
            </div>

            <div class="table-container">
                <table class="data-table" id="table-rejected">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Program</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- ================= STATUS TAB: DELETE ================= -->
        <div class="tab-pane" id="status-delete">

            <div class="table-container">
                <table class="data-table" id="table-delete">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Program</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Notes</th>
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
  <script src="<?= app_path('/admin/javascript/program-input/input_bulk_import.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-input/input_config.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-input/input_utils.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-input/input_manual_override.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-input/input_tabs.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-input/input_actions.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-input/input_participants.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-input/input_program_form.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-input/input_datatables.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/program-input/input_main.js') ?>"></script>
 </body>
</html>



