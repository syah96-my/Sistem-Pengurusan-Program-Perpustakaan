document.addEventListener("DOMContentLoaded", function(){
    const API_KEY = window.GM_API_KEY;
    const BASE = window.GM_API_BASE || "/api/?route=";

    // DOM refs
    const tbody   = document.getElementById("group-tbody");
    const modal   = document.getElementById("group-modal");
    const form    = document.getElementById("group-form");

    const modalTitle = document.getElementById("group-modal-title");
    const idInput    = document.getElementById("group-id");
    const nameInput  = document.getElementById("group-name");
    const saveBtn    = document.getElementById("group-save-btn");

    const btnAdd     = document.getElementById("add-btn");
    const btnClose   = document.getElementById("group-modal-close");
    const btnCancel  = document.getElementById("group-cancel-btn");

    function show(){ modal.classList.add("show"); }
    function hide(){ modal.classList.remove("show"); }

    btnAdd.onclick = () => openCreate();
    btnClose.onclick = hide;
    btnCancel.onclick = hide;
    window.onclick = e => { if(e.target === modal) hide(); };

    // ---------------- LOAD LIST ----------------
    async function loadGroups(){
        try {
            const res = await fetch(BASE+"target_groups/list", {
                headers:{ "X-API-KEY":API_KEY }
            });
            const json = await res.json();
            render(json.groups || []);
        } catch(err){
            tbody.innerHTML = `<tr><td colspan="4" class="empty-state">Failed to load.</td></tr>`;
        }
    }

    // ---------------- RENDER TABLE ----------------
    function render(list){
        list.sort((a, b) => Number(a.id) - Number(b.id));
        tbody.innerHTML = "";
        if(!list.length){
            tbody.innerHTML = `<tr><td colspan="4" class="empty-state">No target groups yet.</td></tr>`;
            return;
        }

        list.forEach(g => {
            const tr = document.createElement("tr");

            tr.innerHTML = `
              <td>${g.id}</td>
              <td>${escape(g.group_name)}</td>
              <td>
                <label class="switch">
                  <input type="checkbox" class="toggle-group" data-id="${g.id}" ${g.enabled=="1"?"checked":""}>
                  <span class="slider"></span>
                </label>
                <span class="status-label">${g.enabled=="1"?"Enabled":"Disabled"}</span>
              </td>
              <td class="actions-col">
                <button class="btn-icon btn-edit btn-edit-group" data-id="${g.id}" data-name="${escape(g.group_name)}">Edit</button>
              </td>
            `;

            tbody.appendChild(tr);
        });

        document.querySelectorAll(".btn-edit-group").forEach(btn=>{
            btn.onclick = () => openEdit(btn.dataset.id, unescape(btn.dataset.name));
        });

        document.querySelectorAll(".toggle-group").forEach(chk=>{
            chk.onchange = toggleStatus;
        });
    }

    // ---------------- HTML ESCAPE ----------------
    function escape(s){
        return String(s||"")
        .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;").replace(/'/g,"&#39;");
    }
    function unescape(s){
        return String(s||"")
        .replace(/&amp;/g,"&").replace(/&lt;/g,"<").replace(/&gt;/g,">")
        .replace(/&quot;/g,'"').replace(/&#39;/g,"'");
    }

    // ---------------- CREATE ----------------
    function openCreate(){
        modalTitle.textContent = "New Target Group";
        idInput.value = "";
        nameInput.value = "";
        show();
        nameInput.focus();
    }

    // ---------------- EDIT ----------------
    function openEdit(id, name){
        modalTitle.textContent = "Edit Target Group";
        idInput.value = id;
        nameInput.value = name;
        show();
        nameInput.focus();
    }

    // ---------------- ENABLE / DISABLE ----------------
    async function toggleStatus(e){
        const chk = e.target;
        const id = chk.dataset.id;
        const enable = chk.checked;

        const label = chk.closest("td").querySelector(".status-label");
        label.textContent = enable ? "Enabled" : "Disabled";

        const confirm = await Swal.fire({
            title: enable ? "Enable?" : "Disable?",
            text: `Are you sure you want to ${enable?"enable":"disable"} this group?`,
            icon:"warning",
            showCancelButton:true
        });

        if(!confirm.isConfirmed){
            chk.checked = !enable;
            label.textContent = !enable ? "Enabled" : "Disabled";
            return;
        }

        try {
            const endpoint = enable ? "target_groups/enable" : "target_groups/disable";
            const body = new URLSearchParams({ id:id });

            const res = await fetch(BASE+endpoint, {
                method:"PUT",
                headers:{
                    "X-API-KEY":API_KEY,
                    "Content-Type":"application/x-www-form-urlencoded"
                },
                body: body.toString()
            });

            const json = await res.json();
            if(!json.success) throw new Error("Failed");

            Swal.fire("Success", `Target group ${enable?"enabled":"disabled"} successfully.`, "success");
            loadGroups();

        } catch(err){
            chk.checked = !enable;
            label.textContent = !enable ? "Enabled" : "Disabled";
            Swal.fire("Error","Failed to update status.","error");
        }
    }

    // ---------------- SAVE ----------------
    form.onsubmit = async function(ev){
        ev.preventDefault();

        const id = idInput.value.trim();
        const name = nameInput.value.trim();

        if(!name){
            Swal.fire("Error","Group name required.","error");
            return;
        }

        saveBtn.disabled = true;
        nameInput.disabled = true;

        try {
            let endpoint, method, body;

            if(!id){
                endpoint = "target_groups/create";
                method = "POST";
                body = new URLSearchParams({ group_name:name });
            } else {
                endpoint = "target_groups/update";
                method = "PUT";
                body = new URLSearchParams({ id:id, group_name:name });
            }

            const res = await fetch(BASE+endpoint, {
                method:method,
                headers:{
                    "X-API-KEY":API_KEY,
                    "Content-Type":"application/x-www-form-urlencoded"
                },
                body: body.toString()
            });

            const json = await res.json();
            if(!json.success) throw new Error(json.message || "Failed");

            Swal.fire("Success", id?"Updated":"Created", "success");
            hide();
            loadGroups();

        } catch(err){
            Swal.fire("Error", err.message || "Save failed.","error");
        } finally {
            saveBtn.disabled = false;
            nameInput.disabled = false;
        }
    };

    // INIT
    loadGroups();
});
