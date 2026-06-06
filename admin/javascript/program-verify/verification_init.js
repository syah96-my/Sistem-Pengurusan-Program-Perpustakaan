$(document).ready(() => {
    ProgramTable.create(
        ProgramConfig.TABLE_MAP[ProgramState.currentTab],
        ProgramState.currentTab
    );
});

/* Tabs */
$(document).on("click", ".tab-button", function () {
    ProgramState.currentTab = $(this).data("tab");

    $(".tab-button").removeClass("active");
    $(this).addClass("active");

    $(".tab-pane").removeClass("active");
    $("#" + ProgramState.currentTab).addClass("active");

    const sel = ProgramConfig.TABLE_MAP[ProgramState.currentTab];
    ProgramState.dtInstances[sel]
        ? ProgramTable.reloadCurrent()
        : ProgramTable.create(sel, ProgramState.currentTab);
});

// Header checkbox -> toggle all rows
$(document).on("change", ".check-all", function () {
    const checked = this.checked;
    $(".row-check").prop("checked", checked);
});

// Row checkbox -> update header state
$(document).on("change", ".row-check", function () {
    const all = $(".row-check").length;
    const checked = $(".row-check:checked").length;

    $(".check-all").prop("checked", all === checked);
});

/* Buttons */
$(document).on("click", ".btn-view", e => ProgramModal.open($(e.currentTarget).data("id")));
$(document).on("click", ".btn-delete", e => ProgramActionsSingle.delete($(e.currentTarget).data("id")));
$(document).on("click", "#btn-approve", ProgramActionsSingle.approve);
$(document).on("click", "#btn-reject", ProgramActionsSingle.reject);
$(document).on("click", "#btn-remove", ProgramActionsSingle.remove);
$(document).on("click", ".js-bulk-approve", function () { ProgramActionsBulk.approve(this); });
$(document).on("click", ".js-bulk-reject", function () { ProgramActionsBulk.reject(this); });
$(document).on("click", ".js-bulk-remove", function () { ProgramActionsBulk.remove(this); });
$(document).on("click", "#verify-modal-close, #btn-close", ProgramModal.close);
