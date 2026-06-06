const API = window.GM_API_BASE || "/api/?route=";
const KEY = window.GM_API_KEY;
let LIMIT = 200;

/* ============================================================
   ROLE-BASED PARAMS (HQ / Branch Admin / Staff)
============================================================ */
function buildParamsForSearch(extra = {}) {
    const role     = Number(window.GM_ROLE_ID);
    const libId    = Number(window.GM_LIBRARY_ID);
    const parentId = Number(window.GM_PARENT_ID);
    const typeId   = Number(window.GM_TYPE_ID);

    let params = { ...extra };
    params.role_id = role;

    if (role === 2) {
        // Branch Admin
        params.parent_library_id = libId;
        params.library_id        = libId;   // ADD
    }

    if (role === 3) {
        // Library Staff
        params.parent_library_id = parentId;
        params.library_id        = libId;
        params.library_type_id   = typeId;
    }

    return params;
}

/* ============================================================
   LOCK FILTER DROPDOWNS BASED ON ROLE
============================================================ */
function applyFilterLocking() {
    const role     = Number(window.GM_ROLE_ID);
    const libId    = Number(window.GM_LIBRARY_ID);
    const parentId = Number(window.GM_PARENT_ID);
    const typeId   = Number(window.GM_TYPE_ID);

    // ROLE 2 - Branch Admin
    if (role === 2) {
        $("#filter-parent").val(libId).prop("disabled", true);
        $("#filter-type option[value='1']").hide();
    }

    // ROLE 3 - Library Staff
    if (role === 3) {
        $("#filter-parent").val(parentId).prop("disabled", true);
        $("#filter-type").val(typeId).prop("disabled", true);
        // Search box stays enabled
    }
}

/* ============================================================
   LOAD FILTER DROPDOWN OPTIONS
============================================================ */
function loadFilters(callback) {
    const headers = { "X-API-KEY": KEY };
    let pending = 2;
    const done = () => (--pending === 0 && callback());

    // ---- Parent Library filter ----
    $.ajax({ url: API + "libraries/list", headers })
      .done(res => {
          const p = $("#filter-parent")
              .empty()
              .append(`<option value="">Parent Library</option>`);

          res.libraries.forEach(lib => {
              if (lib.parent_id === null)
                  p.append(`<option value="${lib.id}">${lib.name}</option>`);
          });
      })
      .always(done);

    // ---- Library Type filter ----
    $.ajax({ url: API + "library_types/list", headers })
      .done(res => {
          const t = $("#filter-type")
              .empty()
              .append(`<option value="">Library Type</option>`);

          res.types.forEach(tt => {
              t.append(`<option value="${tt.id}">${tt.type_name}</option>`);
          });
      })
      .always(done);
}

/* ============================================================
   FETCH SEARCH RESULTS (APPLIED ROLE LOCK HERE)
============================================================ */
function loadSearch() {
    $("#loadingBox").show();

    // Base user UI filters
    let params = {
        q: $("#search-box").val().trim(),
        limit: LIMIT,
        parent_library_id: $("#filter-parent").val(),
        library_type_id: $("#filter-type").val(),
        date_from: $("#date-from").val(),
        date_to: $("#date-to").val()
    };

    // Apply role restrictions
    params = buildParamsForSearch(params);

    $.ajax({
        url: API + "stats/search-programs",
        headers: { "X-API-KEY": KEY },
        data: params,
        success: res => {
            $("#loadingBox").hide();
            renderTable(res.results || []);
        }
    });
}

/* ============================================================
   RENDER TABLE
============================================================ */
function renderTable(rows) {

    if ($.fn.DataTable.isDataTable("#searchTable")) {
        $("#searchTable").DataTable().destroy();
    }

    if (!rows.length) {
        $("#table-body").html("<tr><td colspan='5'>No results found</td></tr>");
        $("#badge-total").text("Total Results: 0");
        return;
    }

    $("#badge-total").text("Total Results: " + rows.length);

    let body = "";
    rows.forEach(r => {
        body += `
            <tr class="prog-row" data-id="${r.program_id}">
                <td>${r.program_name}</td>
                <td>${r.library_name}</td>
                <td>${r.program_start ?? '-'}</td>
                <td>${r.mode ?? '-'}</td>
                <td>${r.status ?? '-'}</td>
            </tr>`;
    });

    $("#table-body").html(body);

    setTimeout(() => {
        $("#searchTable").DataTable({
            paging: true,
            pageLength: 20,
            lengthMenu: [10, 20, 25, 50, 100],
            searching: false,
            ordering: true
        });

        // Click to open modal
        $("#searchTable").off("click", "tr.prog-row")
            .on("click", "tr.prog-row", function () {
                openProgramModal($(this).data("id"));
            });
    }, 20);
}


function setParticipantStatus(p) {
    const tag = $("#v-participant-status");

    // Reset
    tag
        .removeClass()
        .addClass("participant-tag");

    // Manual override wins
    if (parseInt(p.is_manual_override, 10) === 1) {
        tag.addClass("manual")
           .text("Manual Input Participant");
        return;
    }

    const self  = parseInt(p.self_registered_participant_count || 0, 10);
    const staff = parseInt(p.staff_uploaded_participant_count || 0, 10);

    if (self > 0 && staff > 0) {
        tag.addClass("mixed")
           .text("Self Registered + Staff Uploaded");
    } else if (self > 0) {
        tag.addClass("self")
           .text("Self Registered");
    } else if (staff > 0) {
        tag.addClass("staff")
           .text("Staff Uploaded");
    } else {
        tag.addClass("none")
           .text("No Participant Source");
    }
}

function esc(text) {
    if (text === null || text === undefined) return "-";
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/* ============================================================
   OPEN MODAL PROGRAM DETAILS
============================================================ */
function openProgramModal(id) {
    $("#programModal").css("display", "flex");

    $.ajax({
        url: (window.GM_API_BASE || "/api/?route=") + "programs/view&program_id=" + id,
        headers: { "X-API-KEY": KEY },
        success: res => {

            const p = res.program;

            /* --------------------------------------------------
              METADATA
            -------------------------------------------------- */
            $("#v-program-name").text(esc(p.program_name ?? "-"));
            $("#v-library-name").text(esc(p.library_name ?? "-"));
            $("#v-program-id").text(p.program_id ?? "-");

            $("#v-library-type").text(p.library_type_name ?? "-");
            $("#v-parent-library").text(p.parent_library_name ?? "None");

            /* --------------------------------------------------
              BASIC INFO
            -------------------------------------------------- */
            $("#v-scale").text(p.scale_name ?? "-");
            $("#v-mode").text(p.mode ?? "-");

            $("#v-start").text(p.program_start ?? "-");
            $("#v-end").text(p.program_end ?? "-");

            $("#v-location").text(p.location ?? "-");
            $("#v-platform").text(p.platform_name ?? "-");
            $("#v-officiated_by").text(p.officiated_by ?? "-");

            $("#v-image").text(p.cover_image_url ?? "-");
            $("#v-documents").text(p.document_url ?? "-");

            /* --------------------------------------------------
              TARGET GROUPS
            -------------------------------------------------- */
            if (Array.isArray(p.target_groups) && p.target_groups.length > 0) {

                const html = p.target_groups.map(g => {
                    return `
                        <span class="tg-pill status-pill">
                            ${esc(g.group_name)}
                        </span>
                    `;
                }).join(" ");

                $("#v-target-groups").html(html);

            } else {
                $("#v-target-groups").html("<i>No target groups</i>");
            }

            /* --------------------------------------------------
              DESCRIPTION
            -------------------------------------------------- */
            $("#v-details").text(
                esc(p.program_details ?? "-")
            );

            /* --------------------------------------------------
              PARTICIPANTS
            -------------------------------------------------- */
            const physical = Number(p.physical_participant_count ?? 0);
            const online   = Number(p.online_participant_count ?? 0);
            const total    = Number(p.total_participant_count ?? (physical + online));

            $("#v-p-physical").text(physical);
            $("#v-p-online").text(online);
            $("#v-p-total").text(total);

            /* --------------------------------------------------
              PARTICIPANT SOURCE STATUS
            -------------------------------------------------- */
            setParticipantStatus(p);

            /* --------------------------------------------------
              SHOW MODAL
            -------------------------------------------------- */
        }

    });
}

/* ============================================================
   CLOSE MODAL
============================================================ */
function closeModal() {
    $("#programModal").hide();
}

function applyDateRestrictions() {
    const from = $("#date-from").val();
    const to   = $("#date-to").val();

    if (from) {
        $("#date-to").attr("min", from);

        if (to && to < from) {
            $("#date-to").val(from);
        }
    } else {
        $("#date-to").removeAttr("min");
    }
}
/* ============================================================
   INIT PAGE
============================================================ */
$(document).ready(function () {

    // Load dropdowns -> then apply role lock -> then load results
    loadFilters(() => {
        applyFilterLocking();
        loadSearch();
        applyDateRestrictions(); // run once on load
    });

    // Event handlers
    $("#btn-refresh").click(loadSearch);
    $("#search-box").on("input", loadSearch);
    $("#date-from").on("change", function () {
        applyDateRestrictions();
        loadSearch();
    });
    $("#date-to").on("change", loadSearch);
    $(".js-close-program-modal").on("click", closeModal);
});
