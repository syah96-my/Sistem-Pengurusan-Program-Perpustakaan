/* ============================================================
   MANUAL PARTICIPANT OVERRIDE
   ISOLATED - SAFE
============================================================ */

"use strict";

/* ============================================================
   MODE ENFORCEMENT
============================================================ */

function enforceManualMode(mode) {
    if (mode === "physical") {
        $("#manual_online").val(0).prop("disabled", true);
        $("#manual_physical").prop("disabled", false);
    }
    else if (mode === "online") {
        $("#manual_physical").val(0).prop("disabled", true);
        $("#manual_online").prop("disabled", false);
    }
    else { // hybrid
        $("#manual_physical, #manual_online").prop("disabled", false);
    }
}

/* ============================================================
   TOGGLE MANUAL OVERRIDE
============================================================ */

$(document).on("change", "#manual_override_toggle", function () {
    const enabled = $(this).is(":checked");

    if (enabled) {
        $("#manual-count-wrapper").show();

        enforceManualMode($("#program_mode").val());

        // Disable participant features
        $("#open-upload-modal")
            .prop("disabled", true)
            .addClass("btn-disabled");

        $("#open-participant")
            .prop("disabled", true)
            .addClass("btn-disabled");

    } else {
        $("#manual-count-wrapper").hide();
        $("#manual_physical").val(0);
        $("#manual_online").val(0);

        // Re-enable participant features
        $("#open-upload-modal")
            .prop("disabled", false)
            .removeClass("btn-disabled");

        $("#open-participant")
            .prop("disabled", false)
            .removeClass("btn-disabled");
    }
});

/* ============================================================
   MODE CHANGE HOOK (KEEP EXISTING BEHAVIOR)
============================================================ */

$(document).on("change", "#program_mode", function () {
    if ($("#manual_override_toggle").is(":checked")) {
        enforceManualMode($(this).val());
    }
});

/* ============================================================
   END MANUAL OVERRIDE
============================================================ */
