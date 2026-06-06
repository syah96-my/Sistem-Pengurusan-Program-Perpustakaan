<?php
require __DIR__ . '/../login/require_login.php';
?>
<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Platforms</title>
  <script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
  <link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/modal_param_css.css') ?>">
<link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/param_platform.css') ?>">
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
    <h3>Platforms</h3>
    <p class="muted-note">Manage online platforms used by programs (Facebook, Zoom, etc.).</p>
   </div>

   <div class="crud-section">
    <div class="section-header section-header-inline">
     <h2>Platforms</h2>
     <div class="top-actions">
       <button class="btn-primary" id="add-btn">+ New Platform</button>
     </div>
    </div>

    <div class="table-wrap">
      <table class="data-table" id="platforms-table">
        <thead>
          <tr>
            <th class="th-id">ID</th>
            <th>Platform Name</th>
            <th class="th-type">Status</th>
            <th class="th-actions">Actions</th>
          </tr>
        </thead>
        <tbody id="platforms-tbody">
          <tr class="empty-state-row"><td colspan="4" class="empty-state">Loading...</td></tr>
        </tbody>
      </table>
    </div>

   </div>
  </main>

  <!-- Add / Edit Modal -->
  <div id="platform-modal" class="modal" aria-hidden="true">
    <div class="modal-content wide-modal">
      <div class="modal-header">
        <h3 id="platform-modal-title">New Platform</h3>
        <button id="platform-modal-close" class="btn-icon" aria-label="close">&times;</button>
      </div>

      <form id="platform-form" class="modal-body modal-body-spaced">
        <input type="hidden" id="platform-id" value="">
        <div class="form-row">
          <label for="platform-name">Platform Name</label>
          <input id="platform-name" name="platform_name" type="text" class="form-control" placeholder="e.g. Zoom" required>
        </div>

        <div class="form-actions">
          <button type="button" id="platform-cancel-btn" class="btn btn-secondary">Cancel</button>
          <button type="submit" id="platform-save-btn" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- libs -->
  <script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
  <script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
  <script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/pages/param_platform.js') ?>"></script>
 </body>
</html>



