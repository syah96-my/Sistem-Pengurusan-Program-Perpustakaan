/* ============================================================
   PARTICIPANTS: UPLOAD + STATS + MANAGEMENT
   ISOLATED - NO PROGRAM FORM LOGIC
============================================================ */

"use strict";

/* ============================================================
   REFRESH PARTICIPANT STATS
============================================================ */
function refreshParticipantStats(programId) {
    $.ajax({
        url: (window.GM_API_BASE || "/api/?route=") + `programs/view&program_id=${programId}`,
        headers: { "X-API-KEY": API_KEY },
        success: function (res) {
            if (!res.success) return;

            const p = res.program;
            $("#total_participant_count").text(p.total_participant_count ?? 0);
            $("#physical_participant_count").text(p.physical_participant_count ?? 0);
            $("#online_participant_count").text(p.online_participant_count ?? 0);
            $("#average_age").text(p.average_age ?? "N/A");
        }
    });
}

/* ============================================================
   PARTICIPANT CSV UPLOAD
============================================================ */
$(document).on("click", "#upload-csv-btn", function () {

    if ($("#manual_override_toggle").is(":checked")) {
        Swal.fire(
            "Disabled",
            "CSV upload is disabled when manual participant override is enabled.",
            "warning"
        );
        return;
    }

    const programId = $("#participant-upload-modal").data("program_id");
    const fileInput = $("#csv-file")[0].files[0];

    if (!fileInput) {
        Swal.fire("Error", "Please select a CSV file.", "error");
        return;
    }

    const reader = new FileReader();

    reader.onload = function (e) {
        const csvText = e.target.result.trim();

        const rows = csvText.split("\n")
            .map(r => r.split(","))
            .filter(r => r.length >= 4)
            .slice(1); // skip header

        const participants = rows.map(r => ({
            name: r[0]?.trim(),
            gender: r[1]?.trim(),
            age: r[2]?.trim(),
            email: r[3]?.trim(),
            phone: r[4]?.trim() || "",
            occupation: r[5]?.trim() || "",
            company: r[6]?.trim() || "",
            attendance_mode: r[7]?.trim() || "physical",
            registration_source: "staff_upload"
        }));

        $.ajax({
            url: (window.GM_API_BASE || "/api/?route=") + "participants/bulk_upload",
            method: "POST",
            headers: { "X-API-KEY": API_KEY },
            contentType: "application/json",
            data: JSON.stringify({
                program_id: programId,
                participants
            }),
            success: function (res) {

                let html = `
                    <p><strong>Upload completed!</strong></p>
                    <p>Total: ${res.results.length}</p>
                    <p>Success: ${res.results.filter(r => r.success).length}</p>
                    <p>Failed: ${res.results.filter(r => !r.success).length}</p>
                `;

                let failedCSV = "name,gender,age,email,reason\n";

                res.results.forEach((r, idx) => {
                    if (!r.success) {
                        const p = participants[idx];
                        failedCSV += `"${p.name}","${p.gender}","${p.age}","${p.email}","${r.error}"\n`;
                    }
                });

                if (failedCSV.length > 20) {
                    html += `
                        <br>
                        <button id="download-failed" class="btn btn-danger">
                            Download Failed CSV
                        </button>
                    `;

                    $(document)
                        .off("click", "#download-failed")
                        .on("click", "#download-failed", function () {
                            const blob = new Blob([failedCSV], { type: "text/csv" });
                            const url = URL.createObjectURL(blob);

                            const a = document.createElement("a");
                            a.href = url;
                            a.download = "failed_rows.csv";
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                        });
                }

                $("#upload-results").html(html);
                refreshParticipantStats(programId);
            }
        });
    };

    reader.readAsText(fileInput);
});

/* ============================================================
   OPEN PARTICIPANT UPLOAD MODAL
============================================================ */
$(document).on("click", "#open-upload-modal", function () {
    if ($(this).prop("disabled")) return;

    const id = $("#program-form").data("program_id");
    if (!id) return;

    const modal = $("#participant-upload-modal");

    modal
        .data("program_id", id)
        .css("display", "flex");
});


/* ============================================================
   CLOSE PARTICIPANT UPLOAD MODAL
============================================================ */
$(document).on("click", "#close-upload-modal", function () {
    $("#participant-upload-modal").removeClass("show");
    $("#upload-results").html("");
    $("#csv-file").val("");
});

/* ============================================================
   PARTICIPANT MANAGEMENT PAGE (HASHED LINK)
============================================================ */
async function hashId(id) {
    const encoder = new TextEncoder();
    const data = encoder.encode(String(id));
    const hashBuffer = await crypto.subtle.digest("SHA-256", data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
}

$(document).on("click", "#open-participant", async function () {
    if ($(this).prop("disabled")) return;

    const id = $("#program-form").data("program_id");
    if (!id) {
        Swal.fire("Error", "Missing program ID.", "error");
        return;
    }

    const encoder = new TextEncoder();
    const data = encoder.encode(String(id));
    const hashBuffer = await crypto.subtle.digest("SHA-256", data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const token = hashArray.map(b => b.toString(16).padStart(2, "0")).join("");

    const url = `manage_participant.php?p=${token}`;
    window.open(url, "_blank");
});


$(document).on("click", "#close-upload-modal", function () {
    $("#participant-upload-modal").hide();
    $("#upload-results").html("");
    $("#csv-file").val("");
});

$(document).on("click", "#participant-upload-modal", function (e) {
    if (e.target === this) {
        $(this).hide();
        $("#upload-results").html("");
        $("#csv-file").val("");
    }
});



/* ============================================================
   END PARTICIPANTS
============================================================ */
