<?php
require_once __DIR__ . '/../config/bootstrap.php';
header("Content-Type: application/json");
/* ============================================================
   VALIDATE INPUT
============================================================ */
$program_id = $_POST["program_id"] ?? null;
$public_token = $_POST["public_token"] ?? null;
$name       = trim($_POST["nama"] ?? "");
$gender     = $_POST["jantina"] ?? null;
$age     = $_POST["age"] ?? null;
$email      = trim($_POST["email"] ?? "");
$phone      = trim($_POST["phone"] ?? "");
$occupation = trim($_POST["jawatan"] ?? "");
$company    = trim($_POST["jabatan"] ?? "");
$mode       = $_POST["kehadiran"] ?? null;

if (!$program_id || !$public_token || strlen($name) < 2 || !$email) {
    echo json_encode(["isOk" => false, "error" => "Maklumat tidak lengkap"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["isOk" => false, "error" => "Email tidak sah"]);
    exit;
}


/* ============================================================
   INSERT INTO gm_participants
============================================================ */
/* ============================================================
   VALIDATE attendance_mode AGAINST program.mode
============================================================ */

$pm = $pdo->prepare("
    SELECT program_mode, program_start, program_end
    FROM gm_programs
    WHERE program_id = ?
      AND public_token = ?
      AND is_deleted = 0
    LIMIT 1
");
$pm->execute([$program_id, $public_token]);
$programRow = $pm->fetch(PDO::FETCH_ASSOC);

if (!$programRow) {
    echo json_encode(["isOk" => false, "error" => "Program tidak sah"]);
    exit;
}

$today = date("Y-m-d");
$start = substr($programRow["program_start"], 0, 10);
$end = substr($programRow["program_end"], 0, 10);
if ($today < $start || $today > $end) {
    echo json_encode(["isOk" => false, "error" => "Pendaftaran tidak dibuka"]);
    exit;
}

$programMode = $programRow["program_mode"];

if ($programMode === "physical" && $mode !== "physical") {
    echo json_encode(["isOk" => false, "error" => "Program ini fizikal sahaja"]);
    exit;
}

if ($programMode === "online" && $mode !== "online") {
    echo json_encode(["isOk" => false, "error" => "Program ini atas talian sahaja"]);
    exit;
}

if ($programMode === "hybrid" && !in_array($mode, ["physical","online"])) {
    echo json_encode(["isOk" => false, "error" => "Mod kehadiran tidak sah"]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO gm_participants 
    (program_id, participant_name, gender, age, email, phone_number, position_title, organization_name, attendance_mode, attendance_time, registration_source)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'self')
");

try {
    $stmt->execute([
        $program_id,
        $name,
        $gender,
        $age,
        $email,
        $phone,
        $occupation,
        $company,
        $mode
    ]);
/* ============================================================
   SYNC PARTICIPANT STATS (RESPECT MANUAL OVERRIDE)
============================================================ */

// 1. Check manual override flag
$chk = $pdo->prepare("
    SELECT is_manual_override
    FROM gm_program_participant_stats
    WHERE program_id = ?
    LIMIT 1
");
$chk->execute([$program_id]);
$manual = (int)($chk->fetchColumn() ?? 0);

// 2. Only recalc if NOT manual override
if ($manual !== 1) {

    // Recalculate stats from gm_participants
    $pdo->prepare("
        INSERT INTO gm_program_participant_stats (
            program_id,
            total_participant_count,
            male_participant_count,
            female_participant_count,
            average_age,
            physical_participant_count,
            online_participant_count,
            self_registered_participant_count,
            staff_uploaded_participant_count,
            is_manual_override
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
            SUM(registration_source = 'staff_upload'),
            0
        FROM gm_participants
        WHERE program_id = ?
        GROUP BY program_id
        ON DUPLICATE KEY UPDATE
            total_participant_count = VALUES(total_participant_count),
            male_participant_count = VALUES(male_participant_count),
            female_participant_count = VALUES(female_participant_count),
            average_age = VALUES(average_age),
            physical_participant_count = VALUES(physical_participant_count),
            online_participant_count = VALUES(online_participant_count),
            self_registered_participant_count = VALUES(self_registered_participant_count),
            staff_uploaded_participant_count = VALUES(staff_uploaded_participant_count)
    ")->execute([$program_id]);
}


} catch (PDOException $e) {

    // Duplicate email for same program
    if ($e->getCode() == 23000) {
        echo json_encode([
            "isOk" => false,
            "error" => "Email ini sudah didaftarkan."
        ]);
        exit;
    }

    echo json_encode(["isOk" => false, "error" => "Gagal menyimpan data"]);
    exit;
}


/* ============================================================
   SAVE COOKIE (Auto-fill Until Dec 31 Current Year)
============================================================ */

$cookieName = "participant_" . $program_id;
$expiry = strtotime(date("Y") . "-12-31 23:59:59");

$data = [
    "name"       => $name,
    "gender"     => $gender,
    "age"     => $age,
    "email"      => $email,
    "phone"      => $phone,
    "occupation" => $occupation,
    "company"    => $company
];

if (PHP_VERSION_ID >= 70300) {
    setcookie($cookieName, json_encode($data), [
        "expires" => $expiry,
        "path" => "/",
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax",
    ]);
} else {
    setcookie($cookieName, json_encode($data), $expiry, "/; samesite=Lax", "", !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off", true);
}


/* ============================================================
   SUCCESS RESPONSE
============================================================ */
echo json_encode(["isOk" => true]);
exit;
?>
