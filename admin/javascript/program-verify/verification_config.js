/* ============================================================
   Program verification page configuration
============================================================ */

"use strict";

const verificationProgramTypeField = document.getElementById("program_type_id");

const ProgramConfig = {
    API_KEY: window.GM_API_KEY,
    LIBRARY_ID: window.GM_LIBRARY_ID,
    USER_ID: window.GM_USER_ID,
    ROLE_ID: window.GM_ROLE_ID,
    PROGRAM_TYPE_ID: Number(verificationProgramTypeField?.value || 0),

    TABLE_MAP: {
        pending: "#table-pending",
        approved: "#table-approved",
        rejected: "#table-rejected"
    }
};

const ProgramState = {
    currentTab: "pending",
    currentProgramId: null,
    dtInstances: {}
};
