const API = window.GM_API_BASE || "/api/?route=";
const KEY = window.GM_API_KEY;

/* ---------------------------------------------------------
   COLORS
--------------------------------------------------------- */
const palette = [
    "#64b5f6", "#81c784", "#e57373", "#ba68c8",
    "#4db6ac", "#ffb74d", "#9575cd", "#4fc3f7"
];
function getColorFor(name) {
    let hash = 0;
    for (let i = 0; i < name.length; i++)
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return palette[Math.abs(hash) % palette.length];
}

/* ---------------------------------------------------------
   ROLE FILTER LOCKING
--------------------------------------------------------- */
function applyRoleFilterRules() {
    const role     = Number(window.GM_ROLE_ID);
    const libId    = Number(window.GM_LIBRARY_ID);
    const parentId = Number(window.GM_PARENT_ID);
    const typeId   = Number(window.GM_TYPE_ID);

    // ROLE 2 -> Branch Admin
    if (role === 2) {
        $("#filter-parent")
            .val(libId)
            .prop("disabled", true);

        $("#filter-type").prop("disabled", false);
        $("#search-library").prop("disabled", false);
    }

    // ROLE 3 -> Library Staff
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

/* ---------------------------------------------------------
   LOAD FILTERS
--------------------------------------------------------- */
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

/* ---------------------------------------------------------
   HEADER WRAPPER
--------------------------------------------------------- */
function wrapHeader(text) {
    return text.split(" ").map(w => `<div>${w}</div>`).join("");
}

/* ---------------------------------------------------------
   LOAD TARGET GROUP DATA (API)
--------------------------------------------------------- */
function loadTargets() {
    $("#loadingBox").show();

    const role     = Number(window.GM_ROLE_ID);
    const libId    = Number(window.GM_LIBRARY_ID);
    const parentId = Number(window.GM_PARENT_ID);
    const typeId   = Number(window.GM_TYPE_ID);

    let params = {};
    params.role_id = role;
    // ROLE 2 -> Branch Admin
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

    // ROLE 3 -> Library Staff
    else if (role === 3) {
        params.parent_library_id = parentId;
        params.library_id        = libId;
        params.library_type_id   = typeId;
    }

    // SUPER USERS / HQ
    else {
        params.parent_library_id = $("#filter-parent").val();
        params.library_type_id   = $("#filter-type").val();

        const q = $("#search-library").val().trim();
        if (q) params.search = q;
    }

        // Date range (program_start)
    if ($("#filter-date-from").val())
        params.date_from = $("#filter-date-from").val();

    if ($("#filter-date-to").val())
        params.date_to = $("#filter-date-to").val();


    $.ajax({
        url: API + "stats/program-target",
        headers: { "X-API-KEY": KEY },
        data: params,
        success: res => {
            $("#loadingBox").hide();
            renderTable(res.results);
        }
    });
}

/* ---------------------------------------------------------
   RENDER TABLE + SUMMARY
--------------------------------------------------------- */
function renderTable(rows) {

    if ($.fn.DataTable.isDataTable("#targetTable")) {
        $("#targetTable").DataTable().destroy();
    }

    if (!rows || rows.length === 0) {
        $("#table-header-row").html("<th>No data</th>");
        $("#table-body").html("<tr><td>No results found</td></tr>");
        $("#summary-badges").html("");
        return;
    }

    const groups       = [...new Set(rows.map(r => r.group_name))].sort();
    const libraryNames = [...new Set(rows.map(r => r.library_name))];

    let totalPrograms = rows.reduce((a,b)=>a+Number(b.total),0);

    let groupTotals = {};
    groups.forEach(g => groupTotals[g] = 0);
    rows.forEach(r => groupTotals[r.group_name] += Number(r.total));

    let badges = `
        <div class="badge-item badge-accent">
            Total Programs: ${totalPrograms}
        </div>
    `;

    Object.entries(groupTotals).forEach(([g, count]) => {
        badges += `
            <div class="badge-item badge-accent">
                ${g}: ${count}
            </div>`;
    });

    $("#summary-badges").html(badges);

    let hdr = `<th>Library</th>`;
    groups.forEach(g => hdr += `<th class="header-wrap">${wrapHeader(g)}</th>`);
    $("#table-header-row").html(hdr);

    let map = {};
    libraryNames.forEach(lib => {
        map[lib] = {};
        groups.forEach(g => map[lib][g] = 0);
    });

    rows.forEach(r => {
        map[r.library_name][r.group_name] = Number(r.total);
    });

    let body = "";
    libraryNames.forEach(lib => {
        body += `<tr><td>${lib}</td>`;
        groups.forEach(g => body += `<td>${map[lib][g]}</td>`);
        body += `</tr>`;
    });

    $("#table-body").html(body);

    setTimeout(() => {
        $("#targetTable").DataTable({
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
/* ---------------------------------------------------------
   INIT
--------------------------------------------------------- */
$(document).ready(function () {
    loadFilters(() => {
        applyRoleFilterRules();
        loadTargets();
    });

    $("#btn-refresh").click(loadTargets);
    $("#search-library").on("input", loadTargets);
    $("#filter-date-from").on("change", function () {
        applyDateRestrictions();
        loadTargets();
    });
    $("#filter-date-to").on("change", loadTargets);
});
