<?php
require __DIR__ . '/../login/require_login.php';
?>
<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Target Groups</title>
  <script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
  <link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/modal_param_css.css') ?>">
<link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/param_target_group.css') ?>">
</head>

 <body>
  <div class="top-banner">
   <div class="top-banner-content">
    <h1 class="system-name">Sistem Pengurusan Program Perpustakaan</h1>
   </div>
  </div>

  <?php require __DIR__ . '/header/navbar.php';?>

  <main class="main-content page-padded">
    <div class="welcome-card section-spaced">
      <h3>Target Groups</h3>
      <p class="muted-note">Manage program target groups (Children, Teens, etc.).</p>
    </div>

    <div class="crud-section">
      <div class="section-header section-header-inline">
        <h2>Target Groups</h2>
        <div class="top-actions">
          <button class="btn-primary" id="add-btn">+ New Target Group</button>
        </div>
      </div>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th class="th-id">ID</th>
              <th>Group Name</th>
              <th class="th-type">Status</th>
              <th class="th-actions">Actions</th>
            </tr>
          </thead>
          <tbody id="group-tbody">
            <tr><td colspan="4" class="empty-state">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- MODAL -->
  <div id="group-modal" class="modal" aria-hidden="true">
    <div class="modal-content wide-modal">
      <div class="modal-header">
        <h3 id="group-modal-title">New Target Group</h3>
        <button id="group-modal-close" class="btn-icon">&times;</button>
      </div>

      <form id="group-form" class="modal-body modal-body-spaced">
        <input type="hidden" id="group-id">

        <div class="form-row">
          <label for="group-name">Group Name</label>
          <input id="group-name" class="form-control" type="text" placeholder="e.g. Children" required>
        </div>

        <div class="form-actions">
          <button type="button" id="group-cancel-btn" class="btn btn-secondary">Cancel</button>
          <button type="submit" id="group-save-btn" class="btn btn-primary">Save</button>
        </div>
      </form>

    </div>
  </div>


<!-- Libraries -->
<script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/pages/param_target_group.js') ?>"></script>

</body>
</html>



