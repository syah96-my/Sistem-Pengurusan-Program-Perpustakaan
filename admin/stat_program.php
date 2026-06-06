<?php
require __DIR__ . '/../login/require_login.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Program Search</title>
    <script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
    <link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/program_table_css.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/program_modal_css.css') ?>">
    <link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">

  <link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/assets/vendor/datatables/datatables.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/stat_program.css') ?>">
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
  <h3>Global Program Search</h3>
  <p class="muted-note">Search across all verified programs.</p>
</div>

<!-- FILTERS -->
<div class="filter-row">
  <select id="filter-parent"><option value="">Parent Library</option></select>
  <select id="filter-type"><option value="">Library Type</option></select>

  <input type="date" id="date-from">
  <input type="date" id="date-to">

  <input id="search-box" type="text" placeholder="Search program name..." class="search-input-wide">
  <button id="btn-refresh" class="btn btn-primary">Refresh</button>
</div>

<!-- SUMMARY BADGE -->
<div class="badge-box">
  <div class="badge-item" id="badge-total">Total Results: 0</div>
</div>

<div id="loadingBox">Loading...</div>

<!-- TABLE -->
<table id="searchTable" class="display nowrap table-full">
<thead>
<tr>
  <th>Program Name</th>
  <th>Library</th>
  <th>Start Date</th>
  <th>Mode</th>
  <th>Status</th>
</tr>
</thead>
<tbody id="table-body"></tbody>
</table>

<!-- ===========================
     PURE CUSTOM MODAL
=========================== -->
<div id="programModal" class="modal">
  <div class="modal-content wide-modal">

    <!-- HEADER -->
    <div class="modal-header">
      <h2>Program Details</h2>
      <button class="modal-close js-close-program-modal">&times;</button>
    </div>

    <!-- BODY -->
    <div class="verify-body">

      <!-- TITLE -->
      <div class="verify-title">
        <h2 id="v-program-name"></h2>
        <h3 id="v-library-name"></h3>

        <div class="verify-meta">
          <div class="hidden">
            <span><strong>ID:</strong> <span id="v-program-id">None</span></span>
          </div>
          <span><strong>Library Type:</strong> <span id="v-library-type">None</span></span>
          <span><strong>Main Library:</strong> <span id="v-parent-library">None</span></span>
        </div>
      </div>

      <br>

      <!-- TWO COLUMN LAYOUT -->
      <div class="verify-layout">

        <!-- LEFT : PROGRAM DETAILS -->
        <section class="verify-panel">
          <h3>Program Details</h3>

          <div class="detail-row"><label>Scale</label><span id="v-scale">None</span></div>
          <div class="detail-row"><label>Mode</label><span id="v-mode">None</span></div>
          <div class="detail-row"><label>Start</label><span id="v-start"></span></div>
          <div class="detail-row"><label>End</label><span id="v-end"></span></div>
          <div class="detail-row"><label>Location</label><span id="v-location">None</span></div>
          <div class="detail-row"><label>Platform</label><span id="v-platform">None</span></div>
          <div class="detail-row"><label>Officiate</label><span id="v-officiated_by"></span></div>
          <div class="detail-row"><label>Image URL</label><span id="v-image"></span></div>
          <div class="detail-row"><label>Supp. Documents</label><span id="v-documents"></span></div>

          <div class="detail-row full">
            <label>Target Groups</label>
            <span id="v-target-groups"></span>
          </div>

          <div class="detail-row full">
            <label>Description</label>
            <div id="v-details" class="detail-box"></div>
          </div>
        </section>

        <!-- RIGHT : STACK -->
        <div class="verify-side">

          <!-- PARTICIPANTS -->
          <section class="verify-panel">
            <h3>Participants</h3>

            <div class="participant-status">
              <span id="v-participant-status" class="participant-tag"></span>
            </div>

            <div class="participant-grid">
              <div><label>Physical</label><span id="v-p-physical">0</span></div>
              <div><label>Online</label><span id="v-p-online">0</span></div>
              <div class="total"><label>Total</label><span id="v-p-total">0</span></div>
            </div>
          </section>

          <!-- NOTES -->
          <section class="verify-panel">
            <h3>Notes</h3>
            <div id="v-notes-list" class="notes-box">
              <div class="note-item empty">No notes</div>
            </div>
          </section>

        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="modal-footer verify-footer">
      <button class="btn-secondary js-close-program-modal">Close</button>
    </div>

  </div>
</div>



</main>

<script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= app_path('/assets/vendor/datatables/datatables.min.js') ?>"></script>
<script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/pages/stat_program.js') ?>"></script>


</body>
</html>



