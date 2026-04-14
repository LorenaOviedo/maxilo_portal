/**
 * pacientes.js
 * Sistema Maxilofacial Texcoco
 *
 * Depende de: modal.js, catalogos-tabla.js,
 *             antecedentesModel.js, antecedentesController.js
 *
 * Variables inyectadas desde PHP en pacientes.php:
 *   var API_URL   = '...';
 *   var CATALOGOS = { tiposSangre: [...], parentescos: [...], ocupaciones: [...] };
 */

const MODAL_PAC_ID = "modalPaciente";
const POR_PAGINA   = 10;
 
let busquedaActual = "";
let paginaActual   = 1;
let busquedaTimer  = null;
 
// ── Inicialización ────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", function () {
  // Neutralizar búsqueda local de CatalogTable —
  // pacientes.js usa búsqueda AJAX propia
  CatalogTable.initSearch  = function () {};
  CatalogTable.filterTable = function () {};
  CatalogTable.clearSearch = function () {
    const input = document.getElementById("searchInput");
    if (input) input.value = "";
    busquedaActual = "";
    paginaActual   = 1;
    buscarPacientes();
  };
 
  iniciarBusqueda();
  cargarCatalogosHistorial();
});
 
// ── Código postal ─────────────────────────────────────────────────
function iniciarEventosCP() {
  const inputCP      = document.getElementById("inputCP");
  const btnBuscarCP  = document.getElementById("btnBuscarCP");
  const inputColonia = document.getElementById("inputColonia");
  const listaColonias= document.getElementById("listaColonias");
  const inputEstado  = document.getElementById("inputEstado");
  const inputMunicipio = document.getElementById("inputMunicipio");
 
  if (!inputCP) return;
 
  // Clonar para limpiar listeners anteriores
  const nuevoInputCP = inputCP.cloneNode(true);
  inputCP.parentNode.replaceChild(nuevoInputCP, inputCP);
  const nuevoBtn = btnBuscarCP?.cloneNode(true);
  btnBuscarCP?.parentNode.replaceChild(nuevoBtn, btnBuscarCP);
 
  function buscarCP() {
    const cp = nuevoInputCP.value.trim();
    if (!cp || cp.length < 5) {
      CatalogTable.showNotification("Ingresa un CP de 5 dígitos", "warning");
      return;
    }
    inputColonia.value   = "";
    listaColonias.innerHTML = "";
    inputEstado.value    = "";
    inputMunicipio.value = "";
 
    fetch(`${API_URL}?accion=buscar_cp&cp=${encodeURIComponent(cp)}`)
      .then((r) => r.json())
      .then((data) => {
        if (!data.success) {
          CatalogTable.showNotification("Código postal no encontrado", "error");
          return;
        }
        inputEstado.value    = normalizar(data.estado);
        inputMunicipio.value = normalizar(data.municipio);
        listaColonias.innerHTML = data.colonias
          .map((c) => `<option value="${normalizar(c.colonia)}">`)
          .join("");
        inputColonia.value = "";
      })
      .catch(() => CatalogTable.showNotification("Error de conexión", "error"));
  }
 
  // Un solo listener con ambas lógicas
  nuevoInputCP.addEventListener("input", () => {
    const len = nuevoInputCP.value.trim().length;
    if (len < 5) {
      listaColonias.innerHTML  = "";
      inputColonia.value       = "";
      inputEstado.value        = "";
      inputMunicipio.value     = "";
    }
    if (len === 5) buscarCP();
  });
}
 
// ── Búsqueda AJAX con debounce ────────────────────────────────────
function iniciarBusqueda() {
  const searchInput = document.getElementById("searchInput");
  const btnSearch   = document.querySelector(".btn-search");
 
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      clearTimeout(busquedaTimer);
      busquedaTimer = setTimeout(() => {
        busquedaActual = searchInput.value.trim();
        paginaActual   = 1;
        buscarPacientes();
      }, 400);
    });
  }
 
  if (btnSearch) {
    btnSearch.addEventListener("click", () => {
      busquedaActual = searchInput?.value.trim() ?? "";
      paginaActual   = 1;
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
        <td colspan="9" style="text-align:center; padding:40px;">
          <i class="ri-search-line" style="font-size:48px; color:#dee2e6; display:block; margin-bottom:15px;"></i>
          <p style="font-size:16px; color:#6c757d; font-weight:600;">No se encontraron resultados</p>
          <p style="font-size:14px; color:#adb5bd;">Intenta con otro término de búsqueda</p>
        </td>
      </tr>`;
    return;
  }
 
  tbody.innerHTML = pacientes.map((p) => {
    const activo      = parseInt(p.id_estatus) === 1;
    const ultimaVisita = p.ultima_visita
      ? new Date(p.ultima_visita).toLocaleDateString("es-MX") : "—";
    const nombre      = normalizar(p.nombre_completo || "").replace(/'/g, "\\'");
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
        <td class="col-id"    data-label="No. Paciente"   data-col="numero">
          <span style="font-weight:700;">${p.numero_paciente}</span>
        </td>
        <td class="col-exp"   data-label="No. Expediente">${p.id_paciente_expediente || "—"}</td>
        <td class="col-name"  data-label="Nombre"         data-col="nombre">${normalizar(p.nombre_completo)}</td>
        <td class="col-tel"   data-label="Teléfono">${p.telefono || "—"}</td>
        <td class="col-email" data-label="Correo">
          <span class="text-truncate" title="${p.email || ""}">${p.email || "—"}</span>
        </td>
        <td class="col-edad text-center"  data-label="Edad"         data-col="edad">${p.edad || "—"}</td>
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
  }).join("");
}
 
function actualizarPaginacion(p) {
  const contenedor = document.querySelector(".pagination");
  if (!contenedor) return;
 
  contenedor.innerHTML = `
    <button class="pagination-btn" ${p.pagina_actual <= 1 ? "disabled" : ""}
        onclick="irAPagina(${p.pagina_actual - 1})">
        <i class="ri-arrow-left-line"></i> Anterior
    </button>
    <span class="pagination-info">
        Mostrando ${p.inicio}–${p.fin} de ${p.total} paciente(s)
        &nbsp;·&nbsp; Página ${p.pagina_actual} de ${p.total_paginas}
    </span>
    <button class="pagination-btn" ${p.pagina_actual >= p.total_paginas ? "disabled" : ""}
        onclick="irAPagina(${p.pagina_actual + 1})">
        Siguiente <i class="ri-arrow-right-line"></i>
    </button>`;
}
 
function irAPagina(pagina) {
  paginaActual = pagina;
  buscarPacientes();
  document.querySelector(".table-container")?.scrollIntoView({ behavior: "smooth" });
}
 
// ── Cargar datos del paciente ─────────────────────────────────────
function cargarPaciente(id, callback) {
  fetch(`${API_URL}?modulo=pacientes&accion=get&id=${id}`)
    .then((r) => r.json())
    .then((data) => {
      if (!data.success) {
        CatalogTable.showNotification("No se pudo cargar el paciente", "error");
        return;
      }
      console.log('Paciente cargado:', {
          contacto_emergencia: data.paciente?.contacto_emergencia,
          numero_paciente: data.paciente?.numero_paciente
      });
      callback(data.paciente);
    })
    .catch(() => CatalogTable.showNotification("Error al obtener los datos", "error"));
}
 
function mapearDatosPaciente(p) {
  const telefono = (p.contactos || [])
    .find((c) => [1,2,3,4,5].includes(parseInt(c.id_tipo_contacto)))?.valor || "";
  const email = (p.contactos || [])
    .find((c) => parseInt(c.id_tipo_contacto) === 6)?.valor || "";
  const ce   = p.contacto_emergencia || {};
  console.log('mapearDatosPaciente ce:', ce);
  const hist = p.historial           || {};
  const anam = p.anamnesis           || {};
 
  return {
    // Tab 1: Información personal
    id_paciente_expediente: p.id_paciente_expediente || "",
    nombre:           normalizar(p.nombre),
    apellido_paterno: normalizar(p.apellido_paterno),
    apellido_materno: normalizar(p.apellido_materno),
    fecha_nacimiento: p.fecha_nacimiento,
    sexo:             p.sexo,
    id_ocupacion:     p.id_ocupacion || "",
    calle:            normalizar(p.calle || ""),
    numero_exterior:  p.numero_exterior || "",
    numero_interior:  p.numero_interior || "",
    codigo_postal:    p.codigo_postal   || "",
    estado:    "",   // se autocompleta por CP
    municipio: "",
    pais:      "MEXICO",
 
    // Tab 2: Contacto
    email:                email,
    telefono:             telefono,
    contacto_emergencia:  ce.nombres_contacto_emergencia
      ? (ce.nombres_contacto_emergencia + " " + (ce.apellido_contacto_emergencia || "")).trim()
      : "",
    relacion:             ce.parentesco || "",
    telefono_emergencia:  ce.telefono_contacto_emergencia || "",
 
    // Tab 3: Historial
    id_tipo_sangre:  hist.id_tipo_sangre  ?? "",
    _antecedentes:   hist.antecedentes_ids ?? [],
    notas_historial: hist.notas           || "",
 
    // Tab 4: Anamnesis
    anam_enfermedades_cronicas:   anam.enfermedades_cronicas   ?? "",
    anam_antecedentes_familiares: anam.antecedentes_familiares ?? "",
    anam_alergia_latex:           anam.alergia_latex           ?? false,
    anam_salud_general:           anam.salud_general           ?? "",
    anam_actividad_fisica:        anam.actividad_fisica        ?? "",
    anam_numero_comidas:          anam.numero_comidas          ?? "",
    anam_consumo_agua:            anam.consumo_agua            ?? "",
    anam_toma_alcohol:            anam.toma_alcohol            ?? false,
    anam_fuma:                    anam.fuma                    ?? false,
    anam_veces_cepillado:         anam.veces_cepillado         ?? 0,
    anam_sensibilidad_dental:     anam.sensibilidad_dental     ?? false,
    anam_bruxismo:                anam.bruxismo                ?? false,
    anam_ulceras_frecuentes:      anam.ulceras_frecuentes      ?? false,
    anam_historial_extracciones:  anam.historial_extracciones  ?? "",
  };
}
 
// ── Llenar tab de contacto ───────────────────────────────────────
 
function poblarContacto(mapped) {
    const modal = document.getElementById('modalPaciente');
    if (!modal) return;
 
    const campos = {
        'email':                mapped.email               ?? '',
        'telefono':             mapped.telefono            ?? '',
        'contacto_emergencia':  mapped.contacto_emergencia ?? '',
        'relacion':             mapped.relacion            ?? '',
        'telefono_emergencia':  mapped.telefono_emergencia ?? '',
    };
 
    Object.entries(campos).forEach(([name, val]) => {
        const el = modal.querySelector(`[name="${name}"]`);
        if (!el) return;
        const wasDisabled = el.disabled;
        el.disabled = false;  // habilitar temporalmente para asignar valor
        el.value    = val;
        el.disabled = wasDisabled; // restaurar estado
    });
}
 
// ── Modales ───────────────────────────────────────────────────────
 
function resetearEstadoModalPaciente() {
  cambiarTab(MODAL_PAC_ID, "tabInfoPersonal");
  planesController?.limpiar?.();
}
 
function abrirModalNuevoPaciente() {
  nuevoEnModal(MODAL_PAC_ID);
  resetearEstadoModalPaciente();
  document.querySelector('[name="pais"]').value = "MEXICO";
  document.getElementById("modalPacienteNumero").textContent = "";
  document.getElementById("formPaciente").dataset.numeroPaciente = "";
  document.getElementById("grupoCampoId")?.style &&
    (document.getElementById("grupoCampoId").style.display = "none");
  iniciarEventosCP();
  limpiarHistorial();
  limpiarAnamnesis();
 
  const inputId = document.querySelector('#formPaciente [name="id"]');
  if (inputId) {
    inputId.disabled = true;
    inputId.value    = "...";
    fetch(`${API_URL}?modulo=pacientes&accion=next_id`)
      .then((r) => r.json())
      .then((data) => { if (data.success && inputId) inputId.value = data.next_id; });
  }
}
 
function abrirModalVerPaciente(id) {
  cargarPaciente(id, (p) => {
    const mapped = mapearDatosPaciente(p);
    verEnModal(MODAL_PAC_ID, mapped);
    resetearEstadoModalPaciente();
    document.getElementById("modalPacienteNumero").textContent      = p.id_paciente_expediente;
    document.getElementById("formPaciente").dataset.numeroPaciente  = p.numero_paciente;
    document.querySelector('[name="pais"]').value = "MEXICO";
    iniciarEventosCP();
    cargarCPyPreseleccionar(p.codigo_postal, p.colonia);
    poblarHistorial(mapped, true);
    poblarAnamnesis(mapped);
    // Poblar contacto después de que setReadOnly termine
    setTimeout(() => poblarContacto(mapped), 50);
  });
}
 
function abrirModalEditarPaciente(id) {
  cargarPaciente(id, (p) => {
    const mapped = mapearDatosPaciente(p);
    editarEnModal(MODAL_PAC_ID, mapped);
    resetearEstadoModalPaciente();
    document.getElementById("modalPacienteNumero").textContent      = p.id_paciente_expediente;
    document.getElementById("formPaciente").dataset.numeroPaciente  = p.numero_paciente;
    document.querySelector('[name="pais"]').value = "MEXICO";
    iniciarEventosCP();
    cargarCPyPreseleccionar(p.codigo_postal, p.colonia);
    poblarHistorial(mapped, false);
    poblarAnamnesis(mapped);
    // Poblar contacto después de que setReadOnly termine
    setTimeout(() => poblarContacto(mapped), 50);
  });
}
 
// ── Guardar paciente ──────────────────────────────────────────────
document.getElementById("btnGuardarPaciente")
  ?.addEventListener("click", async function () {
    const formPaciente = document.getElementById("formPaciente");
    const formContacto = document.getElementById("formContacto");
    const formData     = new FormData(formPaciente);
    const numeroPaciente = formPaciente.dataset.numeroPaciente || "";
 
    // Campos del tab Contacto
    if (formContacto) {
      new FormData(formContacto).forEach((val, key) => {
        if (!formData.has(key)) formData.append(key, val);
      });
    }
 
    formData.append("modulo", "pacientes");
    formData.append("accion", numeroPaciente ? "update" : "create");
    if (numeroPaciente) formData.append("numero_paciente", numeroPaciente);
 
    const nombre = formData.get("nombre")?.trim()           || "";
    const apPat  = formData.get("apellido_paterno")?.trim() || "";
    const apMat  = formData.get("apellido_materno")?.trim() || "";
 
    // ── Validaciones ──────────────────────────────────────────────
    const reglas = [
      { name: "id_paciente_expediente", label: "No. Expediente",     requerido: false,
        regex: /^\d+$/, msgRegex: "El No. Expediente solo debe contener números" },
      { name: "nombre",           label: "Nombre(s)",         requerido: true,
        regex: /^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ\s]+$/, msgRegex: "El Nombre solo debe contener letras" },
      { name: "apellido_paterno", label: "Apellido paterno",  requerido: true,
        regex: /^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ\s]+$/, msgRegex: "El Apellido paterno solo debe contener letras" },
      { name: "apellido_materno", label: "Apellido materno",  requerido: false,
        regex: /^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ\s]+$/, msgRegex: "El Apellido materno solo debe contener letras" },
      { name: "fecha_nacimiento", label: "Fecha de nacimiento", requerido: true,
        validar: (valor) => {
          const fecha  = new Date(valor);
          const hoy    = new Date();
          const minima = new Date(); minima.setFullYear(hoy.getFullYear() - 120);
          if (fecha >= hoy)    return "La fecha de nacimiento no puede ser futura";
          if (fecha < minima)  return "La fecha de nacimiento no es válida";
          return null;
        }},
      { name: "codigo_postal",   label: "Código postal",      requerido: false,
        regex: /^\d{5}$/, msgRegex: "El Código postal debe tener exactamente 5 dígitos" },
      { name: "email",           label: "Correo electrónico", requerido: false,
        regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, msgRegex: "El formato del correo no es válido" },
      { name: "telefono",        label: "Teléfono",           requerido: false,
        regex: /^\d{10}$/, msgRegex: "El Teléfono debe tener exactamente 10 dígitos" },
      { name: "telefono_emergencia", label: "Teléfono de emergencia", requerido: false,
        regex: /^\d{10}$/, msgRegex: "El Teléfono de emergencia debe tener exactamente 10 dígitos" },
      { name: "contacto_emergencia", label: "Nombre del contacto de emergencia", requerido: false,
        regex: /^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ\s]+$/, msgRegex: "El nombre del contacto solo debe contener letras" },
    ];
 
    for (const regla of reglas) {
      const valor = formData.get(regla.name)?.trim() || "";
      if (regla.requerido && !valor) {
        CatalogTable.showNotification(`El campo "${regla.label}" es obligatorio`, "error");
        document.querySelector(`[name="${regla.name}"]`)?.focus();
        return;
      }
      if (valor && regla.regex && !regla.regex.test(valor)) {
        CatalogTable.showNotification(regla.msgRegex, "error");
        document.querySelector(`[name="${regla.name}"]`)?.focus();
        return;
      }
      if (valor && regla.validar) {
        const error = regla.validar(valor);
        if (error) {
          CatalogTable.showNotification(error, "error");
          document.querySelector(`[name="${regla.name}"]`)?.focus();
          return;
        }
      }
    }
 
    // Validar anamnesis
    if (!validarAnamnesis()) return;
 
    // Verificar duplicados
    if (nombre && apPat) {
      try {
        const res  = await fetch(
          `${API_URL}?modulo=pacientes&accion=check_duplicado` +
          `&nombre=${encodeURIComponent(nombre)}` +
          `&apellido_paterno=${encodeURIComponent(apPat)}` +
          `&apellido_materno=${encodeURIComponent(apMat)}` +
          `&excluir=${numeroPaciente}`
        );
        const data = await res.json();
        if (data.duplicado) {
          CatalogTable.showNotification(
            `Ya existe un paciente con el nombre "${nombre} ${apPat} ${apMat}"`, "error"
          );
          return;
        }
      } catch {
        CatalogTable.showNotification("Error al validar duplicados", "error");
        return;
      }
    }
 
    CatalogTable.showLoading(true);
    recolectarHistorial(formData);
    recolectarAnamnesis(formData);
 
    fetch(API_URL, { method: "POST", body: formData })
      .then((r) => r.json())
      .then((data) => {
        CatalogTable.showLoading(false);
        if (data.success) {
          CatalogTable.showNotification(data.message, "success");
          cerrarModal(MODAL_PAC_ID);
          buscarPacientes();
        } else {
          CatalogTable.showNotification(data.message || "Error al guardar", "error");
        }
      })
      .catch(() => {
        CatalogTable.showLoading(false);
        CatalogTable.showNotification("Error de conexión", "error");
      });
  });
 
// ── Catálogos de historial ────────────────────────────────────────
function cargarCatalogosHistorial() {
  fetch(`${API_URL}?modulo=pacientes&accion=get_catalogos_historial`)
    .then((r) => r.json())
    .then((data) => {
      if (!data.success) return;
 
      // Poblar select de tipo de sangre
      const selectSangre = document.getElementById("selectTipoSangre");
      if (selectSangre && data.tipos_sangre) {
        selectSangre.innerHTML = '<option value="">Seleccionar</option>';
        data.tipos_sangre.forEach((ts) => {
          const opt = document.createElement("option");
          opt.value       = ts.id_tipo_sangre;
          opt.textContent = ts.tipo_sangre;
          selectSangre.appendChild(opt);
        });
      }
 
      // Cargar catálogo en el modelo Vue y montar el componente
      antecedentesModel.catalogo = data.antecedentes_catalogo ?? [];
      antecedentesController.montar();
    })
    .catch(() => console.warn("No se pudieron cargar catálogos de historial"));
}
 
// ── Tab Historial ─────────────────────────────────────────────────
function poblarHistorial(datos, readonly = false) {
  const selectSangre = document.getElementById("selectTipoSangre");
  if (selectSangre) selectSangre.value = datos.id_tipo_sangre ?? "";
 
  antecedentesController.setReadonly(readonly);
  antecedentesController.cargar(datos._antecedentes ?? []);
 
  const notas = document.querySelector('[name="notas_historial"]');
  if (notas) notas.value = datos.notas_historial ?? "";
}
 
function limpiarHistorial() {
  const selectSangre = document.getElementById("selectTipoSangre");
  if (selectSangre) selectSangre.value = "";
 
  antecedentesController.setReadonly(false);
  antecedentesController.limpiar();
 
  const notas = document.querySelector('[name="notas_historial"]');
  if (notas) notas.value = "";
}
 
function recolectarHistorial(formData) {
  const selectSangre = document.getElementById("selectTipoSangre");
  if (selectSangre && !formData.has("id_tipo_sangre")) {
    formData.append("id_tipo_sangre", selectSangre.value);
  }
 
  antecedentesController.obtenerIds().forEach((id) => {
    formData.append("antecedentes_ids[]", id);
  });
 
  const notas = document.querySelector('[name="notas_historial"]');
  if (notas && !formData.has("notas_historial")) {
    formData.append("notas_historial", notas.value);
  }
}
 
// ── Tab Anamnesis ─────────────────────────────────────────────────
function poblarAnamnesis(datos) {
  ["antecedentes_familiares","salud_general",
   "actividad_fisica","consumo_agua","historial_extracciones"].forEach((campo) => {
    const el = document.querySelector(`#tabAnamnesis [name="${campo}"]`);
    if (el) el.value = datos[`anam_${campo}`] ?? "";
  });
 
  const numComidas = document.querySelector('#tabAnamnesis [name="numero_comidas"]');
  if (numComidas) numComidas.value = datos.anam_numero_comidas ?? "";
 
  const vecesCep = document.querySelector('#tabAnamnesis [name="veces_cepillado"]');
  if (vecesCep) vecesCep.value = datos.anam_veces_cepillado ?? 0;
 
  ["alergia_latex","toma_alcohol","fuma","sensibilidad_dental",
   "bruxismo","ulceras_frecuentes"].forEach((campo) => {
    const el = document.querySelector(`#tabAnamnesis [name="${campo}"]`);
    if (el) el.checked = !!datos[`anam_${campo}`];
  });
}
 
function limpiarAnamnesis() {
  document.querySelectorAll("#tabAnamnesis input, #tabAnamnesis select, #tabAnamnesis textarea")
    .forEach((el) => {
      if (el.type === "checkbox") el.checked = false;
      else el.value = "";
    });
}
 
function recolectarAnamnesis(formData) {
  ["enfermedades_cronicas","antecedentes_familiares","salud_general","actividad_fisica",
   "consumo_agua","historial_extracciones","numero_comidas","veces_cepillado"].forEach((campo) => {
    const el = document.querySelector(`#tabAnamnesis [name="${campo}"]`);
    if (el && !formData.has(campo)) formData.append(campo, el.value ?? "");
  });
 
  ["alergia_latex","toma_alcohol","fuma","sensibilidad_dental","bruxismo","ulceras_frecuentes"]
    .forEach((campo) => {
      const el = document.querySelector(`#tabAnamnesis [name="${campo}"]`);
      if (!formData.has(campo)) formData.append(campo, el && el.checked ? "1" : "0");
    });
}
 
function validarAnamnesis() {
  const reglas = [
    { name: "enfermedades_cronicas",   label: "Enfermedades crónicas",    requerido: false,
      regex: /^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ0-9\s,.()\-\/]+$/u,
      msgRegex: 'El campo "Enfermedades crónicas" contiene caracteres no permitidos' },
    { name: "antecedentes_familiares", label: "Antecedentes familiares",  requerido: false,
      regex: /^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ0-9\s,.()\-\/]+$/u,
      msgRegex: 'El campo "Antecedentes familiares" contiene caracteres no permitidos' },
    { name: "salud_general",  label: "Salud general",  requerido: false,
      validar: (v) => ["","mala","buena","muy_buena","excelente"].includes(v)
        ? null : 'Selecciona una opción válida para "Salud general"' },
    { name: "actividad_fisica", label: "Actividad física", requerido: false,
      validar: (v) => ["","sedentario","ligero","activo","muy_activo"].includes(v)
        ? null : 'Selecciona una opción válida para "Actividad física"' },
    { name: "consumo_agua", label: "Consumo de agua", requerido: false,
      validar: (v) => ["","muy_poca","poca","regular","mucha"].includes(v)
        ? null : 'Selecciona una opción válida para "Consumo de agua"' },
    { name: "numero_comidas", label: "Número de comidas al día", requerido: false,
      validar: (v) => {
        if (!v) return null;
        const n = parseInt(v, 10);
        if (isNaN(n) || !Number.isInteger(n)) return 'El campo "Número de comidas" debe ser un número entero';
        if (n < 1 || n > 10) return 'El campo "Número de comidas" debe estar entre 1 y 10';
        return null;
      }},
    { name: "veces_cepillado", label: "Veces que se cepilla al día", requerido: false,
      validar: (v) => {
        if (!v && v !== "0") return null;
        const n = parseInt(v, 10);
        if (isNaN(n) || !Number.isInteger(n)) return 'El campo "Veces que se cepilla" debe ser un número entero';
        if (n < 0 || n > 10) return 'El campo "Veces que se cepilla" debe estar entre 0 y 10';
        return null;
      }},
    { name: "historial_extracciones", label: "Historial de extracciones", requerido: false,
      regex: /^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ0-9\s,.()\-\/]+$/u,
      msgRegex: 'El campo "Historial de extracciones" contiene caracteres no permitidos' },
  ];
 
  for (const regla of reglas) {
    const el    = document.querySelector(`#tabAnamnesis [name="${regla.name}"]`);
    const valor = el ? el.value.trim() : "";
 
    if (regla.requerido && !valor) {
      CatalogTable.showNotification(`El campo "${regla.label}" es obligatorio`, "error");
      el?.focus(); cambiarTab("modalPaciente", "tabAnamnesis");
      return false;
    }
    if (valor && regla.regex && !regla.regex.test(valor)) {
      CatalogTable.showNotification(regla.msgRegex, "error");
      el?.focus(); cambiarTab("modalPaciente", "tabAnamnesis");
      return false;
    }
    if (regla.validar) {
      const error = regla.validar(valor);
      if (error) {
        CatalogTable.showNotification(error, "error");
        el?.focus(); cambiarTab("modalPaciente", "tabAnamnesis");
        return false;
      }
    }
  }
  return true;
}
 
// ── Cambiar estatus ───────────────────────────────────────────────
function cambiarEstatusPaciente(id, nuevoEstatus, nombre) {
  const accion = nuevoEstatus === 1 ? "activar" : "desactivar";
  if (!confirm(`¿Deseas ${accion} al paciente "${nombre}"?`)) return;
 
  const formData = new FormData();
  formData.append("modulo",          "pacientes");
  formData.append("accion",          "status");
  formData.append("numero_paciente", id);
  formData.append("id_estatus",      nuevoEstatus);
 
  CatalogTable.showLoading(true);
 
  fetch(API_URL, { method: "POST", body: formData })
    .then((r) => r.json())
    .then((data) => {
      CatalogTable.showLoading(false);
      if (data.success) {
        CatalogTable.showNotification(data.message, "success");
        buscarPacientes();
      } else {
        CatalogTable.showNotification(data.message || "Error al cambiar estatus", "error");
      }
    })
    .catch(() => {
      CatalogTable.showLoading(false);
      CatalogTable.showNotification("Error de conexión", "error");
    });
}
 
// ── Preseleccionar colonia ────────────────────────────────────────
function cargarCPyPreseleccionar(codigoPostal, coloniaActual) {
  if (!codigoPostal) return;
 
  fetch(`${API_URL}?accion=buscar_cp&cp=${encodeURIComponent(codigoPostal)}`)
    .then((r) => r.json())
    .then((data) => {
      if (!data.success) return;
      document.getElementById("inputEstado").value    = normalizar(data.estado);
      document.getElementById("inputMunicipio").value = normalizar(data.municipio);
      document.getElementById("listaColonias").innerHTML = data.colonias
        .map((c) => `<option value="${normalizar(c.colonia)}">`)
        .join("");
      document.getElementById("inputColonia").value = normalizar(coloniaActual || "");
    });
}