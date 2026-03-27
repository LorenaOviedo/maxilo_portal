/* Tabla de catálogos - Funciones reutilizables para todos los módulos*/

//CONFIGURACIÓN
const CatalogTable = {
  // Configuración por defecto
  config: {
    searchInputId: "searchInput",
    tableSelector: ".data-table tbody tr",
    confirmDelete: true,
    deleteMessage: "¿Estás seguro de que deseas eliminar este registro?",
  },

  //BÚSQUEDA
  initSearch: function () {
    const searchInput = document.getElementById(this.config.searchInputId);
    if (!searchInput) {
      console.warn("Search input not found");
      return;
    }
    searchInput.addEventListener("input", (e) => {
      this.filterTable(e.target.value);
    });
    console.log("✓ Búsqueda en tiempo real inicializada");
  },

  filterTable: function (searchTerm) {
    const term = searchTerm.toLowerCase().trim();
    const rows = document.querySelectorAll(this.config.tableSelector);
    let visibleCount = 0;

    rows.forEach((row) => {
      if (row.querySelector(".empty-state")) return;
      const text = row.textContent.toLowerCase();
      const shouldShow = text.includes(term);
      row.style.display = shouldShow ? "" : "none";
      if (shouldShow) visibleCount++;
    });

    this.toggleNoResults(visibleCount === 0);

    // Reiniciar paginación con los resultados filtrados
    setTimeout(() => this.pagination.reiniciar(), 50);
  },

  toggleNoResults: function (show) {
    let noResultsRow = document.getElementById("noResultsRow");
    if (show && !noResultsRow) {
      const tbody = document.querySelector(".data-table tbody");
      const colspan = document.querySelectorAll(".data-table thead th").length;
      noResultsRow = document.createElement("tr");
      noResultsRow.id = "noResultsRow";
      noResultsRow.innerHTML = `
        <td colspan="${colspan}" style="text-align: center; padding: 40px;">
          <i class="fas fa-search" style="font-size: 48px; color: #dee2e6; margin-bottom: 15px;"></i>
          <p style="font-size: 16px; color: #6c757d; font-weight: 600;">No se encontraron resultados</p>
          <p style="font-size: 14px; color: #adb5bd;">Intenta con otro término de búsqueda</p>
        </td>`;
      tbody.appendChild(noResultsRow);
    } else if (!show && noResultsRow) {
      noResultsRow.remove();
    }
  },

  clearSearch: function () {
    const searchInput = document.getElementById(this.config.searchInputId);
    if (searchInput) {
      searchInput.value = "";
      this.filterTable("");
    }
  },

  //BOTÓN DE BÚSQUEDA
  initSearchButton: function () {
    const searchBtn = document.querySelector(".btn-search");
    if (!searchBtn) {
      console.warn("Search button not found");
      return;
    }
    searchBtn.addEventListener("click", () => {
      const searchInput = document.getElementById(this.config.searchInputId);
      const searchValue = searchInput ? searchInput.value : "";
      this.filterTable(searchValue);
    });
    console.log("Botón de búsqueda inicializado");
  },

  searchInServer: function (searchTerm) {},

  //ACCIONES
  view: function (id, viewUrl = null) {
    const url = viewUrl || `detalle.php?id=${id}`;
    window.location.href = url;
  },

  edit: function (id, formUrl = null) {
    const url = formUrl || `form.php?id=${id}`;
    window.location.href = url;
  },

  delete: function (id, deleteUrl = null, customMessage = null) {
    const message = customMessage || this.config.deleteMessage;
    if (this.config.confirmDelete) {
      if (!confirm(message)) return;
    }
    if (deleteUrl) {
      this.deleteFromServer(id, deleteUrl);
    } else {
      alert(
        "Funcionalidad de eliminación pendiente de implementar.\nID: " + id,
      );
    }
  },

  deleteFromServer: function (id, deleteUrl) {
    this.showLoading(true);
    fetch(deleteUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: id }),
    })
      .then((response) => response.json())
      .then((data) => {
        this.showLoading(false);
        if (data.success) {
          this.removeTableRow(id);
          this.showNotification("Registro eliminado exitosamente", "success");
        } else {
          this.showNotification("Error al eliminar: " + data.message, "error");
        }
      })
      .catch((error) => {
        this.showLoading(false);
        console.error("Error:", error);
        this.showNotification("Error al eliminar el registro", "error");
      });
  },

  removeTableRow: function (id) {
    const rows = document.querySelectorAll(".data-table tbody tr");
    rows.forEach((row) => {
      if (row.textContent.includes(id)) {
        row.style.transition = "opacity 0.3s ease";
        row.style.opacity = "0";
        setTimeout(() => {
          row.remove();
          // Reinicializar paginación tras eliminar fila
          this.pagination.init();
          const remainingRows = document.querySelectorAll(
            ".data-table tbody tr:not(#noResultsRow)",
          );
          if (remainingRows.length === 0) this.showEmptyState();
        }, 300);
      }
    });
  },

  showEmptyState: function () {
    const tbody = document.querySelector(".data-table tbody");
    const colspan = document.querySelectorAll(".data-table thead th").length;
    tbody.innerHTML = `
      <tr>
        <td colspan="${colspan}">
          <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
            <h3 class="empty-state-title">No hay registros</h3>
            <p class="empty-state-text">Comienza agregando tu primer registro</p>
          </div>
        </td>
      </tr>`;
  },

  //NOTIFICACIONES
  showNotification: function (message, type = "info") {
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
      <i class="fas fa-${this.getNotificationIcon(type)}"></i>
      <span>${message}</span>`;
    notification.style.cssText = `
      position: fixed; top: 20px; right: 20px; padding: 15px 20px;
      background: ${this.getNotificationColor(type)}; color: white;
      border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      display: flex; align-items: center; gap: 10px;
      z-index: 9999; animation: slideIn 0.3s ease;
      font-size: 14px; font-weight: 500;`;
    document.body.appendChild(notification);
    setTimeout(() => {
      notification.style.animation = "slideOut 0.3s ease";
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  },

  getNotificationIcon: function (type) {
    const icons = {
      success: "check-circle",
      error: "exclamation-circle",
      warning: "exclamation-triangle",
      info: "info-circle",
    };
    return icons[type] || icons.info;
  },

  getNotificationColor: function (type) {
    const colors = {
      success: "#28a745",
      error: "#dc3545",
      warning: "#ffc107",
      info: "#20a89e",
    };
    return colors[type] || colors.info;
  },

  //LOADING
  showLoading: function (show) {
    let overlay = document.getElementById("loadingOverlay");
    if (show && !overlay) {
      overlay = document.createElement("div");
      overlay.id = "loadingOverlay";
      overlay.className = "loading-overlay";
      overlay.innerHTML = '<div class="spinner"></div>';
      document.querySelector(".table-container").style.position = "relative";
      document.querySelector(".table-container").appendChild(overlay);
    } else if (!show && overlay) {
      overlay.remove();
    }
  },

  // ── PAGINACIÓN DEL LADO DEL CLIENTE ──────────────────────────────────────
  pagination: {
    porPagina: 10,
    paginaActual: 1,
    totalFilas: 0,
    filas: [],

    // Inicializar — toma todas las filas y pagina de 10 en 10
    init: function () {
      const tbody = document.querySelector(".data-table tbody");
      if (!tbody) return;

      // No paginar si hay empty-state
      if (tbody.querySelector(".empty-state")) return;

      // Guardar todas las filas visibles (excluye noResultsRow)
      this.filas = Array.from(tbody.querySelectorAll("tr:not(#noResultsRow)"));
      this.totalFilas = this.filas.length;

      // Eliminar paginación previa si existe
      const prevCont = document.querySelector(".pagination");
      if (prevCont) prevCont.remove();

      // Si hay 10 o menos, mostrar todo sin controles
      if (this.totalFilas <= this.porPagina) {
        this.filas.forEach((f) => (f.style.display = ""));
        return;
      }

      this.paginaActual = 1;
      this.renderPagina();
      this.crearControles();
    },

    // Mostrar solo las filas de la página actual
    renderPagina: function () {
      const inicio = (this.paginaActual - 1) * this.porPagina;
      const fin = inicio + this.porPagina;
      this.filas.forEach((fila, i) => {
        fila.style.display = i >= inicio && i < fin ? "" : "none";
      });
      this.actualizarInfo();
    },

    // Crear contenedor de paginación
    crearControles: function () {
      const contenedor = document.createElement("div");
      contenedor.className = "pagination";
      contenedor.innerHTML = this._htmlControles();

      // Insertar después del .table-container
      const tableContainer = document.querySelector(".table-container");
      tableContainer.insertAdjacentElement("afterend", contenedor);

      contenedor
        .querySelector(".btn-pag-anterior")
        ?.addEventListener("click", () => this.irA(this.paginaActual - 1));
      contenedor
        .querySelector(".btn-pag-siguiente")
        ?.addEventListener("click", () => this.irA(this.paginaActual + 1));
    },

    // Actualizar texto e interactividad
    actualizarInfo: function () {
      const contenedor = document.querySelector(".pagination");
      if (!contenedor) return;

      const totalPags = this.totalPaginas();
      const inicio = (this.paginaActual - 1) * this.porPagina + 1;
      const fin = Math.min(this.paginaActual * this.porPagina, this.totalFilas);

      const info = contenedor.querySelector(".pagination-info");
      if (info) {
        info.textContent = `Mostrando ${inicio}–${fin} de ${this.totalFilas} registro(s) · Página ${this.paginaActual} de ${totalPags}`;
      }

      const btnAnt = contenedor.querySelector(".btn-pag-anterior");
      const btnSig = contenedor.querySelector(".btn-pag-siguiente");
      if (btnAnt) btnAnt.disabled = this.paginaActual <= 1;
      if (btnSig) btnSig.disabled = this.paginaActual >= totalPags;
    },

    // Navegar a página
    irA: function (pagina) {
      const total = this.totalPaginas();
      if (pagina < 1 || pagina > total) return;
      this.paginaActual = pagina;
      this.renderPagina();
      document
        .querySelector(".table-container")
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
    },

    // Reiniciar tras búsqueda/filtrado
    reiniciar: function () {
      const tbody = document.querySelector(".data-table tbody");
      if (!tbody) return;

      // Solo filas visibles y sin ID especial
      this.filas = Array.from(
        tbody.querySelectorAll("tr:not(#noResultsRow)"),
      ).filter((f) => f.style.display !== "none");

      this.totalFilas = this.filas.length;
      this.paginaActual = 1;

      // Eliminar controles previos
      const prevCont = document.querySelector(".pagination");
      if (prevCont) prevCont.remove();

      // Si caben en una página, mostrar todo
      if (this.totalFilas <= this.porPagina) {
        this.filas.forEach((f) => (f.style.display = ""));
        return;
      }

      this.renderPagina();
      this.crearControles();
    },

    totalPaginas: function () {
      return Math.ceil(this.totalFilas / this.porPagina);
    },

    _htmlControles: function () {
      const totalPags = this.totalPaginas();
      const fin = Math.min(this.porPagina, this.totalFilas);
      return `
        <button class="pagination-btn btn-pag-anterior" disabled>
          <i class="ri-arrow-left-line"></i> Anterior
        </button>
        <span class="pagination-info">
          Mostrando 1–${fin} de ${this.totalFilas} registro(s) · Página 1 de ${totalPags}
        </span>
        <button class="pagination-btn btn-pag-siguiente">
          Siguiente <i class="ri-arrow-right-line"></i>
        </button>`;
    },
  },

  //INICIALIZACIÓN
  init: function () {
    console.log("Inicializando Catalog Table...");
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => this.setup());
    } else {
      this.setup();
    }
  },

  setup: function () {
    this.initSearch();
    this.initSearchButton();
    this.initSort();
    this.pagination.init();
    console.log("✓ Catalog Table inicializado correctamente");
  },

  //ORDENAMIENTO
  initSort: function () {
    const headers = document.querySelectorAll(
      ".data-table thead th[data-sort]",
    );
    if (!headers.length) return;

    headers.forEach((th) => {
      th.classList.add("sortable");
      th.addEventListener("click", () => {
        const col = th.getAttribute("data-sort");
        const isAsc = th.classList.contains("sort-asc");
        headers.forEach((h) => h.classList.remove("sort-asc", "sort-desc"));
        th.classList.add(isAsc ? "sort-desc" : "sort-asc");
        this.sortTable(col, !isAsc);
        // Reinicializar paginación tras ordenar
        setTimeout(() => this.pagination.init(), 50);
      });
    });

    this.initSortMobile(headers);
    console.log("Ordenamiento inicializado");
  },

  initSortMobile: function (headers) {
    if (document.querySelector(".sort-mobile-bar")) return;
    const container = document.querySelector(".table-container");
    if (!container) return;

    const bar = document.createElement("div");
    bar.className = "sort-mobile-bar";
    const select = document.createElement("select");
    select.className = "sort-mobile-select";

    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.textContent = "Ordenar por...";
    select.appendChild(defaultOption);

    headers.forEach((th) => {
      const col = th.getAttribute("data-sort");
      const label = th.textContent.trim().replace(/\n/g, " ");

      const optAsc = document.createElement("option");
      optAsc.value = `${col}|asc`;
      optAsc.textContent = `${label} ↑`;
      select.appendChild(optAsc);

      const optDesc = document.createElement("option");
      optDesc.value = `${col}|desc`;
      optDesc.textContent = `${label} ↓`;
      select.appendChild(optDesc);
    });

    select.addEventListener("change", () => {
      if (!select.value) return;
      const [col, dir] = select.value.split("|");
      this.sortTable(col, dir === "asc");
      setTimeout(() => this.pagination.init(), 50);
    });

    bar.appendChild(select);
    container.parentNode.insertBefore(bar, container);
  },

  sortTable: function (col, asc) {
    const tbody = document.querySelector(".data-table tbody");
    const rows = Array.from(tbody.querySelectorAll("tr")).filter((r) => !r.id);

    rows.sort((a, b) => {
      const tdA = a.querySelector(`td[data-col="${col}"]`);
      const tdB = b.querySelector(`td[data-col="${col}"]`);
      if (!tdA || !tdB) return 0;

      const valA = tdA.textContent.trim().toLowerCase();
      const valB = tdB.textContent.trim().toLowerCase();
      const numA = parseFloat(valA.replace(/[^0-9.-]/g, ""));
      const numB = parseFloat(valB.replace(/[^0-9.-]/g, ""));

      if (!isNaN(numA) && !isNaN(numB)) return asc ? numA - numB : numB - numA;
      return asc
        ? valA.localeCompare(valB, "es")
        : valB.localeCompare(valA, "es");
    });

    rows.forEach((row) => tbody.appendChild(row));
  },
};

// ── FUNCIONES GLOBALES ────────────────────────────────────────────

function normalizar(str) {
  if (!str) return "";
  return str
    .toUpperCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");
}

function verRegistro(id, viewUrl = null) {
  CatalogTable.view(id, viewUrl);
}
function editarRegistro(id, formUrl = null) {
  CatalogTable.edit(id, formUrl);
}
function eliminarRegistro(id, deleteUrl = null, customMessage = null) {
  CatalogTable.delete(id, deleteUrl, customMessage);
}
function limpiarBusqueda() {
  CatalogTable.clearSearch();
}

// ── AUTOINICIALIZACIÓN ────────────────────────────────────────────
CatalogTable.init();

// Animaciones CSS
const style = document.createElement("style");
style.textContent = `
  @keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0);   opacity: 1; }
  }
  @keyframes slideOut {
    from { transform: translateX(0);   opacity: 1; }
    to   { transform: translateX(100%); opacity: 0; }
  }
`;
document.head.appendChild(style);
