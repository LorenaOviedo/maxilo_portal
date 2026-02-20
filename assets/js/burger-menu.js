document.addEventListener('DOMContentLoaded', function () {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebar      = document.getElementById('sidebar');
    const overlay      = document.getElementById('sidebarOverlay');

    if (!hamburgerBtn || !sidebar) return;

    function openMenu() {
        sidebar.classList.add('open');
        overlay && overlay.classList.add('active');
        hamburgerBtn.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        sidebar.classList.remove('open');
        overlay && overlay.classList.remove('active');
        hamburgerBtn.classList.remove('open');
        document.body.style.overflow = '';
    }

    // Soporte táctil explícito para iOS/Edge
    hamburgerBtn.addEventListener('click', openMenu, { passive: true });
    hamburgerBtn.addEventListener('touchend', function(e) {
        e.preventDefault();
        openMenu();
    });

    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeMenu, { passive: true });
        sidebarClose.addEventListener('touchend', function(e) {
            e.preventDefault();
            closeMenu();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeMenu, { passive: true });
        overlay.addEventListener('touchend', function(e) {
            e.preventDefault();
            closeMenu();
        });
    }

    sidebar.querySelectorAll('.menu-item a').forEach(link => {
        link.addEventListener('click', closeMenu);
    });
});