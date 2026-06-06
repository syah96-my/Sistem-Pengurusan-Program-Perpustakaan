const ProgramModal = {

    open(programId) {
        ProgramState.currentProgramId = programId;

        $.ajax({
            url: (window.GM_API_BASE || "/api/?route=") + `programs/view&program_id=${programId}`,
            headers: { "X-API-KEY": ProgramConfig.API_KEY }
        }).done(res => {
            const p = res.program || {};
 
            // --------------------------------------------------
            // METADATA
            // --------------------------------------------------
            $("#v-program-name").text(ProgramUtils.esc(p.program_name ?? "-"));
            $("#v-library-name").text(ProgramUtils.esc(p.library_name ?? "-"));
            $("#v-program-id").text(p.program_id ?? "-");
            $("#v-library-type").text(p.library_type_name ?? "-");
            $("#v-parent-library").text(p.parent_library_name ?? "None");
            

            // --------------------------------------------------
            // BASIC INFO
            // --------------------------------------------------
            $("#v-scale").text(p.scale_name ?? "-");
            $("#v-mode").text(p.mode ?? "-");
            $("#v-start").text(p.program_start ?? "-");
            $("#v-end").text(p.program_end ?? "-");
            $("#v-location").text(p.location ?? "-");
            $("#v-platform").text(p.platform_name ?? "-");
            $("#v-officiated_by").text(p.officiated_by ?? "-");
            $("#v-image").text(p.cover_image_url ?? "-");
            $("#v-documents").text(p.document_url ?? "-");

            if (Array.isArray(p.target_groups) && p.target_groups.length > 0) {
                const html = p.target_groups.map(g => {
                    const bg = colorFromId(g.id);
                    const fg = textColorFromId(g.id);

                    return `
                        <span class="tg-pill"
                            class="status-pill">
                            ${ProgramUtils.esc(g.group_name)}
                        </span>
                    `;
                }).join(" ");

                $("#v-target-groups").html(html);
            } else {
                $("#v-target-groups").text("-");
            }


            $("#v-details").text(ProgramUtils.esc(p.program_details ?? "-"));

            // --------------------------------------------------
            // PARTICIPANTS
            // --------------------------------------------------
            const physical = Number(p.physical_participant_count ?? 0);
            const online   = Number(p.online_participant_count ?? 0);
            const total    = Number(p.total_participant_count ?? (physical + online));

            $("#v-p-physical").text(physical);
            $("#v-p-online").text(online);
            $("#v-p-total").text(total);

            // Participant source status (NEW)
            setParticipantStatus(p);

            // --------------------------------------------------
            // NOTES
            // --------------------------------------------------
            ProgramNotes.load(programId);

            $("#program-verify-modal").addClass("show");
        });
    },

    close() {
        $("#program-verify-modal").removeClass("show");
    }
};


/* ============================================================
   PARTICIPANT STATUS LOGIC
============================================================ */
function setParticipantStatus(p) {
    const tag = $("#v-participant-status");

    // Reset
    tag
        .removeClass()
        .addClass("participant-tag");

    // Manual override wins
    if (parseInt(p.is_manual_override, 10) === 1) {
        tag.addClass("manual")
           .text("Manual Input Participant");
        return;
    }

    const self  = parseInt(p.self_registered_participant_count || 0, 10);
    const staff = parseInt(p.staff_uploaded_participant_count || 0, 10);

    if (self > 0 && staff > 0) {
        tag.addClass("mixed")
           .text("Self Registered + Staff Uploaded");
    } else if (self > 0) {
        tag.addClass("self")
           .text("Self Registered");
    } else if (staff > 0) {
        tag.addClass("staff")
           .text("Staff Uploaded");
    } else {
        tag.addClass("none")
           .text("No Participant Source");
    }
}


function colorFromId(id) {
    const hue = (parseInt(id, 10) * 47) % 360;  // prime-ish multiplier
    return `hsl(${hue}, 70%, 85%)`;            // soft pastel
}

function textColorFromId(id) {
    const hue = (parseInt(id, 10) * 47) % 360;
    return `hsl(${hue}, 70%, 30%)`;             // darker text
}
