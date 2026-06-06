const API = window.GM_API_BASE || "/api/?route=";
const KEY = window.GM_API_KEY;

/* ================= ROLE FILTER RULES ================= */
function applyRoleFilterRules() {
    const role     = Number(window.GM_ROLE_ID);
    const libId    = Number(window.GM_LIBRARY_ID);
    const parentId = Number(window.GM_PARENT_ID);
    const typeId   = Number(window.GM_TYPE_ID);

    // Branch Admin
    if (role === 2) {
        $("#filter-parent").val(libId).prop("disabled", true);
        $("#filter-library").prop("disabled", false);
        $("#filter-type option[value='1']").hide();
    }


    // Library Staff
    if (role === 3) {
        $("#filter-parent").closest("select").hide();
        $("#filter-library").closest("select").hide();
        $("#filter-type").closest("select").hide();
    }

}

/* ================= LOAD FILTER OPTIONS ================= */
function loadFilters(callback) {

    const role  = Number(window.GM_ROLE_ID);
    const myLib = Number(window.GM_LIBRARY_ID);

    let url;

    // Decide endpoint by role
    if (role === 1) {
        // HQ -> everything
        url = API + "libraries/get_all";
    }
    else if (role === 2) {
        // Branch Admin -> own branch + children ONLY
        url = API + "libraries/get_children&parent_id=" + myLib;
    }
    else {
        // Staff -> own library only (still returned by get_children)
        url = API + "libraries/get_children&parent_id=" + myLib;
    }

    $.ajax({
        url: url,
        headers: { "X-API-KEY": KEY },
        success: res => {

            const parentSel = $("#filter-parent")
                .empty()
                .append(`<option value="">Parent Library</option>`);

            const libSel = $("#filter-library")
                .empty()
                .append(`<option value="">Library</option>`);

            res.libraries.forEach(l => {

                // Parent dropdown (only real parents)
                if (l.parent_id === null) {
                    parentSel.append(
                        `<option value="${l.id}">${l.name}</option>`
                    );
                }

                // Library dropdown (already filtered by backend)
                libSel.append(
                    `<option value="${l.id}">${l.name}</option>`
                );
            });

            if (callback) callback();
        }
    });

    // ---- Library Types (UNCHANGED) ----
    $.ajax({
        url: API + "library_types/list",
        headers: { "X-API-KEY": KEY },
        success: res => {
            const t = $("#filter-type")
                .empty()
                .append(`<option value="">Library Type</option>`);

            res.types.forEach(tt => {
                t.append(`<option value="${tt.id}">${tt.type_name}</option>`);
            });

            // Hide type 1 for Branch Admin
            if (Number(window.GM_ROLE_ID) === 2) {
                $("#filter-type option[value='1']").remove();
            }
        }
    });
}




/* ================= DATE RESTRICTION ================= */
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

/* ================= INIT ================= */
$(document).ready(function () {

    loadFilters(() => {
        applyRoleFilterRules();
        applyDateRestrictions();
    });

    $("#filter-date-from").on("change", applyDateRestrictions);
});
