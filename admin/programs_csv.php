<?php

require __DIR__ . '/../login/require_login.php';
require __DIR__ . '/../config/database.php';

set_time_limit(0);
ini_set('memory_limit', '512M');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="programs_export.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

function csv_safe_cell($value)
{
    if ($value === null) {
        return '';
    }

    $value = (string)$value;
    return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
}

function csv_safe_row(array $row)
{
    return array_map('csv_safe_cell', $row);
}

/* ===============================
   CSV HEADER
================================ */
fputcsv($output, [
    'Program ID',
    'Program Name',
    'Library',
    'Parent Library',
    'Library Type',
    'Program Type',
    'Scale',
    'Mode',
    'Start Date',
    'End Date',
    'Location',
    'Platform',
    'Officiate',
    'Total Participants',
    'Physical',
    'Online',
    'Status',
    'Target Groups' // LAST COLUMN
]);


$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

/* ===============================
   FILTERS
================================ */
$where  = ["p.is_deleted = 0"];
$params = [];

/* ===============================
   SESSION (SOURCE OF TRUTH)
================================ */
$role       = (int)($_SESSION['role'] ?? 0);
$libraryId  = (int)($_SESSION['library_id'] ?? 0);
$parentId   = (int)($_SESSION['library_parent_id'] ?? 0);

/* ===============================
   HARD ROLE RESTRICTIONS
================================ */

// ROLE 1 - HQ (no restriction)
if ($role === 1) {
    // full access
}

// ROLE 2 - Branch Admin
elseif ($role === 2) {

    if (!$libraryId) {
        http_response_code(403);
        exit('Invalid session');
    }

    // Own branch + its children
    $where[] = "(p.parent_library_id = ? OR p.library_id = ?)";
    $params[] = $libraryId;
    $params[] = $libraryId;
}

// ROLE 3 - Library Staff
elseif ($role === 3) {

    if (!$libraryId) {
        http_response_code(403);
        exit('Invalid session');
    }

    // ONLY own library
    $where[]  = "p.library_id = ?";
    $params[] = $libraryId;
}

// Anything else -> block
else {
    http_response_code(403);
    exit('Unauthorized');
}

/* ===============================
   ALLOWED FILTERS
================================ */
// LIBRARY FILTER - allowed for HQ & Branch Admin
if ($role !== 3 && !empty($_GET['library_id'])) {
    $where[]  = "p.library_id = ?";
    $params[] = $_GET['library_id'];
}

// DATE FILTER - allowed for ALL roles
if (!empty($_GET['date_from'])) {
    $where[]  = "p.program_start >= ?";
    $params[] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $where[]  = "p.program_end <= ?";
    $params[] = $_GET['date_to'];
}

// SEARCH - allowed ONLY for HQ & Branch Admin
if ($role !== 3 && !empty($_GET['q'])) {
    $where[]  = "p.program_name LIKE ?";
    $params[] = '%' . $_GET['q'] . '%';
}

// LIBRARY TYPE - allowed ONLY for HQ & Branch Admin
if ($role !== 3 && !empty($_GET['library_type_id'])) {
    $where[]  = "p.library_type_id = ?";
    $params[] = $_GET['library_type_id'];
}

// PARENT LIBRARY - allowed ONLY for HQ
if ($role === 1 && !empty($_GET['parent_library_id'])) {
    $where[]  = "p.parent_library_id = ?";
    $params[] = $_GET['parent_library_id'];
}

$whereSql = implode(" AND ", $where);

/* ===============================
   MAIN QUERY
================================ */
$sql = "
SELECT
    p.program_id,
    p.program_name,
    l.name  AS library_name,
    pl.name AS parent_library,
    lt.type_name AS library_type,
    pt.type_name AS program_type,
    sc.scale_name,
    p.program_mode AS mode,
    p.program_start,
    p.program_end,
    p.location,
    pf.platform_name,
    p.officiated_by,
    s.total_participant_count,
    s.physical_participant_count,
    s.online_participant_count,
    p.verification_status AS status,

    GROUP_CONCAT(tg.group_name ORDER BY tg.group_name SEPARATOR ', ') AS target_groups

FROM gm_programs p

LEFT JOIN gm_program_participant_stats s ON s.program_id = p.program_id
LEFT JOIN gm_libraries l ON l.id = p.library_id
LEFT JOIN gm_libraries pl ON pl.id = p.parent_library_id
LEFT JOIN gm_library_types lt ON lt.id = p.library_type_id
LEFT JOIN gm_program_types pt ON pt.id = p.program_type_id
LEFT JOIN gm_scales sc ON sc.id = p.scale_id
LEFT JOIN gm_platforms pf ON pf.id = p.platform_id

LEFT JOIN gm_program_target_groups ptg ON ptg.program_id = p.program_id
LEFT JOIN gm_target_groups tg ON tg.id = ptg.target_group_id

WHERE $whereSql
GROUP BY p.program_id
ORDER BY p.program_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

/* ===============================
   STREAM ROWS
================================ */
$count = 0;

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

    fputcsv($output, csv_safe_row([
        $r['program_id'],
        $r['program_name'],
        $r['library_name'],
        $r['parent_library'],
        $r['library_type'],
        $r['program_type'],
        $r['scale_name'],
        $r['mode'],
        $r['program_start'],
        $r['program_end'],
        $r['location'],
        $r['platform_name'],
        $r['officiated_by'],
        $r['total_participant_count'],
        $r['physical_participant_count'],
        $r['online_participant_count'],
        $r['status'],
        $r['target_groups'] // MULTI -> LAST COLUMN
    ]));

    if (++$count % 500 === 0) {
        fflush($output);
    }
}

fclose($output);
exit;
?>


