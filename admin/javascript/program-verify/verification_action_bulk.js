const ProgramActionsBulk = {

    getIds(context) {
        return $(context)
            .closest(".tab-pane")
            .find(".row-check:checked")
            .map((_, e) => e.value)
            .get();
    },

    approve(btn) {
        const ids = this.getIds(btn);
        if (!ids.length) return Swal.fire("No selection", "", "info");

        $.ajax({
            url: (window.GM_API_BASE || "/api/?route=") + "programs/bulk_verify",
            method: "POST",
            headers: { "X-API-KEY": ProgramConfig.API_KEY },
            contentType: "application/json",
            data: JSON.stringify({
                programs: ProgramUtils.buildProgramsArray(ids),
                user_id: ProgramConfig.USER_ID
            })
        }).done(() => {
            Swal.fire("Done", "Bulk verification completed", "success");
            ProgramTable.reloadCurrent();
        });
    },

    reject(btn) {
        const ids = this.getIds(btn);
        if (!ids.length) return Swal.fire("No selection", "", "info");

        Swal.fire({
            title: "Bulk Reject",
            input: "textarea",
            showCancelButton: true
        }).then(sw => {
            if (!sw.isConfirmed) return;

            $.ajax({
                url: (window.GM_API_BASE || "/api/?route=") + "programs/bulk_reject",
                method: "POST",
                headers: { "X-API-KEY": ProgramConfig.API_KEY },
                contentType: "application/json",
                data: JSON.stringify({
                    programs: ProgramUtils.buildProgramsArray(ids),
                    user_id: ProgramConfig.USER_ID,
                    reason: sw.value || ""
                })
            }).done(() => {
                Swal.fire("Done", "Bulk rejection completed", "success");
                ProgramTable.reloadCurrent();
            });
        });
    },

    remove(btn) {
        const ids = this.getIds(btn);
        if (!ids.length) return Swal.fire("No selection", "", "info");

        Swal.fire({
            title: "Bulk Remove",
            text: "Please provide a reason for removal",
            input: "textarea",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Remove",
            confirmButtonColor: "#d33"
        }).then(sw => {
            if (!sw.isConfirmed) return;

            $.ajax({
                url: (window.GM_API_BASE || "/api/?route=") + "programs/remove_bulk",
                method: "POST",
                headers: { "X-API-KEY": ProgramConfig.API_KEY },
                contentType: "application/json",
                data: JSON.stringify({
                    programs: ProgramUtils.buildProgramsArray(ids),
                    user_id: ProgramConfig.USER_ID,
                    reason: sw.value || "Bulk removal"
                })
            }).done(() => {
                Swal.fire("Done", "Bulk removal completed", "success");
                ProgramTable.reloadCurrent();
            });
        });
    }


};
