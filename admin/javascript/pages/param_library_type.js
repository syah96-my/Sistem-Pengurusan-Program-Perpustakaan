document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    const API_KEY = window.GM_API_KEY;
    const API_BASE = window.GM_API_BASE || "/api/?route=";

    function safeLog(...a){ if (window.GM_DEBUG) console.log("[LIB-TYPES]", ...a); }
    function safeErr(...a){ console.error("[LIB-TYPES]", ...a); }

    // DOM helpers
    const typesTbody = document.getElementById("types-tbody");
    const typeModal = document.getElementById("type-modal");
    const typeForm = document.getElementById("type-form");
    const typeIdInput = document.getElementById("type-id");
    const typeNameInput = document.getElementById("type-name");
    const typeModalTitle = document.getElementById("type-modal-title");
    const addBtn = document.getElementById("add-btn");
    const closeBtn = document.getElementById("type-modal-close");
    const cancelBtn = document.getElementById("type-cancel-btn");

    function showModal() { typeModal.classList.add("show"); }
    function hideModal() { typeModal.classList.remove("show"); }

    // Close modal handlers
    addBtn.onclick = () => openCreate();
    closeBtn.onclick = () => hideModal();
    cancelBtn.onclick = () => hideModal();
    window.onclick = e => { if (e.target === typeModal) hideModal(); };

    // ------------------ FETCH & RENDER ------------------
    async function fetchTypes() {
        try {
            const res = await fetch(API_BASE + "library_types/list", {
                headers: { "X-API-KEY": API_KEY }
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || "Failed to load");
            renderTypes(json.types || []);
        } catch (err) {
            safeErr(err);
            Swal.fire("Error", "Failed to load library types.", "error");
            renderTypes([]);
        }
    }

    function renderTypes(types) {
        typesTbody.innerHTML = "";

        if (!types.length) {
            typesTbody.innerHTML = `<tr class="empty-state-row"><td colspan="3" class="empty-state">No library types yet. Add one using the button above.</td></tr>`;
            return;
        }

        types.forEach(t => {
            const tr = document.createElement("tr");

            const idTd = document.createElement("td");
            idTd.textContent = t.id;

            const nameTd = document.createElement("td");
            nameTd.textContent = t.type_name;

            const actionsTd = document.createElement("td");
            actionsTd.className = "actions-col";
            actionsTd.innerHTML = `
                <button class="btn-icon btn-edit" data-id="${t.id}" data-name="${escapeHtml(t.type_name)}" title="Edit">Edit</button>
            `;

            tr.appendChild(idTd);
            tr.appendChild(nameTd);
            tr.appendChild(actionsTd);

            typesTbody.appendChild(tr);
        });

        // bind edit buttons
        Array.from(document.querySelectorAll(".btn-edit")).forEach(btn => {
            btn.addEventListener("click", (e) => {
                const id = btn.dataset.id;
                const name = unescapeHtml(btn.dataset.name);
                openEdit(id, name);
            });
        });
    }

    // simple html escape/unescape for dataset usage
    function escapeHtml(str) {
        return String(str || "").replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/'/g,"&#39;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
    }
    function unescapeHtml(s) {
        return String(s || "").replace(/&amp;/g,"&").replace(/&quot;/g,'"').replace(/&#39;/g, "'").replace(/&lt;/g,"<").replace(/&gt;/g,">");
    }

    // ------------------ CREATE / EDIT FLOW ------------------
    function openCreate() {
        typeModalTitle.textContent = "New Library Type";
        typeIdInput.value = "";
        typeNameInput.value = "";
        typeNameInput.removeAttribute("disabled");
        document.getElementById("type-save-btn").textContent = "Create";
        showModal();
        typeNameInput.focus();
    }

    function openEdit(id, name) {
        typeModalTitle.textContent = "Edit Library Type";
        typeIdInput.value = id;
        typeNameInput.value = name;
        typeNameInput.removeAttribute("disabled");
        document.getElementById("type-save-btn").textContent = "Save";
        showModal();
        typeNameInput.focus();
    }

    // ------------------ SUBMIT ------------------
    typeForm.addEventListener("submit", async function (ev) {
        ev.preventDefault();

        const id = typeIdInput.value.trim();
        const typeName = typeNameInput.value.trim();

        if (!typeName) {
            Swal.fire("Error", "Type name is required.", "error");
            return;
        }

        // disable while sending
        typeNameInput.setAttribute("disabled", "disabled");
        document.getElementById("type-save-btn").setAttribute("disabled", "disabled");

        try {
if (!id) {
    // CREATE (POST x-www-form-urlencoded)
    const createBody = `type_name=${encodeURIComponent(typeName)}`;

    const res = await fetch(API_BASE + "library_types/create", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            "X-API-KEY": API_KEY
        },
        body: createBody
    });

    const json = await res.json();

    if (!json.success) throw new Error(json.message || "Create failed");
    Swal.fire("Created", "Library type created.", "success");
    hideModal();
    await fetchTypes();

} else {
    // UPDATE (PUT x-www-form-urlencoded)
    const updateBody = `id=${encodeURIComponent(id)}&type_name=${encodeURIComponent(typeName)}`;

    const res = await fetch(API_BASE + "library_types/update", {
        method: "PUT",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            "X-API-KEY": API_KEY
        },
        body: updateBody
    });

    const json = await res.json();

    if (!json.success) throw new Error(json.message || "Update failed");
    Swal.fire("Updated", "Library type updated.", "success");
    hideModal();
    await fetchTypes();
}

        } catch (err) {
            safeErr(err);
            Swal.fire("Error", err.message || "Failed to save.", "error");
        } finally {
            typeNameInput.removeAttribute("disabled");
            document.getElementById("type-save-btn").removeAttribute("disabled");
        }
    });

    // ================= Init =================
    fetchTypes();
});
