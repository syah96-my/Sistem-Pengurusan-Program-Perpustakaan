/* ============================================================
   PROGRAM ACTION BUTTONS
   DELETE | REJECT | CANCEL | COPY LINK
============================================================ */

"use strict";

/* ============================================================
   DELETE PROGRAM
============================================================ */
$(document).on("click", ".btn-delete", function () {
    const id = $(this).data("id");

    Swal.fire({
        title: "Delete this program?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it"
    }).then(result => {
        if (!result.isConfirmed) return;

        $.ajax({
            url: (window.GM_API_BASE || "/api/?route=") + "programs/delete",
            method: "POST",
            headers: { "X-API-KEY": API_KEY },
            contentType: "application/json",
            data: JSON.stringify({
                program_id: id,
                user_id: window.GM_USER_ID
            }),
            success: function (res) {
                if (res.success) {
                    Swal.fire("Deleted!", "Program removed.", "success");
                    reloadCurrentTable();
                } else {
                    Swal.fire("Error", res.message || "Failed to delete", "error");
                }
            }
        });
    });
});

/* ============================================================
   REJECT PROGRAM
============================================================ */
$(document).on("click", ".btn-reject", function () {
    const id = $(this).data("id");

    Swal.fire({
        title: "Reject this program?",
        text: "Add a short reason for the audit trail.",
        input: "textarea",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, reject"
    }).then(result => {
        if (!result.isConfirmed) return;

        $.ajax({
            url: (window.GM_API_BASE || "/api/?route=") + "programs/reject",
            method: "POST",
            headers: { "X-API-KEY": API_KEY },
            contentType: "application/json",
            data: JSON.stringify({
                program_id: id,
                user_id: window.GM_USER_ID,
                reason: result.value || "Rejected from program table"
            }),
            success: function (res) {
                if (res.success) {
                    Swal.fire("Rejected!", "Program marked as rejected.", "success");
                    reloadCurrentTable();
                } else {
                    Swal.fire("Error", res.message || "Failed to reject", "error");
                }
            }
        });
    });
});

/* ============================================================
   CANCEL PROGRAM (STAGE CHANGE)
============================================================ */
$(document).on("click", ".btn-cancel-prog", function () {
    const id = $(this).data("id");

    Swal.fire({
        title: "Cancel this program?",
        text: "This program will move to the Cancelled stage.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, cancel it"
    }).then(result => {
        if (!result.isConfirmed) return;

        $.ajax({
            url: (window.GM_API_BASE || "/api/?route=") + "programs/set_stage",
            method: "PUT",
            headers: { "X-API-KEY": API_KEY },
            contentType: "application/json",
            data: JSON.stringify({
                program_id: id,
                program_stage: "cancelled"
            }),
            success: function (res) {
                if (res.success) {
                    Swal.fire("Cancelled!", "Program moved to cancelled stage.", "success");
                    reloadCurrentTable();
                } else {
                    Swal.fire("Error", res.message || "Failed to cancel", "error");
                }
            }
        });
    });
});

/* ============================================================
   COPY PUBLIC PROGRAM LINK
============================================================ */
$(document).on("click", ".btn-copy-link", function () {
    const programId = $(this).data("id");

    $.ajax({
        url: (window.GM_API_BASE || "/api/?route=") + `programs/view&program_id=${programId}`,
        headers: { "X-API-KEY": API_KEY },
        success: function (res) {
            if (!res?.program?.public_token) {
                Swal.fire("Error", "UID not found!", "error");
                return;
            }

            const finalURL =
                APP.DOMAIN + "/public/index.php?i=" +
                res.program.public_token;

            Swal.fire({
                title: "Copy Program Link",
                html: `
                    <p>URL for users:</p>
                    <div class="copy-url-box">
                        ${finalURL}
                    </div>
                    <br>
                    <button id="copy-url-btn"
                        class="swal2-confirm swal2-styled btn-inline-primary">
                        Copy Link
                    </button>
                `,
                showConfirmButton: false,
                didOpen: () => {
                    $("#copy-url-btn").on("click", function () {
                        navigator.clipboard.writeText(finalURL);
                        Swal.fire("Copied!", "URL copied to clipboard.", "success");
                    });
                }
            });
        }
    });
});

/* ============================================================
   END ACTIONS
============================================================ */
