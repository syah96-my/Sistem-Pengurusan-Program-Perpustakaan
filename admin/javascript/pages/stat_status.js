const API = window.GM_API_BASE || "/api/?route=";
const KEY = window.GM_API_KEY;

/* ---------------------------------------------------------
   APPLY ROLE RULES TO FILTER UI
--------------------------------------------------------- */
function applyRoleFilterRules() {
    const role     = Number(window.GM_ROLE_ID);
    const libId    = Number(window.GM_LIBRARY_ID);
    const parentId = Number(window.GM_PARENT_ID);
    const typeId   = Number(window.GM_TYPE_ID);

    // ROLE 2 - Branch Admin
    if (role === 2) {
        $("#filter-parent").val(libId).prop("disabled", true);
        $("#filter-library").val(libId).prop("disabled", true); // FIX
        $("#filter-type").prop("disabled", false);
        $("#search-library").prop("disabled", false);
    }

    // ROLE 3 - Library Staff
    else if (role === 3) {
        $("#filter-parent").val(parentId).prop("disabled", true);
        $("#filter-library").val(libId).prop("disabled", true);
        $("#filter-type").val(typeId).prop("disabled", true);
        $("#search-library").prop("disabled", true);
    }

    // ROLE 1 (HQ) -> full access, no lock
}

/* ---------------------------------------------------------
   LOAD FILTER OPTIONS
--------------------------------------------------------- */
function loadFilters(callback) {
    $.ajax({
        url: API + "libraries/list",
        headers: { "X-API-KEY": KEY },
        success: res => {

            $("#filter-parent").empty().append(`<option value="">Parent Library</option>`);
            $("#filter-library").empty().append(`<option value="">Library</option>`);

            res.libraries.forEach(lib => {

                // Parent libraries (HQ / main)
                if (lib.parent_id === null) {
                    $("#filter-parent").append(
                        `<option value="${lib.id}">${lib.name}</option>`
                    );
                }

                // All libraries
                $("#filter-library").append(
                    `<option value="${lib.id}">${lib.name}</option>`
                );
            });

            if (callback) callback();
        }
    });
}

/* ---------------------------------------------------------
   BUILD PARAMS WITH ROLE OVERRIDE
--------------------------------------------------------- */
function buildParams() {
    const role     = Number(window.GM_ROLE_ID);
    const libId    = Number(window.GM_LIBRARY_ID);
    const parentId = Number(window.GM_PARENT_ID);
    const typeId   = Number(window.GM_TYPE_ID);

    let params = {};
    params.role_id = role;

    // ROLE 2 -> Branch Admin
    if (role === 2) {
        params.parent_library_id = libId;   // users under branch
        params.library_id        = libId;   // INCLUDE ADMIN OWN RECORDS

        if ($("#filter-type").val()) {
            params.library_type_id = $("#filter-type").val();
        }

        const q = $("#search-library").val().trim();
        if (q) params.search = q;
    }


    // ROLE 3 -> Library Staff
    else if (role === 3) {
        params.parent_library_id = parentId;
        params.library_id        = libId;
        params.library_type_id   = typeId;
    }

    // HQ / unrestricted
    else {
        if ($("#filter-parent").val())
            params.parent_library_id = $("#filter-parent").val();

        if ($("#filter-library").val())
            params.library_id = $("#filter-library").val();

        if ($("#filter-type").val())
            params.library_type_id = $("#filter-type").val();

        const q = $("#search-library").val().trim();
        if (q) params.search = q;
    }

    if ($("#filter-date-from").val())
      params.date_from = $("#filter-date-from").val();

    if ($("#filter-date-to").val())
        params.date_to = $("#filter-date-to").val();

    return params;
}

/* ---------------------------------------------------------
   LOAD PROGRAM STATUS ANALYTICS
--------------------------------------------------------- */
function loadStatus() {
    const params = buildParams();

    $.ajax({
        url: API + "stats/program-status",
        headers: { "X-API-KEY": KEY },
        data: params,
        success: res => {

            /* ===== OVERALL CARDS ===== */
            $("#val-incomplete").text(res.status.overall.incomplete);
            $("#val-pending").text(res.status.overall.pending);
            $("#val-verified").text(res.status.overall.verified);
            $("#val-rejected").text(res.status.overall.rejected);

            /* ===== TABLE: BY PROGRAM TYPE ===== */
            const tbody = $("#status-by-type-table tbody");
            tbody.empty();

            if (!res.status.by_program_type || res.status.by_program_type.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="5" class="text-center-muted">No data</td>
                    </tr>
                `);
                return;
            }

          // Custom sort:
          // - program_type_id 2 -> 5 in ascending order
          // - program_type_id 1 ALWAYS at bottom
          const sorted = [...res.status.by_program_type].sort((a, b) => {

              // force type 1 to bottom
              if (a.program_type_id === 1) return 1;
              if (b.program_type_id === 1) return -1;

              // normal ascending order for others
              return a.program_type_id - b.program_type_id;
          });

          sorted.forEach(r => {
              tbody.append(`
                  <tr>
                      <td>${r.type_name}</td>
                      <td>${r.incomplete}</td>
                      <td>${r.pending}</td>
                      <td>${r.verified}</td>
                      <td>${r.rejected}</td>
                  </tr>
              `);
          });

        }

    });
}
/* ---------------------------------------------------------
   DATE RESTRICTION
--------------------------------------------------------- */

function applyDateRestrictions() {
    const from = $("#filter-date-from").val();
    const to   = $("#filter-date-to").val();

    // End date cannot be earlier than start date
    if (from) {
        $("#filter-date-to").attr("min", from);

        // Auto-fix invalid selection
        if (to && to < from) {
            $("#filter-date-to").val(from);
        }
    } else {
        $("#filter-date-to").removeAttr("min");
    }
}

/* ---------------------------------------------------------
   INIT
--------------------------------------------------------- */
$(document).ready(function () {

    loadFilters(() => {
        applyRoleFilterRules();
        loadStatus();
        applyDateRestrictions();
    });

    $("#btn-refresh").click(loadStatus);
    $("#search-library").on("input", loadStatus);
    $("#filter-date-from").on("change", function () {
    applyDateRestrictions();
    loadStatus();
    });

    $("#filter-date-to").on("change", loadStatus);
});
