document.addEventListener('DOMContentLoaded', function () {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebar      = document.getElementById('sidebar');
    const overlay      = document.getElementById('sidebarOverlay');

    // Si no existe el botón
    if (!hamburgerBtn) return;

    function openMenu() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        hamburgerBtn.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        hamburgerBtn.classList.remove('open');
        document.body.style.overflow = '';
    }

    hamburgerBtn.addEventListener('click', openMenu);
    sidebarClose.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);

    sidebar.querySelectorAll('.menu-item a').forEach(link => {
        link.addEventListener('click', closeMenu);
    });
});