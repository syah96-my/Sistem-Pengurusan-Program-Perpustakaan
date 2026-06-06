(function () {
    "use strict";

    const API_KEY     = window.GM_API_KEY;
    const API_BASE = window.GM_API_BASE || "/api/?route=";
    const LIBRARY_ID  = window.GM_LIBRARY_ID;
    const USER_ID     = window.GM_USER_ID;
    const LIB_TYPE_ID = window.GM_TYPE_ID;
    const PARENT_ID   = window.GM_PARENT_ID;

    let table;

    /* ================================
       LOAD PLATFORMS
    ================================ */
    function loadPlatforms(target = "#platform_id", selected = null) {
        return $.ajax({
            url: API_BASE + "platforms/list",
            headers: { "X-API-KEY": API_KEY }
        }).done(res => {
            const sel = $(target).empty()
                .append(`<option value="" disabled>Please Select</option>`);

            res.platforms?.forEach(p =>
                sel.append(`<option value="${p.id}">${p.platform_name}</option>`)
            );

            if (selected) sel.val(selected);
        });
    }

    /* ================================
       LOAD PROGRAM TYPES
    ================================ */
    function loadProgramTypes(selected = null) {
        return $.ajax({
            url: API_BASE + "program_types/list",
            headers: { "X-API-KEY": API_KEY }
        }).done(res => {
            const sel = $("#program_type_id").empty()
                .append(`<option value="" disabled>Please Select</option>`);

            res.types?.forEach(t =>
                sel.append(`<option value="${t.id}">${t.type_name}</option>`)
            );

            if (selected) sel.val(selected);
        });
    }

    /* ================================
       DATATABLE
    ================================ */
    function initTable() {
        table = $("#socmed-table").DataTable({
            processing: true,
            serverSide: false,
            order: [[3, "desc"]],
            ajax: {
                url: API_BASE + "socmed/list",
                headers: { "X-API-KEY": API_KEY },
                data: d => {
                    d.library_id  = LIBRARY_ID;
                    d.platform_id = $("#filter-platform").val();
                    d.year        = $("#filter-year").val();
                },
                dataSrc: "data"
            },
            columns: [
                { data: "activity_id" },
                { data: "activity_title" },
                { data: "platform_name" },
                { data: "activity_date" },
                {
                    data: "post_url",
                    render: v => v ? `<a href="${v}" target="_blank">View</a>` : "-"
                },
                {
                    data: null,
                    orderable: false,
                    render: r => `
                        <button class="btn-icon btn-edit" data-id="${r.activity_id}">Edit</button>
                        <button class="btn-icon btn-delete" data-id="${r.activity_id}">Delete</button>
                    `
                }
            ]
        });
    }

    /* ================================
       ADD
    ================================ */
    $("#add-btn").on("click", () => {
        $("#socmed-form")[0].reset();
        $("#socmed-form").data("mode", "create").removeData("activity_id");

        loadPlatforms();
        loadProgramTypes();

        $("#socmed-modal").addClass("show");
    });

    /* ================================
       EDIT
    ================================ */
    $(document).on("click", ".btn-edit", function () {
        const activityId = $(this).data("id");

        $.ajax({
            url: API_BASE + "socmed/view&activity_id=" + activityId,
            headers: { "X-API-KEY": API_KEY }
        }).done(res => {
            const s = res.data;

            $("#socmed-form")
                .data("mode", "edit")
                .data("activity_id", activityId);

            $("#activity_name").val(s.activity_title);
            $("#activity_date").val(s.activity_date); // correct for <input type="date">
            $("#document_url").val(s.post_url);
            $("#activity_details").val(s.activity_description);
            $("#reach_estimate").val(s.reach_estimate);
            $("#engagement_estimate").val(s.engagement_estimate);

            $.when(
                loadPlatforms("#platform_id", s.platform_id),
                loadProgramTypes(s.program_type_id)
            ).then(() => {
                $("#socmed-modal").addClass("show");
            });
        });
    });

    /* ================================
       SAVE (CREATE + UPDATE)
    ================================ */
    $("#socmed-form").on("submit", function (e) {
        e.preventDefault();

        const mode = $(this).data("mode");
        const activityId = $(this).data("activity_id");

        const payload = {
            parent_library_id: PARENT_ID || null,
            library_id: LIBRARY_ID,
            library_type_id: LIB_TYPE_ID,
            program_type_id: $("#program_type_id").val(),
            platform_id: $("#platform_id").val(),
            activity_title: $("#activity_name").val(),
            activity_description: $("#activity_details").val(),
            activity_date: $("#activity_date").val(), // DATE ONLY
            post_url: $("#document_url").val(),
            reach_estimate: $("#reach_estimate").val() || null,
            engagement_estimate: $("#engagement_estimate").val() || null,
            user_id: USER_ID
        };

        let url = API_BASE + "socmed/create";
        let method = "POST";

        if (mode === "edit") {
            payload.activity_id = activityId;
            url = API_BASE + "socmed/update";
            method = "PUT";
        }

        $.ajax({
            url,
            method,
            headers: { "X-API-KEY": API_KEY },
            contentType: "application/json",
            data: JSON.stringify(payload)
        }).done(() => {
            $("#socmed-modal").removeClass("show");
            table.ajax.reload(null, false);
            Swal.fire("Saved", "Social media activity saved.", "success");
        });
    });

    /* ================================
       DELETE
    ================================ */
    $(document).on("click", ".btn-delete", function () {
        const activityId = $(this).data("id");

        Swal.fire({
            title: "Delete this activity?",
            icon: "warning",
            showCancelButton: true
        }).then(r => {
            if (!r.isConfirmed) return;

            $.ajax({
                url: API_BASE + "socmed/delete",
                method: "POST",
                headers: { "X-API-KEY": API_KEY },
                contentType: "application/json",
                data: JSON.stringify({
                    activity_id: activityId,
                    user_id: USER_ID
                })
            }).done(() => table.ajax.reload(null, false));
        });
    });

    /* ================================
       INIT
    ================================ */
    $(document).ready(() => {
        $.ajax({
            url: API_BASE + "platforms/list",
            headers: { "X-API-KEY": API_KEY }
        }).done(res => {
            const sel = $("#filter-platform").empty()
                .append(`<option value="">All Platforms</option>`);

            res.platforms?.forEach(p =>
                sel.append(`<option value="${p.id}">${p.platform_name}</option>`)
            );
        });

        initTable();
    });

    $("#filter-platform, #filter-year").on("change", function () {
        if (table) {
            table.ajax.reload();
        }
    });



})();
