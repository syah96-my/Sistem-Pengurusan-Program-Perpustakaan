const ProgramTable = {

    create(selector, status) {

        if (ProgramState.dtInstances[selector]) {
            ProgramState.dtInstances[selector].destroy();
            $(selector).empty();
        }

        ProgramState.dtInstances[selector] = $(selector).DataTable({
            processing: true,
            serverSide: true,
            order: [[1, "desc"]],
            ajax: {
                url: (window.GM_API_BASE || "/api/?route=") + "programs/datatables_verify",
                type: "GET",
                headers: { "X-API-KEY": ProgramConfig.API_KEY },
                data: d => {
                    d.parent_library_id = ProgramConfig.LIBRARY_ID;
                    d.library_id        = ProgramConfig.LIBRARY_ID; 
                    d.program_type_id  = ProgramConfig.PROGRAM_TYPE_ID;
                    d.status_filter    = ProgramUtils.mapStatus(status);
                }
            },

            columns: [
                {
                    data: "program_id",
                    orderable: false,
                    title: `<input type="checkbox" class="check-all">`,
                    render: id => `<input type="checkbox" class="row-check" value="${id}">`
                },
                { data: "program_id" },
                { data: "program_name" },
                { data: "program_start" },
                { data: "program_end" },
                {
                    data: null,
                    orderable: false,
                    render: row => `
                        <button class="btn-icon btn-view" data-id="${row.program_id}">View</button>
                    `
                }
            ]
        });
            // Attach AFTER creation
            ProgramState.dtInstances[selector].on("draw", function () {
                $(".check-all").prop("checked", false);
            });
    },

    reloadCurrent() {
        const sel = ProgramConfig.TABLE_MAP[ProgramState.currentTab];
        ProgramState.dtInstances[sel]?.ajax.reload(null, false);
    }

};
