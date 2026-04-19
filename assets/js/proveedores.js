/**
 * Submódulo de proveedores
 *
 * Controlador del módulo de proveedores.
 * Depende de: CatalogTable (catalog-table.js), API_URL, CATALOGOS_PROV
 */
 
const proveedorController = {
 
    _modoEdicion: false,
    _soloLectura: false,
    _idActual:    null,
 
    // ─────────────────────────────────────────────────────────────────────
    // INICIALIZACIÓN — poblar selects con catálogos del servidor
    // ─────────────────────────────────────────────────────────────────────
 
    inicializar() {
        if (!window.CATALOGOS_PROV) return;
 
        // Tipos de producto/servicio
        const selTipo = document.getElementById('provTipoProducto');
        if (selTipo) {
            (CATALOGOS_PROV.tiposProductoProveedor ?? []).forEach(t => {
                const opt = document.createElement('option');
                opt.value       = t.id_tipo_producto_proveedor;
                opt.textContent = t.tipo_producto_proveedor;
                selTipo.appendChild(opt);
            });
        }
 
        // Resolver IDs de tipos de contacto (teléfono y email)
        this._resolverTiposContacto();
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // ABRIR MODAL
    // @param {number|null} idProveedor — null = nuevo
    // @param {boolean}     soloLectura
    // ─────────────────────────────────────────────────────────────────────
 
    async abrir(idProveedor = null, soloLectura = false) {
        this._limpiarFormulario();
        this._resolverTiposContacto();
        this._soloLectura = soloLectura;
 
        if (idProveedor) {
            this._modoEdicion = true;
            this._idActual    = idProveedor;
            await this._cargarDatos(idProveedor);
        } else {
            this._modoEdicion = false;
            this._soloLectura = false;
            this._idActual    = null;
            document.getElementById('modalProveedorNombre').textContent = '— Nuevo —';
        }
 
        this._aplicarModoLectura(soloLectura);
        abrirModal('modalProveedor');
        cambiarTab('modalProveedor', 'tabProvDatos');
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // MODO SOLO LECTURA
    // ─────────────────────────────────────────────────────────────────────
 
    _aplicarModoLectura(soloLectura) {
        document.querySelectorAll('#modalProveedor input, #modalProveedor select, #modalProveedor textarea')
            .forEach(el => { el.disabled = soloLectura; });
 
        const btnGuardar = document.getElementById('btnGuardarProveedor');
        if (btnGuardar) btnGuardar.style.display = soloLectura ? 'none' : '';
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // CARGAR DATOS EN EL MODAL
    // ─────────────────────────────────────────────────────────────────────
 
    async _cargarDatos(id) {
        try {
            const r    = await fetch(`${API_URL}?modulo=proveedores&accion=get_proveedor&id=${id}`);
            const data = await r.json();
            if (!data.success) return;
 
            const p = data.proveedor;
 
            // Título
            document.getElementById('modalProveedorNombre').textContent = p.razon_social ?? '';
 
            // Tab 1 — Datos generales
            this._setVal('provId',            p.id_proveedor                ?? '');
            this._setVal('provIdDireccion',   p.id_direccion                ?? '');
            this._setVal('provRFC',           p.rfc                         ?? '');
            this._setVal('provTipoPersona',   p.tipo_persona                ?? '');
            this._setVal('provRazonSocial',   p.razon_social                ?? '');
            this._setVal('provTipoProducto',  p.id_tipo_producto_proveedor  ?? '');
 
            // Tab 2 — Contacto
            (p.contactos ?? []).forEach(c => {
                const tipo = c.tipo_contacto?.toLowerCase() ?? '';
                if (tipo.includes('tel') || tipo.includes('cel'))
                    this._setVal('provTelefono', c.valor);
                if (tipo.includes('email') || tipo.includes('correo'))
                    this._setVal('provEmail', c.valor);
            });
 
            // Dirección
            this._setVal('provCalle',    p.calle            ?? '');
            this._setVal('provNumExt',   p.numero_exterior  ?? '');
            this._setVal('provNumInt',   p.numero_interior  ?? '');
            this._setVal('provCP',       p.codigo_postal    ?? '');
            this._setVal('provColonia',  p.colonia          ?? '');
            this._setVal('provIdCp',     p.id_cp            ?? '');
            this._setVal('provEstado',   p.estado           ?? '');
            this._setVal('provMunicipio',p.municipio        ?? '');
 
            // Tab 3 — Condiciones comerciales
            this._setVal('provTerminosPago',  p.terminos_pago  ?? '');
            this._setVal('provDiasCredito',   p.dias_credito   ?? '0');
            this._setVal('provLimiteCredito', p.limite_credito ?? '0.00');
 
        } catch (err) {
            console.error('proveedorController._cargarDatos:', err);
            CatalogTable.showNotification('Error al cargar datos del proveedor', 'error');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // GUARDAR — validaciones + envío
    // ─────────────────────────────────────────────────────────────────────
 
    async guardar() {
        if (this._soloLectura) return;
 
        // ── Validaciones frontend ────────────────────────────────────────
        // RFC: formato mexicano — 3-4 letras + 6 dígitos + 3 alfanuméricos
        const regexRFC    = /^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i;
        const regexEmail  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const regexTel    = /^\d{10}$/;
        const regexCP     = /^\d{5}$/;
        const regexNum    = /^\d+(\.\d{1,2})?$/;
 
        const rfc        = this._getVal('provRFC').toUpperCase();
        const tipoPersona= this._getVal('provTipoPersona');
        const razonSocial= this._getVal('provRazonSocial');
        const telefono   = this._getVal('provTelefono');
        const email      = this._getVal('provEmail');
        const cp         = this._getVal('provCP');
        const diasCred   = this._getVal('provDiasCredito');
        const limCred    = this._getVal('provLimiteCredito');
 
        // Obligatorios
        if (!rfc) {
            CatalogTable.showNotification('El RFC es obligatorio', 'error');
            this._focusTab('tabProvDatos', 'provRFC'); return;
        }
        if (!regexRFC.test(rfc)) {
            CatalogTable.showNotification('El RFC no tiene un formato válido (Ej: LOMP800101ABC)', 'error');
            this._focusTab('tabProvDatos', 'provRFC'); return;
        }
        if (!tipoPersona) {
            CatalogTable.showNotification('Selecciona el tipo de persona', 'error');
            this._focusTab('tabProvDatos', 'provTipoPersona'); return;
        }
        if (!razonSocial) {
            CatalogTable.showNotification('La razón social es obligatoria', 'error');
            this._focusTab('tabProvDatos', 'provRazonSocial'); return;
        }
 
        // Opcionales con formato
        if (telefono && !regexTel.test(telefono)) {
            CatalogTable.showNotification('El teléfono debe tener exactamente 10 dígitos', 'error');
            this._focusTab('tabProvContacto', 'provTelefono'); return;
        }
        if (email && !regexEmail.test(email)) {
            CatalogTable.showNotification('El formato del correo electrónico no es válido', 'error');
            this._focusTab('tabProvContacto', 'provEmail'); return;
        }
        if (cp && !regexCP.test(cp)) {
            CatalogTable.showNotification('El código postal debe tener exactamente 5 dígitos', 'error');
            this._focusTab('tabProvContacto', 'provCP'); return;
        }
        if (diasCred && (isNaN(parseInt(diasCred)) || parseInt(diasCred) < 0)) {
            CatalogTable.showNotification('Los días de crédito deben ser un número positivo', 'error');
            this._focusTab('tabProvComercial', 'provDiasCredito'); return;
        }
        if (limCred && (isNaN(parseFloat(limCred)) || parseFloat(limCred) < 0)) {
            CatalogTable.showNotification('El límite de crédito debe ser un número positivo', 'error');
            this._focusTab('tabProvComercial', 'provLimiteCredito'); return;
        }
 
        // Si CP vacío, limpiar campos dependientes para evitar FK inválidos
        if (!cp) {
            this._setVal('provIdCp',      '');
            this._setVal('provColonia',   '');
            this._setVal('provEstado',    '');
            this._setVal('provMunicipio', '');
        }
 
        // ── Armar FormData ───────────────────────────────────────────────
        const formData = new FormData();
        formData.append('modulo', 'proveedores');
        formData.append('accion', this._modoEdicion ? 'actualizar_proveedor' : 'crear_proveedor');
 
        if (this._modoEdicion) {
            formData.append('id_proveedor',        this._idActual);
            formData.append('id_direccion_actual', this._getVal('provIdDireccion'));
        }
 
        // Datos generales
        formData.append('rfc',                      rfc);
        formData.append('tipo_persona',             tipoPersona);
        formData.append('razon_social',             razonSocial.toUpperCase());
        formData.append('id_tipo_producto_proveedor', this._getVal('provTipoProducto'));
 
        // Contacto
        formData.append('telefono', telefono);
        formData.append('email',    email);
        formData.append('id_tipo_contacto_telefono', this._getVal('provIdTipoTel'));
        formData.append('id_tipo_contacto_email',    this._getVal('provIdTipoEmail'));
 
        // Dirección
        formData.append('calle',            this._getVal('provCalle'));
        formData.append('numero_exterior',  this._getVal('provNumExt'));
        formData.append('numero_interior',  this._getVal('provNumInt'));
        formData.append('id_cp',            this._getVal('provIdCp'));
 
        // Comercial
        formData.append('terminos_pago',  this._getVal('provTerminosPago'));
        formData.append('dias_credito',   this._getVal('provDiasCredito')   || '0');
        formData.append('limite_credito', this._getVal('provLimiteCredito') || '0');
 
        // ── Enviar ───────────────────────────────────────────────────────
        CatalogTable.showLoading(true);
        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            CatalogTable.showLoading(false);
 
            if (data.success) {
                CatalogTable.showNotification(
                    this._modoEdicion
                        ? 'Proveedor actualizado correctamente'
                        : 'Proveedor creado correctamente',
                    'success'
                );
                cerrarModal('modalProveedor');
                setTimeout(() => window.location.reload(), 800);
            } else {
                CatalogTable.showNotification(data.message || 'Error al guardar', 'error');
            }
        } catch (err) {
            CatalogTable.showLoading(false);
            console.error('proveedorController.guardar:', err);
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // CAMBIAR ESTATUS
    // ─────────────────────────────────────────────────────────────────────
 
    async cambiarEstatus(id, nuevoEstatus, nombre) {
        const accion = nuevoEstatus === 1 ? 'activar' : 'desactivar';
        if (!confirm(`¿${accion.charAt(0).toUpperCase() + accion.slice(1)} al proveedor "${nombre}"?`)) return;
 
        const formData = new FormData();
        formData.append('modulo',       'proveedores');
        formData.append('accion',       'status_proveedor');
        formData.append('id_proveedor', id);
        formData.append('id_estatus',   nuevoEstatus);
 
        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            if (data.success) {
                CatalogTable.showNotification(data.message, 'success');
                setTimeout(() => window.location.reload(), 800);
            } else {
                CatalogTable.showNotification(data.message || 'Error', 'error');
            }
        } catch (err) {
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // CÓDIGO POSTAL
    // ─────────────────────────────────────────────────────────────────────
 
    async buscarCP(cp) {
        if (cp.length !== 5) return;
        try {
            const r    = await fetch(`${API_URL}?accion=buscar_cp&cp=${cp}`);
            const data = await r.json();
            if (!data.success) return;
 
            this._setVal('provEstado',    data.estado);
            this._setVal('provMunicipio', data.municipio);
 
            const datalist = document.getElementById('provListaColonias');
            if (!datalist) return;
 
            datalist.innerHTML = '';
            data.colonias.forEach(c => {
                const opt        = document.createElement('option');
                opt.value        = c.colonia;
                opt.dataset.idCp = c.id_cp;
                datalist.appendChild(opt);
            });
 
            if (data.colonias.length === 1) {
                this._setVal('provColonia', data.colonias[0].colonia);
                this._setVal('provIdCp',   data.colonias[0].id_cp);
            }
        } catch (err) {
            console.warn('proveedorController.buscarCP:', err);
        }
    },
 
    _onColoniaChange(valor) {
        const opts = document.querySelectorAll('#provListaColonias option');
        const opt  = [...opts].find(o => o.value === valor);
        if (opt) this._setVal('provIdCp', opt.dataset.idCp ?? '');
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────
 
    _setVal(id, val) {
        const el = document.getElementById(id);
        if (el) el.value = val ?? '';
    },
 
    _getVal(id) {
        return document.getElementById(id)?.value?.trim() ?? '';
    },
 
    /** Navega al tab indicado y enfoca el campo */
    _focusTab(tabId, fieldId) {
        cambiarTab('modalProveedor', tabId);
        setTimeout(() => document.getElementById(fieldId)?.focus(), 100);
    },
 
    _resolverTiposContacto() {
        const tipos = CATALOGOS_PROV?.tiposContacto ?? [];
        const tel   = tipos.find(t =>
            t.tipo_contacto.toLowerCase().includes('tel') ||
            t.tipo_contacto.toLowerCase().includes('cel')
        );
        const email = tipos.find(t =>
            t.tipo_contacto.toLowerCase().includes('email') ||
            t.tipo_contacto.toLowerCase().includes('correo')
        );
        if (tel)   this._setVal('provIdTipoTel',   tel.id_tipo_contacto);
        if (email) this._setVal('provIdTipoEmail', email.id_tipo_contacto);
    },
 
    _limpiarFormulario() {
        [
            'provId', 'provIdDireccion', 'provRFC', 'provTipoPersona', 'provRazonSocial',
            'provTipoProducto', 'provTelefono', 'provEmail',
            'provCalle', 'provNumExt', 'provNumInt', 'provCP', 'provColonia',
            'provIdCp', 'provEstado', 'provMunicipio',
            'provTerminosPago', 'provDiasCredito', 'provLimiteCredito',
        ].forEach(id => this._setVal(id, ''));
 
        document.getElementById('modalProveedorNombre').textContent = '';
    },
};
 
// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS
// ─────────────────────────────────────────────────────────────────────────────
 
document.addEventListener('DOMContentLoaded', () => {
 
    // Inicializar catálogos en los selects
    proveedorController.inicializar();
 
    // Guardar
    document.getElementById('btnGuardarProveedor')
        ?.addEventListener('click', () => proveedorController.guardar());
 
    // Búsqueda por código postal
    document.getElementById('provCP')
        ?.addEventListener('input', e => {
            if (e.target.value.length === 5)
                proveedorController.buscarCP(e.target.value);
        });
 
    // Selección de colonia
    document.getElementById('provColonia')
        ?.addEventListener('change', e =>
            proveedorController._onColoniaChange(e.target.value)
        );
 
    // RFC a mayúsculas en tiempo real
    document.getElementById('provRFC')
        ?.addEventListener('input', e => {
            e.target.value = e.target.value.toUpperCase();
        });
 
    // Razón social a mayúsculas en tiempo real
    document.getElementById('provRazonSocial')
        ?.addEventListener('input', e => {
            e.target.value = e.target.value.toUpperCase();
        });
 
    // Búsqueda en la tabla (en tiempo real, reutiliza CatalogTable)
    CatalogTable.init();
});