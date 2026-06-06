<?php
require __DIR__ . '/../login/require_login.php';
?>
<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Library Types</title>
  <script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
  <link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/modal_param_css.css') ?>">
<!-- keep SweetAlert + jquery (no datatables) -->
  <link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/param_library_type.css') ?>">
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
    <h3>Library Types</h3>
    <p class="muted-note">Manage library type names used across the system.</p>
   </div>

   <div class="crud-section">

    <div class="section-header section-header-inline">
     <h2>Library Types</h2>
     <div class="top-actions">
       <button class="btn-primary" id="add-btn">+ New Type</button>
     </div>
    </div>

    <div class="table-wrap">
      <table class="data-table" id="types-table">
        <thead>
          <tr>
            <th class="th-id">ID</th>
            <th>Type Name</th>
            <th class="th-actions">Actions</th>
          </tr>
        </thead>
        <tbody id="types-tbody">
          <tr class="empty-state-row"><td colspan="3" class="empty-state">Loading...</td></tr>
        </tbody>
      </table>
    </div>

   </div>
  </main>

  <!-- Add / Edit Modal (re-uses your modal styles) -->
  <div id="type-modal" class="modal" aria-hidden="true">
    <div class="modal-content wide-modal">
      <div class="modal-header">
        <h3 id="type-modal-title">New Library Type</h3>
        <button id="type-modal-close" class="btn-icon" aria-label="close">&times;</button>
      </div>

      <form id="type-form" class="modal-body modal-body-spaced">
        <input type="hidden" id="type-id" value="">
        <div class="form-row">
          <label for="type-name">Type Name</label>
          <input id="type-name" name="type_name" type="text" class="form-control" placeholder="e.g. Main Library" required>
        </div>

        <div class="form-actions">
          <button type="button" id="type-cancel-btn" class="btn btn-secondary">Cancel</button>
          <button type="submit" id="type-save-btn" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- libs -->
  <script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
  <script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>

<script src="<?= app_path('/admin/javascript/pages/param_library_type.js') ?>"></script>

 </body>
</html>



