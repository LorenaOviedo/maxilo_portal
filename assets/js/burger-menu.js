document.addEventListener("DOMContentLoaded", function () {
  const hamburgerBtn = document.getElementById("hamburgerBtn");
  const sidebarClose = document.getElementById("sidebarClose");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebarOverlay");
  const userAvatarBtn = document.getElementById("userAvatarBtn");
  const userDropdown = document.getElementById("userDropdown");

  if (userAvatarBtn && userDropdown) {
    userAvatarBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      userDropdown.classList.toggle("show");
    });

    // Cerrar el modal si se hace clic fuera de él
    document.addEventListener("click", function (e) {
      if (!userAvatarBtn.contains(e.target)) {
        userDropdown.classList.remove("show");
      }
    });
  }

  if (!hamburgerBtn || !sidebar) return;

  function openMenu() {
    sidebar.classList.add("open");
    overlay && overlay.classList.add("active");
    hamburgerBtn.classList.add("open");
    document.body.style.overflow = "hidden";
  }

  function closeMenu() {
    sidebar.classList.remove("open");
    overlay && overlay.classList.remove("active");
    hamburgerBtn.classList.remove("open");
    document.body.style.overflow = "";
  }

  // Soporte táctil exp. para iOS/Edge
  hamburgerBtn.addEventListener("click", openMenu, { passive: true });
  hamburgerBtn.addEventListener("touchend", function (e) {
    e.preventDefault();
    openMenu();
  });

  if (sidebarClose) {
    sidebarClose.addEventListener("click", closeMenu, { passive: true });
    sidebarClose.addEventListener("touchend", function (e) {
      e.preventDefault();
      closeMenu();
    });
  }

  if (overlay) {
    overlay.addEventListener("click", closeMenu, { passive: true });
    overlay.addEventListener("touchend", function (e) {
      e.preventDefault();
      closeMenu();
    });
  }

  sidebar.querySelectorAll(".menu-item a").forEach((link) => {
    link.addEventListener("click", closeMenu);
  });
});
