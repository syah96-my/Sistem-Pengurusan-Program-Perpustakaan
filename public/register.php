<?php
require_once __DIR__ . '/../config/bootstrap.php';

$public_token = $_GET['i'] ?? null;
if (!$public_token) {
    die('Invalid link.');
}

$stmt = $pdo->prepare('SELECT * FROM gm_programs WHERE public_token = ? AND is_deleted = 0 LIMIT 1');
$stmt->execute([$public_token]);
$program = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$program) {
    die('Program not found.');
}

$programId = (int)$program['program_id'];
$mode = $program['program_mode'];

$today = date('Y-m-d');
$start = substr($program['program_start'], 0, 10);
$end = substr($program['program_end'], 0, 10);
if ($today < $start) {
    header('Location: index.php?i=' . urlencode($public_token));
    exit;
}
if ($today > $end) {
    header('Location: index.php?i=' . urlencode($public_token));
    exit;
}
$cookieName = 'participant_' . $programId;
$auto = [];

if (!empty($_COOKIE[$cookieName])) {
    $decoded = json_decode($_COOKIE[$cookieName], true);
    if (is_array($decoded)) {
        $auto = $decoded;
    }
}

$tarikh = date('d/m/Y', strtotime($program['program_start']));
$tempat = $program['location'] ?: '-';

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <title>Borang Kehadiran</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= app_path('/public/css/pages/register.css') ?>">
</head>
<body>
<div class="container">
  <div class="card">
    <h1><?= e($program['program_name']) ?></h1>
    <div class="sub">Borang Pendaftaran Rasmi</div>
    <div class="info">
      <div>Tarikh: <b><?= e($tarikh) ?></b></div>
      <div>Tempat: <b><?= e($tempat) ?></b></div>
    </div>
  </div>

  <div class="card">
    <form id="attendanceForm">
      <input type="hidden" name="program_id" value="<?= $programId ?>">
      <input type="hidden" name="public_token" value="<?= e($public_token) ?>">

      <div>
        <label>Nama Penuh</label>
        <input name="nama" required placeholder="Nama penuh" value="<?= e($auto['name'] ?? '') ?>">
      </div>

      <div>
        <label>Jantina</label>
        <select name="jantina" required>
          <option value="">Pilih</option>
          <option value="Lelaki" <?= (($auto['gender'] ?? '') === 'Lelaki') ? 'selected' : '' ?>>Lelaki</option>
          <option value="Perempuan" <?= (($auto['gender'] ?? '') === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
        </select>
      </div>

      <div>
        <label>Umur</label>
        <input type="number" name="age" min="1" max="120" required value="<?= e($auto['age'] ?? '') ?>">
      </div>

      <div>
        <label>Jenis Kehadiran</label>
        <?php if ($mode === 'physical'): ?>
          <select name="kehadiran"><option value="physical">Fizikal</option></select>
        <?php elseif ($mode === 'online'): ?>
          <select name="kehadiran"><option value="online">Online</option></select>
        <?php else: ?>
          <select name="kehadiran" required>
            <option value="">Pilih</option>
            <option value="physical">Fizikal</option>
            <option value="online">Online</option>
          </select>
        <?php endif; ?>
      </div>

      <div>
        <label>Email</label>
        <input type="email" name="email" required value="<?= e($auto['email'] ?? '') ?>">
      </div>

      <div>
        <label>No Telefon</label>
        <input name="phone" required value="<?= e($auto['phone'] ?? '') ?>">
      </div>

      <div>
        <label>Jawatan</label>
        <input name="jawatan" required value="<?= e($auto['occupation'] ?? '') ?>">
      </div>

      <div>
        <label>Jabatan/Agensi</label>
        <input name="jabatan" required value="<?= e($auto['company'] ?? '') ?>">
      </div>

      <button class="form-submit-button" type="submit">HANTAR BORANG</button>
      <div id="message"></div>
    </form>
  </div>

  <div class="footer">Maklumat anda akan dirahsiakan dan hanya untuk tujuan pendaftaran.</div>
</div>

<script>
const form = document.getElementById("attendanceForm");
const msg = document.getElementById("message");
const btn = form.querySelector("button");

form.addEventListener("submit", async (event) => {
  event.preventDefault();
  btn.disabled = true;
  btn.textContent = "Menghantar...";

  const response = await fetch("submit.php", {
    method: "POST",
    body: new FormData(form)
  });

  const result = await response.json();
  msg.style.display = "block";

  if (result.isOk) {
    msg.textContent = "Kehadiran berjaya direkodkan!";
    msg.className = "success";
    form.reset();

    setTimeout(() => {
      window.location = "success.php?i=<?= urlencode($public_token) ?>";
    }, 1000);
  } else {
    msg.textContent = "Ralat: " + (result.error ?? "Sistem gagal.");
    msg.className = "error";
  }

  btn.disabled = false;
  btn.textContent = "HANTAR BORANG";
});
</script>
</body>
</html>
