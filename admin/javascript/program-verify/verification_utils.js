const ProgramUtils = {

    esc(value) {
        if (!value) return "";
        return String(value).replace(/[&<>"]/g, c => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;"
        }[c]));
    },

    mapStatus(uiStatus) {
        return uiStatus === "approved" ? "verified" : uiStatus;
    },

    buildProgramsArray(ids) {
        return ids.map(id => ({ program_id: parseInt(id, 10) }));
    }

};
