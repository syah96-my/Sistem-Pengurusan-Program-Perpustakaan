document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    const API_KEY = window.GM_API_KEY;
    const API_BASE = window.GM_API_BASE || "/api/?route=";
    const PARENT_ID = window.GM_LIBRARY_ID;

    const dtInstances = {};
    const TYPES = [];

    function safeLog(...a){ if (window.GM_DEBUG) console.log("[CHILD]", ...a); }
    function safeErr(...a){ console.error("[CHILD]", ...a); }
    function el(id){ return document.getElementById(id); }

    // ============================================
    // LOAD LIBRARY TYPES
    // ============================================
    async function loadLibraryTypes() {
        try {
            const res = await fetch(API_BASE + "library_types/list", {
                headers: { "X-API-KEY": API_KEY }
            });
            const js = await res.json();
            return js.types || js.library_types || [];
        } catch (e) {
            safeErr("Failed to load types", e);
            return [];
        }
    }

    // ============================================
    // BUILD TABS + TABLES
    // ============================================
    function buildTabs(types) {
        types.sort((a, b) => Number(a.id) - Number(b.id));
        const filteredTypes = types.filter(t => Number(t.id) > 2);
        const tabs = el("tabs");
        const contents = el("tab-contents");

        tabs.innerHTML = "";
        contents.innerHTML = "";

        filteredTypes.forEach(t => {
            const typeId = t.id;
            const name = t.type_name;

            // Tab button
            const btn = document.createElement("button");
            btn.className = "tab-button";
            btn.dataset.typeid = typeId;
            btn.textContent = name;
            btn.addEventListener("click", onTabClick);
            tabs.appendChild(btn);

            // Tab content container
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
                            <th>Name</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            `;
            contents.appendChild(pane);

            // Populate modal dropdown
              const ddl = el("child-type-id");
            
            // Add default option once
            if (!ddl.dataset.initialized) {
                ddl.innerHTML = '<option value="">Please Select</option>';
                ddl.dataset.initialized = "1";
            }
            
            if (Number(typeId) > 2) {
                const opt = document.createElement("option");
                opt.value = typeId;
                opt.textContent = name;
                ddl.appendChild(opt);
            }
        });

        // Activate first tab
        const firstBtn = tabs.querySelector(".tab-button");
        if (firstBtn) firstBtn.click();
    }

    // ============================================
    // ON TAB CLICK
    // ============================================
    function onTabClick(e) {
        const btn = e.currentTarget;
        const typeId = btn.dataset.typeid;

        document.querySelectorAll(".tab-button").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        document.querySelectorAll(".tab-pane").forEach(p => p.classList.remove("active"));
        el("tab-type-" + typeId).classList.add("active");

        const tableId = "#table-type-" + typeId;

        if (!dtInstances[tableId]) {
            createDataTable(tableId, typeId);
        } else {
            dtInstances[tableId].ajax.reload(null, false);
        }
    }

    // ============================================
    // CREATE DATATABLE
    // ============================================
    function createDataTable(selector, typeId) {
        dtInstances[selector] = $(selector).DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            ajax: {
                url: API_BASE + "child/datatables",
                type: "GET",
                headers: { "X-API-KEY": API_KEY },
                data: function (d) {
                    d.parent_id = PARENT_ID;
                    d.type_id = typeId;

                    const status = $(selector)
                        .closest(".tab-pane")
                        .find(".tag-filter.active")
                        .data("status") || "";

                    d.status = status;
                },
                dataSrc: json => json.data || []
            },
            columns: [
                { data: "id" },
                { data: "name" },
                { data: "address" },
                { data: "status" },
                {
                    data: null,
                    orderable: false,
                    render: row => `
                        <button class="btn-icon btn-edit" data-id="${row.id}" title="Edit">Edit</button>
                
                        <button class="btn-icon btn-${row.status === 'active' ? 'deactivate' : 'activate'}" data-id="${row.id}">
                            ${row.status === "active" ? "Active" : "Inactive"}
                        </button>
                    `
                }

            ]
        });
    }

    // ============================================
    // TAG FILTERS
    // ============================================
    $(document).on("click", ".tag-filter", function () {
        const row = $(this).closest(".filter-tags");
        row.find(".tag-filter").removeClass("active");
        $(this).addClass("active");

        const table = $(this).closest(".tab-pane").find("table");
        const tableId = "#" + table.attr("id");
        dtInstances[tableId].ajax.reload(null, false);
    });

    // ============================================
    // INIT PAGE
    // ============================================
    (async function init() {
        const types = await loadLibraryTypes();
        TYPES.push(...types);
        buildTabs(types);
    })();

    // ============================================
    // OPEN MODAL FOR CREATE
    // ============================================
    el("add-btn").addEventListener("click", () => {
        el("child-form").reset();
        el("child-id").value = "";
        el("child-modal-title").textContent = "New Branch Library";
        el("child-modal").classList.add("show");
    });

    el("child-cancel-btn").addEventListener("click", () =>
        el("child-modal").classList.remove("show")
    );

    // ============================================
    // SUBMIT CREATE / UPDATE
    // ============================================
    $("#child-form").on("submit", function (ev) {
        ev.preventDefault();

        const id = el("child-id").value;
        const payload = {
            name: el("child-name").value.trim(),
            address: el("child-address").value.trim(),
            type_id: el("child-type-id").value,
            parent_id: PARENT_ID
        };

        if (!payload.name || !payload.type_id) {
            return Swal.fire("Error", "Name and type are required", "error");
        }

        // CREATE
        if (!id) {
            fetch(API_BASE + "child/create", {
                method: "POST",
                headers: {
                    "X-API-KEY": API_KEY,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(payload)
            })
                .then(r => r.json())
                .then(js => {
                    if (js.success) {
                        Swal.fire("Success", "Child created", "success");
                        el("child-modal").classList.remove("show");

                        const tableId = "#table-type-" + payload.type_id;
                        dtInstances[tableId].ajax.reload();
                    } else {
                        Swal.fire("Error", js.error || "Failed", "error");
                    }
                })
                .catch(err => safeErr(err));
        }
        // UPDATE
        else {
            const form = $.param({
                id,
                name: payload.name,
                address: payload.address,
                type_id: payload.type_id
            });

            $.ajax({
                url: API_BASE + "child/update",
                method: "PUT",
                headers: { "X-API-KEY": API_KEY },
                data: form,
                success: function (res) {
                    if (res.success) {
                        Swal.fire("Updated", "Child updated", "success");
                        el("child-modal").classList.remove("show");
                        dtInstances["#table-type-" + payload.type_id].ajax.reload();
                    } else {
                        Swal.fire("Error", res.error || "Failed", "error");
                    }
                }
            });
        }
    });

    // ============================================
    // EDIT BUTTON
    // ============================================
    $(document).on("click", ".btn-edit", function () {
        const id = $(this).data("id");

        $.ajax({
            url: API_BASE + "child/list",
            headers: { "X-API-KEY": API_KEY },
            data: { parent_id: PARENT_ID },
            success: function (res) {
                const found = res.children.find(c => c.id == id);

                if (!found)
                    return Swal.fire("Error", "Record not found", "error");

                el("child-id").value = found.id;
                el("child-name").value = found.name;
                el("child-address").value = found.address;
                el("child-type-id").value = found.type_id;

                el("child-modal-title").textContent = "Edit Child";
                el("child-modal").classList.add("show");
            }
        });
    });

    // ============================================
    // ACTIVATE / DEACTIVATE
    // ============================================
    $(document).on("click", ".btn-activate, .btn-deactivate", function () {
        const id = $(this).data("id");
        const isActive = $(this).hasClass("btn-activate");

        $.ajax({
            url: API_BASE + "child/" + (isActive ? "activate" : "deactivate"),
            method: "PUT",
            headers: { "X-API-KEY": API_KEY },
            data: $.param({ id }),
            success: res => {
                if (res.success) {
                    Swal.fire("Updated", res.message, "success");
                    Object.values(dtInstances).forEach(dt => dt.ajax.reload(null, false));
                }
            }
        });
    });

    // ============================================
    // BULK IMPORT
    // ============================================
// ---------- Bulk import (fixed) ----------
el("bulk-import-btn").addEventListener("click", () => {
    el("bulk-form").reset();
    el("bulk-results").innerHTML = "";
    el("bulk-modal").classList.add("show");
});

el("bulk-cancel-btn").addEventListener("click", () =>
    el("bulk-modal").classList.remove("show")
);

// CSV parser for name,address (handles quoted values minimally)
function parseCSVNameAddress(text) {
    // split into non-empty lines
    const lines = text.split(/\r\n/).map(l => l.trim()).filter(Boolean);

    const rows = lines.map((line, idx) => {
        // basic CSV splitting supporting simple quoted values
        // (won't handle every edge-case but safe for normal files).
        // If you need fully-compliant CSV, use a small parser lib.
        const parts = [];
        let cur = "";
        let inQuotes = false;
        for (let i = 0; i < line.length; i++) {
            const ch = line[i];
            if (ch === '"' ) {
                // lookahead for escaped quote
                if (inQuotes && line[i+1] === '"') {
                    cur += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (ch === ',' && !inQuotes) {
                parts.push(cur);
                cur = "";
            } else {
                cur += ch;
            }
        }
        parts.push(cur);
        const name = (parts[0] || "").trim();
        const address = (parts[1] || "").trim();

        return {
            row: idx + 1,
            name,
            address
        };
    });

    return rows;
}

$("#bulk-form").on("submit", function (ev) {
    ev.preventDefault();

    try {
        const file = el("bulk-file").files[0];
        const selectedTypeId = el("bulk-type-id").value;
        const parentId = el("bulk-parent-id").value || PARENT_ID;

        if (!file) return Swal.fire("Error", "Please select a CSV file.", "error");
        if (!selectedTypeId) return Swal.fire("Error", "Please select a Child Library Type.", "error");

        const reader = new FileReader();

        reader.onload = async function (e) {
            try {
                const text = e.target.result;
                const rows = parseCSVNameAddress(text);
                
                // --- OPTIONAL HEADER SUPPORT (name,address) ---
                if (rows.length > 0) {
                    const first = rows[0];
                    const n = first.name.toLowerCase().trim();
                    const a = first.address.toLowerCase().trim();
                
                    // if header detected, remove it
                    if ((n === "name" || n === "nama") && (a === "address" || a === "alamat")) {
                        rows.shift();
                    }
                }


                if (rows.length === 0) {
                    return Swal.fire("Error", "CSV contains no valid rows.", "error");
                }

                // validate rows
                const errors = [];
                const children = [];

                rows.forEach(r => {
                    if (!r.name) {
                        errors.push({ row: r.row, error: "Missing name" });
                        return;
                    }
                    // address may be empty - allowed
                    children.push({
                        name: r.name,
                        address: r.address,
                        type_id: selectedTypeId
                    });
                });

                if (errors.length > 0) {
                    // build downloadable CSV with errors column
                    let csv = "row,name,address,error\n";
                    rows.forEach(r => {
                        const err = errors.find(e => e.row === r.row);
                        csv += `${r.row},"${(r.name || "").replace(/"/g, '""')}","${(r.address || "").replace(/"/g, '""')}","${err ? err.error : ""}"\n`;
                    });

                    const blob = new Blob([csv], { type: "text/csv" });
                    const url = URL.createObjectURL(blob);

                    el("bulk-results").innerHTML = `
                        <div class="text-danger">Some rows invalid. Download, fix and re-upload.</div>
                        <a href="${url}" download="bulk_child_errors.csv" class="btn btn-danger">Download Error CSV</a>
                    `;
                    return;
                }

                // Build payload
                const finalPayload = {
                    parent_id: parentId,
                    libraries: children
                };

                // Send to API
                const res = await fetch(API_BASE + "child/bulk_import", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-API-KEY": API_KEY
                    },
                    body: JSON.stringify(finalPayload)
                });

                if (!res.ok) {
                    const txt = await res.text();
                    console.error("Server returned non-OK:", res.status, txt);
                    return Swal.fire("Error", "Server error during upload. See console for details.", "error");
                }

                const js = await res.json();
                
                el("bulk-results").innerHTML = `
                    <p><strong>Inserted:</strong> ${js.inserted || 0}</p>
                    <p><strong>Skipped:</strong> ${js.skipped || 0}</p>
                `;
                
                if (js.errors && js.errors.length) {
                    el("bulk-results").innerHTML += `
                        <div class="text-danger">
                            <strong>Errors:</strong><br>
                            ${js.errors.map(e => `Row ${e.row}: ${e.error}`).join("<br>")}
                        </div>
                    `;
                } else {
                    // SUCCESS - close modal + show alert
                    el("bulk-modal").classList.remove("show");
                    Swal.fire("Success", "Bulk import completed successfully!", "success");
                }


                // reload all datatables
                Object.values(dtInstances).forEach(dt => dt.ajax.reload(null, false));

            } catch (innerErr) {
                console.error("Error processing CSV:", innerErr);
                Swal.fire("Error", "Unexpected error while processing CSV. Check console.", "error");
            }
        };

        reader.onerror = function (err) {
            console.error("FileReader error:", err);
            Swal.fire("Error", "Failed to read file.", "error");
        };

        reader.readAsText(file, "UTF-8");
    } catch (err) {
        console.error("Bulk form submit error:", err);
        Swal.fire("Error", "Unexpected error. Check console for details.", "error");
    }
});

});
