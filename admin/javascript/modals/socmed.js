(function () {
    "use strict";

    const modal = document.getElementById("socmed-modal");
    const closeButtons = [
        document.getElementById("socmed-modal-close"),
        document.getElementById("socmed-cancel-btn")
    ].filter(Boolean);

    function closeModal() {
        modal?.classList.remove("show");
    }

    closeButtons.forEach((button) => {
        button.addEventListener("click", closeModal);
    });

    window.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
})();
