<?php
require_once __DIR__ . '/../../config/bootstrap.php';

header('Content-Type: application/javascript; charset=UTF-8');

$context = [
    'apiKey' => gm_csrf_token(),
    'csrfToken' => gm_csrf_token(),
    'basePath' => GM_BASE_PATH,
    'apiBase' => app_path('/api/?route='),
    'libraryId' => $_SESSION['library_id'] ?? null,
    'userId' => $_SESSION['user_id'] ?? null,
    'parentId' => $_SESSION['library_parent_id'] ?? null,
    'typeId' => $_SESSION['library_type_id'] ?? null,
    'roleId' => $_SESSION['role'] ?? null,
    'domain' => $config['domain'] ?? '',
];
?>
window.GM_CONTEXT = <?= json_encode($context, JSON_UNESCAPED_SLASHES) ?>;
window.GM_API_KEY = window.GM_CONTEXT.apiKey;
window.GM_BASE_PATH = window.GM_CONTEXT.basePath || "";
window.GM_API_BASE = window.GM_CONTEXT.apiBase;
window.GM_LIBRARY_ID = window.GM_CONTEXT.libraryId;
window.GM_USER_ID = window.GM_CONTEXT.userId;
window.GM_PARENT_ID = window.GM_CONTEXT.parentId;
window.GM_TYPE_ID = window.GM_CONTEXT.typeId;
window.GM_ROLE_ID = window.GM_CONTEXT.roleId;
window.GM_DOMAIN = window.GM_CONTEXT.domain;
