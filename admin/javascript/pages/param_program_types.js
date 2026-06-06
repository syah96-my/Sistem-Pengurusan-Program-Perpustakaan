document.addEventListener("DOMContentLoaded", function(){
    const API_KEY = window.GM_API_KEY;
    const BASE = window.GM_API_BASE || "/api/?route=";

    // DOM elements
    const tbody = document.getElementById("ptype-tbody");
    const modal = document.getElementById("ptype-modal");
    const modalTitle = document.getElementById("ptype-modal-title");
    const idInput = document.getElementById("ptype-id");
    const nameInput = document.getElementById("ptype-name");
    const saveBtn = document.getElementById("ptype-save-btn");

    const btnAdd = document.getElementById("add-btn");
    const btnClose = document.getElementById("ptype-modal-close");
    const btnCancel = document.getElementById("ptype-cancel-btn");
    
    const form = document.getElementById("ptype-form");  

    function show(){ modal.classList.add("show"); }
    function hide(){ modal.classList.remove("show"); }

    btnAdd.onclick = () => openCreate();
    btnClose.onclick = hide;
    btnCancel.onclick = hide;
    window.onclick = e => { if(e.target === modal) hide(); };

    // ---------------- FETCH LIST ----------------
    async function loadTypes(){
        try {
            const res = await fetch(BASE+"program_types/list", {
                headers:{ "X-API-KEY":API_KEY }
            });
            const json = await res.json();
            render(json.types || []);
        } catch(err){
            tbody.innerHTML = `<tr><td colspan="4" class="empty-state">Failed to load.</td></tr>`;
        }
    }

    // ---------------- RENDER TABLE ----------------
    function render(list){
        tbody.innerHTML = "";
        if(!list.length){
            tbody.innerHTML = `<tr><td colspan="4" class="empty-state">No program types yet.</td></tr>`;
            return;
        }

        list.forEach(t => {
            const tr = document.createElement("tr");

            tr.innerHTML = `
              <td>${t.id}</td>
              <td>${escape(t.type_name)}</td>
              <td>
                <label class="switch">
                  <input type="checkbox" class="toggle" data-id="${t.id}" ${t.enabled=="1"?"checked":""}>
                  <span class="slider"></span>
                </label>
                <span class="status-label">${t.enabled=="1"?"Enabled":"Disabled"}</span>
              </td>
              <td class="actions-col">
                <button class="btn-icon btn-edit" data-id="${t.id}" data-name="${escape(t.type_name)}">Edit</button>
              </td>
            `;

            tbody.appendChild(tr);
        });

        // Bind actions
        document.querySelectorAll(".btn-edit").forEach(btn=>{
            btn.onclick = () => openEdit(btn.dataset.id, unescape(btn.dataset.name));
        });

        document.querySelectorAll(".toggle").forEach(chk=>{
            chk.onchange = toggleStatus;
        });
    }

    // ---------------- ESCAPE HTML ----------------
    function escape(str){
        return String(str || "")
          .replace(/&/g,"&amp;")
          .replace(/</g,"&lt;")
          .replace(/>/g,"&gt;")
          .replace(/"/g,"&quot;")
          .replace(/'/g,"&#39;");
    }
    function unescape(str){
        return String(str || "")
          .replace(/&amp;/g,"&")
          .replace(/&lt;/g,"<")
          .replace(/&gt;/g,">")
          .replace(/&quot;/g,'"')
          .replace(/&#39;/g,"'");
    }

    // ---------------- CREATE ----------------
    function openCreate(){
        modalTitle.textContent = "New Program Type";
        idInput.value = "";
        nameInput.value = "";
        show();
        nameInput.focus();
    }

    // ---------------- EDIT ----------------
    function openEdit(id, name){
        modalTitle.textContent = "Edit Program Type";
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

        const res = await Swal.fire({
            title: enable?"Enable?":"Disable?",
            text: `Are you sure you want to ${enable?"enable":"disable"} this type?`,
            icon:"warning",
            showCancelButton:true
        });

        if(!res.isConfirmed){
            chk.checked = !enable;
            label.textContent = !enable ? "Enabled" : "Disabled";
            return;
        }

        try {
            const endpoint = enable ? "program_types/enable" : "program_types/disable";

            const body = new URLSearchParams({ id:id });
            const req = await fetch(BASE+endpoint, {
                method:"PUT",
                headers:{
                    "X-API-KEY":API_KEY,
                    "Content-Type":"application/x-www-form-urlencoded"
                },
                body: body.toString()
            });

            const json = await req.json();
            if(!json.success) throw new Error("Failed");

            Swal.fire("Success", `Program type ${enable?"enabled":"disabled"} successfully.`, "success");
            loadTypes();

        } catch(err){
            chk.checked = !enable;
            label.textContent = !enable ? "Enabled" : "Disabled";
            Swal.fire("Error", "Action failed.", "error");
        }
    }

    // ---------------- SAVE (CREATE/UPDATE) ----------------
    form.onsubmit = async function(ev){
        ev.preventDefault();

        const id = idInput.value.trim();
        const name = nameInput.value.trim();

        if(!name){
            Swal.fire("Error","Type name required.","error");
            return;
        }

        saveBtn.disabled = true;
        nameInput.disabled = true;

        try {
            let endpoint, method, body;

            if(!id){
                endpoint = "program_types/create";
                method = "POST";
                body = new URLSearchParams({ type_name:name });
            } else {
                endpoint = "program_types/update";
                method = "PUT";
                body = new URLSearchParams({ id:id, type_name:name });
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
            loadTypes();

        } catch(err){
            Swal.fire("Error", err.message || "Save failed","error");
        } finally {
            saveBtn.disabled = false;
            nameInput.disabled = false;
        }
    };

    // INIT
    loadTypes();
});
