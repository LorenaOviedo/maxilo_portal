/**
 * pacientes.js
 *
 * Lógica específica del módulo de Pacientes.
 * Depende de: modal.js, catalogos-tabla.js
 *
 * Variables inyectadas desde PHP en pacientes.php:
 *   var API_URL   = '...';
 *   var CATALOGOS = { tiposSangre: [...], parentescos: [...], ocupaciones: [...] };
 */

const MODAL_PAC_ID = "modalPaciente";
const POR_PAGINA = 10;

let busquedaActual = "";
let paginaActual = 1;
let busquedaTimer = null;

// ── Inicialización ─────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", function () {
  iniciarBusqueda();
});

// ── Búsqueda AJAX con debounce ─────────────────────────────────────
function iniciarBusqueda() {
  const searchInput = document.getElementById("searchInput");
  const btnSearch = document.querySelector(".btn-search");

  if (searchInput) {
    searchInput.addEventListener("input", () => {
      clearTimeout(busquedaTimer);
      busquedaTimer = setTimeout(() => {
        busquedaActual = searchInput.value.trim();
        paginaActual = 1;
        buscarPacientes();
      }, 400);
    });
  }

  if (btnSearch) {
    btnSearch.addEventListener("click", () => {
      busquedaActual = searchInput?.value.trim() ?? "";
      paginaActual = 1;
      buscarPacientes();
    });
  }
}

function buscarPacientes() {
  const url =
    `${API_URL}?modulo=pacientes&accion=search` +
    `&buscar=${encodeURIComponent(busquedaActual)}` +
    `&pagina=${paginaActual}` +
    `&por_pagina=${POR_PAGINA}`;

  CatalogTable.showLoading(true);

  fetch(url)
    .then((r) => r.json())
    .then((data) => {
      CatalogTable.showLoading(false);
      if (data.success) {
        actualizarTabla(data.pacientes);
        actualizarPaginacion(data.paginacion);
      } else {
        CatalogTable.showNotification("Error al buscar", "error");
      }
    })
    .catch(() => {
      CatalogTable.showLoading(false);
      CatalogTable.showNotification("Error de conexión", "error");
    });
}

function actualizarTabla(pacientes) {
  const tbody = document.querySelector(".data-table tbody");

  if (!pacientes || !pacientes.length) {
    tbody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align:center; padding:40px;">
                    <i class="ri-search-line" style="font-size:48px; color:#dee2e6; display:block; margin-bottom:15px;"></i>
                    <p style="font-size:16px; color:#6c757d; font-weight:600;">No se encontraron resultados</p>
                    <p style="font-size:14px; color:#adb5bd;">Intenta con otro término de búsqueda</p>
                </td>
            </tr>`;
    return;
  }

  tbody.innerHTML = pacientes
    .map((p) => {
      const activo = parseInt(p.id_estatus) === 1;
      const numero = p.numero_paciente;
      const ultimaVisita = p.ultima_visita
        ? new Date(p.ultima_visita).toLocaleDateString("es-MX")
        : "—";
      const nombre = normalizar(p.nombre_completo || "").replace(/'/g, "\\'");
      const badgeEstatus = activo
        ? `<span class="badge badge-active">${p.estatus.toUpperCase()}</span>`
        : `<span class="badge badge-inactive">${p.estatus.toUpperCase()}</span>`;
      const btnEstatus = activo
        ? `<button type="button" class="btn-action btn-delete" title="Desactivar"
                onclick="cambiarEstatusPaciente(${p.numero_paciente}, 2, '${nombre}')">
                <i class="ri-forbid-line"></i></button>`
        : `<button type="button" class="btn-action btn-activate" title="Activar"
                onclick="cambiarEstatusPaciente(${p.numero_paciente}, 1, '${nombre}')">
                <i class="ri-checkbox-circle-line"></i></button>`;

      return `
            <tr>
                <td class="col-id"    data-label="No. Paciente"  data-col="numero">
                    <span style="font-weight:700;">${p.numero_paciente}</span>
                    <br>
                    <span style="font-size:11px; color:#6c757d;">Exp: ${p.id_paciente_expediente}</span>
                </td>
                <td class="col-name"  data-label="Nombre"        data-col="nombre">${normalizar(p.nombre_completo)}</td>
                <td class="col-tel"   data-label="Teléfono">${p.telefono || "—"}</td>
                <td class="col-email" data-label="Correo">
                    <span class="text-truncate" title="${p.email || ""}">${p.email || "—"}</span>
                </td>
                <td class="col-edad text-center"  data-label="Edad"        data-col="edad">${p.edad || "—"}</td>
                <td class="col-visit text-center" data-label="Última visita">${ultimaVisita}</td>
                <td class="col-status text-center" data-label="Estatus">${badgeEstatus}</td>
                <td class="col-actions" data-label="Acciones">
                    <div class="action-buttons">
                        <button type="button" class="btn-action btn-view" title="Ver"
                            onclick="abrirModalVerPaciente(${p.numero_paciente})">
                            <i class="ri-eye-line"></i></button>
                        <button type="button" class="btn-action btn-edit" title="Editar"
                            onclick="abrirModalEditarPaciente(${p.numero_paciente})">
                            <i class="ri-edit-box-line"></i></button>
                        ${btnEstatus}
                    </div>
                </td>
            </tr>`;
    })
    .join("");
}

function actualizarPaginacion(p) {
  const contenedor = document.querySelector(".pagination");
  if (!contenedor) return;

  const hayAnterior = p.pagina_actual > 1;
  const haySiguiente = p.pagina_actual < p.total_paginas;

  contenedor.innerHTML = `
        <button class="pagination-btn" ${!hayAnterior ? "disabled" : ""}
            onclick="irAPagina(${p.pagina_actual - 1})">
            <i class="ri-arrow-left-line"></i> Anterior
        </button>
        <span class="pagination-info">
            Mostrando ${p.inicio}–${p.fin} de ${p.total} paciente(s)
            &nbsp;·&nbsp; Página ${p.pagina_actual} de ${p.total_paginas}
        </span>
        <button class="pagination-btn" ${!haySiguiente ? "disabled" : ""}
            onclick="irAPagina(${p.pagina_actual + 1})">
            Siguiente <i class="ri-arrow-right-line"></i>
        </button>`;
}

function irAPagina(pagina) {
  paginaActual = pagina;
  buscarPacientes();
  document
    .querySelector(".table-container")
    ?.scrollIntoView({ behavior: "smooth" });
}

// ── Cargar datos del paciente desde el servidor ────────────────────
function cargarPaciente(id, callback) {
  fetch(`${API_URL}?modulo=pacientes&accion=get&id=${id}`)
    .then((r) => r.json())
    .then((data) => {
      if (!data.success) {
        CatalogTable.showNotification("No se pudo cargar el paciente", "error");
        return;
      }
      callback(data.paciente);
    })
    .catch(() =>
      CatalogTable.showNotification("Error al obtener los datos", "error"),
    );
}

function mapearDatosPaciente(p) {
  // ids 1,2,3,4,5 = teléfonos | id 6 = correo electrónico
  const telefono =
    (p.contactos || []).find((c) =>
      [1, 2, 3, 4, 5].includes(parseInt(c.id_tipo_contacto)),
    )?.valor || "";
  const email =
    (p.contactos || []).find((c) => parseInt(c.id_tipo_contacto) === 6)
      ?.valor || "";
  const ce = p.contacto_emergencia || {};
  const hist = p.historial || {};

  return {
    // Tab 1: Información personal — coincide con name= del modal
    id: p.numero_paciente,
    nombre: normalizar(p.nombre),
    apellido_paterno: normalizar(p.apellido_paterno),
    apellido_materno: normalizar(p.apellido_materno),
    fecha_nacimiento: p.fecha_nacimiento,
    sexo: p.sexo,
    id_ocupacion: p.id_ocupacion || "",
    calle: normalizar(p.calle || ""),
    codigo_postal: p.codigo_postal || "",
    colonia: normalizar(p.colonia || ""),
    estado: normalizar(p.estado || ""),
    ciudad: normalizar(p.municipio || ""),
    pais: "MEXICO",

    // Tab 2: Contacto — coincide con name= del modal
    email: email,
    telefono: telefono,
    // Nombre completo del contacto de emergencia en un solo campo
    contacto_emergencia: ce.nombres_contacto_emergencia
      ? (
          ce.nombres_contacto_emergencia +
          " " +
          (ce.apellido_contacto_emergencia || "")
        ).trim()
      : "",
    relacion: ce.parentesco || "",
    telefono_emergencia: ce.telefono_contacto_emergencia || "",

    // Tab 3: Historial — coincide con name= del modal
    tipo_sangre: hist.tipo_sangre || "",
    antecedentes_medicos: hist.antecedentes || "",
    notas_historial: hist.notas || "",
  };
}

// ── Botones de la tabla ────────────────────────────────────────────
function abrirModalNuevoPaciente() {
  nuevoEnModal(MODAL_PAC_ID);
  document.getElementById("modalPacienteNumero").textContent = "";
  document.getElementById("grupoCampoId").style.display = "none";

  const inputId = document.querySelector('#formPaciente [name="id"]');

  // Deshabilitar el campo ID y mostrar "..." mientras se obtiene el siguiente ID disponible
  if (inputId) {
    inputId.disabled = true;
    inputId.value = "...";

    fetch(`${API_URL}?modulo=pacientes&accion=next_id`)
      .then((r) => r.json())
      .then((data) => {
        if (data.success && inputId) {
          inputId.value = data.next_id;
        }
      });
  }
}

function abrirModalVerPaciente(id) {
  cargarPaciente(id, (p) => {
    verEnModal(MODAL_PAC_ID, mapearDatosPaciente(p));
    document.getElementById("modalPacienteNumero").textContent =
      p.id_paciente_expediente;
  });
}

function abrirModalEditarPaciente(id) {
  cargarPaciente(id, (p) => {
    editarEnModal(MODAL_PAC_ID, mapearDatosPaciente(p));
    document.getElementById("modalPacienteNumero").textContent =
      p.id_paciente_expediente;
    document.getElementById("grupoCampoId").style.display = "";
  });
}

// ── Guardar paciente ───────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", function () {
  document
    .getElementById("btnGuardarPaciente")
    ?.addEventListener("click", function () {
      const formPaciente = document.getElementById("formPaciente");
      const formContacto = document.getElementById("formContacto");
      const id = formPaciente.querySelector('[name="id"]')?.value;
      const formData = new FormData(formPaciente);

      // Agregar campos del tab Contacto
      if (formContacto) {
        new FormData(formContacto).forEach((val, key) => {
          if (!formData.has(key)) formData.append(key, val);
        });
      }

      // Agregar campos del tab Historial (textarea y campos sueltos fuera de form)
      [
        "tipo_sangre",
        "antecedentes_medicos",
        "medicamentos",
        "cirugias_previas",
        "notas_historial",
      ].forEach((campo) => {
        const el = document.querySelector(`[name="${campo}"]`);
        if (el && !formData.has(campo)) formData.append(campo, el.value);
      });

      formData.append("modulo", "pacientes");
      formData.append("accion", id ? "update" : "create");
      if (id) formData.append("numero_paciente", id);

      CatalogTable.showLoading(true);

      fetch(API_URL, { method: "POST", body: formData })
        .then((r) => r.json())
        .then((data) => {
          CatalogTable.showLoading(false);
          if (data.success) {
            CatalogTable.showNotification(data.message, "success");
            cerrarModal(MODAL_PAC_ID);
            buscarPacientes();
          } else {
            CatalogTable.showNotification(
              data.message || "Error al guardar",
              "error",
            );
          }
        })
        .catch(() => {
          CatalogTable.showLoading(false);
          CatalogTable.showNotification("Error de conexión", "error");
        });
    });
});

// ── Cambiar estatus ────────────────────────────────────────────────
function cambiarEstatusPaciente(id, nuevoEstatus, nombre) {
  const accion = nuevoEstatus === 1 ? "activar" : "desactivar";
  if (!confirm(`¿Deseas ${accion} al paciente "${nombre}"?`)) return;

  const formData = new FormData();
  formData.append("modulo", "pacientes");
  formData.append("accion", "status");
  formData.append("numero_paciente", id);
  formData.append("id_estatus", nuevoEstatus);

  CatalogTable.showLoading(true);

  fetch(API_URL, { method: "POST", body: formData })
    .then((r) => r.json())
    .then((data) => {
      CatalogTable.showLoading(false);
      if (data.success) {
        CatalogTable.showNotification(data.message, "success");
        buscarPacientes();
      } else {
        CatalogTable.showNotification(
          data.message || "Error al cambiar estatus",
          "error",
        );
      }
    })
    .catch(() => {
      CatalogTable.showLoading(false);
      CatalogTable.showNotification("Error de conexión", "error");
    });
}
