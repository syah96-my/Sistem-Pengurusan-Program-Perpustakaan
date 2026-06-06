<?php
require_once __DIR__ . '/../config/bootstrap.php';

/* ============================================================
   LOAD PROGRAM USING TOKEN
============================================================ */
$public_token = $_GET["i"] ?? null;
if (!$public_token) die("Invalid link.");

$stmt = $pdo->prepare("SELECT * FROM gm_programs WHERE public_token=? LIMIT 1");
$stmt->execute([$public_token]);
$program = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$program) die("Program not found.");

$program_name = $program["program_name"];
$document_url     = $program["document_url"]; // Document link (optional)

// If empty, button will be hidden in JS
?>
<!doctype html>
<html lang="ms">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Selamat Datang</title>
  <link rel="stylesheet" href="<?= app_path('/public/css/pages/success.css') ?>">
</head>

<body>
  <main id="app" class="public-app"></main>

  <script>
    const programName = <?= json_encode($program_name) ?>;
    const documentLink = <?= json_encode($document_url) ?>;

    const config = {
      welcome_text: "Selamat Datang ke",
      program_name: programName,
      button_text: "Buka Dokumen",
      background_color: "#0f172a",
      card_color: "#1e293b",
      text_color: "#f8fafc",
      accent_color: "#8b5cf6",
      button_color: "#3b82f6",
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

    function render() {
      const baseSize = config.font_size;
      const safeWelcome = escapeHtml(config.welcome_text);
      const safeProgramName = escapeHtml(config.program_name);
      const safeButtonText = escapeHtml(config.button_text);

      app.innerHTML = `
        <div class="public-stage">

          <div class="ornament animate-float ornament-top public-accent"></div>
          <div class="ornament animate-float ornament-bottom public-button-accent"></div>

          <div class="public-content">
            <div class="decorative-border public-accent">

              <div class="animate-fade-in-up mb-4 delay-sm">
                <div class="public-kicker">
                  ${safeWelcome}
                </div>
              </div>

        <h1 class="animate-fade-in-up public-program-title">
          ${safeProgramName}
        </h1>


              ${
                documentLink 
                ? `<button id="documentBtn" 
                        class="document-button public-document-button">
                      ${safeButtonText}
                   </button>`
                : `<p class="public-muted-message">Tiada dokumen untuk program ini.</p>`
              }
            </div>
          </div>

          <div class="animate-fade-in-up public-official">
             RASMI 
          </div>

        </div>
      `;

      // If no button required
      if (!documentLink) return;

      // Add button handler
      document.getElementById("documentBtn").onclick = () => {
        try {
          const url = new URL(documentLink, window.location.href);
          if (!["http:", "https:"].includes(url.protocol)) return;
          window.open(url.href, "_blank", "noopener");
        } catch (error) {
          return;
        }
      };
    }

    render();
  </script>

</body>
</html>
