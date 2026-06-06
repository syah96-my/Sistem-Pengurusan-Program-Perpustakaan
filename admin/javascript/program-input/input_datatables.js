/* ============================================================
   PROGRAM DATATABLES
   DATA + RENDERING ONLY
============================================================ */

"use strict";

/* ============================================================
   CONFIG
============================================================ */

const API_KEY = window.GM_API_KEY;
const API_BASE = window.GM_API_BASE || "/api/?route=";
const LIBRARY_ID = window.GM_LIBRARY_ID;
const PROGRAM_TYPE_ID = window.GM_PROGRAM_TYPE_ID;

/* ============================================================
   TABLE STATE
============================================================ */

const TABLE_MAP = {
    incomplete: "#table-incomplete",
    pending: "#table-pending",
    verified: "#table-verified",
    rejected: "#table-rejected",
    delete: "#table-delete"
};

const dtInstances = {};
let currentStatusTab = "incomplete";
let currentStageFilter = "";

/* ============================================================
   COLUMN DEFINITIONS
============================================================ */

const NORMAL_COLUMNS = [
    { data: "program_id", width: "6%" },

    {
        data: null,
        width: "38%",
        render: row => `
            <div class="program-title-cell">
                <strong>${row.program_name}</strong><br>
                Mode: ${row.mode}<br>
                Location: ${row.location || "-"}<br>
                Officiate: ${row.officiated_by || "-"}
            </div>
        `
    },

    { data: "program_start", width: "16%" },
    { data: "program_end", width: "16%" },

    {
        data: "status",
        width: "10%",
        render: s => s ? s.charAt(0).toUpperCase() + s.slice(1) : "-"
    },

    {
        data: null,
        width: "24%",
        className: "col-actions",
        orderable: false,
        render: row => {
            const stage = row.program_stage;
            const status = row.status;

            if (status === "verified") {
                return `<button class="btn-icon btn-view" data-id="${row.program_id}">View</button>`;
            }

            if (status === "incomplete" && stage === "pre_program") {
                return `
                    <button class="btn-icon btn-edit" data-id="${row.program_id}">Edit</button>
                    <button class="btn-icon btn-copy-link" data-id="${row.program_id}">Link</button>
                    <button class="btn-icon btn-delete" data-id="${row.program_id}">Delete</button>
                `;
            }

            if (status === "incomplete" || status === "pending" || status === "rejected") {
                return `
                    <button class="btn-icon btn-edit" data-id="${row.program_id}">Edit</button>
                    <button class="btn-icon btn-delete" data-id="${row.program_id}">Delete</button>
                `;
            }

            return "";
        }
    }
];

const DELETE_COLUMNS = [
    { data: "program_id", width: "6%" },
    { data: "program_name", width: "30%" },
    { data: "program_start", width: "16%" },
    { data: "program_end", width: "16%" },
    {
        data: "note_text",
        width: "32%",
        render: n => n ? n : "-"
    }
];

/* ============================================================
   DATATABLE CREATOR
============================================================ */

function createDataTable(selector, status) {
    if (dtInstances[selector]) {
        try { dtInstances[selector].destroy(); } catch (e) {}
        $(selector).empty();
    }

    const columnsConfig =
        status === "delete"
            ? DELETE_COLUMNS
            : NORMAL_COLUMNS;

    dtInstances[selector] = $(selector).DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        destroy: true,
        order: [[2, "desc"]],

        ajax: {
            url: status === "delete"
                ? API_BASE + "programs/datatables_delete"
                : API_BASE + "programs/datatables",
            type: "GET",
            headers: { "X-API-KEY": API_KEY },
            data: function (d) {
                d.library_id = LIBRARY_ID;
                d.program_type_id = PROGRAM_TYPE_ID;
                d.status_filter = status;
                d.program_stage = currentStageFilter;
            },
            dataSrc: json => json?.data ?? []
        },

        columns: columnsConfig
    });
}

/* ============================================================
   TABLE HELPERS
============================================================ */

function initAllTables() {
    createDataTable(TABLE_MAP[currentStatusTab], currentStatusTab);
}

function reloadCurrentTable() {
    const selector = TABLE_MAP[currentStatusTab];
    if (dtInstances[selector]) {
        dtInstances[selector].ajax.reload(null, false);
    }
}

/* ============================================================
   TAB SWITCHING
============================================================ */

$(document).on("click", ".tab-button", function () {
    const status = $(this).data("status");
    if (!status) return;

    currentStatusTab = status;

    $(".tab-button").removeClass("active");
    $(this).addClass("active");

    $(".tab-pane").removeClass("active");
    $("#status-" + status).addClass("active");

    const selector = TABLE_MAP[status];
    if (!dtInstances[selector]) {
        createDataTable(selector, status);
    } else {
        reloadCurrentTable();
    }
});


/* ============================================================
   TAB COUNTS + AUTO FOCUS REJECTED
============================================================ */
function loadTabCounts() {
    $.ajax({
        url: API_BASE + "statuscount/summary",
        method: "GET",
        headers: { "X-API-KEY": API_KEY },
        data: {
            library_id: LIBRARY_ID,
            program_type_id: PROGRAM_TYPE_ID
        },
        success: function (res) {
            if (!res || !res.success || !res.summary) return;

            const inc = res.summary.incomplete || 0;
            const rej = res.summary.rejected || 0;

            if (inc > 0) {
                $("#count-incomplete").text(inc).show();
            } else {
                $("#count-incomplete").hide();
            }

            if (rej > 0) {
                $("#count-rejected").text(rej).show();

                if (currentStatusTab !== "rejected") {
                    currentStatusTab = "rejected";

                    $(".tab-button").removeClass("active");
                    $('.tab-button[data-status="rejected"]').addClass("active");

                    $(".tab-pane").removeClass("active");
                    $("#status-rejected").addClass("active");

                    if (!dtInstances["#table-rejected"]) {
                        createDataTable("#table-rejected", "rejected");
                    } else {
                        reloadCurrentTable();
                    }
                }
            } else {
                $("#count-rejected").hide();
            }
        }
    });
}
/* ============================================================
   INIT
============================================================ */

$(document).ready(function () {
    initAllTables();
    loadTabCounts();
});
