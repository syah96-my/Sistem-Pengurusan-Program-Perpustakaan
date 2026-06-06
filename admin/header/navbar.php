<?php
/**
 * UNIFIED HEADER
 * - Library = base
 * - Admin (HQ) via role gates
 * - Requires: login/require_login.php
 */

// Safety (should already exist, but keep harmless)
$libraryId   = (int)($_SESSION['library_id'] ?? 0);
$libraryType = (int)($_SESSION['library_type_id'] ?? 99);

$isHQ       = ($libraryId === 1);        // Super admin
$isVerifier = ($libraryType <= 2);       // Can verify programs

/* ============================================================
   LOAD PROGRAM TYPES
============================================================ */
$stmt = $pdo->prepare("
    SELECT id, type_name
    FROM gm_program_types
    WHERE enabled = 1
    ORDER BY id ASC
");
$stmt->execute();
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Move type_id = 1 to bottom */
$id1 = null;
$ordered = [];

foreach ($types as $t) {
    if ((int)$t['id'] === 1) {
        $id1 = $t;
    } else {
        $ordered[] = $t;
    }
}
if ($id1) $ordered[] = $id1;

/* ============================================================
   COUNTS (OPTIMIZED)
============================================================ */

/* INCOMPLETE (library) */
$incompleteCounts = [];
$rejectedCounts   = [];
$pendingCounts    = [];

if ($libraryId) {

    // Incomplete
    $stmt = $pdo->prepare("
        SELECT program_type_id, COUNT(*) total
        FROM gm_programs
        WHERE verification_status = 'incomplete'
          AND is_deleted = 0
          AND library_id = ?
        GROUP BY program_type_id
    ");
    $stmt->execute([$libraryId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $incompleteCounts[$r['program_type_id']] = $r['total'];
    }

    // Rejected
    $stmt = $pdo->prepare("
        SELECT program_type_id, COUNT(*) total
        FROM gm_programs
        WHERE verification_status = 'rejected'
          AND is_deleted = 0
          AND library_id = ?
        GROUP BY program_type_id
    ");
    $stmt->execute([$libraryId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $rejectedCounts[$r['program_type_id']] = $r['total'];
    }

    // Pending (verification)
    if ($libraryId === 1) {
        // HQ: parent_library_id IS NULL OR = 1
        $stmt = $pdo->prepare("
            SELECT program_type_id, COUNT(*) total
            FROM gm_programs
            WHERE verification_status = 'pending'
              AND is_deleted = 0
              AND (parent_library_id IS NULL OR parent_library_id = 1)
            GROUP BY program_type_id
        ");
        $stmt->execute();
    } else {
        // Normal library: only its own parent
        $stmt = $pdo->prepare("
            SELECT program_type_id, COUNT(*) total
            FROM gm_programs
            WHERE verification_status = 'pending'
              AND is_deleted = 0
              AND parent_library_id = ?
            GROUP BY program_type_id
        ");
        $stmt->execute([$libraryId]);
    }

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $pendingCounts[$r['program_type_id']] = $r['total'];
    }

}

$incompleteTotal = array_sum($incompleteCounts);
$rejectedTotal   = array_sum($rejectedCounts);
$pendingTotal = array_sum($pendingCounts);

?>

<header class="header">
  <div class="header-content">

    <nav class="menu-container">

      <!-- DASHBOARD -->
      <div class="menu-item">
        <button class="menu-button" data-menu="dashboard">
          Dashboard <div class="dropdown-icon"></div>
        </button>
        <div class="dropdown-menu" data-dropdown="dashboard">
          <a class="dropdown-item" href="index.php">Overview</a>
        </div>
      </div>

      <!-- REPORTS -->
      <div class="menu-item">
        <button class="menu-button" data-menu="reports">
          Reports <div class="dropdown-icon"></div>
        </button>
        <div class="dropdown-menu" data-dropdown="reports">
          <a class="dropdown-item" href="stat_status.php">Verification Status</a>
          <a class="dropdown-item" href="stat_program_type.php">Program Type</a>
          <a class="dropdown-item" href="stat_scale.php">Program Scale</a>
          <a class="dropdown-item" href="stat_program_mod.php">Program Mode</a>
          <a class="dropdown-item" href="stat_program_target.php">Program Target</a>
          <a class="dropdown-item" href="stat_participant_analytics.php">Participant Analysis</a>
          <a class="dropdown-item" href="stat_program.php">Program List</a>
          <a class="dropdown-item" href="stat_download.php">Download Program</a>
        </div>
      </div>

      <!-- PROGRAMS -->
      <div class="menu-item">
        <button class="menu-button" data-menu="programs">
          Programs
          <?php if ($incompleteTotal > 0): ?>
            <span class="badge badge-warn"><?= $incompleteTotal ?></span>
          <?php endif; ?>
          <?php if ($rejectedTotal > 0): ?>
            <span class="badge badge-danger"><?= $rejectedTotal ?></span>
          <?php endif; ?>
          <div class="dropdown-icon"></div>
        </button>

        <div class="dropdown-menu" data-dropdown="programs">
          <?php foreach ($ordered as $type): ?>
            <?php
              $token      = gm_encrypt($type['id']);
              $inc        = $incompleteCounts[$type['id']] ?? 0;
              $rej        = $rejectedCounts[$type['id']] ?? 0;
            ?>
            <a class="dropdown-item" href="program_input.php?t=<?= urlencode($token) ?>">
              <?= htmlspecialchars($type['type_name']) ?>
              <?php if ($inc > 0): ?><span class="badge badge-warn"><?= $inc ?></span><?php endif; ?>
              <?php if ($rej > 0): ?><span class="badge badge-danger"><?= $rej ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>

          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="program_socmed.php">Social Media</a>
        </div>
      </div>

      <!-- VERIFICATION -->
      <?php if ($isVerifier): ?>
      <div class="menu-item">
        <button class="menu-button" data-menu="verification">
          Verification
          <?php if ($pendingTotal > 0): ?>
            <span class="badge badge-danger"><?= $pendingTotal ?></span>
          <?php endif; ?>
          <div class="dropdown-icon"></div>
        </button>
        <div class="dropdown-menu" data-dropdown="verification">
          <?php foreach ($ordered as $type): ?>
            <?php
              $token = gm_encrypt($type['id']);
              $cnt   = $pendingCounts[$type['id']] ?? 0;
            ?>
            <a class="dropdown-item" href="program_verify.php?t=<?= urlencode($token) ?>">
              <?= htmlspecialchars($type['type_name']) ?>
              <?php if ($cnt > 0): ?>
                  <span class="badge badge-danger"><?= $cnt ?></span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php
      // allow both HQ + Verifier to see the Admin menu
      $canAdminMenu = ($isHQ || $isVerifier);
      ?>

      <!-- ADMIN (HQ + VERIFIER) -->
      <?php if ($canAdminMenu): ?>
      <div class="menu-item">
        <button class="menu-button" data-menu="admin">
          Administrator <div class="dropdown-icon"></div>
        </button>

        <div class="dropdown-menu" data-dropdown="admin">

          <!-- HQ ONLY -->
          <?php if ($isHQ): ?>
            <a class="dropdown-item" href="adm_libraries.php">Libraries</a>
          <?php endif; ?>

          <!-- Shared -->
          <a class="dropdown-item" href="adm_branch.php">Branch Management</a>
          <a class="dropdown-item" href="adm_users.php">User Management</a>

        </div>
      </div>
      <?php endif; ?>

      <!-- PARAMETER (HQ ONLY) -->
      <?php if ($isHQ): ?>
      <div class="menu-item">
        <button class="menu-button" data-menu="parameter">
          Parameter <div class="dropdown-icon"></div>
        </button>

        <div class="dropdown-menu" data-dropdown="parameter">
          <a class="dropdown-item" href="param_library_type.php">Library Type</a>
          <a class="dropdown-item" href="param_program_types.php">Program Types</a>
          <a class="dropdown-item" href="param_platform.php">Platform List</a>
          <a class="dropdown-item" href="param_scales.php">Scale List</a>
          <a class="dropdown-item" href="param_target_group.php">Target Group</a>
        </div>
      </div>
      <?php endif; ?>


    </nav>

    <!-- USER MENU -->
    <div class="user-menu">
      <button class="user-button" data-menu="user">
        <?= htmlspecialchars($_SESSION['username']) ?>
        <div class="dropdown-icon"></div>
      </button>
      <div class="dropdown-menu" data-dropdown="user">
        <a class="dropdown-item" href="<?= app_path('/login/logout.php') ?>">Log Out</a>
      </div>
    </div>

  </div>
</header>
