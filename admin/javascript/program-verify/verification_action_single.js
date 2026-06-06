const ProgramActionsSingle = {

    approve() {
        $.ajax({
            url: (window.GM_API_BASE || "/api/?route=") + "programs/verify",
            method: "POST",
            headers: { "X-API-KEY": ProgramConfig.API_KEY },
            contentType: "application/json",
            data: JSON.stringify({
                program_id: ProgramState.currentProgramId,
                user_id: ProgramConfig.USER_ID
            })
        }).done(() => {
            Swal.fire("Approved", "Program verified.", "success");
            ProgramModal.close();
            ProgramTable.reloadCurrent();
        });
    },

    reject() {
        Swal.fire({
            title: "Reject Program",
            input: "textarea",
            icon: "warning",
            showCancelButton: true
        }).then(sw => {
            if (!sw.isConfirmed) return;

            $.ajax({
                url: (window.GM_API_BASE || "/api/?route=") + "programs/reject",
                method: "POST",
                headers: { "X-API-KEY": ProgramConfig.API_KEY },
                contentType: "application/json",
                data: JSON.stringify({
                    program_id: ProgramState.currentProgramId,
                    user_id: ProgramConfig.USER_ID,
                    reason: sw.value || ""
                })
            }).done(() => {
                Swal.fire("Rejected", "Program rejected.", "success");
                ProgramModal.close();
                ProgramTable.reloadCurrent();
            });
        });
    },

    remove() {
        Swal.fire({
            title: "Delete Program",
            input: "textarea",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Delete",
            confirmButtonColor: "#d33"
        }).then(sw => {
            if (!sw.isConfirmed) return;

            $.ajax({
                url: (window.GM_API_BASE || "/api/?route=") + "programs/remove",
                method: "POST",
                headers: { "X-API-KEY": ProgramConfig.API_KEY },
                contentType: "application/json",
                data: JSON.stringify({
                    program_id: ProgramState.currentProgramId,
                    user_id: ProgramConfig.USER_ID,
                    reason: sw.value || "Deleted via workflow"
                })
            }).done(() => {
                Swal.fire("Deleted", "Program deleted.", "success");
                ProgramModal.close();
                ProgramTable.reloadCurrent();
            });
        });
    }


};
