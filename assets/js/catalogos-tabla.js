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
  /*Inicializar búsqueda en tiempo real*/
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

  /**
   * Filtrar tabla por término de búsqueda
   * @param {string} searchTerm - Término a buscar
   */
  filterTable: function (searchTerm) {
    const term = searchTerm.toLowerCase().trim();
    const rows = document.querySelectorAll(this.config.tableSelector);
    let visibleCount = 0;

    rows.forEach((row) => {
      // Ignora fila de empty state
      if (row.querySelector(".empty-state")) {
        return;
      }

      const text = row.textContent.toLowerCase();
      const shouldShow = text.includes(term);

      row.style.display = shouldShow ? "" : "none";

      if (shouldShow) {
        visibleCount++;
      }
    });

    // Mostrar mensaje si no hay resultados
    this.toggleNoResults(visibleCount === 0);
  },

  /**
   * Mostrar/ocultar mensaje de "sin resultados"
   * @param {boolean} show - Mostrar u ocultar
   */
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
                </td>
            `;
      tbody.appendChild(noResultsRow);
    } else if (!show && noResultsRow) {
      noResultsRow.remove();
    }
  },

  /*Limpiar búsqueda*/
  clearSearch: function () {
    const searchInput = document.getElementById(this.config.searchInputId);
    if (searchInput) {
      searchInput.value = "";
      this.filterTable("");
    }
  },

  //BOTÓN DE BÚSQUEDA
  /*Inicializar botón de búsqueda*/
  initSearchButton: function () {
    const searchBtn = document.querySelector(".btn-search");

    if (!searchBtn) {
      console.warn("Search button not found");
      return;
    }

    searchBtn.addEventListener("click", () => {
      const searchInput = document.getElementById(this.config.searchInputId);
      const searchValue = searchInput ? searchInput.value : "";

      console.log("Buscando:", searchValue);

      // Aquí puedes implementar búsqueda en servidor si lo necesitas
      // this.searchInServer(searchValue);
    });

    console.log("Botón de búsqueda inicializado");
  },

  /**
   * Búsqueda en servidor (pendiente)!
   * @param {string} searchTerm - Término a buscar
   */
  searchInServer: function (searchTerm) {
    // Ejemplo de implementación con fetch
    /*
        fetch('search_endpoint.php?q=' + encodeURIComponent(searchTerm))
            .then(response => response.json())
            .then(data => {
                // Actualizar tabla con resultados
                this.updateTableWithResults(data);
            })
            .catch(error => {
                console.error('Error en búsqueda:', error);
            });
        */
  },

  //ACCIONES (VER/EDITAR/ELIMINAR)
  /**
   * Ver detalles del registro
   * @param {string} id - ID del registro
   * @param {string} viewUrl - URL de la página de detalles (opc)
   */
  view: function (id, viewUrl = null) {
    const url = viewUrl || `detalle.php?id=${id}`;
    window.location.href = url;
  },

  /**
   * Editar registro
   * @param {string} id - ID del registro
   * @param {string} formUrl - URL del formulario de edición (opc)
   */
  edit: function (id, formUrl = null) {
    const url = formUrl || `form.php?id=${id}`; //EDITAR POR URL DE FORMULARIO DE EDICIÓN
    window.location.href = url;
  },

  /**
   * Eliminar registro
   * @param {string} id - ID del registro
   * @param {string} deleteUrl - URL del endpoint de eliminación (opc)
   * @param {string} customMessage - Mensaje personalizado de confirmación (opc)
   */
  delete: function (id, deleteUrl = null, customMessage = null) {
    const message = customMessage || this.config.deleteMessage;

    if (this.config.confirmDelete) {
      if (!confirm(message)) {
        return;
      }
    }
    // Si hay URL de eliminación, hacer petición
    if (deleteUrl) {
      this.deleteFromServer(id, deleteUrl);
    } else {
      //solo mostrar alerta
      alert(
        "Funcionalidad de eliminación pendiente de implementar.\nID: " + id,
      );
    }
  },

  /**
   * Eliminar registro en servidor
   * @param {string} id - ID del registro
   * @param {string} deleteUrl - URL del endpoint
   */
  deleteFromServer: function (id, deleteUrl) {
    // Mostrar loading
    this.showLoading(true);

    fetch(deleteUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ id: id }),
    })
      .then((response) => response.json())
      .then((data) => {
        this.showLoading(false);

        if (data.success) {
          // Eliminar fila de la tabla
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

  /**
   * Remover fila de la tabla
   * @param {string} id - ID del registro
   */
  removeTableRow: function (id) {
    const rows = document.querySelectorAll(".data-table tbody tr");
    rows.forEach((row) => {
      // Buscar la fila que contenga el ID
      if (row.textContent.includes(id)) {
        row.style.transition = "opacity 0.3s ease";
        row.style.opacity = "0";

        setTimeout(() => {
          row.remove();

          // Verificar si quedan filas
          const remainingRows = document.querySelectorAll(
            ".data-table tbody tr:not(#noResultsRow)",
          );
          if (remainingRows.length === 0) {
            this.showEmptyState();
          }
        }, 300);
      }
    });
  },

  /*Mostrar estado vacío*/
  showEmptyState: function () {
    const tbody = document.querySelector(".data-table tbody");
    const colspan = document.querySelectorAll(".data-table thead th").length;

    tbody.innerHTML = `
            <tr>
                <td colspan="${colspan}">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <h3 class="empty-state-title">No hay registros</h3>
                        <p class="empty-state-text">Comienza agregando tu primer registro</p>
                    </div>
                </td>
            </tr>
        `;
  },

  //NOTIFICACIONES
  /**
   * Mostrar notificación
   * @param {string} message - Mensaje a mostrar
   * @param {string} type - Tipo: 'success', 'error', 'warning', 'info'
   */
  showNotification: function (message, type = "info") {
    // Crear elemento de notificación
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
            <i class="fas fa-${this.getNotificationIcon(type)}"></i>
            <span>${message}</span>
        `;

    // Estilos dinámicos para la notificación
    notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${this.getNotificationColor(type)};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        `;

    document.body.appendChild(notification);

    //Quitar automaticamente después de 3 segundos
    setTimeout(() => {
      notification.style.animation = "slideOut 0.3s ease";
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  },

  /*Obtener icono según tipo de notificación*/
  getNotificationIcon: function (type) {
    const icons = {
      success: "check-circle",
      error: "exclamation-circle",
      warning: "exclamation-triangle",
      info: "info-circle",
    };
    return icons[type] || icons.info;
  },

  /*Obtener color según tipo de notificación*/
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
  /**
   * Mostrar/ocultar loading overlay
   * @param {boolean} show - Mostrar u ocultar
   */
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

  //INICIALIZACIÓN
  /**
   * Inicializar todas las funcionalidades
   */
  init: function () {
    console.log("Inicializando Catalog Table...");

    // Esperar a que el DOM esté listo
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => {
        this.setup();
      });
    } else {
      this.setup();
    }
  },

  /* Configurar todas las funcionalidades*/
  setup: function () {
    this.initSearch();
    this.initSearchButton();
    this.initSort();
    console.log("Catalog Table inicializado correctamente");
  },

  //ORDENAMIENTO

  /**
   * Inicializar ordenamiento
   * Lee los th con atributo data-sort, se ordena por esa columna mediante un click listener
   */
  initSort: function () {
    const headers = document.querySelectorAll(
      ".data-table thead th[data-sort]",
    );
    if (!headers.length) return;

    // Escritorio: flechitas en encabezados
    headers.forEach((th) => {
      th.classList.add("sortable");
      th.addEventListener("click", () => {
        const col = th.getAttribute("data-sort");
        const isAsc = th.classList.contains("sort-asc");

        // Limpiar clases de otros headers
        headers.forEach((h) => h.classList.remove("sort-asc", "sort-desc"));

        // Aplicar dirección
        th.classList.add(isAsc ? "sort-desc" : "sort-asc");
        this.sortTable(col, !isAsc);
      });
    });

    // Móvil: crear select dinámico con las columnas que están disponibles
    this.initSortMobile(headers);

    console.log("Ordenamiento inicializado");
  },

  /**
   * Crear select de ordenamiento para móvil
   */
  initSortMobile: function (headers) {
    if (document.querySelector(".sort-mobile-bar")) return; // Evitar duplicados
    const container = document.querySelector(".table-container");
    if (!container) return;

    const bar = document.createElement("div");
    bar.className = "sort-mobile-bar";

    const select = document.createElement("select");
    select.className = "sort-mobile-select";

    // Opción por defecto
    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.textContent = "Ordenar por...";
    select.appendChild(defaultOption);

    // Una opción por columna con data-sort, en ambas direcciones
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
    });

    bar.appendChild(select);
    container.parentNode.insertBefore(bar, container);
  },

  /**
   * Ordenar filas de la tabla por columna
   * @param {string} col   - valor del atributo data-sort del th
   * @param {boolean} asc  - true = ascendente
   */
  sortTable: function (col, asc) {
    const tbody = document.querySelector(".data-table tbody");
    const rows = Array.from(tbody.querySelectorAll("tr")).filter((r) => !r.id);

    rows.sort((a, b) => {
      const tdA = a.querySelector(`td[data-col="${col}"]`);
      const tdB = b.querySelector(`td[data-col="${col}"]`);

      if (!tdA || !tdB) return 0;

      const valA = tdA.textContent.trim().toLowerCase();
      const valB = tdB.textContent.trim().toLowerCase();

      // Intentar ordenar como número si aplica
      const numA = parseFloat(valA.replace(/[^0-9.-]/g, ""));
      const numB = parseFloat(valB.replace(/[^0-9.-]/g, ""));

      if (!isNaN(numA) && !isNaN(numB)) {
        return asc ? numA - numB : numB - numA;
      }

      return asc
        ? valA.localeCompare(valB, "es")
        : valB.localeCompare(valA, "es");
    });

    rows.forEach((row) => tbody.appendChild(row));
  },
};

function normalizar(str) {
  if (!str) return "";
  return str
    .toUpperCase()
    .normalize("NFD") // separa letras de acentos
    .replace(/[\u0300-\u036f]/g, "") // elimina los acentos
}

//FUNCIONES GLOBALES
/*Ver detalles del registro*/
function verRegistro(id, viewUrl = null) {
  CatalogTable.view(id, viewUrl);
}

/*Editar registro*/
function editarRegistro(id, formUrl = null) {
  CatalogTable.edit(id, formUrl);
}

/*Eliminar registro*/
function eliminarRegistro(id, deleteUrl = null, customMessage = null) {
  CatalogTable.delete(id, deleteUrl, customMessage);
}

/*Limpiar búsqueda*/
function limpiarBusqueda() {
  CatalogTable.clearSearch();
}

//AUTOINICIALIZACIÓN

// Inicializar automáticamente cuando se carga el script
CatalogTable.init();

// Agregar animaciones CSS
const style = document.createElement("style");
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
