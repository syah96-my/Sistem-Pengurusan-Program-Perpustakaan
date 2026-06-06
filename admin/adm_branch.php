<?php
require __DIR__ . '/../login/require_login.php';
?>
<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Branch</title>
  <script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
  <link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/admin_table.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/branch_modal.css') ?>">

  <link rel="stylesheet" href="<?= app_path('/assets/vendor/datatables/datatables.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/adm_branch.css') ?>">
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
     <h3>Branch Libraries</h3>
      <p class="muted-note">Manage branch libraries.</p>
   </div>

   <div class="crud-section">
    <div class="section-header">
     <h2>Branch Library Management</h2>
     <div class="button-group">
       <button class="btn-primary" id="add-btn">+ New Branch Library</button>
       <button class="btn-primary" id="bulk-import-btn">Bulk Import</button>
     </div>
    </div>

    <!-- DYNAMIC TABS INSERTED HERE -->
    <div id="types-tabs" class="tabs-container">
      <div id="tabs" class="tabs"></div>

      <div id="tab-contents" class="tab-content">
        <!-- each tab-pane will be appended here -->
      </div>
    </div>

   </div>
  </main>

  <!-- MODALS -->
  <!-- Create / Edit Modal -->
  <div id="child-modal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <h3 id="child-modal-title">New Child Library</h3>

      <form id="child-form">
        <input type="hidden" id="child-id" value="">
        <input type="hidden" id="child-parent-id" value="<?php echo htmlspecialchars($_SESSION['library_id']); ?>">

        <div>
          <label>Type</label>
          <select id="child-type-id" required></select>
        </div>

        <div>
          <label>Name</label>
          <input type="text" id="child-name" required>
        </div>

        <div>
          <label>Address</label>
          <textarea id="child-address" rows="3"></textarea>
        </div>

        <div class="panel-spaced">
          <button type="submit" class="btn-primary">Save</button>
          <button type="button" id="child-cancel-btn" class="btn">Cancel</button>
        </div>
      </form>
    </div>
  </div>

<!-- Bulk Import Modal -->
<div id="bulk-modal" class="modal" aria-hidden="true">
  <div class="modal-content">
    <h3>Bulk Import Child Libraries</h3>

    <p>
      CSV columns required:<br>
      <code>name,address</code>
    </p>

    <form id="bulk-form">

      <!-- Parent ID -->
        <input type="hidden" id="bulk-parent-id"
               value="<?php echo htmlspecialchars($_SESSION['library_id']); ?>"
               readonly>


      <!-- STATIC TYPE SELECT -->
      <div>
        <label>Child Library Type</label>
        <select id="bulk-type-id" required>
          <option value="">-- Select Type --</option>
          <?php
            // Load library types directly from DB
            $stmt = $pdo->query("SELECT id, type_name FROM gm_library_types WHERE id > 2 ORDER BY id ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo '<option value="' . htmlspecialchars($row['id']) . '">' .
                   htmlspecialchars($row['type_name']) .
                   '</option>';
            }
          ?>
        </select>
      </div>

      <!-- CSV File -->
      <div>
        <label>CSV File</label>
        <input type="file" id="bulk-file" accept=".csv" required>
      </div>

        <div class="panel-spaced-sm">
          <a href="<?= app_path('/assets/templates/branches_bulk_template.csv') ?>"
             target="_blank"
             class="btn dark-button"
            >
             Download Sample CSV
          </a>
        </div>

      <div class="panel-spaced">
        <button type="submit" class="btn-primary">Upload</button>
        <button type="button" id="bulk-cancel-btn" class="btn">Cancel</button>
      </div>
    </form>

    <div id="bulk-results" class="panel-spaced"></div>
  </div>
</div>



  <!-- libs -->
  <script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
  <script src="<?= app_path('/assets/vendor/datatables/datatables.min.js') ?>"></script>
  <script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/pages/adm_branch.js') ?>"></script>

 </body>
</html>



