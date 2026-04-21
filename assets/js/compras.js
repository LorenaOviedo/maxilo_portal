/**
 * Módulo de compras
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
        const btnImprimir = document.getElementById('btnImprimirCompra');
        if (btnGuardar)  btnGuardar.style.display  = soloLectura ? 'none' : '';
        if (btnAgregar)  btnAgregar.style.display   = soloLectura ? 'none' : '';
        // Mostrar imprimir solo cuando hay una orden existente (edición o lectura)
        if (btnImprimir) btnImprimir.style.display  = this._idActual ? '' : 'none';
 
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
        formData.append('tasa_iva',                '16');
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
 
    imprimirCompra() {
        if (!this._idActual) return;
 
        const folio     = document.getElementById('ocFolio')?.value || '—';
        const proveedor = document.querySelector('#ocProveedor option:checked')?.textContent || '—';
        const tipo      = document.querySelector('#ocTipoCompra option:checked')?.textContent || '—';
        const moneda    = document.querySelector('#ocMoneda option:checked')?.textContent    || '—';
        const estatus   = document.querySelector('#ocEstatus option:checked')?.textContent   || '—';
        const fechaEm   = document.getElementById('ocFechaEmision')?.value    || '';
        const fechaEnt  = document.getElementById('ocFechaEntregaEst')?.value || '';
        const obs       = document.getElementById('ocObservaciones')?.value   || '';
        const anio      = new Date().getFullYear();
 
        const fmt = n => '$' + parseFloat(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 });
        const formatFecha = f => f
            ? new Date(f + 'T12:00:00').toLocaleDateString('es-MX', { day:'2-digit', month:'long', year:'numeric' })
            : '—';
 
        const totales = this._calcularTotales();
 
        const filasDetalle = this._detalle.length
            ? this._detalle.map(d => `
                <tr>
                    <td>${escHtml(d.nombre)}</td>
                    <td style="text-align:center">${escHtml(d.codigo || '—')}</td>
                    <td style="text-align:center">${d.cantidad}</td>
                    <td style="text-align:right">${fmt(d.precio_unitario)}</td>
                    <td style="text-align:right"><strong>${fmt(d.subtotal_linea)}</strong></td>
                </tr>`).join('')
            : '<tr><td colspan="5" style="text-align:center;color:#adb5bd;padding:16px;">Sin productos</td></tr>';
 
        const obsHtml = obs
            ? '<div class="notas-box"><div class="notas-label">Observaciones</div>' + escHtml(obs) + '</div>'
            : '';
 
        const ventana = window.open('', '_blank', 'width=900,height=700');
        ventana.document.write(
            '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">' +
            '<title>Orden de Compra ' + escHtml(folio) + '</title>' +
            '<style>' +
            '* { box-sizing:border-box; margin:0; padding:0; }' +
            'html { -webkit-font-smoothing:antialiased; }' +
            'body { font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#1a1a1a; background:#fff; padding:12mm 14mm; }' +
            '.header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #192D8C; padding-bottom:10px; margin-bottom:16px; }' +
            '.clinica-nombre { font-size:18px; font-weight:900; color:#192D8C; text-transform:uppercase; }' +
            '.clinica-sub { font-size:9px; color:#555; text-transform:uppercase; margin-top:2px; }' +
            '.header-meta { text-align:right; font-size:10px; color:#555; line-height:1.6; }' +
            '.titulo { font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:16px; }' +
            '.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px 24px; background:#f8f9fa; border-radius:6px; padding:12px 16px; margin-bottom:16px; }' +
            '.info-item { display:flex; flex-direction:column; gap:2px; }' +
            '.info-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#555; }' +
            '.info-value { font-size:12px; font-weight:600; color:#212529; }' +
            'table { width:100%; border-collapse:collapse; font-size:11px; margin-bottom:16px; }' +
            'thead th { background:#192D8C; color:#fff; padding:7px 10px; text-align:left; font-size:10px; text-transform:uppercase; }' +
            'tbody tr:nth-child(even) { background:#f8f9ff; }' +
            'tbody td { padding:6px 10px; border-bottom:1px solid #e9ecef; }' +
            '.totales-box { display:flex; flex-direction:column; align-items:flex-end; gap:4px; margin-bottom:16px; font-size:12px; }' +
            '.totales-box .fila { display:flex; gap:40px; }' +
            '.totales-box .fila span:first-child { color:#555; }' +
            '.totales-box .fila span:last-child { font-weight:600; min-width:80px; text-align:right; }' +
            '.total-bar { display:flex; justify-content:space-between; font-weight:700; font-size:14px; border-top:2px solid #1a1a1a; border-bottom:2px solid #1a1a1a; padding:8px 0; margin-bottom:16px; }' +
            '.notas-box { background:#fffbeb; border-left:3px solid #f59e0b; border-radius:4px; padding:10px 14px; font-size:11px; color:#495057; margin-bottom:16px; }' +
            '.notas-label { font-weight:700; margin-bottom:4px; }' +
            '.emisor-envio { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }' +
            '.emisor-bloque { background:#f8f9fa; border-radius:6px; padding:10px 14px; font-size:11px; color:#212529; }' +
            '.emisor-titulo { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#555; margin-bottom:6px; border-bottom:1px solid #dee2e6; padding-bottom:4px; }' +
            '.emisor-row { display:flex; gap:6px; margin-bottom:3px; line-height:1.4; }' +
            '.emisor-label { font-weight:700; flex-shrink:0; }' +
            '@media print { .emisor-envio { grid-template-columns:1fr 1fr; } }' +
            '.footer { margin-top:20px; border-top:3px solid #192D8C; padding-top:8px; display:flex; justify-content:space-between; font-size:9px; color:#192D8C; }' +
            '@media print { body { padding:8mm 10mm; } }' +
            '</style></head><body>' +
            '<div class="header"><div>' +
            '<div class="clinica-nombre">Maxilofacial Texcoco</div>' +
            '<div class="clinica-sub">Ortodoncia &middot; Cirug&iacute;a Maxilofacial &middot; Patolog&iacute;a Oral</div>' +
            '</div><div class="header-meta">' +
            '<strong>Fecha de impresi&oacute;n:</strong> ' + new Date().toLocaleDateString('es-MX') + '<br>' +
            'Dr. Alfonso Ayala G&oacute;mez</div></div>' +
            '<div class="titulo">Orden de Compra &mdash; ' + escHtml(folio) + '</div>' +
            '<div class="emisor-envio">' +
            '<div class="emisor-bloque">' +
            '<div class="emisor-titulo">Datos del emisor</div>' +
            '<div class="emisor-row"><span class="emisor-label">Raz&oacute;n social:</span><span>ALFONSO AYALA G&Oacute;MEZ</span></div>' +
            '<div class="emisor-row"><span class="emisor-label">RFC:</span><span>565689S5S5KLS</span></div>' +
            '<div class="emisor-row"><span class="emisor-label">Domicilio fiscal:</span><span>Retorno C No. 8 Fracc. San Mart&iacute;n, Texcoco, Estado de M&eacute;xico</span></div>' +
            '<div class="emisor-row"><span class="emisor-label">Tel&eacute;fono(s):</span><span>5689475263</span></div>' +
            '</div>' +
            '<div class="emisor-bloque">' +
            '<div class="emisor-titulo">Enviar a</div>' +
            '<div class="emisor-row"><strong>ALFONSO AYALA G&Oacute;MEZ</strong></div>' +
            '<div class="emisor-row">Retorno C No. 8 Fracc. San Mart&iacute;n, Texcoco, Estado de M&eacute;xico</div>' +
            '</div>' +
            '</div>' +
            '<div class="info-grid">' +
            '<div class="info-item"><span class="info-label">Proveedor</span><span class="info-value">' + escHtml(proveedor) + '</span></div>' +
            '<div class="info-item"><span class="info-label">Tipo de compra</span><span class="info-value">' + escHtml(tipo) + '</span></div>' +
            '<div class="info-item"><span class="info-label">Fecha de emisi&oacute;n</span><span class="info-value">' + formatFecha(fechaEm) + '</span></div>' +
            '<div class="info-item"><span class="info-label">Entrega estimada</span><span class="info-value">' + formatFecha(fechaEnt) + '</span></div>' +
            '<div class="info-item"><span class="info-label">Moneda</span><span class="info-value">' + escHtml(moneda) + '</span></div>' +
            '<div class="info-item"><span class="info-label">Estatus</span><span class="info-value">' + escHtml(estatus) + '</span></div>' +
            '</div>' +
            '<table><thead><tr>' +
            '<th>Producto</th><th style="text-align:center">C&oacute;digo</th>' +
            '<th style="text-align:center">Cantidad</th><th style="text-align:right">Precio unit.</th><th style="text-align:right">Subtotal</th>' +
            '</tr></thead><tbody>' + filasDetalle + '</tbody></table>' +
            '<div class="totales-box">' +
            '<div class="fila"><span>Subtotal:</span><span>' + fmt(totales.subtotal) + '</span></div>' +
            '<div class="fila"><span>IVA (16%):</span><span>' + fmt(totales.iva) + '</span></div>' +
            '</div>' +
            '<div class="total-bar"><span>TOTAL</span><span>' + fmt(totales.total) + '</span></div>' +
            obsHtml +
            '<div class="footer">' +
            '<span>Sistema Maxilofacial Texcoco &mdash; ' + anio + '</span>' +
            '<span>Retorno C No. 8 Fracc. San Mart&iacute;n, Texcoco, Estado de M&eacute;xico</span>' +
            '</div>' +
            '<script>window.onload=function(){window.print();window.close();};<\/script>' +
            '</body></html>'
        );
        ventana.document.close();
    },
 
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
        const tasa     = 0.16;  // IVA fijo 16%
        const subtotal = this._detalle.reduce((s, d) => s + d.subtotal_linea, 0);
        const iva      = parseFloat((subtotal * tasa).toFixed(2));
        const total    = parseFloat((subtotal + iva).toFixed(2));
        return { subtotal, iva, total };
    },
 
    _actualizarTotales() {
        const totales = this._calcularTotales();
        const fmt = n => '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
 
        const elSub   = document.getElementById('resSubtotal');
        const elIva   = document.getElementById('resIva');
        const elTotal = document.getElementById('resTotal');
 
        if (elSub)   elSub.textContent   = fmt(totales.subtotal);
        if (elIva)   elIva.textContent   = fmt(totales.iva);
        if (elTotal) elTotal.textContent = fmt(totales.total);
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