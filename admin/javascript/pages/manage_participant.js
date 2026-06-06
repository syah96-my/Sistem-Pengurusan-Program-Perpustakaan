/* client-side search for current page */
function searchTable() {
    const q = document.getElementById('searchInput').value.trim().toLowerCase();
    const rows = document.querySelectorAll('#participantTable tbody tr');
    rows.forEach(r=>{
        if(!q){ r.style.display=''; return; }
        const text = r.textContent.toLowerCase();
        r.style.display = text.indexOf(q) !== -1 ? '' : 'none';
    });
}

document.querySelectorAll(".js-confirm-delete").forEach((form) => {
    form.addEventListener("submit", (event) => {
        if (!confirm("Delete this participant?")) {
            event.preventDefault();
        }
    });
});
