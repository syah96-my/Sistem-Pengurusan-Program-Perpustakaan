document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    const API_KEY = window.GM_API_KEY;
    const API_BASE = window.GM_API_BASE || "/api/?route=";

    function safeLog(...a){ if (window.GM_DEBUG) console.log("[PLATFORMS]", ...a); }
    function safeErr(...a){ console.error("[PLATFORMS]", ...a); }

    // DOM refs
    const tbody = document.getElementById("platforms-tbody");
    const addBtn = document.getElementById("add-btn");
    const modal = document.getElementById("platform-modal");
    const modalTitle = document.getElementById("platform-modal-title");
    const modalClose = document.getElementById("platform-modal-close");
    const form = document.getElementById("platform-form");
    const idInput = document.getElementById("platform-id");
    const nameInput = document.getElementById("platform-name");
    const cancelBtn = document.getElementById("platform-cancel-btn");

    function showModal(){ modal.classList.add("show"); }
    function hideModal(){ modal.classList.remove("show"); }

    addBtn.onclick = openCreate;
    modalClose.onclick = hideModal;
    cancelBtn.onclick = hideModal;
    window.onclick = e => { if (e.target === modal) hideModal(); };

    // ---------------- fetch & render ----------------
    async function fetchPlatforms() {
        try {
            const res = await fetch(API_BASE + "platforms/list", {
                headers: { "X-API-KEY": API_KEY }
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || "Failed to load");
            renderPlatforms(json.platforms || []);
        } catch (err) {
            safeErr(err);
            Swal.fire("Error", "Failed to load platforms.", "error");
            renderPlatforms([]);
        }
    }

    function renderPlatforms(list) {
        tbody.innerHTML = "";
        if (!list.length) {
            tbody.innerHTML = `<tr class="empty-state-row"><td colspan="4" class="empty-state">No platforms yet. Add one using the button above.</td></tr>`;
            return;
        }

        list.forEach(p => {
            const tr = document.createElement("tr");

            const idTd = document.createElement("td");
            idTd.textContent = p.id;

            const nameTd = document.createElement("td");
            nameTd.textContent = p.platform_name;

            const statusTd = document.createElement("td");
            // switch: checkbox input checked => enabled ("1")
            const checked = String(p.enabled) === "1";
            statusTd.innerHTML = `
                <label class="switch" title="${checked ? 'Enabled' : 'Disabled'}">
                    <input type="checkbox" class="platform-switch" data-id="${p.id}" ${checked ? "checked" : ""}>
                    <span class="slider"></span>
                </label>
                <span class="status-label">${checked ? "Enabled" : "Disabled"}</span>
            `;

            const actionsTd = document.createElement("td");
            actionsTd.className = "actions-col";
            actionsTd.innerHTML = `
                <button class="btn-icon btn-edit" data-id="${p.id}" data-name="${escapeHtml(p.platform_name)}" title="Edit">Edit</button>
            `;

            tr.appendChild(idTd);
            tr.appendChild(nameTd);
            tr.appendChild(statusTd);
            tr.appendChild(actionsTd);

            tbody.appendChild(tr);
        });

        // bind edit buttons
        Array.from(document.querySelectorAll(".btn-edit")).forEach(btn => {
            btn.addEventListener("click", (e) => {
                const id = btn.dataset.id;
                const name = unescapeHtml(btn.dataset.name);
                openEdit(id, name);
            });
        });

        // bind switches
        Array.from(document.querySelectorAll(".platform-switch")).forEach(chk => {
            chk.addEventListener("change", onSwitchToggle);
        });
    }

    function escapeHtml(str) {
        return String(str || "").replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/'/g,"&#39;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
    }
    function unescapeHtml(s) {
        return String(s || "").replace(/&amp;/g,"&").replace(/&quot;/g,'"').replace(/&#39;/g,"'").replace(/&lt;/g,"<").replace(/&gt;/g,">");
    }

    // ---------------- create / edit ----------------
    function openCreate() {
        modalTitle.textContent = "New Platform";
        idInput.value = "";
        nameInput.value = "";
        nameInput.removeAttribute("disabled");
        document.getElementById("platform-save-btn").textContent = "Create";
        showModal();
        nameInput.focus();
    }

    function openEdit(id, name) {
        modalTitle.textContent = "Edit Platform";
        idInput.value = id;
        nameInput.value = name;
        nameInput.removeAttribute("disabled");
        document.getElementById("platform-save-btn").textContent = "Save";
        showModal();
        nameInput.focus();
    }

    // ---------------- toggle handler (enable/disable) ----------------
    async function onSwitchToggle(e) {
        const checkbox = e.currentTarget;
        const platformId = checkbox.dataset.id;
        const willEnable = checkbox.checked; // if checked => enable

        // optimistic UI: change label immediately, but revert on failure
        const statusLabel = checkbox.closest("td").querySelector(".status-label");
        statusLabel.textContent = willEnable ? "Enabled" : "Disabled";

        // confirm action
        const actionText = willEnable ? "enable" : "disable";
        const confirm = await Swal.fire({
            title: `Confirm ${actionText}`,
            text: `Are you sure you want to ${actionText} this platform?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: willEnable ? "Yes, enable" : "Yes, disable"
        });

        if (!confirm.isConfirmed) {
            // revert switch and label
            checkbox.checked = !willEnable;
            statusLabel.textContent = willEnable ? "Disabled" : "Enabled";
            return;
        }

        try {
            const endpoint = willEnable ? "platforms/enable" : "platforms/disable";

            const bodyParams = new URLSearchParams({ id: platformId });

            const res = await fetch(API_BASE + endpoint, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                    "X-API-KEY": API_KEY
                },
                body: bodyParams.toString()
            });

            const json = await res.json();
            if (!json.success) throw new Error(json.message || "Failed");

            Swal.fire(willEnable ? "Enabled" : "Disabled", `Platform ${willEnable ? "enabled" : "disabled"} successfully.`, "success");
            await fetchPlatforms();
        } catch (err) {
            safeErr(err);
            Swal.fire("Error", err.message || "Action failed", "error");
            // revert UI
            checkbox.checked = !willEnable;
            const statusLabel2 = checkbox.closest("td").querySelector(".status-label");
            statusLabel2.textContent = checkbox.checked ? "Enabled" : "Disabled";
        }
    }

    // ---------------- submit create/update ----------------
    form.addEventListener("submit", async function (ev) {
        ev.preventDefault();

        const id = idInput.value.trim();
        const platformName = nameInput.value.trim();

        if (!platformName) {
            Swal.fire("Error", "Platform name is required.", "error");
            return;
        }

        // disable while sending
        nameInput.setAttribute("disabled", "disabled");
        document.getElementById("platform-save-btn").setAttribute("disabled", "disabled");

        try {
            if (!id) {
                // CREATE (POST x-www-form-urlencoded)
                const body = new URLSearchParams({ platform_name: platformName });

                const res = await fetch(API_BASE + "platforms/create", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                        "X-API-KEY": API_KEY
                    },
                    body: body.toString()
                });

                const json = await res.json();
                if (!json.success) throw new Error(json.message || "Create failed");

                Swal.fire("Created", "Platform created.", "success");
                hideModal();
                await fetchPlatforms();
            } else {
                // UPDATE (PUT x-www-form-urlencoded)
                const body = new URLSearchParams({ id: id, platform_name: platformName });

                const res = await fetch(API_BASE + "platforms/update", {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                        "X-API-KEY": API_KEY
                    },
                    body: body.toString()
                });

                const json = await res.json();
                if (!json.success) throw new Error(json.message || "Update failed");

                Swal.fire("Updated", "Platform updated.", "success");
                hideModal();
                await fetchPlatforms();
            }
        } catch (err) {
            safeErr(err);
            Swal.fire("Error", err.message || "Failed to save.", "error");
        } finally {
            nameInput.removeAttribute("disabled");
            document.getElementById("platform-save-btn").removeAttribute("disabled");
        }
    });

    // init
    fetchPlatforms();
});
