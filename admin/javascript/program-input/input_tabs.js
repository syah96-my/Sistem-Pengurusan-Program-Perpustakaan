/* ============================================================
   PROGRAM STATUS TABS + STAGE FILTERS
   UI ONLY - NO DATA LOGIC
============================================================ */

"use strict";

/* ============================================================
   STATUS TAB SWITCHING
   .tab-button[data-status="incomplete|pending|verified|rejected"]
============================================================ */

$(document).on("click", ".tab-button", function () {
    const status = $(this).data("status");
    if (!status) return;

    // update state
    currentStatusTab = status;

    // UI: tabs
    $(".tab-button").removeClass("active");
    $(this).addClass("active");

    // UI: panes
    $(".tab-pane").removeClass("active");
    $("#status-" + status).addClass("active");

    // table handling
    const selector = TABLE_MAP[status];
    if (!dtInstances[selector]) {
        createDataTable(selector, status);
    } else {
        reloadCurrentTable();
    }
});

/* ============================================================
   STAGE FILTERS (INSIDE EACH STATUS TAB)
   .stage-btn[data-stage="|pre_program|completed|cancelled"]
============================================================ */

$(document).on("click", ".stage-btn", function () {
    const stage = $(this).data("stage") ?? "";

    // update state
    currentStageFilter = stage;

    // UI: highlight active filter in this group
    $(this)
        .closest(".stage-filter")
        .find(".stage-btn")
        .removeClass("active");

    $(this).addClass("active");

    // reload current table
    reloadCurrentTable();
});

/* ============================================================
   END TABS
============================================================ */
