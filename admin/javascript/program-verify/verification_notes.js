const ProgramNotes = {

    load(programId) {
        $("#v-notes-list").html("Loading...");

        $.ajax({
            url: (window.GM_API_BASE || "/api/?route=") + `programs/note/list&program_id=${programId}`,
            headers: { "X-API-KEY": ProgramConfig.API_KEY }
        }).done(res => {
            const box = $("#v-notes-list").empty();

            if (!res.notes || res.notes.length === 0) {
                box.html(`<div class="note-item empty">No notes found.</div>`);
                return;
            }

            res.notes.forEach(n => {
                box.append(`
                    <div class="note-item">
                        <div class="note-header">
                            <strong>${ProgramUtils.esc(n.username)}</strong>
                            <span>${ProgramUtils.esc(n.role)}</span>
                            <small>${ProgramUtils.esc(n.created_at)}</small>
                        </div>
                        <div class="note-text">${ProgramUtils.esc(n.note_text)}</div>
                    </div>
                `);
            });
        });
    }

};
