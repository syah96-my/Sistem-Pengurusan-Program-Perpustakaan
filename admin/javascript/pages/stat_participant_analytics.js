const API = window.GM_API_BASE || "/api/?route=";
const KEY = window.GM_API_KEY;

const COLORS = {
    total: "#1976d2",
    physical: "#2e7d32",
    online: "#0288d1",
    self: "#8e24aa",
    staff: "#ef6c00"
};

// ============================
// ROLE FILTER LOCKING
// ============================
function applyRoleFilterRules() {
    const role = Number(window.GM_ROLE_ID);
    const libId = Number(window.GM_LIBRARY_ID);     // branch library id
    const parentId = Number(window.GM_PARENT_ID);   // staff parent id
    const typeId = Number(window.GM_TYPE_ID);

    // ROLE 2 -> BRANCH ADMIN
    // Branch admin's own library is the parent
    if (role === 2) {
        $("#filter-parent")
            .val(libId)           // FIX - Correct parent selection
            .prop("disabled", true);

        $("#filter-type").prop("disabled", false);
        $("#search-library").prop("disabled", false);
    }

    // ROLE 3 -> LIBRARY STAFF
    else if (role === 3) {
        $("#filter-parent")
            .val(parentId)
            .prop("disabled", true);

        $("#filter-type")
            .val(typeId)
            .prop("disabled", true);

        $("#search-library").prop("disabled", true);
    }
}

// ============================
// LOAD FILTER LISTS
// ============================
function loadFilters(callback) {
    const headers = { "X-API-KEY": KEY };
    let pending = 2;
    const done = () => (--pending === 0 && callback());

    $.ajax({ url: API + "library_types/list", headers })
      .done(res => {
          const el = $("#filter-type").empty().append(`<option value="">Library Type</option>`);
          res.types.forEach(t => el.append(`<option value="${t.id}">${t.type_name}</option>`));
      }).always(done);

    $.ajax({ url: API + "libraries/list", headers })
      .done(res => {
          const parentSel = $("#filter-parent").empty().append(`<option value="">Parent Library</option>`);
          res.libraries.forEach(lib => {
              if (lib.parent_id === null)
                  parentSel.append(`<option value="${lib.id}">${lib.name}</option>`);
          });
      }).always(done);
}

// ============================
// LOAD PARTICIPANT STATS
// ============================
function loadParticipants() {
    $("#loadingBox").show();

    const role = Number(window.GM_ROLE_ID);
    const libId = Number(window.GM_LIBRARY_ID);
    const parentId = Number(window.GM_PARENT_ID);
    const typeId = Number(window.GM_TYPE_ID);

    let params = {};
    params.role_id = role;

    // ROLE 2 -> BRANCH ADMIN
    if (role === 2) {

        // include own library + branch libraries
        params.parent_library_id = libId;
        params.library_id        = libId;
        $("#filter-type option[value='1']").hide();
        if ($("#filter-type").val())
            params.library_type_id = $("#filter-type").val();

        const q = $("#search-library").val().trim();
        if (q) params.search = q;
    }


    // ROLE 3 -> LIBRARY STAFF
    else if (role === 3) {
        params.library_id = libId;              // 89
        params.parent_library_id = parentId;    // 13 (MUST include)
        params.library_type_id = typeId;   
    }

    // FULL ACCESS ROLES
    else {
        params.parent_library_id = $("#filter-parent").val();
        params.library_type_id = $("#filter-type").val();

        const q = $("#search-library").val().trim();
        if (q) params.search = q;
    }

        // Date range (program_start)
    if ($("#filter-date-from").val())
        params.date_from = $("#filter-date-from").val();

    if ($("#filter-date-to").val())
        params.date_to = $("#filter-date-to").val();

    $.ajax({
        url: API + "stats/participants",
        headers: { "X-API-KEY": KEY },
        data: params,
        success: res => {
            $("#loadingBox").hide();
            renderTable(res.results);
        }
    });
}

// ============================
// RENDER TABLE
// ============================
function renderTable(rows) {

    if ($.fn.DataTable.isDataTable("#partTable")) {
        $("#partTable").DataTable().destroy();
    }

    if (!rows || rows.length === 0) {
        $("#table-body").html("<tr><td colspan='6'>No data found</td></tr>");
        $("#summary-badges").html("");
        return;
    }

    let totals = {
        total: 0,
        physical: 0,
        online: 0,
        self: 0,
        staff: 0
    };

    let body = "";

    rows.forEach(r => {
        const t = Number(r.total);
        const ph = Number(r.physical);
        const on = Number(r.online);
        const sr = Number(r.self_registered);
        const su = Number(r.staff_uploaded);

        totals.total += t;
        totals.physical += ph;
        totals.online += on;
        totals.self += sr;
        totals.staff += su;

        body += `
            <tr>
                <td>${r.library_name}</td>
                <td>${t}</td>
                <td>${ph}</td>
                <td>${on}</td>
                <td>${sr}</td>
                <td>${su}</td>
            </tr>
        `;
    });

    $("#table-body").html(body);

    $("#summary-badges").html(`
        <div class="badge-item badge-total">Total: ${totals.total}</div>
        <div class="badge-item badge-physical">Physical: ${totals.physical}</div>
        <div class="badge-item badge-online">Online: ${totals.online}</div>
        <div class="badge-item badge-self">Self Registered: ${totals.self}</div>
        <div class="badge-item badge-staff">Staff Uploaded: ${totals.staff}</div>
    `);

    setTimeout(() => {
        $("#partTable").DataTable({
            paging: true,
            pageLength: 20,
            lengthMenu: [10, 20, 25, 50, 100],
            searching: false,
            ordering: true,
            autoWidth: false,
            scrollX: false,
            columnDefs: [
                { targets: 0, className: "dt-left" },
                { targets: "_all", className: "dt-center" }
            ]
        });
    }, 10);
}

function applyDateRestrictions() {
    const from = $("#filter-date-from").val();
    const to   = $("#filter-date-to").val();

    if (from) {
        $("#filter-date-to").attr("min", from);
        if (to && to < from) {
            $("#filter-date-to").val(from);
        }
    } else {
        $("#filter-date-to").removeAttr("min");
    }
}


// ============================
// INITIALIZE PAGE
// ============================
$(document).ready(function () {
    loadFilters(() => {
        applyRoleFilterRules();
        loadParticipants();
    });

    $("#btn-refresh").click(loadParticipants);
    $("#search-library").on("input", loadParticipants);
    $("#filter-date-from").on("change", function () {
        applyDateRestrictions();
        loadParticipants();
    });
    $("#filter-date-to").on("change", loadParticipants);
});
