document.addEventListener("DOMContentLoaded", function(){
    const API_KEY = window.GM_API_KEY;
    const BASE = window.GM_API_BASE || "/api/?route=";

    // DOM refs
    const tbody = document.getElementById("scale-tbody");
    const modal = document.getElementById("scale-modal");
    const modalTitle = document.getElementById("scale-modal-title");
    const idInput = document.getElementById("scale-id");
    const nameInput = document.getElementById("scale-name");
    const saveBtn = document.getElementById("scale-save-btn");

    const btnAdd = document.getElementById("add-btn");
    const btnClose = document.getElementById("scale-modal-close");
    const btnCancel = document.getElementById("scale-cancel-btn");

    const form = document.getElementById("scale-form");

    function show(){ modal.classList.add("show"); }
    function hide(){ modal.classList.remove("show"); }

    btnAdd.onclick = () => openCreate();
    btnClose.onclick = hide;
    btnCancel.onclick = hide;
    window.onclick = e => { if(e.target === modal) hide(); };

    // ---------------- LOAD LIST ----------------
    async function loadScales(){
        try {
            const res = await fetch(BASE+"scales/list", {
                headers:{ "X-API-KEY":API_KEY }
            });
            const json = await res.json();
            render(json.scales || []);
        } catch(err){
            tbody.innerHTML = `<tr><td colspan="4" class="empty-state">Failed to load.</td></tr>`;
        }
    }

    // ---------------- RENDER ----------------
    function render(list){
        tbody.innerHTML = "";
        if(!list.length){
            tbody.innerHTML = `<tr><td colspan="4" class="empty-state">No scales yet.</td></tr>`;
            return;
        }

        list.forEach(s => {
            const tr = document.createElement("tr");

            tr.innerHTML = `
              <td>${s.id}</td>
              <td>${escape(s.scale_name)}</td>
              <td>
                <label class="switch">
                  <input type="checkbox" class="toggle-scale" data-id="${s.id}" ${s.enabled=="1"?"checked":""}>
                  <span class="slider"></span>
                </label>
                <span class="status-label">${s.enabled=="1"?"Enabled":"Disabled"}</span>
              </td>
              <td class="actions-col">
                <button class="btn-icon btn-edit btn-edit-scale" data-id="${s.id}" data-name="${escape(s.scale_name)}">Edit</button>
              </td>
            `;

            tbody.appendChild(tr);
        });

        document.querySelectorAll(".btn-edit-scale").forEach(btn=>{
            btn.onclick = () => openEdit(btn.dataset.id, unescape(btn.dataset.name));
        });

        document.querySelectorAll(".toggle-scale").forEach(chk=>{
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
        modalTitle.textContent = "New Scale";
        idInput.value = "";
        nameInput.value = "";
        show();
        nameInput.focus();
    }

    // ---------------- EDIT ----------------
    function openEdit(id, name){
        modalTitle.textContent = "Edit Scale";
        idInput.value = id;
        nameInput.value = name;
        show();
        nameInput.focus();
    }

    // ---------------- ENABLE/DISABLE ----------------
    async function toggleStatus(e){
        const chk = e.target;
        const id = chk.dataset.id;
        const enable = chk.checked;

        const label = chk.closest("td").querySelector(".status-label");
        label.textContent = enable ? "Enabled" : "Disabled";

        const confirm = await Swal.fire({
            title: enable?"Enable?":"Disable?",
            text:`Are you sure you want to ${enable?"enable":"disable"} this scale?`,
            icon:"warning",
            showCancelButton:true
        });

        if(!confirm.isConfirmed){
            chk.checked = !enable;
            label.textContent = !enable?"Enabled":"Disabled";
            return;
        }

        try {
            const endpoint = enable ? "scales/enable" : "scales/disable";
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
            if(!json.success) throw new Error("Action failed");

            Swal.fire("Success", `Scale ${enable?"enabled":"disabled"} successfully.`, "success");
            loadScales();

        } catch(err){
            chk.checked = !enable;
            label.textContent = !enable?"Enabled":"Disabled";
            Swal.fire("Error", "Failed to update status.", "error");
        }
    }

    // ---------------- SAVE ----------------
    form.onsubmit = async function(ev){
        ev.preventDefault();

        const id = idInput.value.trim();
        const name = nameInput.value.trim();

        if(!name){
            Swal.fire("Error","Scale name required.","error");
            return;
        }

        saveBtn.disabled = true;
        nameInput.disabled = true;

        try {
            let endpoint, method, body;

            if(!id){
                endpoint = "scales/create";
                method = "POST";
                body = new URLSearchParams({ scale_name:name });
            } else {
                endpoint = "scales/update";
                method = "PUT";
                body = new URLSearchParams({ id:id, scale_name:name });
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
            if(!json.success) throw new Error(json.message || "Save failed");

            Swal.fire("Success", id?"Updated":"Created", "success");
            hide();
            loadScales();

        } catch(err){
            Swal.fire("Error", err.message || "Save failed.","error");
        } finally {
            saveBtn.disabled = false;
            nameInput.disabled = false;
        }
    }

    // INIT
    loadScales();
});
