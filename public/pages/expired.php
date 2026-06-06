<?php
if (!function_exists('app_path')) {
    require_once __DIR__ . '/../../config/bootstrap.php';
}

// $PROGRAM is injected from index.php
$PROGRAM = $PROGRAM ?? ['program_name' => 'Program'];
$program_name = $PROGRAM["program_name"];
?>
<!doctype html>
<html lang="ms">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pendaftaran Tamat</title>

  <link rel="stylesheet" href="<?= app_path('/public/css/pages/pages__expired.css') ?>">
</head>

<body>
  <main id="app" class="public-app"></main>

  <script>
    const config = {
      program_name: <?= json_encode($program_name) ?>,
      main_message: "Sesi Pendaftaran Telah Tamat",
      background_color: "#0f172a",
      text_color: "#f8fafc",
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
      const safeMessage = escapeHtml(cfg.main_message);
      const safeProgramName = escapeHtml(cfg.program_name);

      app.innerHTML = `
        <div class="public-stage">

          <div class="ornament animate-float ornament-top public-accent"></div>
          <div class="ornament animate-float ornament-bottom public-accent delay-card"></div>

          <div class="public-content">

            <div class="divider-row animate-fade-in-up delay-sm">
              <div class="divider-line"></div>
              <div class="divider-spacer"></div>
              <div class="divider-line"></div>
            </div>

            <h1 class="animate-fade-in-up expired-title">
              ${safeMessage}
            </h1>

            <p class="animate-fade-in-up delay-md expired-program-name">
              ${safeProgramName}
            </p>

            <div class="divider-row animate-fade-in-up delay-lg">
              <div class="divider-line"></div>
              <div class="divider-spacer"></div>
              <div class="divider-line"></div>
            </div>

          </div>

          <div class="animate-fade-in-up delay-xl expired-footer">
             TERIMA KASIH 
          </div>

        </div>
      `;
    }

    renderPage(config);
  </script>

</body>
</html>
