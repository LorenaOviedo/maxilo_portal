/**
 * compras.js
 * Controlador del módulo de órdenes de compra.
 * Depende de: CatalogTable, API_URL, CATALOGOS_OC
 */
 
const compraController = {
 
    _modoEdicion: false,
    _soloLectura: false,
    _idActual:    null,
    _detalle:     [],   // [{ id_producto, nombre, cantidad, precio_unitario, subtotal_linea }]
 
    // ─────────────────────────────────────────────────────────────────────
    // INICIALIZAR — poblar selects con catálogos
    // ─────────────────────────────────────────────────────────────────────
 
    inicializar() {
        if (!window.CATALOGOS_OC) return;
 
        this._poblarSelect('ocProveedor',   CATALOGOS_OC.proveedores,         'id_proveedor',            'razon_social');
        this._poblarSelect('ocTipoCompra',  CATALOGOS_OC.tiposCompra,         'id_tipo_compra',          'tipo_compra');
        this._poblarSelect('ocMoneda',      CATALOGOS_OC.monedas,             'id_moneda',               'moneda');
        this._poblarSelect('ocEstatus',     CATALOGOS_OC.estadosOrdenCompra,  'id_estatus_orden_compra', 'estatus_orden_compra');
        this._poblarSelect('ocSelectProducto', CATALOGOS_OC.productos,        'id_producto',             'nombre_producto',
            p => `[${p.codigo_producto}] ${p.nombre_producto}${p.marca ? ' — ' + p.marca : ''}`
        );
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // ABRIR MODAL
    // ─────────────────────────────────────────────────────────────────────
 
    async abrir(idCompra = null, soloLectura = false) {
        this._limpiarFormulario();
        this.inicializar();          // poblar selects
        this._soloLectura = soloLectura;
 
        if (idCompra) {
            this._modoEdicion = true;
            this._idActual    = idCompra;
        } else {
            this._modoEdicion = false;
            this._idActual    = null;
            document.getElementById('modalCompraFolio').textContent = '— Nueva —';
            // Fecha de emisión por defecto = hoy (solo para nueva orden)
            const hoy = new Date().toISOString().split('T')[0];
            this._setVal('ocFechaEmision', hoy);
        }
 
        this._aplicarModoLectura(soloLectura);
 
        // Abrir modal PRIMERO para que los selects estén activos en el DOM
        abrirModal('modalCompra');
        cambiarTab('modalCompra', 'tabOCDatos');
 
        // Cargar datos DESPUÉS de abrir el modal
        if (idCompra) {
            await this._cargarDatos(idCompra);
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // MODO SOLO LECTURA
    // ─────────────────────────────────────────────────────────────────────
 
    _aplicarModoLectura(soloLectura) {
        document.querySelectorAll('#modalCompra input, #modalCompra select, #modalCompra textarea')
            .forEach(el => { el.disabled = soloLectura; });
 
        const btnGuardar  = document.getElementById('btnGuardarCompra');
        const btnAgregar  = document.getElementById('btnAgregarProducto');
        if (btnGuardar) btnGuardar.style.display = soloLectura ? 'none' : '';
        if (btnAgregar) btnAgregar.style.display  = soloLectura ? 'none' : '';
 
        document.querySelectorAll('#bodyDetalle .btn-accion.eliminar')
            .forEach(btn => { btn.style.display = soloLectura ? 'none' : ''; });
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // CARGAR DATOS
    // ─────────────────────────────────────────────────────────────────────
 
    async _cargarDatos(id) {
        try {
            const r    = await fetch(`${API_URL}?modulo=compras&accion=get&id=${id}`);
            const data = await r.json();
            if (!data.success) return;
 
            const c = data.compra;
 
            document.getElementById('modalCompraFolio').textContent = c.folio_compra ?? '';
 
            // Campos de texto/fecha — asignación inmediata
            this._setVal('ocId',              c.id_compra              ?? '');
            this._setVal('ocFolio',           c.folio_compra           ?? '');
            this._setVal('ocFechaEmision',    c.fecha_emision          ?? '');
            this._setVal('ocFechaEntregaEst', c.fecha_entrega_estimada ?? '');
            this._setVal('ocFechaEntrega',    c.fecha_entrega          ?? '');
 
            if (c.fecha_entrega) {
                const g = document.getElementById('groupFechaEntrega');
                if (g) g.style.display = '';
            }
 
            // Selects con FK — esperar al siguiente frame para que el DOM esté listo
            requestAnimationFrame(() => requestAnimationFrame(() => {
                this._setVal('ocProveedor',  String(c.id_proveedor            ?? ''));
                this._setVal('ocTipoCompra', String(c.id_tipo_compra          ?? ''));
                this._setVal('ocMoneda',     String(c.id_moneda               ?? ''));
                this._setVal('ocEstatus',    String(c.id_estatus_orden_compra ?? ''));
            }));
 
            // Tab 2 — Detalle
            this._detalle = (c.detalle ?? []).map(d => ({
                id_producto:     d.id_producto,
                nombre:          d.nombre_producto,
                codigo:          d.codigo_producto,
                marca:           d.marca ?? '',
                cantidad:        parseInt(d.cantidad),
                precio_unitario: parseFloat(d.precio_unitario),
                subtotal_linea:  parseFloat(d.subtotal_linea),
            }));
            this._renderDetalle();
 
            // Tab 3 — Totales
            this._setVal('ocTasaIva',       c.tasa_iva      ?? '16');
            this._setVal('ocObservaciones', c.observaciones ?? '');
            this._actualizarTotales();
 
        } catch (err) {
            console.error('compraController._cargarDatos:', err);
            CatalogTable.showNotification('Error al cargar la orden de compra', 'error');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // GUARDAR
    // ─────────────────────────────────────────────────────────────────────
 
    async guardar() {
        if (this._soloLectura) return;
 
        // Validaciones frontend
        const folio    = this._getVal('ocFolio').toUpperCase();
        const proveedor= this._getVal('ocProveedor');
        const tipo     = this._getVal('ocTipoCompra');
        const fecha    = this._getVal('ocFechaEmision');
        const moneda   = this._getVal('ocMoneda');
 
        if (!folio) {
            CatalogTable.showNotification('El folio de la orden es obligatorio', 'error');
            this._focusTab('tabOCDatos', 'ocFolio'); return;
        }
        if (!/^[A-Z0-9\-]+$/.test(folio)) {
            CatalogTable.showNotification('El folio solo puede contener letras, números y guiones', 'error');
            this._focusTab('tabOCDatos', 'ocFolio'); return;
        }
        if (!proveedor) {
            CatalogTable.showNotification('Selecciona un proveedor', 'error');
            this._focusTab('tabOCDatos', 'ocProveedor'); return;
        }
        if (!tipo) {
            CatalogTable.showNotification('Selecciona el tipo de compra', 'error');
            this._focusTab('tabOCDatos', 'ocTipoCompra'); return;
        }
        if (!fecha) {
            CatalogTable.showNotification('La fecha de emisión es obligatoria', 'error');
            this._focusTab('tabOCDatos', 'ocFechaEmision'); return;
        }
        if (!moneda) {
            CatalogTable.showNotification('Selecciona la moneda', 'error');
            this._focusTab('tabOCDatos', 'ocMoneda'); return;
        }
        if (this._detalle.length === 0) {
            CatalogTable.showNotification('Agrega al menos un producto al detalle', 'error');
            cambiarTab('modalCompra', 'tabOCDetalle'); return;
        }
 
        const totales = this._calcularTotales();
        const formData = new FormData();
        formData.append('modulo', 'compras');
        formData.append('accion', this._modoEdicion ? 'actualizar_compra' : 'crear_compra');
 
        if (this._modoEdicion) formData.append('id_compra', this._idActual);
 
        formData.append('folio_compra',            folio);
        formData.append('id_proveedor',            proveedor);
        formData.append('id_tipo_compra',          tipo);
        formData.append('fecha_emision',           fecha);
        formData.append('fecha_entrega_estimada',  this._getVal('ocFechaEntregaEst'));
        formData.append('fecha_entrega',           this._getVal('ocFechaEntrega'));
        formData.append('id_moneda',               moneda);
        formData.append('id_estatus_orden_compra', this._getVal('ocEstatus') || '1');
        formData.append('tasa_iva',                this._getVal('ocTasaIva') || '16');
        formData.append('observaciones',           this._getVal('ocObservaciones'));
        formData.append('detalle_json',            JSON.stringify(this._detalle));
 
        CatalogTable.showLoading(true);
        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            CatalogTable.showLoading(false);
 
            if (data.success) {
                CatalogTable.showNotification(
                    this._modoEdicion ? 'Orden actualizada correctamente' : 'Orden creada correctamente',
                    'success'
                );
                cerrarModal('modalCompra');
                setTimeout(() => window.location.reload(), 800);
            } else {
                CatalogTable.showNotification(data.message || 'Error al guardar', 'error');
            }
        } catch (err) {
            CatalogTable.showLoading(false);
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // CANCELAR ORDEN
    // ─────────────────────────────────────────────────────────────────────
 
    async cancelar(id, folio) {
        if (!confirm(`¿Cancelar la orden de compra "${folio}"? Esta acción no se puede deshacer.`)) return;
 
        const formData = new FormData();
        formData.append('modulo',    'compras');
        formData.append('accion',    'cancelar_compra');
        formData.append('id_compra', id);
 
        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            if (data.success) {
                CatalogTable.showNotification('Orden cancelada', 'success');
                setTimeout(() => window.location.reload(), 800);
            } else {
                CatalogTable.showNotification(data.message || 'Error', 'error');
            }
        } catch (err) {
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // DETALLE — PRODUCTOS
    // ─────────────────────────────────────────────────────────────────────
 
    mostrarFilaProducto() {
        const row = document.getElementById('rowNuevoProducto');
        if (row) row.style.display = 'flex';
        this._setVal('ocSelectProducto', '');
        this._setVal('ocCantidad',       '');
        this._setVal('ocPrecioUnitario', '');
    },
 
    ocultarFilaProducto() {
        const row = document.getElementById('rowNuevoProducto');
        if (row) row.style.display = 'none';
    },
 
    confirmarProducto() {
        const sel      = document.getElementById('ocSelectProducto');
        const cantidad = parseInt(this._getVal('ocCantidad'));
        const precio   = parseFloat(this._getVal('ocPrecioUnitario'));
 
        if (!sel?.value) {
            CatalogTable.showNotification('Selecciona un producto', 'warning'); return;
        }
        if (!cantidad || cantidad < 1) {
            CatalogTable.showNotification('La cantidad debe ser mayor a 0', 'warning'); return;
        }
        if (isNaN(precio) || precio < 0) {
            CatalogTable.showNotification('El precio debe ser un número positivo', 'warning'); return;
        }
        if (this._detalle.some(d => d.id_producto == sel.value)) {
            CatalogTable.showNotification('Este producto ya está en el detalle', 'warning'); return;
        }
 
        // Buscar nombre del producto en el catálogo
        const prod = (CATALOGOS_OC.productos ?? []).find(p => p.id_producto == sel.value);
 
        this._detalle.push({
            id_producto:     parseInt(sel.value),
            nombre:          prod?.nombre_producto ?? sel.options[sel.selectedIndex].textContent,
            codigo:          prod?.codigo_producto ?? '',
            marca:           prod?.marca ?? '',
            cantidad:        cantidad,
            precio_unitario: precio,
            subtotal_linea:  parseFloat((cantidad * precio).toFixed(2)),
        });
 
        this.ocultarFilaProducto();
        this._renderDetalle();
        this._actualizarTotales();
    },
 
    quitarProducto(idx) {
        this._detalle.splice(idx, 1);
        this._renderDetalle();
        this._actualizarTotales();
    },
 
    _renderDetalle() {
        const tbody = document.getElementById('bodyDetalle');
        if (!tbody) return;
 
        document.getElementById('ocDetalleJson').value = JSON.stringify(this._detalle);
 
        if (!this._detalle.length) {
            tbody.innerHTML = `
                <tr id="rowSinProductos">
                    <td colspan="5" style="text-align:center; color:#adb5bd; padding:16px;">
                        Sin productos agregados
                    </td>
                </tr>`;
            return;
        }
 
        tbody.innerHTML = this._detalle.map((d, i) => `
            <tr>
                <td>
                    <div style="font-weight:600;">${escHtml(d.nombre)}</div>
                    <div style="font-size:12px;color:#6c757d;">${escHtml(d.codigo)}${d.marca ? ' · ' + escHtml(d.marca) : ''}</div>
                </td>
                <td class="text-center">${d.cantidad}</td>
                <td class="text-right">$${d.precio_unitario.toFixed(2)}</td>
                <td class="text-right"><strong>$${d.subtotal_linea.toFixed(2)}</strong></td>
                <td class="acciones-cell">
                    <button class="btn-accion eliminar"
                        style="${this._soloLectura ? 'display:none' : ''}"
                        onclick="compraController.quitarProducto(${i})">
                        <i class="ri-delete-bin-6-line"></i>
                    </button>
                </td>
            </tr>`).join('');
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // TOTALES
    // ─────────────────────────────────────────────────────────────────────
 
    _calcularTotales() {
        const tasa     = parseFloat(this._getVal('ocTasaIva') || '16') / 100;
        const subtotal = this._detalle.reduce((s, d) => s + d.subtotal_linea, 0);
        const iva      = parseFloat((subtotal * tasa).toFixed(2));
        const total    = parseFloat((subtotal + iva).toFixed(2));
        return { subtotal, iva, total };
    },
 
    _actualizarTotales() {
        const tasa    = parseFloat(this._getVal('ocTasaIva') || '16');
        const totales = this._calcularTotales();
 
        const fmt = n => '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
 
        const elSub   = document.getElementById('resSubtotal');
        const elIva   = document.getElementById('resIva');
        const elTotal = document.getElementById('resTotal');
        const elLabel = document.getElementById('resIvaLabel');
 
        if (elSub)   elSub.textContent   = fmt(totales.subtotal);
        if (elIva)   elIva.textContent   = fmt(totales.iva);
        if (elTotal) elTotal.textContent = fmt(totales.total);
        if (elLabel) elLabel.textContent = `IVA (${tasa}%):`;
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
 
    _focusTab(tabId, fieldId) {
        cambiarTab('modalCompra', tabId);
        setTimeout(() => document.getElementById(fieldId)?.focus(), 100);
    },
 
    _poblarSelect(selectId, items, valKey, labelKey, labelFn = null) {
        const sel = document.getElementById(selectId);
        if (!sel || !items) return;
        const placeholder = sel.options[0]?.textContent ?? 'Seleccionar...';
        sel.innerHTML = `<option value="">${placeholder}</option>`;
        items.forEach(item => {
            const opt       = document.createElement('option');
            opt.value       = item[valKey];
            opt.textContent = labelFn ? labelFn(item) : item[labelKey];
            sel.appendChild(opt);
        });
    },
 
    _limpiarFormulario() {
        this._detalle = [];
        [
            'ocId', 'ocFolio', 'ocProveedor', 'ocTipoCompra',
            'ocFechaEmision', 'ocFechaEntregaEst', 'ocFechaEntrega',
            'ocMoneda', 'ocEstatus', 'ocObservaciones',
        ].forEach(id => this._setVal(id, ''));
        this._setVal('ocTasaIva', '16');
        document.getElementById('modalCompraFolio').textContent = '';
        document.getElementById('groupFechaEntrega').style.display = 'none';
        this._renderDetalle();
        this._actualizarTotales();
        const row = document.getElementById('rowNuevoProducto');
        if (row) row.style.display = 'none';
    },
};
 
// ─────────────────────────────────────────────────────────────────────────────
// HELPERS GLOBALES
// ─────────────────────────────────────────────────────────────────────────────
 
function escHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}
 
// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS
// ─────────────────────────────────────────────────────────────────────────────
 
document.addEventListener('DOMContentLoaded', () => {
 
    document.getElementById('btnGuardarCompra')
        ?.addEventListener('click', () => compraController.guardar());
 
    // Folio a mayúsculas
    document.getElementById('ocFolio')
        ?.addEventListener('input', e => { e.target.value = e.target.value.toUpperCase(); });
 
    // Recalcular totales al cambiar IVA
    document.getElementById('ocTasaIva')
        ?.addEventListener('change', () => compraController._actualizarTotales());
 
    // Al seleccionar producto, pre-llenar precio del catálogo
    document.getElementById('ocSelectProducto')
        ?.addEventListener('change', e => {
            const prod = (CATALOGOS_OC.productos ?? []).find(p => p.id_producto == e.target.value);
            if (prod?.precio_compra) {
                compraController._setVal('ocPrecioUnitario', prod.precio_compra);
            }
        });
 
    // Búsqueda en tabla
    CatalogTable.init();
});