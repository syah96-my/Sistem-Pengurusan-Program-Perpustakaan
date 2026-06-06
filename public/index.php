<?php

// ? UNIVERSAL BOOTSTRAP (DB, timezone, session if needed)
require_once __DIR__ . '/../config/bootstrap.php';

$public_token = $_GET["i"] ?? null;
if (!$public_token) {
    die("Invalid link.");
}

/* ============================================
   LOAD PROGRAM
============================================ */
$stmt = $pdo->prepare("
    SELECT *
    FROM gm_programs
    WHERE public_token = ? AND is_deleted = 0
    LIMIT 1
");
$stmt->execute([$public_token]);
$program = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$program) {
    die("Program not found.");
}

$today = date("Y-m-d");

$start = substr($program["program_start"], 0, 10);
$end   = substr($program["program_end"], 0, 10);

/* ============================================
   AJAX STATUS CHECK
============================================ */
if (isset($_GET["check"])) {

    if ($today < $start) {
        echo json_encode(["status" => "not_started"]);
        exit;
    }

    if ($today > $end) {
        echo json_encode(["status" => "ended"]);
        exit;
    }

    echo json_encode(["status" => "open"]);
    exit;
}

/* ============================================
   PAGE LOGIC
============================================ */

// NOT STARTED
if ($today < $start) {
    $PROGRAM = $program;
    $TOKEN   = $public_token;  // used by JS polling
    include __DIR__ . "/pages/not_started.php";
    exit;
}

// ENDED
if ($today > $end) {
    $PROGRAM = $program;
    $TOKEN   = $public_token;
    include __DIR__ . "/pages/expired.php";
    exit;
}

/* ============================================
   REGISTRATION OPEN ? REDIRECT
============================================ */
header("Location: register.php?i=" . urlencode($public_token));
exit;
?>