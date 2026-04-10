/**
 * inventario.js
 * Controlador del módulo de inventario — solo lectura.
 * Depende de: CatalogTable, API_URL
 */
 
const inventarioController = {
 
    // ─────────────────────────────────────────────────────────────────────
    // VER DETALLE
    // ─────────────────────────────────────────────────────────────────────
 
    async ver(id) {
        // Mostrar modal con spinner mientras carga
        this._setTexto('modalInvNombre', '');
        this._resetCampos();
        abrirModal('modalInventario');
 
        try {
            const r    = await fetch(`${API_URL}?modulo=inventario&accion=get&id=${id}`);
            const data = await r.json();
 
            if (!data.success) {
                CatalogTable.showNotification('Error al cargar el detalle', 'error');
                cerrarModal('modalInventario');
                return;
            }
 
            const i = data.inventario;
            this._poblarModal(i);
 
        } catch (err) {
            console.error('inventarioController.ver:', err);
            CatalogTable.showNotification('Error de conexión', 'error');
            cerrarModal('modalInventario');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // POBLAR MODAL
    // ─────────────────────────────────────────────────────────────────────
 
    _poblarModal(i) {
        const hoy  = new Date().toISOString().split('T')[0];
        const en30 = new Date(Date.now() + 30 * 864e5).toISOString().split('T')[0];
 
        // Header
        this._setTexto('modalInvNombre', i.nombre_producto ?? '');
 
        // Producto
        this._setTexto('invDetCodigo',    i.codigo_producto      ?? '—');
        this._setTexto('invDetNombre',    i.nombre_producto      ?? '—');
        this._setTexto('invDetTipo',      i.nombre_tipo_producto ?? '—');
        this._setTexto('invDetMarca',     i.marca                ?? '—');
        this._setTexto('invDetPrecio',    i.precio_compra
            ? '$' + parseFloat(i.precio_compra).toLocaleString('es-MX', { minimumFractionDigits: 2 })
            : '—');
        this._setTexto('invDetRegistro',  i.registro_sanitario   ?? '—');
        this._setTexto('invDetDescripcion', i.descripcion        ?? '—');
 
        // Stock con badge de color
        const stock    = parseInt(i.stock    ?? 0);
        const stockMin = parseInt(i.stock_minimo ?? 0);
        let   badgeCls = 'badge-success';
        if (stock === 0)          badgeCls = 'badge-danger';
        else if (stock <= stockMin) badgeCls = 'badge-warning';
 
        document.getElementById('invDetStock').innerHTML =
            `<span class="badge ${badgeCls}">${stock}</span>`;
 
        this._setTexto('invDetStockMin', stockMin);
        this._setTexto('invDetLote',     i.lote ?? '—');
 
        // Fechas formateadas
        this._setTexto('invDetFechaFab',
            i.fecha_fabricacion ? this._formatFecha(i.fecha_fabricacion) : '—');
 
        // Caducidad con alerta visual
        if (!i.fecha_caducidad) {
            this._setTexto('invDetFechaCad', '—');
        } else if (i.fecha_caducidad < hoy) {
            document.getElementById('invDetFechaCad').innerHTML =
                `<span class="badge badge-danger">
                    <i class="ri-error-warning-line"></i>
                    ${this._formatFecha(i.fecha_caducidad)} — CADUCADO
                </span>`;
        } else if (i.fecha_caducidad <= en30) {
            document.getElementById('invDetFechaCad').innerHTML =
                `<span class="badge badge-warning">
                    <i class="ri-alarm-warning-line"></i>
                    ${this._formatFecha(i.fecha_caducidad)} — Por caducar
                </span>`;
        } else {
            this._setTexto('invDetFechaCad', this._formatFecha(i.fecha_caducidad));
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────
 
    _setTexto(id, texto) {
        const el = document.getElementById(id);
        if (el) el.textContent = texto ?? '—';
    },
 
    _formatFecha(str) {
        if (!str) return '—';
        const [y, m, d] = str.split('-');
        return `${d}/${m}/${y}`;
    },
 
    _resetCampos() {
        [
            'invDetCodigo', 'invDetNombre', 'invDetTipo', 'invDetMarca',
            'invDetPrecio', 'invDetRegistro', 'invDetDescripcion',
            'invDetStock', 'invDetStockMin', 'invDetLote',
            'invDetFechaFab', 'invDetFechaCad',
        ].forEach(id => this._setTexto(id, '—'));
    },
};
 
// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS
// ─────────────────────────────────────────────────────────────────────────────
 
document.addEventListener('DOMContentLoaded', () => {
    CatalogTable.init();
});
 