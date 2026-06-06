/* ============================================================
   PROGRAM FORM (ADD / VIEW / EDIT / SUBMIT)
   LARGE BUT ISOLATED - NO DATATABLE CREATION
============================================================ */

"use strict";

$(document).on("change", "#program_mode", function () {
    const mode = $(this).val();

    toggleModeFields(mode);

    // keep manual override inputs consistent
    if ($("#manual_override_toggle").is(":checked")) {
        enforceManualMode(mode);
    }
});

/* ============================================================
   PROGRAM LOOKUP LOADERS
============================================================ */
function loadProgramLookups(callback) {
    const headers = { "X-API-KEY": API_KEY };
    let pending = 3;
    const done = () => (--pending === 0 && callback());

    $.ajax({ url: (window.GM_API_BASE || "/api/?route=") + "scales/list", headers })
        .done(res => {
            const el = $("#scale_id").empty();
            el.append(`<option value="" disabled selected>Please Select</option>`);
            res.scales?.forEach(s =>
                s.enabled == "1" &&
                el.append(`<option value="${s.id}">${s.scale_name}</option>`)
            );
        }).always(done);

    $.ajax({ url: (window.GM_API_BASE || "/api/?route=") + "platforms/list", headers })
        .done(res => {
            const el = $("#platform_id").empty();
            el.append(`<option value="" disabled selected>Please Select</option>`);
            res.platforms?.forEach(p =>
                p.enabled == "1" &&
                el.append(`<option value="${p.id}">${p.platform_name}</option>`)
            );
        }).always(done);

    $.ajax({ url: (window.GM_API_BASE || "/api/?route=") + "target_groups/list", headers })
        .done(res => {
            const box = $("#target-group-list").empty();
            const sorted = (res.groups ?? []).sort((a, b) => a.id - b.id);
            sorted.forEach(g =>
                g.enabled == "1" &&
                box.append(`
                    <label class="checkbox-item">
                        <input type="checkbox" value="${g.id}"> ${g.group_name}
                    </label>
                `)
            );
        }).always(done);
}

/* ============================================================
   TARGET GROUP RULE
============================================================ */
$(document).on("change", "#target-group-list input[type=checkbox]", function () {
    const isOne = $(this).val() === "1";
    const checked = $(this).is(":checked");

    if (isOne && checked) {
        $("#target-group-list input[type=checkbox]")
            .not('[value="1"]')
            .prop("checked", false)
            .prop("disabled", true);
    } else {
        $("#target-group-list input[value='1']").prop("checked", false);
        $("#target-group-list input[type=checkbox]").prop("disabled", false);
    }
});

/* ============================================================
   DATE CONSTRAINTS
============================================================ */
$(document).on("change", "#program_start", function () {
    const start = $(this).val();
    if (!start) return;

    $("#program_end").attr("min", start);
    const end = $("#program_end").val();
    if (end && end < start) $("#program_end").val(start);
});

/* ============================================================
   AUTO ENABLE MANUAL OVERRIDE FOR PAST PROGRAM (CREATE MODE)
============================================================ */
$(document).on("change", "#program_end", function () {
    const formMode = $("#program-form").data("mode");
    if (formMode !== "create") return;

    const endVal = $(this).val();
    if (!endVal) return;

    if (new Date(endVal) < new Date()) {
        $("#manual-override-box").show();
        $("#manual_override_toggle").prop("checked", true);
        $("#manual-count-wrapper").show();
        enforceManualMode($("#program_mode").val());

        $("#open-upload-modal, #open-participant")
            .prop("disabled", true)
            .addClass("btn-disabled");
    } else {
        $("#manual-override-box").hide();
        $("#manual_override_toggle").prop("checked", false);
        $("#manual-count-wrapper").hide();

        $("#open-upload-modal, #open-participant")
            .prop("disabled", false)
            .removeClass("btn-disabled");
    }
});

/* ============================================================
   ADD PROGRAM
============================================================ */
$(document).on("click", "#add-btn", function () {

    $("#program-form")[0].reset();
    $("#program-form").data("mode", "create");
    $("#program-form input, #program-form select, #program-form textarea")
        .prop("disabled", false);

    $(".program-save-btn").show();
    $("#open-upload-modal, #open-participant").hide();

    $("#participant-stats-section").show();
    $("#total_participant_count, #physical_participant_count, #online_participant_count").text(0);

    loadProgramLookups(() => {
        $("#program_mode").val("physical");
        toggleModeFields("physical");

        $("#manual-override-box").hide();
        $("#manual_override_toggle").prop("checked", false);
        $("#manual-count-wrapper").hide();
        $("#manual_physical, #manual_online").val(0);

        $("#program-modal").addClass("show");
        setTimeout(() => $("#program_end").trigger("change"), 0);
    });
});

/* ============================================================
   VIEW PROGRAM
============================================================ */
$(document).on("click", ".btn-view", function () {
    const id = $(this).data("id");

    $("#program-modal-title").text("View Program");
    $("#program-form").data("mode", "view").data("program_id", id);

    loadProgramLookups(() => {
        $.ajax({
            url: (window.GM_API_BASE || "/api/?route=") + `programs/view&program_id=${id}`,
            headers: { "X-API-KEY": API_KEY },
            success(res) {
                const p = res.program;

                refreshParticipantStats(id);

                $("#open-upload-modal").hide();
                $("#open-participant").show();

                $("#program_name").val(p.program_name);
                $("#program_start").val(p.program_start.replace(" ", "T"));
                $("#program_end").val(p.program_end.replace(" ", "T"));

                $("#program_mode").val(p.mode);
                toggleModeFields(p.mode);
                enforceManualMode(p.mode);

                $("#platform_id").val(p.platform_id);
                $("#location").val(p.location);
                $("#officiated_by").val(p.officiated_by);
                $("#scale_id").val(p.scale_id);
                $("#document_url").val(p.document_url);
                $("#program_details").val(p.program_details);
                $("#cover_image_url").val(p.cover_image_url);

                $("#program-form input, #program-form select, #program-form textarea")
                    .prop("disabled", true);

                $("#program-save-btn").hide();

                $("#target-group-list input")
                    .prop("checked", false)
                    .prop("disabled", true);

                p.target_groups.forEach(g =>
                    $(`#target-group-list input[value="${g.id}"]`).prop("checked", true)
                );

                if (new Date(p.program_end) < new Date()) {
                    $("#manual-override-box").show();
                } else {
                    $("#manual-override-box").hide();
                    $("#manual_override_toggle").prop("checked", false);
                    $("#manual-count-wrapper").hide();
                }

                $("#program-modal").addClass("show");
            }
        });
    });
});

/* ============================================================
   EDIT PROGRAM
============================================================ */
$(document).on("click", ".btn-edit", function () {
    const id = $(this).data("id");

    $("#program-modal-title").text("Edit Program");

    /* ===============================
       FORM STATE
    =============================== */
    $("#program-form")
        .data("mode", "edit")
        .data("program_id", id);

    $("#program-form input, #program-form select, #program-form textarea")
        .prop("disabled", false);

    $(".program-save-btn").show();

    /* ===============================
       FORCE PARTICIPANT UI VISIBLE
    =============================== */
    $(".upload-row").show();
    $("#participant-stats-section").show();
    $("#open-upload-modal").show();
    $("#open-participant").show();

    // Reset disable state
    $("#open-upload-modal, #open-participant")
        .prop("disabled", false)
        .removeClass("btn-disabled");

    /* ===============================
       RESET MANUAL OVERRIDE UI
    =============================== */
    $("#manual_override_toggle").prop("checked", false);
    $("#manual-count-wrapper").hide();
    $("#manual_physical").val(0);
    $("#manual_online").val(0);

    loadProgramLookups(() => {
        $.ajax({
            url: (window.GM_API_BASE || "/api/?route=") + `programs/view&program_id=${id}`,
            headers: { "X-API-KEY": API_KEY },
            success(res) {
                const p = res.program;

                /* ===============================
                   PARTICIPANT STATS
                =============================== */
                refreshParticipantStats(id);

                /* ===============================
                   MANUAL OVERRIDE (IF ANY)
                =============================== */
                if (p.is_manual_override == 1) {
                    $("#manual_override_toggle").prop("checked", true);
                    $("#manual-count-wrapper").show();

                    $("#manual_physical").val(p.physical_participant_count ?? 0);
                    $("#manual_online").val(p.online_participant_count ?? 0);

                    $("#open-upload-modal, #open-participant")
                        .prop("disabled", true)
                        .addClass("btn-disabled");
                }

                /* ===============================
                   FORM FIELDS
                =============================== */
                $("#program_name").val(p.program_name);
                $("#program_start").val(p.program_start.replace(" ", "T"));
                $("#program_end").val(p.program_end.replace(" ", "T"));

                $("#program_mode").val(p.mode);
                toggleModeFields(p.mode);
                enforceManualMode(p.mode);

                $("#platform_id").val(p.platform_id);
                $("#location").val(p.location);
                $("#officiated_by").val(p.officiated_by);
                $("#scale_id").val(p.scale_id);
                $("#document_url").val(p.document_url);
                $("#program_details").val(p.program_details);
                $("#cover_image_url").val(p.cover_image_url);

                /* ===============================
                   TARGET GROUPS
                =============================== */
                $("#target-group-list input")
                    .prop("checked", false)
                    .prop("disabled", false);

                p.target_groups.forEach(g => {
                    $(`#target-group-list input[value="${g.id}"]`)
                        .prop("checked", true);
                });

                if (p.target_groups.some(g => g.id == 1)) {
                    $("#target-group-list input")
                        .not('[value="1"]')
                        .prop("checked", false)
                        .prop("disabled", true);
                }

                /* ===============================
                   MANUAL OVERRIDE VISIBILITY
                =============================== */
                const programEnd = new Date(p.program_end.replace(" ", "T"));
                if (programEnd < new Date()) {
                    $("#manual-override-box").show();
                } else {
                    $("#manual-override-box").hide();
                }

                $("#program-modal").addClass("show");
            }
        });
    });
});


/* ============================================================
   SUBMIT PROGRAM (CREATE + UPDATE)
============================================================ */
$("#program-form").on("submit", function (e) {
    e.preventDefault();

    const mode = $(this).data("mode");
    if (mode === "view") return;

    const toSQL = dt => dt.replace("T", " ") + ":00";
    let targetGroups = [];

    /* ===============================
       URL VALIDATION
    =============================== */
    const urlLink  = $("#document_url").val().trim();
    const imageUrl = $("#cover_image_url").val().trim();

    if (!Utils.isValidURL(urlLink)) {
        Swal.fire(
            "Invalid Document URL",
            "Document URL must start with http:// or https://",
            "warning"
        );
        return;
    }

    if (!Utils.isValidURL(imageUrl)) {
        Swal.fire(
            "Invalid Image URL",
            "Poster Image URL must start with http:// or https://",
            "warning"
        );
        return;
    }

    $("#target-group-list input:checked").each(function () {
        targetGroups.push($(this).val());
    });

    let payload = {
        parent_library_id: $("#parent_library_id").val() || null,
        library_type_id: $("#library_type_id").val(),
        library_id: LIBRARY_ID,
        program_type_id: PROGRAM_TYPE_ID,
        scale_id: $("#scale_id").val(),
        mode: $("#program_mode").val(),
        program_name: $("#program_name").val(),
        program_start: toSQL($("#program_start").val()),
        program_end: toSQL($("#program_end").val()),
        platform_id: $("#program_mode").val() !== "physical" ? $("#platform_id").val() : null,
        location: $("#location").val(),
        officiated_by: $("#officiated_by").val(),
        program_details: $("#program_details").val(),
        document_url: $("#document_url").val(),
        cover_image_url: $("#cover_image_url").val(),
        target_group_ids: targetGroups,
        user_id: window.GM_USER_ID
    };

    if ($("#manual_override_toggle").is(":checked")) {
        const mp = parseInt($("#manual_physical").val() || 0, 10);
        const mo = parseInt($("#manual_online").val() || 0, 10);

        if (mp + mo <= 0) {
            Swal.fire("Invalid Manual Override",
                "Please enter at least 1 participant.",
                "warning"
            );
            return;
        }

        payload.is_manual_override = 1;
        payload.manual_physical = mp;
        payload.manual_online = mo;
    } else {
        payload.is_manual_override = 0;
    }

    let url = (window.GM_API_BASE || "/api/?route=") + "programs/create";
    let method = "POST";

    if (mode === "edit") {
        payload.program_id = $(this).data("program_id");
        url = (window.GM_API_BASE || "/api/?route=") + "programs/update";
        method = "PUT";
    }

    $.ajax({
        url,
        method,
        headers: { "X-API-KEY": API_KEY },
        contentType: "application/json",
        data: JSON.stringify(payload),
        success(res) {
            if (!res.success) {
                Swal.fire("Error", res.message || "Failed", "error");
                return;
            }

            $("#program-modal").removeClass("show");

            // EDIT MODE -> just reload
            if (mode === "edit") {
                Swal.fire("Updated!", "Program updated successfully.", "success");
                reloadCurrentTable();
                loadTabCounts();
                return;
            }

            const programId = res.program_id;

            // MANUAL OVERRIDE -> skip participant upload
            if (payload.is_manual_override === 1) {
                Swal.fire(
                    "Saved",
                    "Program saved with manual participant count.",
                    "success"
                );
                reloadCurrentTable();
                loadTabCounts();
                return;
            }

            // CREATE MODE -> ASK TO UPLOAD PARTICIPANTS
            Swal.fire({
                title: "Program Created!",
                html: `
                    <p>The program has been created successfully.</p>
                    <p><strong>Program ID:</strong> ${programId}</p>
                    <br>
                    <p>Would you like to upload participants now?</p>
                `,
                icon: "success",
                showCancelButton: true,
                confirmButtonText: "Upload Participants",
                cancelButtonText: "Later"
            }).then(result => {
                reloadCurrentTable();
                loadTabCounts();

                if (!result.isConfirmed) return;

                $("#participant-upload-modal")
                    .data("program_id", programId)
                    .addClass("show");
            });
        }

    });
});

/* ============================================================
   CLOSE PROGRAM MODAL
============================================================ */
$("#program-cancel-btn, #program-modal-close").on("click", function () {
    $("#program-modal").removeClass("show");
});

//$(document).on("click", "#program-modal", function (e) {
//    if ($(e.target).is("#program-modal")) {
//        $("#program-modal").removeClass("show");
//    }
//});

/* ============================================================
   END PROGRAM FORM
============================================================ */
