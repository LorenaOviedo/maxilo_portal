/**
 * especialistas.js
 *
 * Controlador del módulo de especialistas.
 * Depende de: CatalogTable (catalog-table.js), API_URL
 */

const especialistaController = {
  _catalogos: null,
  _espTemp: [],
  _modoEdicion: false,
  _soloLectura: false,
  _idActual: null,

  // ─────────────────────────────────────────────────────────────────────
  // INICIALIZACIÓN
  // ─────────────────────────────────────────────────────────────────────

  async inicializar() {
    if (this._catalogos) return;
    try {
      const r = await fetch(
        `${API_URL}?modulo=especialistas&accion=get_catalogos_especialistas`,
      );
      const data = await r.json();
      if (data.success) {
        this._catalogos = data;
        this._poblarSelectEspecialidades();
        this._resolverTiposContacto();
      }
    } catch (err) {
      console.warn("especialistaController: catálogos no disponibles", err);
    }
  },

  // ─────────────────────────────────────────────────────────────────────
  // ABRIR MODAL
  // @param {number|null} idEspecialista — null = nuevo
  // @param {boolean}     soloLectura    — true = botón Ver, false = Editar
  // ─────────────────────────────────────────────────────────────────────

  async abrir(idEspecialista = null, soloLectura = false) {
    await this.inicializar();
    this._limpiarFormulario();
    this._resolverTiposContacto();
    this._soloLectura = soloLectura;

    if (idEspecialista) {
      this._modoEdicion = true;
      this._idActual = idEspecialista;
      await this._cargarDatos(idEspecialista);
    } else {
      this._modoEdicion = false;
      this._soloLectura = false; // nuevo siempre es editable
      this._idActual = null;
      document.getElementById("modalEspecialistaNombre").textContent =
        "— Nuevo —";
    }

    this._aplicarModoLectura(soloLectura);
    abrirModal("modalEspecialista");
    cambiarTab("modalEspecialista", "tabEspPersonal");
  },

  // ─────────────────────────────────────────────────────────────────────
  // MODO SOLO LECTURA
  // Deshabilita todos los inputs y oculta el botón guardar
  // ─────────────────────────────────────────────────────────────────────

  _aplicarModoLectura(soloLectura) {
    // Inputs y selects del modal
    document
      .querySelectorAll(
        "#modalEspecialista input, #modalEspecialista select, #modalEspecialista textarea",
      )
      .forEach((el) => {
        el.disabled = soloLectura;
      });

    // Botón guardar
    const btnGuardar = document.getElementById("btnGuardarEspecialista");
    if (btnGuardar) btnGuardar.style.display = soloLectura ? "none" : "";

    // Botón agregar especialidad
    const btnAgregar = document.getElementById("btnAgregarEsp");
    if (btnAgregar) btnAgregar.style.display = soloLectura ? "none" : "";

    // Botones eliminar de la tabla de especialidades
    document
      .querySelectorAll("#bodyEspecialidades .btn-accion.eliminar")
      .forEach((btn) => {
        btn.style.display = soloLectura ? "none" : "";
      });
  },

  // ─────────────────────────────────────────────────────────────────────
  // CARGAR DATOS EN EL MODAL
  // ─────────────────────────────────────────────────────────────────────

  async _cargarDatos(id) {
    try {
      const r = await fetch(
        `${API_URL}?modulo=especialistas&accion=get&id=${id}`,
      );
      const data = await r.json();
      if (!data.success) return;

      const e = data.especialista;

      // Título
      document.getElementById("modalEspecialistaNombre").textContent =
        `${e.nombre} ${e.apellido_paterno}`;

      // Tab 1 — Personal
      this._setVal("espId", e.id_especialista ?? "");
      this._setVal("espNombre", e.nombre ?? "");
      this._setVal("espApPat", e.apellido_paterno ?? "");
      this._setVal("espApMat", e.apellido_materno ?? "");
      this._setVal("espFechaNac", e.fecha_nacimiento ?? "");
      this._setVal("espFechaCont", e.fecha_contratacion ?? "");

      // Dirección
      this._setVal("espCalle", e.calle ?? "");
      this._setVal("espNumExt", e.numero_exterior ?? "");
      this._setVal("espNumInt", e.numero_interior ?? "");
      this._setVal("espCP", e.codigo_postal ?? "");
      this._setVal("espColonia", e.colonia ?? "");
      this._setVal("espIdCp", e.id_cp ?? "");
      this._setVal("espEstado", e.estado ?? "");
      this._setVal("espMunicipio", e.municipio ?? "");

      // Tab 2 — Contacto
      (e.contactos ?? []).forEach((c) => {
        const tipo = c.tipo_contacto?.toLowerCase() ?? "";
        if (tipo.includes("tel") || tipo.includes("cel"))
          this._setVal("espTelefono", c.valor);
        if (tipo.includes("tel") || tipo.includes("cel"))
          this._setVal("espIdTipoTel", c.id_tipo_contacto);
        if (tipo.includes("email") || tipo.includes("correo"))
          this._setVal("espEmail", c.valor);
        if (tipo.includes("email") || tipo.includes("correo"))
          this._setVal("espIdTipoEmail", c.id_tipo_contacto);
      });

      // Tab 3 — Educación
      this._espTemp = (e.especialidades ?? []).map((esp) => ({
        id_especialidad: esp.id_especialidad,
        nombre: esp.nombre_especialidad,
        cedula_profesional: esp.cedula_profesional ?? "",
        institucion: esp.institucion ?? "",
      }));
      this._renderEspecialidades();
    } catch (err) {
      console.error("especialistaController._cargarDatos:", err);
      CatalogTable.showNotification(
        "Error al cargar datos del especialista",
        "error",
      );
    }
  },

  // ─────────────────────────────────────────────────────────────────────
  // GUARDAR
  // ─────────────────────────────────────────────────────────────────────

  async guardar() {
    if (this._soloLectura) return;

    const nombre = this._getVal("espNombre");
    const apPat = this._getVal("espApPat");

    if (!nombre) {
      CatalogTable.showNotification("El nombre es requerido", "error");
      return;
    }
    if (!apPat) {
      CatalogTable.showNotification(
        "El apellido paterno es requerido",
        "error",
      );
      return;
    }

    const formData = new FormData();
    formData.append("modulo", "especialistas");
    formData.append("accion", this._modoEdicion ? "actualizar_especialista" : "crear_especialista");

    if (this._modoEdicion) formData.append("id_especialista", this._idActual);

    // Personal
    [
      "espNombre",
      "espApPat",
      "espApMat",
      "espFechaNac",
      "espFechaCont",
      "espCalle",
      "espNumExt",
      "espNumInt",
      "espIdCp",
    ].forEach((id) => {
      const campo = this._idToField(id);
      formData.append(campo, this._getVal(id));
    });

    formData.append("id_estatus", "1");

    // Contacto
    formData.append("telefono", this._getVal("espTelefono"));
    formData.append("email", this._getVal("espEmail"));
    formData.append("id_tipo_contacto_telefono", this._getVal("espIdTipoTel"));
    formData.append("id_tipo_contacto_email", this._getVal("espIdTipoEmail"));

    // Especialidades
    formData.append("especialidades_json", JSON.stringify(this._espTemp));

    CatalogTable.showLoading(true);
    try {
      const r = await fetch(API_URL, { method: "POST", body: formData });
      const data = await r.json();
      CatalogTable.showLoading(false);

      if (data.success) {
        CatalogTable.showNotification(
          this._modoEdicion
            ? "Especialista actualizado correctamente"
            : "Especialista creado correctamente",
          "success",
        );
        cerrarModal("modalEspecialista");
        // Recargar página para reflejar cambios en la tabla
        setTimeout(() => window.location.reload(), 800);
      } else {
        CatalogTable.showNotification(
          data.message || "Error al guardar",
          "error",
        );
      }
    } catch (err) {
      CatalogTable.showLoading(false);
      CatalogTable.showNotification("Error de conexión", "error");
    }
  },

  // ─────────────────────────────────────────────────────────────────────
  // CAMBIAR ESTATUS
  // ─────────────────────────────────────────────────────────────────────

  async cambiarEstatus(id, nuevoEstatus, nombre) {
    const accion = nuevoEstatus === 1 ? "activar" : "desactivar";
    if (
      !confirm(
        `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} al especialista ${nombre}?`,
      )
    )
      return;

    const formData = new FormData();
    formData.append("modulo", "especialistas");
    formData.append("accion", "status_especialista");
    formData.append("id_especialista", id);
    formData.append("id_estatus", nuevoEstatus);

    try {
      const r = await fetch(API_URL, { method: "POST", body: formData });
      const data = await r.json();
      if (data.success) {
        CatalogTable.showNotification(data.message, "success");
        setTimeout(() => window.location.reload(), 800);
      } else {
        CatalogTable.showNotification(data.message || "Error", "error");
      }
    } catch (err) {
      CatalogTable.showNotification("Error de conexión", "error");
    }
  },

  // ─────────────────────────────────────────────────────────────────────
  // ESPECIALIDADES (Tab 3)
  // ─────────────────────────────────────────────────────────────────────

  mostrarFilaEsp() {
    const row = document.getElementById("rowNuevaEsp");
    if (row) row.style.display = "flex";
    this._setVal("espSelectEsp", "");
    this._setVal("espCedula", "");
    this._setVal("espInstitucion", "");
  },

  ocultarFilaEsp() {
    const row = document.getElementById("rowNuevaEsp");
    if (row) row.style.display = "none";
  },

  confirmarEsp() {
    const sel = document.getElementById("espSelectEsp");
    if (!sel?.value) {
      CatalogTable.showNotification("Selecciona una especialidad", "warning");
      return;
    }
    if (this._espTemp.some((e) => e.id_especialidad == sel.value)) {
      CatalogTable.showNotification(
        "Esta especialidad ya fue agregada",
        "warning",
      );
      return;
    }

    this._espTemp.push({
      id_especialidad: parseInt(sel.value),
      nombre: sel.options[sel.selectedIndex].textContent.trim(),
      cedula_profesional: this._getVal("espCedula"),
      institucion: this._getVal("espInstitucion"),
    });

    this.ocultarFilaEsp();
    this._renderEspecialidades();
  },

  quitarEsp(idx) {
    this._espTemp.splice(idx, 1);
    this._renderEspecialidades();
  },

  _renderEspecialidades() {
    const tbody = document.getElementById("bodyEspecialidades");
    if (!tbody) return;

    document.getElementById("espEspecialidadesJson").value = JSON.stringify(
      this._espTemp,
    );

    if (!this._espTemp.length) {
      tbody.innerHTML = `
                <tr id="rowSinEsp">
                    <td colspan="4" style="text-align:center; color:#adb5bd; padding:16px;">
                        Sin especialidades registradas
                    </td>
                </tr>`;
      return;
    }

    tbody.innerHTML = this._espTemp
      .map(
        (e, i) => `
            <tr>
                <td>${escHtml(e.nombre)}</td>
                <td>${escHtml(e.cedula_profesional) || "—"}</td>
                <td>${escHtml(e.institucion) || "—"}</td>
                <td class="acciones-cell">
                    <button class="btn-accion eliminar"
                        style="${this._soloLectura ? "display:none" : ""}"
                        onclick="especialistaController.quitarEsp(${i})">
                        <i class="ri-delete-bin-6-line"></i>
                    </button>
                </td>
            </tr>`,
      )
      .join("");
  },

  // ─────────────────────────────────────────────────────────────────────
  // CÓDIGO POSTAL
  // ─────────────────────────────────────────────────────────────────────

  async buscarCP(cp) {
    if (cp.length !== 5) return;
    try {
      const r = await fetch(`${API_URL}?accion=buscar_cp&cp=${cp}`);
      const data = await r.json();
      if (!data.success) return;

      this._setVal("espEstado", data.estado);
      this._setVal("espMunicipio", data.municipio);

      const datalist = document.getElementById("espListaColonias");
      if (!datalist) return;

      datalist.innerHTML = "";
      data.colonias.forEach((c) => {
        const opt = document.createElement("option");
        opt.value = c.colonia;
        opt.dataset.idCp = c.id_cp;
        datalist.appendChild(opt);
      });

      if (data.colonias.length === 1) {
        this._setVal("espColonia", data.colonias[0].colonia);
        this._setVal("espIdCp", data.colonias[0].id_cp);
      }
    } catch (err) {
      console.warn("especialistaController.buscarCP:", err);
    }
  },

  _onColoniaChange(valor) {
    const opts = document.querySelectorAll("#espListaColonias option");
    const opt = [...opts].find((o) => o.value === valor);
    if (opt) this._setVal("espIdCp", opt.dataset.idCp ?? "");
  },

  // ─────────────────────────────────────────────────────────────────────
  // HELPERS
  // ─────────────────────────────────────────────────────────────────────

  _setVal(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val ?? "";
  },

  _getVal(id) {
    return document.getElementById(id)?.value?.trim() ?? "";
  },

  /** Convierte id del elemento al nombre del campo del form */
  _idToField(id) {
    const mapa = {
      espNombre: "nombre",
      espApPat: "apellido_paterno",
      espApMat: "apellido_materno",
      espFechaNac: "fecha_nacimiento",
      espFechaCont: "fecha_contratacion",
      espCalle: "calle",
      espNumExt: "numero_exterior",
      espNumInt: "numero_interior",
      espIdCp: "id_cp",
    };
    return mapa[id] ?? id;
  },

  _poblarSelectEspecialidades() {
    const sel = document.getElementById("espSelectEsp");
    if (!sel || !this._catalogos?.especialidades) return;
    sel.innerHTML = '<option value="">Seleccionar especialidad...</option>';
    this._catalogos.especialidades.forEach((e) => {
      const opt = document.createElement("option");
      opt.value = e.id_especialidad;
      opt.textContent = e.nombre;
      sel.appendChild(opt);
    });
  },

  _resolverTiposContacto() {
    const tipos = this._catalogos?.tiposContacto ?? [];
    const tel = tipos.find(
      (t) =>
        t.tipo_contacto.toLowerCase().includes("tel") ||
        t.tipo_contacto.toLowerCase().includes("cel"),
    );
    const email = tipos.find(
      (t) =>
        t.tipo_contacto.toLowerCase().includes("email") ||
        t.tipo_contacto.toLowerCase().includes("correo"),
    );
    if (tel) this._setVal("espIdTipoTel", tel.id_tipo_contacto);
    if (email) this._setVal("espIdTipoEmail", email.id_tipo_contacto);
  },

  _limpiarFormulario() {
    this._espTemp = [];
    [
      "espId",
      "espNombre",
      "espApPat",
      "espApMat",
      "espFechaNac",
      "espFechaCont",
      "espCalle",
      "espNumExt",
      "espNumInt",
      "espCP",
      "espColonia",
      "espIdCp",
      "espEstado",
      "espMunicipio",
      "espTelefono",
      "espEmail",
    ].forEach((id) => this._setVal(id, ""));
    document.getElementById("modalEspecialistaNombre").textContent = "";
    this._renderEspecialidades();
    document.getElementById("rowNuevaEsp").style.display = "none";
  },
};

// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS
// ─────────────────────────────────────────────────────────────────────────────

document.addEventListener("DOMContentLoaded", () => {
  document
    .getElementById("btnGuardarEspecialista")
    ?.addEventListener("click", () => especialistaController.guardar());

  document.getElementById("espCP")?.addEventListener("input", (e) => {
    if (e.target.value.length === 5)
      especialistaController.buscarCP(e.target.value);
  });

  document
    .getElementById("espColonia")
    ?.addEventListener("change", (e) =>
      especialistaController._onColoniaChange(e.target.value),
    );
});
