document.addEventListener("DOMContentLoaded", function(){
    "use strict";

    const API_KEY = window.GM_API_KEY;
    const BASE = window.GM_API_BASE || "/api/?route=";

    // DOM refs
    const tbody = document.getElementById("library-tbody");
    const modal = document.getElementById("library-modal");
    const form = document.getElementById("library-form");
    const modalTitle = document.getElementById("library-modal-title");
    const idInput = document.getElementById("library-id");
    const nameInput = document.getElementById("library-name");
    const typeSelect = document.getElementById("library-type");
    const addressInput = document.getElementById("library-address");
    const saveBtn = document.getElementById("library-save-btn");

    const btnAdd = document.getElementById("add-btn");
    const btnClose = document.getElementById("library-modal-close");
    const btnCancel = document.getElementById("library-cancel-btn");

    let TYPE_MAP = {}; // id -> type_name

    function show(){ modal.classList.add("show"); }
    function hide(){ modal.classList.remove("show"); }

    btnAdd.onclick = openCreate;
    btnClose.onclick = hide;
    btnCancel.onclick = hide;
    window.onclick = e => { if(e.target === modal) hide(); };

    // ---------------- LOAD TYPES ----------------
    async function loadLibraryTypes() {
        try {
            const res = await fetch(BASE + "library_types/list", {
                headers: { "X-API-KEY": API_KEY }
            });
            const json = await res.json();
            const types = json.types || [];
            typeSelect.innerHTML = `<option value="">Select type</option>`;
            types.forEach(t => {
                TYPE_MAP[t.id] = t.type_name;
                const opt = document.createElement("option");
                opt.value = t.id;
                opt.textContent = t.type_name;
                typeSelect.appendChild(opt);
            });
        } catch (err) {
            typeSelect.innerHTML = `<option value="">Failed to load types</option>`;
        }
    }

    // ---------------- LOAD LIST ----------------
    async function loadLibraries(){
        tbody.innerHTML = `<tr><td colspan="6" class="empty-state">Loading...</td></tr>`;
        try {
            const res = await fetch(BASE+"libraries/list", {
                headers: { "X-API-KEY": API_KEY }
            });
            const json = await res.json();
            render(json.libraries || []);
        } catch(err){
            tbody.innerHTML = `<tr><td colspan="6" class="empty-state">Failed to load.</td></tr>`;
        }
    }

    // ---------------- RENDER ----------------
    function render(list){
        tbody.innerHTML = "";
        if(!list.length){
            tbody.innerHTML = `<tr><td colspan="6" class="empty-state">No libraries yet.</td></tr>`;
            return;
        }

        list.forEach(l => {
            const tr = document.createElement("tr");

            const typeName = TYPE_MAP[l.type_id] || ("Type ID: " + l.type_id);

            tr.innerHTML = `
              <td>${l.id}</td>
              <td>${escapeHtml(l.name)}</td>
              <td>${escapeHtml(typeName)}</td>
              <td>${escapeHtml(l.address || "")}</td>
              <td>
                <label class="switch">
                  <input type="checkbox" class="toggle-lib" data-id="${l.id}" ${l.status==="active"?"checked":""}>
                  <span class="slider"></span>
                </label>
                <span class="status-label">${l.status==="active"?"Active":"Inactive"}</span>
              </td>
              <td class="actions-col">
                <button class="btn-icon btn-edit btn-edit-lib" data-id="${l.id}" data-name="${escapeAttr(l.name)}" data-type="${l.type_id}" data-address="${escapeAttr(l.address || '')}">Edit</button>
              </td>
            `;

            tbody.appendChild(tr);
        });

        // bind edit
        document.querySelectorAll(".btn-edit-lib").forEach(btn=>{
            btn.addEventListener("click", () => {
                const id = btn.dataset.id;
                const name = unescapeAttr(btn.dataset.name);
                const typeId = btn.dataset.type;
                const address = unescapeAttr(btn.dataset.address);
                openEdit(id, name, typeId, address);
            });
        });

        // bind toggles
        document.querySelectorAll(".toggle-lib").forEach(chk=>{
            chk.addEventListener("change", onToggleStatus);
        });
    }

    // ---------------- helpers ----------------
    function escapeHtml(s){
        return String(s||"")
          .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
          .replace(/"/g,"&quot;").replace(/'/g,"&#39;");
    }
    function escapeAttr(s){
        return String(s||"").replace(/"/g,"&quot;").replace(/'/g,"&#39;");
    }
    function unescapeAttr(s){
        return String(s||"").replace(/&quot;/g,'"').replace(/&#39;/g,"'");
    }

    // ---------------- CREATE / EDIT ----------------
    function openCreate(){
        modalTitle.textContent = "New Library";
        idInput.value = "";
        nameInput.value = "";
        typeSelect.value = "";
        addressInput.value = "";
        show();
        nameInput.focus();
    }

    function openEdit(id, name, typeId, address){
        modalTitle.textContent = "Edit Library";
        idInput.value = id;
        nameInput.value = name;
        typeSelect.value = typeId || "";
        addressInput.value = address || "";
        show();
        nameInput.focus();
    }

    // ---------------- TOGGLE STATUS ----------------
    async function onToggleStatus(e){
        const chk = e.target;
        const id = chk.dataset.id;
        const willActivate = chk.checked;
        const label = chk.closest("td").querySelector(".status-label");
        label.textContent = willActivate ? "Active" : "Inactive";

        const confirm = await Swal.fire({
            title: willActivate ? "Activate?" : "Deactivate?",
            text: `Are you sure you want to ${willActivate ? "activate" : "deactivate"} this library?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: willActivate ? "Yes, activate" : "Yes, deactivate"
        });

        if(!confirm.isConfirmed){
            chk.checked = !willActivate;
            label.textContent = !willActivate ? "Active" : "Inactive";
            return;
        }

        try {
            const endpoint = willActivate ? "libraries/activate" : "libraries/deactivate";
            const body = new URLSearchParams({ id: id });

            const res = await fetch(BASE + endpoint, {
                method: "PUT",
                headers: {
                    "X-API-KEY": API_KEY,
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: body.toString()
            });

            const json = await res.json();
            if(!json.success) throw new Error(json.message || "Failed");

            Swal.fire("Success", json.message || (willActivate ? "Activated" : "Deactivated"), "success");
            loadLibraries();
        } catch(err){
            chk.checked = !willActivate;
            label.textContent = !willActivate ? "Active" : "Inactive";
            Swal.fire("Error", err.message || "Action failed", "error");
        }
    }

    // ---------------- SAVE (CREATE/UPDATE) ----------------
    form.onsubmit = async function(ev){
        ev.preventDefault();

        const id = idInput.value.trim();
        const name = nameInput.value.trim();
        const typeId = typeSelect.value;
        const address = addressInput.value.trim();

        if(!name || !typeId){
            Swal.fire("Error", "Name and Type are required.", "error");
            return;
        }

        saveBtn.disabled = true;
        nameInput.disabled = true;
        typeSelect.disabled = true;
        addressInput.disabled = true;

        try {
            if(!id){
                // CREATE: API accepts JSON (recommended)
                const res = await fetch(BASE + "libraries/create", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-API-KEY": API_KEY
                    },
                    body: JSON.stringify({
                        name: name,
                        type_id: typeId,
                        address: address
                    })
                });
                const json = await res.json();
                if(!json.success) throw new Error(json.message || "Create failed");

                Swal.fire("Created", json.message || "Library created.", "success");
                hide();
                loadLibraries();
            } else {
                // UPDATE: controller expects x-www-form-urlencoded
                const body = new URLSearchParams({ id: id, name: name, type_id: typeId, address: address });

                const res = await fetch(BASE + "libraries/update", {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                        "X-API-KEY": API_KEY
                    },
                    body: body.toString()
                });
                const json = await res.json();
                if(!json.success) throw new Error(json.message || "Update failed");

                Swal.fire("Updated", json.message || "Library updated.", "success");
                hide();
                loadLibraries();
            }
        } catch(err){
            Swal.fire("Error", err.message || "Save failed", "error");
        } finally {
            saveBtn.disabled = false;
            nameInput.disabled = false;
            typeSelect.disabled = false;
            addressInput.disabled = false;
        }
    };

    // INIT: load types first, then libraries
    (async function init(){
        await loadLibraryTypes();
        await loadLibraries();
    })();

});
