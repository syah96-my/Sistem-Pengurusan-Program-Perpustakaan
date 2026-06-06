/* ===============================
   NAVBAR DROPDOWN HANDLING
   =============================== */

document.querySelectorAll('.menu-button, .user-button').forEach(button => {
    button.addEventListener('click', (e) => {
        e.stopPropagation();

        const menuId = button.getAttribute('data-menu');
        const dropdown = document.querySelector(`[data-dropdown="${menuId}"]`);
        const isActive = button.classList.contains('active');

        // Close all first
        document.querySelectorAll('.menu-button, .user-button').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('.dropdown-menu').forEach(dd => {
            dd.classList.remove('show');
        });

        // Re-open if not active
        if (!isActive) {
            button.classList.add('active');
            dropdown.classList.add('show');
        }
    });
});

// Close on click outside
document.addEventListener('click', () => {
    document.querySelectorAll('.menu-button, .user-button').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.dropdown-menu').forEach(dd => {
        dd.classList.remove('show');
    });
});

// Prevent closing when clicking inside menu
document.querySelectorAll('.dropdown-menu').forEach(drop => {
    drop.addEventListener('click', (e) => e.stopPropagation());
});
