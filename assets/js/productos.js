/**
 * Submódulo de productos
 * Controlador del módulo de productos.
 * Depende de: CatalogTable, API_URL, CATALOGOS_PROD
 */
 
const productoController = {
 
    _modoEdicion: false,
    _soloLectura: false,
    _idActual:    null,
 
    // ─────────────────────────────────────────────────────────────────────
    // INICIALIZAR
    // ─────────────────────────────────────────────────────────────────────
 
    inicializar() {
        if (!window.CATALOGOS_PROD) return;
 
        const sel = document.getElementById('prodTipo');
        if (sel && sel.options.length <= 1) {
            (CATALOGOS_PROD.tiposProducto ?? []).forEach(t => {
                const opt       = document.createElement('option');
                opt.value       = t.id_tipo_producto;
                opt.textContent = t.nombre_tipo_producto;
                sel.appendChild(opt);
            });
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // ABRIR MODAL
    // @param {number|null} id          — null = nuevo
    // @param {boolean}     soloLectura — true = Ver, false = Editar
    // ─────────────────────────────────────────────────────────────────────
 
    async abrir(id = null, soloLectura = false) {
        this._limpiarFormulario();
        this.inicializar();
        this._soloLectura = soloLectura;
 
        if (id) {
            this._modoEdicion = true;
            this._idActual    = id;
        } else {
            this._modoEdicion = false;
            this._idActual    = null;
            document.getElementById('modalProductoNombre').textContent = '— Nuevo —';
        }
 
        this._aplicarModoLectura(soloLectura);
 
        // Abrir modal ANTES de cargar datos (para que los selects estén en DOM)
        abrirModal('modalProducto');
        cambiarTab('modalProducto', 'tabProdDatos');
 
        if (id) await this._cargarDatos(id);
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // MODO SOLO LECTURA
    // ─────────────────────────────────────────────────────────────────────
 
    _aplicarModoLectura(soloLectura) {
        document.querySelectorAll('#modalProducto input, #modalProducto select, #modalProducto textarea')
            .forEach(el => { el.disabled = soloLectura; });
 
        const btnGuardar = document.getElementById('btnGuardarProducto');
        if (btnGuardar) btnGuardar.style.display = soloLectura ? 'none' : '';
 
 
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // CARGAR DATOS
    // ─────────────────────────────────────────────────────────────────────
 
    async _cargarDatos(id) {
        try {
            const r    = await fetch(`${API_URL}?modulo=productos&accion=get&id=${id}`);
            const data = await r.json();
            if (!data.success) return;
 
            const p = data.producto;
 
            document.getElementById('modalProductoNombre').textContent = p.nombre_producto ?? '';
 
            // Tab 1 — Datos del producto
            this._setVal('prodId',       p.id_producto       ?? '');
            this._setVal('prodCodigo',   p.codigo_producto   ?? '');
            this._setVal('prodNombre',   p.nombre_producto   ?? '');
            this._setVal('prodMarca',    p.marca             ?? '');
            this._setVal('prodRegistro', p.registro_sanitario ?? '');
            this._setVal('prodPrecio',   p.precio_compra     ?? '');
            this._setVal('prodDescripcion', p.descripcion    ?? '');
 
            // Select tipo — requestAnimationFrame para garantizar DOM listo
            requestAnimationFrame(() => requestAnimationFrame(() => {
                this._setVal('prodTipo', String(p.id_tipo_producto ?? ''));
            }));
 
            // Tab 2 — Inventario
            this._setVal('prodStockMin', p.stock_minimo   ?? '0');
            this._setVal('prodLote',     p.lote           ?? '');
            this._setVal('prodFechaFab', p.fecha_fabricacion ?? '');
            this._setVal('prodFechaCad', p.fecha_caducidad   ?? '');
 
        } catch (err) {
            console.error('productoController._cargarDatos:', err);
            CatalogTable.showNotification('Error al cargar datos del producto', 'error');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // GUARDAR
    // ─────────────────────────────────────────────────────────────────────
 
    async guardar() {
        if (this._soloLectura) return;
 
        // ── Validaciones frontend ─────────────────────────────────────────
        const codigo   = this._getVal('prodCodigo').toUpperCase();
        const nombre   = this._getVal('prodNombre').toUpperCase();
        const tipo     = this._getVal('prodTipo');
        const precio   = this._getVal('prodPrecio');
        const stockMin = this._getVal('prodStockMin');
        const fechaFab = this._getVal('prodFechaFab');
        const fechaCad = this._getVal('prodFechaCad');
 
        // Código
        if (!codigo) {
            CatalogTable.showNotification('El código del producto es obligatorio', 'error');
            this._focusTab('tabProdDatos', 'prodCodigo'); return;
        }
        if (!/^[A-Z0-9\-]+$/.test(codigo)) {
            CatalogTable.showNotification('El código solo puede contener letras, números y guiones', 'error');
            this._focusTab('tabProdDatos', 'prodCodigo'); return;
        }
 
        // Nombre
        if (!nombre) {
            CatalogTable.showNotification('El nombre del producto es obligatorio', 'error');
            this._focusTab('tabProdDatos', 'prodNombre'); return;
        }
 
        // Tipo
        if (!tipo) {
            CatalogTable.showNotification('Selecciona el tipo de producto', 'error');
            this._focusTab('tabProdDatos', 'prodTipo'); return;
        }
 
        // Precio
        if (precio === '' || precio === null) {
            CatalogTable.showNotification('El precio de compra es obligatorio', 'error');
            this._focusTab('tabProdDatos', 'prodPrecio'); return;
        }
        if (isNaN(parseFloat(precio)) || parseFloat(precio) < 0) {
            CatalogTable.showNotification('El precio debe ser un número positivo', 'error');
            this._focusTab('tabProdDatos', 'prodPrecio'); return;
        }
 
        // Stock mínimo
        if (stockMin && (isNaN(parseInt(stockMin)) || parseInt(stockMin) < 0)) {
            CatalogTable.showNotification('El stock mínimo no puede ser negativo', 'error');
            this._focusTab('tabProdInventario', 'prodStockMin'); return;
        }
 
        // Fechas de inventario
        if (fechaFab && fechaCad && fechaFab >= fechaCad) {
            CatalogTable.showNotification('La fecha de fabricación debe ser anterior a la de caducidad', 'error');
            this._focusTab('tabProdInventario', 'prodFechaFab'); return;
        }
        if (fechaCad && fechaCad < new Date().toISOString().split('T')[0]) {
            CatalogTable.showNotification('La fecha de caducidad no puede ser una fecha pasada', 'error');
            this._focusTab('tabProdInventario', 'prodFechaCad'); return;
        }
 
        // ── Armar FormData ────────────────────────────────────────────────
        const formData = new FormData();
        formData.append('modulo', 'productos');
        formData.append('accion', this._modoEdicion ? 'update' : 'create');
 
        if (this._modoEdicion) {
            formData.append('id_producto',     this._idActual);
            formData.append(this._modoEdicion ? 'id_producto' : '', this._idActual ?? '');
        }
 
        // Datos del producto
        formData.append('codigo_producto',    codigo);
        formData.append('nombre_producto',    nombre);
        formData.append('id_tipo_producto',   tipo);
        formData.append('precio_compra',      precio);
        formData.append('marca',              this._getVal('prodMarca').toUpperCase());
        formData.append('registro_sanitario', this._getVal('prodRegistro').toUpperCase());
        formData.append('descripcion',        this._getVal('prodDescripcion'));
 
        // Inventario
        formData.append('stock', '0');
        formData.append('stock_minimo',       stockMin || '0');
        formData.append('lote',               this._getVal('prodLote').toUpperCase());
        formData.append('fecha_fabricacion',  fechaFab);
        formData.append('fecha_caducidad',    fechaCad);
 
        // ── Enviar ────────────────────────────────────────────────────────
        CatalogTable.showLoading(true);
        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            CatalogTable.showLoading(false);
 
            if (data.success) {
                CatalogTable.showNotification(
                    this._modoEdicion
                        ? 'Producto actualizado correctamente'
                        : 'Producto creado correctamente',
                    'success'
                );
                cerrarModal('modalProducto');
                setTimeout(() => window.location.reload(), 800);
            } else {
                CatalogTable.showNotification(data.message || 'Error al guardar', 'error');
            }
        } catch (err) {
            CatalogTable.showLoading(false);
            console.error('productoController.guardar:', err);
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // ELIMINAR
    // ─────────────────────────────────────────────────────────────────────
 
    async eliminar(id, nombre) {
        if (!confirm(`¿Eliminar el producto "${nombre}"?\n\nEsta acción eliminará también su registro de inventario y no se puede deshacer.`)) return;
 
        const formData = new FormData();
        formData.append('modulo',      'productos');
        formData.append('accion',      'delete');
        formData.append('id_producto', id);
 
        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
 
            if (data.success) {
                CatalogTable.showNotification('Producto eliminado correctamente', 'success');
                setTimeout(() => window.location.reload(), 800);
            } else {
                CatalogTable.showNotification(data.message || 'Error al eliminar', 'error');
            }
        } catch (err) {
            CatalogTable.showNotification('Error de conexión', 'error');
        }
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
        cambiarTab('modalProducto', tabId);
        setTimeout(() => document.getElementById(fieldId)?.focus(), 100);
    },
 
    _limpiarFormulario() {
        [
            'prodId', 'prodCodigo', 'prodNombre', 'prodMarca',
            'prodRegistro', 'prodPrecio', 'prodDescripcion',
            'prodStockMin', 'prodLote',
            'prodFechaFab', 'prodFechaCad',
        ].forEach(id => this._setVal(id, ''));
 
        // Resetear select sin borrar opciones
        const sel = document.getElementById('prodTipo');
        if (sel) sel.value = '';
 
        document.getElementById('modalProductoNombre').textContent = '';
    },
};
 
// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS
// ─────────────────────────────────────────────────────────────────────────────
 
document.addEventListener('DOMContentLoaded', () => {
 
    document.getElementById('btnGuardarProducto')
        ?.addEventListener('click', () => productoController.guardar());
 
    // Código a mayúsculas en tiempo real
    document.getElementById('prodCodigo')
        ?.addEventListener('input', e => {
            e.target.value = e.target.value.toUpperCase();
        });
 
    // Nombre a mayúsculas en tiempo real
    document.getElementById('prodNombre')
        ?.addEventListener('input', e => {
            e.target.value = e.target.value.toUpperCase();
        });
 
    // Marca a mayúsculas en tiempo real
    document.getElementById('prodMarca')
        ?.addEventListener('input', e => {
            e.target.value = e.target.value.toUpperCase();
        });
 
    // Búsqueda en tabla
    CatalogTable.init();
});