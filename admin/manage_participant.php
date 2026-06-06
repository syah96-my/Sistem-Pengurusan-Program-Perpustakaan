
<?php
// manage_participant.php
require __DIR__ . '/../login/require_login.php';


/* -------------------------
   Resolve hashed token (SHA256 of program_id)
   ------------------------- */
$token = $_GET['p'] ?? '';

if (!$token) {
    http_response_code(400);
    echo "Invalid access (missing token).";
    exit;
}

// Load all program ids (small cost; if huge, we can optimize later)
$stmt = $pdo->query("SELECT program_id FROM gm_programs");
$allPrograms = $stmt->fetchAll(PDO::FETCH_COLUMN);

$program_id = null;
foreach ($allPrograms as $pid) {
    if (hash('sha256', (string)$pid) === $token) {
        $program_id = (int)$pid;
        break;
    }
}

if (!$program_id) {
    http_response_code(404);
    echo "Invalid or expired link.";
    exit;
}

/* -------------------------
   Fetch program header info
   ------------------------- */
$stmt = $pdo->prepare("
    SELECT program_name, program_start, program_end, program_mode AS mode, location, platform_id
    FROM gm_programs
    WHERE program_id = ?
    LIMIT 1
");
$stmt->execute([$program_id]);
$program = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$program) {
    http_response_code(404);
    echo "Program not found.";
    exit;
}

/* platform label */
$platformName = null;
if (!empty($program['platform_id'])) {
    $pstm = $pdo->prepare("SELECT platform_name FROM gm_platforms WHERE id = ? LIMIT 1");
    $pstm->execute([$program['platform_id']]);
    $prow = $pstm->fetch(PDO::FETCH_ASSOC);
    $platformName = $prow['platform_name'] ?? null;
}

/* -------------------------
   Delete participant (POST)
   ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (empty($_POST['csrf_token']) || !hash_equals(gm_csrf_token(), (string)$_POST['csrf_token'])) {
        http_response_code(403);
        echo "Invalid security token.";
        exit;
    }

    $deleteId = intval($_POST['delete_id']);

    // small validation: ensure participant belongs to this program
    $chk = $pdo->prepare("SELECT participant_id FROM gm_participants WHERE participant_id = ? AND program_id = ? LIMIT 1");
    $chk->execute([$deleteId, $program_id]);
    if ($chk->fetchColumn()) {
        $del = $pdo->prepare("DELETE FROM gm_participants WHERE participant_id = ? AND program_id = ?");
        $del->execute([$deleteId, $program_id]);

        // update stats
        updateStats($pdo, $program_id);
    }

    // redirect back to avoid repost
    header("Location: manage_participant.php?p=" . urlencode($token) . "&page=" . (int)($_GET['page'] ?? 1));
    exit;
}

/* -------------------------
   Pagination / Fetch participants
   ------------------------- */
$limit = 100;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* total rows */
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM gm_participants WHERE program_id = ?");
$totalStmt->execute([$program_id]);
$totalRows = (int)$totalStmt->fetchColumn();

/* fetch rows for this page */
$stmt = $pdo->prepare("
    SELECT participant_id, participant_name AS name, gender, position_title AS occupation, organization_name AS company,
           attendance_mode, attendance_time, registration_source
    FROM gm_participants
    WHERE program_id = ?
    ORDER BY participant_id ASC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $program_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------
   updateStats function
   ------------------------- */
function updateStats($pdo, $program_id)
{
    $stmt = $pdo->prepare("
        REPLACE INTO gm_program_participant_stats (
            program_id,
            total_participant_count,
            male_participant_count,
            female_participant_count,
            average_age,
            physical_participant_count,
            online_participant_count,
            self_registered_participant_count,
            staff_uploaded_participant_count
        )
        SELECT 
            program_id,
            COUNT(*),
            SUM(gender = 'male'),
            SUM(gender = 'female'),
            AVG(age),
            SUM(attendance_mode = 'physical'),
            SUM(attendance_mode = 'online'),
            SUM(registration_source = 'self'),
            SUM(registration_source = 'staff_upload')
        FROM gm_participants
        WHERE program_id = ?
    ");
    $stmt->execute([$program_id]);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Participants - <?= htmlspecialchars($program['program_name']) ?></title>
<script src="<?= app_path('/admin/javascript/pages/manage_participant.js') ?>"></script>
  <link rel="stylesheet" href="<?= app_path('/admin/css/pages/manage_participant.css') ?>">
</head>
<body>
<div class="container">
    <h1>Participants - <?= htmlspecialchars($program['program_name']) ?></h1>

    <div class="program-box">
        <div><strong><?= htmlspecialchars($program['program_name']) ?></strong></div>
        <div class="program-meta">
            <?= htmlspecialchars($program['program_start']) ?> -> <?= htmlspecialchars($program['program_end']) ?>
            <?php if ($program['mode'] !== 'online'): ?>
                &nbsp; | &nbsp; Location: <?= htmlspecialchars($program['location']) ?>
            <?php endif; ?>
            <?php if ($program['mode'] !== 'physical' && $platformName): ?>
                &nbsp; | &nbsp; Platform: <?= htmlspecialchars($platformName) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="controls">
        <input id="searchInput" class="search" placeholder="Search name / company / occupation / mode..." oninput="searchTable()" />
        <div class="align-right-muted">
            Showing <?= count($participants) ?> of <?= $totalRows ?> participants
        </div>
    </div>

    <div class="table-card">
        <table id="participantTable">
            <thead>
                <tr>
                    <th class="th-name">Name</th>
                    <th class="th-id">Gender</th>
                    <th class="th-occupation">Occupation</th>
                    <th class="th-occupation">Company</th>
                    <th class="th-id">Mode</th>
                    <th class="th-attendance-time">Attendance Time</th>
                    <th class="th-id">Registered By</th>
                    <th class="th-id-sm">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($participants)): ?>
                    <tr><td colspan="8" class="empty-table-cell">No participants found on this page.</td></tr>
                <?php else: ?>
                    <?php foreach ($participants as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['gender']) ?></td>
                            <td><?= htmlspecialchars($p['occupation']) ?></td>
                            <td><?= htmlspecialchars($p['company']) ?></td>
                            <td><?= htmlspecialchars($p['attendance_mode']) ?></td>
                            <td><?= htmlspecialchars($p['attendance_time']) ?></td>
                            <td><?= htmlspecialchars($p['registration_source']) ?></td>
                            <td>
                                <form method="post" class="js-confirm-delete">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="delete_id" value="<?= (int)$p['participant_id'] ?>">
                                    <button class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pager" aria-label="pagination">
        <?php
            $totalPages = max(1, (int)ceil($totalRows / $limit));
            $show = 7; // how many page links to show
            $start = max(1, $page - intval($show/2));
            $end = min($totalPages, $start + $show - 1);
            if ($end - $start < $show - 1) { $start = max(1, $end - $show + 1); }
        ?>
        <?php if ($page > 1): ?>
            <a href="?p=<?= urlencode($token) ?>&page=<?= $page-1 ?>">Prev</a>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="?p=<?= urlencode($token) ?>&page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?p=<?= urlencode($token) ?>&page=<?= $page+1 ?>">Next</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>



