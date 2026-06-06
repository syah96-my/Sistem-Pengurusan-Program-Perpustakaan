<?php
if (!function_exists('app_path')) {
    require_once __DIR__ . '/../../config/bootstrap.php';
}

// $PROGRAM is injected from index.php
$PROGRAM = $PROGRAM ?? [
    'program_name' => 'Program',
    'program_start' => date('Y-m-d H:i:s'),
    'location' => '-'
];
$TOKEN = $TOKEN ?? ($_GET['i'] ?? '');
$program_name = $PROGRAM["program_name"];
$tarikh = date("d/m/Y", strtotime($PROGRAM["program_start"]));
$masa   = date("h:i A", strtotime($PROGRAM["program_start"]));
$tempat = $PROGRAM["location"];

?>
<!doctype html>
<html lang="ms">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pendaftaran Belum Bermula</title>

  <link rel="stylesheet" href="<?= app_path('/public/css/pages/pages__not_started.css') ?>">
</head>

<body>
  <main id="app" class="public-app"></main>

  <script>
    const config = {
      program_name: <?= json_encode($program_name) ?>,
      tarikh: <?= json_encode($tarikh) ?>,
      masa: <?= json_encode($masa) ?>,
      tempat: <?= json_encode($tempat) ?>,
      status_message: "Sesi Pendaftaran Belum Bermula",

      background_color: "#0f172a",
      card_color: "#ffffff",
      text_color: "#1e293b",
      header_text_color: "#f8fafc",
      accent_color: "#8b5cf6",
      font_family: "Inter",
      font_size: 16
    };

    const app = document.getElementById("app");
    const escapeHtml = value => String(value ?? "").replace(/[&<>"']/g, char => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;"
    }[char]));

    function renderPage(cfg) {
      const baseSize = cfg.font_size;
      const safeProgramName = escapeHtml(cfg.program_name);
      const safeDate = escapeHtml(cfg.tarikh);
      const safeTime = escapeHtml(cfg.masa);
      const safePlace = escapeHtml(cfg.tempat);

      app.innerHTML = `
        <div class="public-stage">

          <div class="ornament animate-float ornament-top public-accent"></div>
          <div class="ornament animate-float ornament-bottom public-accent delay-card"></div>

          <div class="public-content">
            <div class="info-card animate-fade-in-up public-card">
              
            <h1 class="public-card-title">
              ${safeProgramName}
            </h1>


              <div class="public-info-grid">

                <div class="public-info-item public-info-blue">
                  <div class="public-blue public-info-icon">Date</div>
                  <div>
                    <div class="public-info-label">
                      Tarikh & Masa
                    </div>
                    <div class="public-info-value">${safeDate}</div>
                    <div class="public-info-sub">${safeTime}</div>
                  </div>
                </div>

                <div class="public-info-item public-info-purple">
                  <div class="public-accent public-info-icon">Place</div>
                  <div>
                    <div class="public-info-label">Tempat</div>
                    <div class="public-info-value">${safePlace}</div>
                  </div>
                </div>

              </div>
            </div>

            <div class="public-message-wrap animate-fade-in-up delay-sm">
              <h2 class="public-title">
                ${cfg.status_message}
              </h2>
            </div>

          </div>

          <div class="animate-fade-in-up public-footer-note">
             SILA TUNGGU 
          </div>

        </div>
      `;
    }

    renderPage(config);
  </script>
 

  <!-- COUNTDOWN + AUTO CHECK -->
  <script>
    /* ---------------------------------------------
       COUNTDOWN TIMER
    --------------------------------------------- */
    const startDateTime = new Date("<?= $PROGRAM['program_start'] ?>").getTime();
    const countdownEl = document.createElement("div");

    countdownEl.className = "countdown";

document.querySelector(".public-content").appendChild(countdownEl);

    function updateCountdown() {
        const now = new Date().getTime();
        const diff = startDateTime - now;

        if (diff <= 0) {
            countdownEl.innerHTML = "Pendaftaran sedang dibuka...";
            window.location.href = "register.php?i=<?= urlencode($TOKEN) ?>";
            return;
        }

        let seconds = Math.floor(diff / 1000);
        let minutes = Math.floor(seconds / 60);
        let hours   = Math.floor(minutes / 60);

        const d = Math.floor(hours / 24);
        hours = hours % 24;
        minutes = minutes % 60;
        seconds = seconds % 60;

        countdownEl.innerHTML = `
            <span class="countdown-title">
                Pendaftaran dibuka dalam:
            </span><br>
            ${d > 0 ? d + " hari " : ""}
            ${hours} jam ${minutes} minit ${seconds} saat
        `;
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();


    /* ---------------------------------------------
       AUTO CHECK EVERY 30 SECONDS (backup check)
    --------------------------------------------- */
    setInterval(async () => {
      try {
        const res = await fetch("<?= app_path('/public/index.php?i=' . urlencode($TOKEN) . '&check=1') ?>");
        const data = await res.json();

        if (data.status === "open") {
          window.location.href = "register.php?i=<?= urlencode($TOKEN) ?>";
        }

        if (data.status === "ended") {
          window.location.href = "index.php?i=<?= urlencode($TOKEN) ?>";
        }

      } catch (err) {
        console.error("Check failed", err);
      }
    }, 30000);
  </script>

</body>
</html>
