/**
 * especialistas.js
 *
 * Controlador del módulo de especialistas.
 * Patrón idéntico a pacientes.js / planes.js.
 * Depende de: CatalogTable (catalog-table.js), API_URL
 */

const especialistaController = {

    _catalogos:    null,   // { especialidades[], tiposContacto[] }
    _espTemp:      [],     // especialidades pendientes de guardar
    _modoEdicion:  false,
    _idActual:     null,

    // ─────────────────────────────────────────────────────────────────────
    // INICIALIZACIÓN
    // ─────────────────────────────────────────────────────────────────────

    async inicializar() {
        if (this._catalogos) return;
        try {
            const r    = await fetch(`${API_URL}?modulo=especialistas&accion=get_catalogos_especialistas`);
            const data = await r.json();
            if (data.success) {
                this._catalogos = data;
                this._poblarSelectEspecialidades();
                this._resolverTiposContacto();
            }
        } catch (err) {
            console.warn('especialistaController: no se pudieron cargar catálogos', err);
        }
    },

    // ─────────────────────────────────────────────────────────────────────
    // ABRIR MODAL
    // ─────────────────────────────────────────────────────────────────────

    async abrir(idEspecialista = null) {
        await this.inicializar();
        this._limpiarFormulario();

        if (idEspecialista) {
            this._modoEdicion = true;
            this._idActual    = idEspecialista;
            await this._cargarDatos(idEspecialista);
        } else {
            this._modoEdicion = false;
            this._idActual    = null;
            document.getElementById('modalEspecialistaNombre').textContent = '— Nuevo —';
        }

        abrirModal('modalEspecialista');
        cambiarTab('modalEspecialista', 'tabEspPersonal');
    },

    // ─────────────────────────────────────────────────────────────────────
    // CARGAR DATOS EN EL MODAL
    // ─────────────────────────────────────────────────────────────────────

    async _cargarDatos(id) {
        try {
            const r    = await fetch(`${API_URL}?modulo=especialistas&accion=get&id=${id}`);
            const data = await r.json();
            if (!data.success) return;

            const e = data.especialista;

            // Título
            document.getElementById('modalEspecialistaNombre').textContent =
                `${e.nombre} ${e.apellido_paterno}`;

            // Tab 1 — Personal
            document.getElementById('espId').value        = e.id_especialista;
            document.getElementById('espNombre').value    = e.nombre          ?? '';
            document.getElementById('espApPat').value     = e.apellido_paterno ?? '';
            document.getElementById('espApMat').value     = e.apellido_materno ?? '';
            document.getElementById('espFechaNac').value  = e.fecha_nacimiento  ?? '';
            document.getElementById('espFechaCont').value = e.fecha_contratacion ?? '';

            // Dirección
            document.getElementById('espCalle').value     = e.calle            ?? '';
            document.getElementById('espNumExt').value    = e.numero_exterior   ?? '';
            document.getElementById('espNumInt').value    = e.numero_interior   ?? '';
            document.getElementById('espCP').value        = e.codigo_postal     ?? '';
            document.getElementById('espColonia').value   = e.colonia           ?? '';
            document.getElementById('espIdCp').value      = e.id_cp             ?? '';
            document.getElementById('espEstado').value    = e.estado            ?? '';
            document.getElementById('espMunicipio').value = e.municipio         ?? '';

            // Tab 2 — Contacto
            (e.contactos ?? []).forEach(c => {
                const tipo = c.tipo_contacto?.toLowerCase() ?? '';
                if (tipo.includes('tel') || tipo.includes('cel')) {
                    document.getElementById('espTelefono').value = c.valor;
                }
                if (tipo.includes('email') || tipo.includes('correo')) {
                    document.getElementById('espEmail').value = c.valor;
                }
            });

            // Tab 3 — Educación
            this._espTemp = (e.especialidades ?? []).map(esp => ({
                id_especialidad:   esp.id_especialidad,
                nombre:            esp.nombre_especialidad,
                cedula_profesional: esp.cedula_profesional ?? '',
                institucion:       esp.institucion         ?? '',
            }));
            this._renderEspecialidades();

        } catch (err) {
            console.error('especialistaController._cargarDatos:', err);
        }
    },

    // ─────────────────────────────────────────────────────────────────────
    // GUARDAR
    // ─────────────────────────────────────────────────────────────────────

    async guardar() {
        const nombre  = document.getElementById('espNombre').value.trim();
        const apPat   = document.getElementById('espApPat').value.trim();

        if (!nombre) {
            CatalogTable.showNotification('El nombre es requerido', 'error');
            return;
        }
        if (!apPat) {
            CatalogTable.showNotification('El apellido paterno es requerido', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('modulo', 'especialistas');
        formData.append('accion', this._modoEdicion ? 'update' : 'create');

        if (this._modoEdicion)
            formData.append('id_especialista', this._idActual);

        // Personal
        ['nombre','apellido_paterno','apellido_materno',
         'fecha_nacimiento','fecha_contratacion',
         'calle','numero_exterior','numero_interior'].forEach(campo => {
            const el = document.querySelector(`[name="${campo}"]`);
            if (el) formData.append(campo, el.value);
        });

        formData.append('id_cp', document.getElementById('espIdCp').value);
        formData.append('id_estatus', '1');

        // Contacto
        formData.append('telefono', document.getElementById('espTelefono').value.trim());
        formData.append('email',    document.getElementById('espEmail').value.trim());
        formData.append('id_tipo_contacto_telefono',
            document.getElementById('espIdTipoTel').value);
        formData.append('id_tipo_contacto_email',
            document.getElementById('espIdTipoEmail').value);

        // Especialidades
        formData.append('especialidades_json', JSON.stringify(this._espTemp));

        CatalogTable.showLoading(true);
        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            CatalogTable.showLoading(false);

            if (data.success) {
                CatalogTable.showNotification(
                    this._modoEdicion
                        ? 'Especialista actualizado correctamente'
                        : 'Especialista creado correctamente',
                    'success'
                );
                cerrarModal('modalEspecialista');
                // Recargar tabla de la página
                if (typeof cargarEspecialistas === 'function') cargarEspecialistas();
            } else {
                CatalogTable.showNotification(data.message || 'Error al guardar', 'error');
            }
        } catch (err) {
            CatalogTable.showLoading(false);
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },

    // ─────────────────────────────────────────────────────────────────────
    // CAMBIAR ESTATUS (activar / desactivar desde la tabla)
    // ─────────────────────────────────────────────────────────────────────

    async cambiarEstatus(id, nuevoEstatus) {
        const accion = nuevoEstatus === 1 ? 'activar' : 'desactivar';
        if (!confirm(`¿${accion.charAt(0).toUpperCase() + accion.slice(1)} este especialista?`)) return;

        const formData = new FormData();
        formData.append('modulo',          'especialistas');
        formData.append('accion',          'status');
        formData.append('id_especialista', id);
        formData.append('id_estatus',      nuevoEstatus);

        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            if (data.success) {
                CatalogTable.showNotification(data.message, 'success');
                if (typeof cargarEspecialistas === 'function') cargarEspecialistas();
            } else {
                CatalogTable.showNotification(data.message || 'Error', 'error');
            }
        } catch (err) {
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },

    // ─────────────────────────────────────────────────────────────────────
    // ESPECIALIDADES (Tab 3)
    // ─────────────────────────────────────────────────────────────────────

    mostrarFilaEsp() {
        document.getElementById('rowNuevaEsp').style.display = 'flex';
        document.getElementById('espSelectEsp').value = '';
        document.getElementById('espCedula').value    = '';
        document.getElementById('espInstitucion').value = '';
    },

    ocultarFilaEsp() {
        document.getElementById('rowNuevaEsp').style.display = 'none';
    },

    confirmarEsp() {
        const sel = document.getElementById('espSelectEsp');
        if (!sel.value) {
            CatalogTable.showNotification('Selecciona una especialidad', 'warning');
            return;
        }

        // Evitar duplicados
        if (this._espTemp.some(e => e.id_especialidad == sel.value)) {
            CatalogTable.showNotification('Esta especialidad ya fue agregada', 'warning');
            return;
        }

        this._espTemp.push({
            id_especialidad:    parseInt(sel.value),
            nombre:             sel.options[sel.selectedIndex].textContent,
            cedula_profesional: document.getElementById('espCedula').value.trim(),
            institucion:        document.getElementById('espInstitucion').value.trim(),
        });

        this.ocultarFilaEsp();
        this._renderEspecialidades();
    },

    quitarEsp(idx) {
        this._espTemp.splice(idx, 1);
        this._renderEspecialidades();
    },

    _renderEspecialidades() {
        const tbody = document.getElementById('bodyEspecialidades');
        if (!tbody) return;

        // Actualizar JSON oculto
        document.getElementById('espEspecialidadesJson').value =
            JSON.stringify(this._espTemp);

        if (!this._espTemp.length) {
            tbody.innerHTML = `
                <tr id="rowSinEsp">
                    <td colspan="4" style="text-align:center; color:#adb5bd; padding:16px;">
                        Sin especialidades registradas
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = this._espTemp.map((e, i) => `
            <tr>
                <td>${escHtml(e.nombre)}</td>
                <td>${escHtml(e.cedula_profesional) || '—'}</td>
                <td>${escHtml(e.institucion) || '—'}</td>
                <td class="acciones-cell">
                    <button class="btn-accion eliminar"
                        onclick="especialistaController.quitarEsp(${i})">
                        <i class="ri-delete-bin-6-line"></i>
                    </button>
                </td>
            </tr>`).join('');
    },

    // ─────────────────────────────────────────────────────────────────────
    // CÓDIGO POSTAL (igual que pacientes)
    // ─────────────────────────────────────────────────────────────────────

    async buscarCP(cp) {
        if (cp.length !== 5) return;
        try {
            const r    = await fetch(`${API_URL}?accion=buscar_cp&cp=${cp}`);
            const data = await r.json();
            if (!data.success) return;

            document.getElementById('espEstado').value    = data.estado;
            document.getElementById('espMunicipio').value = data.municipio;

            const datalist = document.getElementById('espListaColonias');
            datalist.innerHTML = '';
            data.colonias.forEach(c => {
                const opt   = document.createElement('option');
                opt.value   = c.colonia;
                opt.dataset.idCp = c.id_cp;
                datalist.appendChild(opt);
            });

            // Si solo hay una colonia, seleccionarla
            if (data.colonias.length === 1) {
                document.getElementById('espColonia').value = data.colonias[0].colonia;
                document.getElementById('espIdCp').value   = data.colonias[0].id_cp;
            }
        } catch (err) {
            console.warn('especialistaController.buscarCP:', err);
        }
    },

    _onColoniaChange(valor) {
        const datalist = document.getElementById('espListaColonias');
        const opt = [...datalist.options].find(o => o.value === valor);
        if (opt) document.getElementById('espIdCp').value = opt.dataset.idCp ?? '';
    },

    // ─────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────

    _poblarSelectEspecialidades() {
        const sel = document.getElementById('espSelectEsp');
        if (!sel || !this._catalogos?.especialidades) return;
        sel.innerHTML = '<option value="">Seleccionar especialidad...</option>';
        this._catalogos.especialidades.forEach(e => {
            const opt = document.createElement('option');
            opt.value       = e.id_especialidad;
            opt.textContent = e.nombre;
            sel.appendChild(opt);
        });
    },

    /**
     * Resuelve los IDs de tipo de contacto para teléfono y email
     * a partir del catálogo, y los guarda en los hidden inputs.
     */
    _resolverTiposContacto() {
        const tipos = this._catalogos?.tiposContacto ?? [];
        const tel   = tipos.find(t =>
            t.tipo_contacto.toLowerCase().includes('tel') ||
            t.tipo_contacto.toLowerCase().includes('cel')
        );
        const email = tipos.find(t =>
            t.tipo_contacto.toLowerCase().includes('email') ||
            t.tipo_contacto.toLowerCase().includes('correo')
        );
        if (tel)   document.getElementById('espIdTipoTel').value   = tel.id_tipo_contacto;
        if (email) document.getElementById('espIdTipoEmail').value = email.id_tipo_contacto;
    },

    _limpiarFormulario() {
        this._espTemp = [];

        ['espId','espNombre','espApPat','espApMat','espFechaNac','espFechaCont',
         'espCalle','espNumExt','espNumInt','espCP','espColonia','espIdCp',
         'espEstado','espMunicipio','espTelefono','espEmail'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        document.getElementById('modalEspecialistaNombre').textContent = '';
        this._renderEspecialidades();
    },
};

// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS
// ─────────────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {

    // Guardar especialista
    document.getElementById('btnGuardarEspecialista')
        ?.addEventListener('click', () => especialistaController.guardar());

    // Búsqueda de CP al escribir
    document.getElementById('espCP')
        ?.addEventListener('input', e => {
            if (e.target.value.length === 5)
                especialistaController.buscarCP(e.target.value);
        });

    // Selección de colonia
    document.getElementById('espColonia')
        ?.addEventListener('change', e =>
            especialistaController._onColoniaChange(e.target.value)
        );
});
