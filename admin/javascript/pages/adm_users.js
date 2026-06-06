let CURRENT_TYPE_ID = null;
document.addEventListener("DOMContentLoaded", async function () {

    const API_KEY = window.GM_API_KEY;
    const API_BASE = window.GM_API_BASE || "/api/?route=";

    const dtInstances = {};
    let ALL_LIBRARIES = [];

    // =======================================================
    // PAREENT LIBRARY PERMISSION
    // =======================================================

    function applyParentLibraryPermission() {
        const isHQ = Number(window.GM_USER_ID) === 1;

        const parentSelect = document.getElementById("filter-parent");

        if (!parentSelect) return;

        if (isHQ) {
            // HQ: full control
            parentSelect.disabled = false;
            parentSelect.style.display = "";
        } else {
            // Non-HQ: force parent
            parentSelect.value = window.GM_LIBRARY_ID;
            parentSelect.disabled = true;

            // Optional: hide instead of disable
            // parentSelect.closest("div").style.display = "none";
        }
    }
    // =======================================================
    // ROLE PERMISSION
    // =======================================================
    function applyRolePermission() {
        const isHQ = Number(window.GM_USER_ID) === 1;
        const roleSelect = document.getElementById("user-role-id");

        if (!roleSelect) return;

        if (isHQ) {
            // HQ: can choose Admin or User
            roleSelect.disabled = false;
            roleSelect.style.display = "";
        } else {
            // Non-HQ: force User role
            roleSelect.value = 3; // User
            roleSelect.disabled = true;

            // Optional: hide instead of disable
            // roleSelect.closest("div").style.display = "none";
        }
    }


    function applyRolePermissionForEdit(userRoleId) {
        const isGod = Number(window.GM_USER_ID) === 1;
        const roleSelect = document.getElementById("user-role-id");
    
        if (!roleSelect) return;
    
        // Reset options visibility
        [...roleSelect.options].forEach(opt => opt.hidden = false);
    
        if (isGod) {
            // Super admin: full control
            roleSelect.value = userRoleId;
            roleSelect.disabled = false;
            return;
        }
    
        // Password Non-GOD: allow ONLY role 2 & 3
        [...roleSelect.options].forEach(opt => {
            const val = Number(opt.value);
            if (val !== 2 && val !== 3) {
                opt.hidden = true;
            }
        });
    
        // Force role to User (3)
        roleSelect.value = 3;
        roleSelect.disabled = true;
    }

    // =======================================================
    // LOAD ALL LIBRARIES (FOR FILTERING)
    // =======================================================
    async function loadAllLibraries() {
        const res = await fetch(API_BASE + "libraries/get_all", {
            headers: { "X-API-KEY": API_KEY }
        });
        const js = await res.json();
        ALL_LIBRARIES = js.libraries || [];
    }

    // =======================================================
    // REFRESH LIBRARY LIST BASED ON FILTERS
    // =======================================================
    function refreshLibraryDropdown() {
        const parentFilter = document.getElementById("filter-parent").value || "";
        const typeFilter = document.getElementById("filter-type").value || "";
        const ddl = document.getElementById("user-library-id");

        ddl.innerHTML = '<option value="">Please Select</option>';

        ALL_LIBRARIES.forEach(lib => {
            if (parentFilter && lib.parent_id != parentFilter) return;
            if (typeFilter && lib.type_id != typeFilter) return;

            ddl.appendChild(new Option(lib.name, lib.id));
        });
    }

    // =======================================================
    async function loadLibraryTypes() {
        const res = await fetch(API_BASE + "library_types/list", {
            headers: { "X-API-KEY": API_KEY }
        });
        const js = await res.json();
        return js.types || [];
    }

    // =======================================================
    function buildTabs(types) {
        const tabs = document.getElementById("tabs");
        const contents = document.getElementById("tab-contents");
        const isHQ = Number(window.GM_USER_ID) === 1;
        tabs.innerHTML = "";
        contents.innerHTML = "";

        types.forEach(t => {
            const typeId = t.id;
            if (!isHQ && (typeId == 1 || typeId == 2)) return;
            const name = t.type_name;

            const btn = document.createElement("button");
            btn.className = "tab-button";
            btn.dataset.typeid = typeId;
            btn.textContent = name;
            btn.addEventListener("click", onTabClick);
            tabs.appendChild(btn);

            const pane = document.createElement("div");
            pane.className = "tab-pane";
            pane.id = "tab-type-" + typeId;

            pane.innerHTML = `
                <div class="filter-tags">
                    <button class="tag-filter active" data-status="">All</button>
                    <button class="tag-filter" data-status="active">Active</button>
                    <button class="tag-filter" data-status="inactive">Inactive</button>
                </div>
                <table class="data-table" id="table-type-${typeId}" width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Library</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            `;
            contents.appendChild(pane);
        });

        tabs.querySelector(".tab-button").click();
    }

    // =======================================================
    function onTabClick(e) {
        const btn = e.currentTarget;
        const typeId = btn.dataset.typeid;
        CURRENT_TYPE_ID = typeId;
        document.querySelectorAll(".tab-button").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        document.querySelectorAll(".tab-pane").forEach(p => p.classList.remove("active"));
        document.getElementById("tab-type-" + typeId).classList.add("active");

        const tableId = "#table-type-" + typeId;

        if (!dtInstances[tableId]) {
            createUsersTable(tableId, typeId);
        } else {
            dtInstances[tableId].ajax.reload(null, false);
        }
    }

    // =======================================================
    function createUsersTable(selector, typeId) {
        dtInstances[selector] = $(selector).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: API_BASE + "users/datatables",
                type: "GET",
                headers: { "X-API-KEY": API_KEY },
                data: function (d) {
                    d.parent_id = window.GM_LIBRARY_ID;
                    d.type_id = typeId;
                    d.status = $(selector)
                        .closest(".tab-pane")
                        .find(".tag-filter.active")
                        .data("status") || "";
                }
            },
            columns: [
                { data: "id" },
                { data: "username" },
                { data: "library_name" },
                { data: "role_name" },
                { data: "status" },
                {
                    data: null,
                    orderable: false,
                    render: row => {
                        // Password System user (ID = 1): edit + reset only
                        if (Number(row.id) === 1) {
                            return `
                                <div class="action-buttons">
                                    <button class="btn-icon btn-edit" data-id="${row.id}">Edit</button>
                                    <button class="btn-icon btn-reset" data-id="${row.id}">Reset</button>
                                </div>
                            `;
                        }
                
                        // Normal users
                        return `
                            <div class="action-buttons">
                                <button class="btn-icon btn-edit" data-id="${row.id}">Edit</button>
                
                                <button class="btn-icon ${row.status === 'active' ? 'btn-deactivate' : 'btn-activate'}"
                                        data-id="${row.id}">
                                    ${row.status === 'active' ? 'Deactivate' : 'Activate'}
                                </button>
                
                                <button class="btn-icon btn-reset" data-id="${row.id}">Reset</button>
                            </div>
                        `;
                    }
                }
            ]
        });
    }

    // =======================================================
    // INITIAL LOAD
    await loadAllLibraries();
    applyParentLibraryPermission();
    applyRolePermission();
    buildTabs(await loadLibraryTypes());

    // =======================================================
    // NEW USER ACTION
    document.getElementById("add-btn").onclick = () => {
        IS_EDITING = false;
        document.getElementById("user-form").reset();
        document.getElementById("user-id").value = "";
        document.getElementById("user-modal-title").textContent = "New User";
    
        // Force parent library only
        applyParentLibraryPermission();
        applyRolePermission();
        document.getElementById("filter-type").value = "";
        refreshLibraryDropdown();
    
        // Refresh library dropdown based on parent/type
        refreshLibraryDropdown();
    
        // Library is selectable (NOT forced)
        $("#user-library-id").prop("disabled", false);
    
        // Force role to 3
        applyRolePermission();
    
        $("#user-modal").addClass("show");
    };



    document.getElementById("user-cancel-btn").onclick = () => {
        IS_EDITING = false;
        document.getElementById("user-modal").classList.remove("show");
    };


    // =======================================================
    // USER FORM SAVE - WITH ERROR ALERT
    // =======================================================
    $("#user-form").on("submit", async function (e) {
        e.preventDefault();
        const id = $("#user-id").val();
        const email = $("#user-username").val().trim();

        const emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;

        if (!emailRegex.test(email)) {
            Swal.fire(
                "Invalid Email",
                "Username must be a valid email address",
                "error"
            );
            return;
        }
    
        const payload = {
            username: $("#user-username").val(),
            library_id: $("#user-library-id").val(),
            role_id: $("#user-role-id").val()
        };
        if (id) payload.id = id;
    
        const url = API_BASE + (id ? "users/update" : "users/create");
        const method = id ? "PUT" : "POST";
    
        const res = await fetch(url, {
            method,
            headers: { "X-API-KEY": API_KEY, "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });
    
        const js = await res.json();
    
        // If backend returns error -> show SweetAlert
        if (!js.success) {
            Swal.fire("Error", js.error || "Something went wrong", "error");
            return;
        }
    
        // Success
        Swal.fire("Success", "Saved", "success");
        $("#user-modal").removeClass("show");
        Object.values(dtInstances).forEach(dt => dt.ajax.reload());
    });

    // =======================================================
    // EDIT USER - FIXED
    // =======================================================
$(document).on("click", ".btn-edit", function () {
    const id = $(this).data("id");

    $.ajax({
        url: API_BASE + "users/get",
        method: "GET",
        headers: { "X-API-KEY": API_KEY },
        data: { id },
        success: function (res) {
            const u = res.user;

            IS_EDITING = true;

            // 1. SET PARENT FROM USER (NOT GM_LIBRARY_ID)
            $("#filter-parent")
                .val(String(u.parent_id))
                .prop("disabled", true);

            // 2. SET TYPE
            $("#filter-type")
                .val(String(u.type_id));

            // 3. BUILD LIBRARY LIST
            refreshLibraryDropdown();

            // 4. SELECT LIBRARY
            $("#user-library-id")
                .val(String(u.library_id))
                .prop("disabled", Number(u.id) === 1);

            // 5. BASIC FIELDS
            $("#user-id").val(u.id);
            $("#user-username").val(u.username);

            // 6. ROLE
            applyRolePermissionForEdit(u.role_id);

            $("#user-modal-title").text("Edit User");
            $("#user-modal").addClass("show");
        }
    });
});






    // =======================================================
    // ACTIVATE / DEACTIVATE USER
    $(document).on("click", ".btn-activate, .btn-deactivate", function () {
        const id = $(this).data("id");
        const api = $(this).hasClass("btn-activate")
            ? "users/activate"
            : "users/deactivate";

        fetch(API_BASE + api, {
            method: "PUT",
            headers: { "X-API-KEY": API_KEY, "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        })
            .then(r => r.json())
            .then(js => {
                Swal.fire("Updated", js.message, "success");
                Object.values(dtInstances).forEach(dt => dt.ajax.reload(null, false));
            });
    });

    // =======================================================
    // RESET PASSWORD
    $(document).on("click", ".btn-reset", function () {
        const id = $(this).data("id");

        fetch(API_BASE + "users/reset_password", {
            method: "PUT",
            headers: { "X-API-KEY": API_KEY, "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        })
            .then(r => r.json())
            .then(js => Swal.fire("Done", "Password reset to the default password", "success"));
    });

    // =======================================================
    // DELETE USER
    $(document).on("click", ".btn-delete", function () {
        const id = $(this).data("id");

        Swal.fire({
            title: "Delete user",
            icon: "warning",
            showCancelButton: true
        }).then(r => {
            if (!r.isConfirmed) return;

            fetch(API_BASE + "users/delete", {
                method: "DELETE",
                headers: { "X-API-KEY": API_KEY, "Content-Type": "application/json" },
                body: JSON.stringify({ id })
            })
                .then(r => r.json())
                .then(js => {
                    if (js.success) {
                        Swal.fire("Deleted", "", "success");
                        Object.values(dtInstances).forEach(dt => dt.ajax.reload());
                    }
                });
        });
    });

    // =======================================================
    // LIBRARY FILTER CHANGE EVENTS (IMPORTANT)
    // =======================================================
document.getElementById("filter-parent").addEventListener("change", function () {
    refreshLibraryDropdown();

    if (!IS_EDITING) {
        $("#user-library-id").val("");
    }
});

document.getElementById("filter-type").addEventListener("change", function () {
    refreshLibraryDropdown();

    if (!IS_EDITING) {
        $("#user-library-id").val("");
    }
});


    // =======================================================
    // BULK IMPORT USERS
    // =======================================================
    $("#bulk-import-btn").on("click", () => {
        $("#bulk-modal").addClass("show");
        $("#bulk-results").html("");
    });

    $("#bulk-cancel-btn").on("click", () =>
        $("#bulk-modal").removeClass("show")
    );

    $("#bulk-form").on("submit", async function (ev) {
        ev.preventDefault();

        const file = $("#bulk-file")[0].files[0];
        if (!file) return Swal.fire("Error", "Choose CSV first", "error");

        const text = await file.text();
        const lines = text.split(/\r\n/).filter(x => x.trim());

        const users = [];
        const errors = [];

        lines.forEach((line, index) => {
            if (index === 0) return;
            const row = index + 1;
            const parts = line.split(",").map(x => x.trim());

            const username = parts[0];

            const emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
            if (!emailRegex.test(username)) {
                errors.push(`Row ${row}: Invalid email format`);
                return;
            }
            const library_id = parts[1];
            const role_id = parts[2] || 3;
            const status = parts[3] || "active";

            if (!username) {
                errors.push(`Row ${row}: Missing username`);
                return;
            }
            if (!library_id || isNaN(parseInt(library_id))) {
                errors.push(`Row ${row}: Invalid library_id`);
                return;
            }

            users.push({
                username,
                library_id: parseInt(library_id),
                role_id: parseInt(role_id),
                status
            });
        });

        if (errors.length > 0) {
            $("#bulk-results").html(`
                <div class="text-danger">
                    <strong>Import Errors:</strong><br>
                    ${errors.join("<br>")}
                </div>
            `);
            return;
        }

        const res = await fetch(API_BASE + "users/bulk_import", {
            method: "POST",
            headers: {
                "X-API-KEY": API_KEY,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ users })
        });

        const js = await res.json();

        if (js.success && (!js.errors || js.errors.length === 0)) {
            Swal.fire("Success", "Bulk user import completed!", "success");
        }

        $("#bulk-results").html(`
            <p><strong>Inserted:</strong> ${js.inserted}</p>
            <p><strong>Skipped:</strong> ${js.skipped}</p>

            ${
                js.errors.length
                    ? `<p class="text-danger"><strong>API Errors:</strong><br>
                       ${js.errors.map(e => `Row ${e.row}: ${e.error}`).join("<br>")}
                       </p>`
                    : ""
            }
        `);

        Object.values(dtInstances).forEach(dt => dt.ajax.reload());
    });

});
