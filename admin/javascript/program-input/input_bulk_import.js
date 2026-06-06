/* ============================================================
   BULK PROGRAM IMPORT (CSV)
   ISOLATED MODULE - DO NOT MIX WITH OTHERS
============================================================ */

document.getElementById("open-template").onclick = function () {
    window.open((window.GM_BASE_PATH || "") + "/assets/templates/participants_bulk_template.csv", "_blank");
};

document.getElementById("download-template-btn").onclick = function () {
    window.open((window.GM_BASE_PATH || "") + "/assets/templates/programs_bulk_template.csv", "_blank");
};

document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    const API_KEY  = window.GM_API_KEY;
    const API_BASE = window.GM_API_BASE || "/api/?route=";

    let SCALE_MAP   = {};
    let PLATFORM_MAP = {};
    let TARGET_MAP   = {};

    function safeLog(...a) { if (window.GM_DEBUG) console.log("[BULK]", ...a); }
    function safeErr(...a) { console.error("[BULK]", ...a); }

    function element(id) {
        const el = document.getElementById(id);
        if (!el) safeErr("Missing element:", id);
        return el;
    }

    /* ================= CSV PARSER ================= */
    function parseCSV(text) {
        const lines  = text.trim().split("\n");
        const header = lines[0].split(",").map(h => h.trim());

        return lines.slice(1).map((line, idx) => {
            const cols = line.split(",");
            let row = {};
            header.forEach((h, i) => row[h] = (cols[i] || "").trim());
            row.__rowIndex = idx + 2; // real CSV row number
            return row;
        });
    }

    /* ================= DATE PARSER (DD/MM/YYYY) ================= */
    function convertDate(value) {
        if (!value) return null;

        const regex = /^(\d{1,2})\/(\d{1,2})\/(\d{4})(?:\s+(\d{1,2}):(\d{2}))?$/;
        const m = value.trim().match(regex);
        if (!m) return null;

        let [, dd, mm, yyyy, HH = "00", MM = "00"] = m;

        dd = dd.padStart(2, "0");
        mm = mm.padStart(2, "0");
        HH = HH.padStart(2, "0");
        MM = MM.padStart(2, "0");

        return `${yyyy}-${mm}-${dd} ${HH}:${MM}:00`;
    }

    function isValidDate(value) {
        return convertDate(value) !== null;
    }

    /* ================= LOAD LOOKUP MAPPINGS ================= */
    async function loadMappings() {
        const headers = { "X-API-KEY": API_KEY };

        const s = await fetch(API_BASE + "scales/list", { headers }).then(r => r.json());
        (s.scales || []).forEach(x => SCALE_MAP[x.scale_name] = x.id);

        const p = await fetch(API_BASE + "platforms/list", { headers }).then(r => r.json());
        (p.platforms || []).forEach(x => PLATFORM_MAP[x.platform_name] = x.id);

        const t = await fetch(API_BASE + "target_groups/list", { headers }).then(r => r.json());
        (t.groups || []).forEach(x => TARGET_MAP[x.group_name] = x.id);

        safeLog("Mappings loaded");
    }
    loadMappings();

    /* ================= ELEMENTS ================= */
    const modal      = element("bulk-import-modal");
    const openBtn    = element("bulk-import-btn");
    const closeBtn   = element("bulk-import-close");
    const cancelBtn  = element("bulk-import-cancel");
    const importForm = element("bulk-import-form");
    const inputFile  = element("import-file");

    openBtn.onclick   = () => modal.style.display = "flex";
    closeBtn.onclick  = () => modal.style.display = "none";
    cancelBtn.onclick = () => modal.style.display = "none";
    window.onclick    = e => { if (e.target === modal) modal.style.display = "none"; };

    /* ================= ERROR CSV GENERATOR ================= */
    function downloadErrorCSV(rows, errors) {
        modal.style.display = "none";

        const headers = Object.keys(rows[0]).filter(k => !k.startsWith("__"));
        let csv = headers.join(",") + ",error_message\n";

        rows.forEach(r => {
            const rowErr = errors
                .filter(e => e.startsWith(`Row ${r.__rowIndex}:`))
                .map(e => e.replace(`Row ${r.__rowIndex}:`, "").trim())
                .join("; ");

            const rowValues = headers.map(h =>
                `"${(r[h] || "").replace(/"/g, '""')}"`
            ).join(",");

            csv += `${rowValues},"${rowErr}"\n`;
        });

        const blob = new Blob([csv], { type: "text/csv" });
        const url  = URL.createObjectURL(blob);

        Swal.fire({
            icon: "error",
            title: "Row Errors Found",
            html: `
                Please download the error CSV, fix the issues, and upload again.<br><br>
                <a href="${url}" download="import_errors.csv"
                   class="swal2-confirm swal2-styled"
                   class="btn-inline-danger">
                   Download Error CSV
                </a>
            `
        });
    }

    /* ================= SUBMIT HANDLER ================= */
    importForm.addEventListener("submit", async function (ev) {
        ev.preventDefault();

        const file = inputFile.files[0];
        if (!file) {
            modal.style.display = "none";
            return Swal.fire("Error", "No CSV selected.", "error");
        }

        const rows   = parseCSV(await file.text());
        let errors = [];

        /* ================= VALIDATION ================= */
        rows.forEach(r => {
            const n = r.__rowIndex;

            // scale (ID, numeric in CSV)
            if (!r.scale || isNaN(r.scale))
                errors.push(`Row ${n}: Invalid scale "${r.scale}"`);

            const mode = (r.mode || "").trim().toLowerCase();
            if (!["physical", "online", "hybrid"].includes(mode))
                errors.push(`Row ${n}: Invalid mode "${r.mode}"`);

            const hasPlatform = r.platform && PLATFORM_MAP[r.platform.trim()];
            const hasLocation = r.location?.trim() !== "";

            if (mode === "physical") {
                if (hasPlatform)
                    errors.push(`Row ${n}: Platform not allowed for PHYSICAL`);
                if (!hasLocation)
                    errors.push(`Row ${n}: Location required for PHYSICAL`);
            }

            if (mode === "online") {
                if (!hasPlatform)
                    errors.push(`Row ${n}: Platform required for ONLINE`);
            }

            if (mode === "hybrid") {
                if (!hasLocation)
                    errors.push(`Row ${n}: Location required for HYBRID`);
                if (!hasPlatform)
                    errors.push(`Row ${n}: Platform required for HYBRID`);
            }

            if (!r.targets)
                errors.push(`Row ${n}: Missing targets`);
            else {
                r.targets.split("|").forEach(t => {
                    if (isNaN(t.trim()))
                        errors.push(`Row ${n}: Invalid target id "${t}"`);
                });
            }

            if (!isValidDate(r.program_start))
                errors.push(`Row ${n}: Invalid program_start "${r.program_start}"`);

            if (!isValidDate(r.program_end))
                errors.push(`Row ${n}: Invalid program_end "${r.program_end}"`);
        });



        if (errors.length > 0) {
            safeErr("Validation errors:", errors);
            downloadErrorCSV(rows, errors);
            return;
        }

        /* ================= BUILD PAYLOAD ================= */
        const programsPayload = rows.map(r => ({
            library_id: document.getElementById("import-library-id").value,
            parent_library_id: document.getElementById("import-program-parent").value,
            library_type_id: document.getElementById("import-library-type").value,
            program_type_id: document.getElementById("import-program-id").value,

            scale_id: parseInt(r.scale, 10),
            platform_id: r.platform ? PLATFORM_MAP[r.platform.trim()] : null,
            mode: r.mode.trim().toLowerCase(),

            program_name: r.program_name,
            program_start: convertDate(r.program_start),
            program_end: convertDate(r.program_end),
            location: r.location,
            officiated_by: r.officiated_by,
            program_details: r.program_details,
            document_url: r.document_url,
            cover_image_url: r.cover_image_url,

            user_id: document.getElementById("import-program-id").value,
            target_group_ids: r.targets.split("|").map(t => parseInt(t.trim(), 10))
        }));




        safeLog("FINAL BULK PAYLOAD:", programsPayload);

        /* ================= API CALL ================= */
        const response = await fetch(API_BASE + "programs/bulk_import", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-API-KEY": API_KEY
            },
            body: JSON.stringify({ programs: programsPayload })
        });

        const apiJson = await response.json();

        let success = apiJson.results.filter(r => r.success).length;
        let fail    = apiJson.results.length - success;

        modal.style.display = "none";

        Swal.fire({
            icon: fail > 0 ? "warning" : "success",
            title: "Bulk Import Completed",
            html: `
                <b>${success}</b> inserted successfully.<br>
                <b>${fail}</b> failed.<br>
                Check console for details.
            `
            
        });
        // Refresh table
        if (typeof reloadCurrentTable === "function") {
            reloadCurrentTable();
        }
        console.table(apiJson.results);
    });

});
