<?php
require __DIR__ . '/../login/require_login.php';

?>
<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Libraries</title>
  <script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
  <link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/modal_param_css.css') ?>">
<link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/adm_libraries.css') ?>">
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
      <h3>Libraries</h3>
      <p class="muted-note">Manage parent libraries.</p>
    </div>

    <div class="crud-section">
      <div class="section-header section-header-inline">
        <h2>Libraries</h2>
        <div class="top-actions">
          <button class="btn-primary" id="add-btn">+ New Library</button>
        </div>
      </div>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th class="th-id-sm">ID</th>
              <th>Name</th>
              <th class="th-type">Type</th>
              <th class="th-address">Address</th>
              <th class="th-actions">Status</th>
              <th class="th-actions-sm">Actions</th>
            </tr>
          </thead>
          <tbody id="library-tbody">
            <tr><td colspan="6" class="empty-state">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- MODAL -->
  <div id="library-modal" class="modal" aria-hidden="true">
    <div class="modal-content wide-modal">
      <div class="modal-header">
        <h3 id="library-modal-title">New Library</h3>
        <button id="library-modal-close" class="btn-icon">&times;</button>
      </div>

      <form id="library-form" class="modal-body modal-body-spaced">
        <input type="hidden" id="library-id" value="">

        <div class="form-row form-row-spaced">
          <label for="library-name">Name</label>
          <input id="library-name" type="text" class="form-control" placeholder="e.g. Melaka State Library" required>
        </div>

        <div class="form-row form-row-spaced">
          <label for="library-type">Type</label>
          <select id="library-type" class="form-control" required>
            <option value="">Loading types...</option>
          </select>
        </div>

        <div class="form-row form-row-spaced">
          <label for="library-address">Address</label>
          <input id="library-address" type="text" class="form-control" placeholder="e.g. Bandar Hilir" required>
        </div>

        <div class="form-actions">
          <button type="button" id="library-cancel-btn" class="btn btn-secondary">Cancel</button>
          <button type="submit" id="library-save-btn" class="btn btn-primary">Save</button>
        </div>
      </form>

    </div>
  </div>

<!-- Libraries -->
<script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>

<script src="<?= app_path('/admin/javascript/pages/adm_libraries.js') ?>"></script>

</body>
</html>



