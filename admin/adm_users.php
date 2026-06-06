<?php
require __DIR__ . '/../login/require_login.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Account Management</title>
<script src="<?= app_path('/admin/javascript/global-context.php') ?>"></script>
<link rel="stylesheet" href="<?= app_path('/admin/css/global_css.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/components.css') ?>">
<link rel="stylesheet" href="<?= app_path('/admin/css/param_css.css') ?>">
<link rel="stylesheet" href="<?= app_path('/admin/css/admin_table.css') ?>">
<link rel="stylesheet" href="<?= app_path('/admin/css/account_css.css') ?>">


<link rel="stylesheet" href="<?= app_path('/assets/vendor/datatables/datatables.min.css') ?>">
<link rel="stylesheet" href="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/adm_users.css') ?>">
</head>

<body>

<div class="top-banner">
    <div class="top-banner-content">
        <h1 class="system-name">Sistem Pengurusan Program Perpustakaan</h1>
    </div>
</div>

<?php require __DIR__ . '/header/navbar.php';?>

<main class="main-content">

    <div class="welcome-card">
        <h3>User Accounts</h3>
    </div>

    <div class="crud-section">
        <div class="section-header">
            <h2>User Account Management</h2>
            <div class="button-group">
                <button id="add-btn" class="btn-primary">+ New User</button>
                <button id="bulk-import-btn" class="btn-primary">Bulk Import</button>
            </div>
        </div>

        <div id="types-tabs" class="tabs-container">
            <div id="tabs" class="tabs"></div>
            <div id="tab-contents" class="tab-content"></div>
        </div>
    </div>

</main>

<!-- USER MODAL -->
<div id="user-modal" class="modal">
    <div class="modal-content">
        <h3 id="user-modal-title">New User</h3>

        <form id="user-form">
            <input type="hidden" id="user-id">

        <!-- FILTER: Parent -->
        <div>
            <label>Filter by Parent Library</label>
            <select id="filter-parent">
                <option value="">All Parents</option>
                <?php
                // Load ONLY top-level libraries (parent_id is NULL)
                $stmt = $pdo->query("SELECT id, name FROM gm_libraries WHERE parent_id IS NULL ORDER BY name ASC");
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="'.$r['id'].'">'.$r['name'].'</option>';
                }
                ?>
            </select>
        </div>
        
        <!-- FILTER: Library Type -->
        <div>
            <label>Filter by Library Type</label>
            <select id="filter-type">
                <option value="" selected>All Types</option>
                <?php
                $isHQ = ($_SESSION['user_id'] ?? 0) == 1;
                
                $sql = "
                    SELECT id, type_name
                    FROM gm_library_types
                    WHERE " . ($isHQ ? "id >= 2" : "id > 2") . "
                    ORDER BY id ASC
                ";
                
                $stmt = $pdo->query($sql);
                
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="'.$r['id'].'">'.$r['type_name'].'</option>';
                }

                ?>
            </select>
        </div>
        
        <!-- FILTERED LIBRARY LIST -->
        <div>
            <label>Library</label>
            <select id="user-library-id" required>
                <option value="">Please Select</option>
            </select>
        </div>


            <div>
                <label>Role</label>
                <select id="user-role-id" required>
                    <?php
                    $stmt = $pdo->query("SELECT id, role_name FROM gm_roles ORDER BY id ASC");
                    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="'.$r['id'].'">'.$r['role_name'].'</option>';
                    }
                    ?>
                </select>
            </div>

            <div>
                <label>Username (Email)</label>
                <input type="email" id="user-username" required>
            </div>

            <div class="panel-spaced">
                <button type="submit" class="btn-primary">Save</button>
                <button type="button" id="user-cancel-btn" class="btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- BULK IMPORT -->
<div id="bulk-modal" class="modal">
    <div class="modal-content">
        <h3>Bulk Import Users</h3>

        <p>CSV columns:<br><code>username,library_id,role_id,status(optional)</code></p>

        <form id="bulk-form">
            <input type="file" id="bulk-file" accept=".csv" required>
                        <div class="panel-spaced-sm">
                <a href="<?= app_path('/assets/templates/users_bulk_template.csv') ?>"
                   target="_blank"
                   class="btn warning-button"
                  >
                    Download Template CSV
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

<script src="<?= app_path('/assets/js/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= app_path('/assets/vendor/datatables/datatables.min.js') ?>"></script>
<script src="<?= app_path('/assets/vendor/sweetalert2/sweetalert2.min.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/common/menu.js') ?>"></script>
<script src="<?= app_path('/admin/javascript/pages/adm_users.js') ?>"></script>

</body>
</html>



