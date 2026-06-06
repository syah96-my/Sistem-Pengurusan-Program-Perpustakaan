/* ============================================================
   Program input page configuration
============================================================ */

"use strict";

window.APP = window.APP || {};

const programTypeField = document.getElementById("program_type_id");
window.GM_PROGRAM_TYPE_ID = Number(programTypeField?.value || 0);

APP.API_KEY = window.GM_API_KEY;
APP.API_BASE = window.GM_API_BASE || "/api/?route=";
APP.LIBRARY_ID = window.GM_LIBRARY_ID ?? null;
APP.USER_ID = window.GM_USER_ID ?? null;
APP.PARENT_ID = window.GM_PARENT_ID ?? null;
APP.TYPE_ID = window.GM_TYPE_ID ?? null;
APP.ROLE_ID = window.GM_ROLE_ID ?? null;
APP.DOMAIN = window.GM_DOMAIN ?? null;

APP.TABLE_MAP = {
    incomplete: "#table-incomplete",
    pending: "#table-pending",
    verified: "#table-verified",
    rejected: "#table-rejected"
};

APP.DEBUG = false;
