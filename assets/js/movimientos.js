/**
 * movimientos.js
 * Controlador del módulo de movimientos de inventario.
 * Depende de: CatalogTable, API_URL, CATALOGOS_MOV, SESSION_USUARIO
 */
 
const movimientoController = {
 
    _stockActual: 0,   // stock del producto encontrado
 
    // ─────────────────────────────────────────────────────────────────────
    // ABRIR MODAL
    // ─────────────────────────────────────────────────────────────────────
 
    abrir() {
        this._limpiar();
        this._poblarTipos();
        abrirModal('modalMovimiento');
 
        // Fecha por defecto = hoy
        const hoy = new Date().toISOString().split('T')[0];
        this._setVal('movFecha', hoy);
 
        // Foco en el código
        setTimeout(() => document.getElementById('movCodigo')?.focus(), 150);
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // BUSCAR PRODUCTO POR CÓDIGO + LOTE
    // ─────────────────────────────────────────────────────────────────────
 
    async buscarProducto() {
        const codigo = this._getVal('movCodigo').toUpperCase();
        const lote   = this._getVal('movLote');
 
        if (!codigo) {
            CatalogTable.showNotification('Ingresa el código del producto', 'warning');
            document.getElementById('movCodigo')?.focus();
            return;
        }
 
        // Ocultar resultados anteriores
        this._ocultarResultado();
 
        try {
            const params = new URLSearchParams({
                modulo: 'movimientos',
                accion: 'buscar_inventario',
                codigo,
                lote,
            });
            const r    = await fetch(`${API_URL}?${params}`);
            const data = await r.json();
 
            if (!data.success || !data.inventario) {
                document.getElementById('movNoEncontrado').style.display = 'block';
                document.getElementById('movFormDatos').style.display    = 'none';
                document.getElementById('btnGuardarMovimiento').style.display = 'none';
                return;
            }
 
            this._mostrarResultado(data.inventario);
 
        } catch (err) {
            console.error('movimientoController.buscarProducto:', err);
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },
 
    _mostrarResultado(inv) {
        this._stockActual = parseInt(inv.stock ?? 0);
        this._setVal('movIdInventario', inv.id_inventario);
 
        // Tarjeta de resultado
        document.getElementById('movResNombre').textContent =
            inv.nombre_producto + (inv.marca ? ` — ${inv.marca}` : '');
        document.getElementById('movResCodigo').textContent =
            `[${inv.codigo_producto}] ${inv.nombre_tipo_producto ?? ''}`;
        document.getElementById('movResLote').textContent =
            inv.lote ? `Lote: ${inv.lote}` : 'Sin lote registrado';
        document.getElementById('movResStock').textContent = this._stockActual;
 
        document.getElementById('movResultado').style.display    = 'block';
        document.getElementById('movNoEncontrado').style.display = 'none';
        document.getElementById('movFormDatos').style.display    = 'block';
        document.getElementById('btnGuardarMovimiento').style.display = '';
 
        // Actualizar hint de cantidad según tipo seleccionado
        this._actualizarHint();
    },
 
    _ocultarResultado() {
        document.getElementById('movResultado').style.display    = 'none';
        document.getElementById('movNoEncontrado').style.display = 'none';
        document.getElementById('movFormDatos').style.display    = 'none';
        document.getElementById('movPreview').style.display      = 'none';
        document.getElementById('btnGuardarMovimiento').style.display = 'none';
        this._setVal('movIdInventario', '');
        this._stockActual = 0;
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // PREVIEW DEL STOCK RESULTANTE
    // ─────────────────────────────────────────────────────────────────────
 
    actualizarPreview() {
        const tipo     = parseInt(this._getVal('movTipo'));
        const cantidad = parseInt(this._getVal('movCantidad')) || 0;
        const preview  = document.getElementById('movPreview');
        const previewStock = document.getElementById('movPreviewStock');
 
        if (!tipo || cantidad <= 0 || !this._getVal('movIdInventario')) {
            preview.style.display = 'none';
            return;
        }
 
        let resultado;
        let color = '#212529';
 
        if (tipo === 1) {          // Entrada
            resultado = this._stockActual + cantidad;
            color     = '#2e7d32';
        } else if (tipo === 2) {   // Salida
            resultado = this._stockActual - cantidad;
            color     = resultado < 0 ? '#c62828' : '#e65100';
        } else if (tipo === 3) {   // Ajuste
            resultado = cantidad;
            color     = '#1565c0';
        } else {
            preview.style.display = 'none';
            return;
        }
 
        previewStock.textContent = resultado;
        previewStock.style.color = color;
        preview.style.display    = 'block';
 
        if (tipo === 2 && resultado < 0) {
            previewStock.textContent = `${resultado} ⚠ Stock insuficiente`;
        }
    },
 
    _actualizarHint() {
        const tipo  = parseInt(this._getVal('movTipo'));
        const hint  = document.getElementById('movCantidadHint');
        const label = document.getElementById('movCantidadLabel');
        if (!hint || !label) return;
 
        const textos = {
            1: { label: 'Cantidad a ingresar',   hint: 'Unidades que entran al almacén' },
            2: { label: 'Cantidad a retirar',     hint: `Máximo disponible: ${this._stockActual}` },
            3: { label: 'Nuevo stock (ajuste)',   hint: 'El stock quedará exactamente en este valor' },
        };
        const cfg = textos[tipo] ?? { label: 'Cantidad', hint: '' };
        label.innerHTML = `${cfg.label} <span class="required">*</span>`;
        hint.textContent = cfg.hint;
 
        this.actualizarPreview();
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // GUARDAR
    // ─────────────────────────────────────────────────────────────────────
 
    async guardar() {
        const idInventario = this._getVal('movIdInventario');
        const tipo         = this._getVal('movTipo');
        const cantidad     = this._getVal('movCantidad');
        const fecha        = this._getVal('movFecha');
 
        // Validaciones frontend
        if (!idInventario) {
            CatalogTable.showNotification('Busca primero el producto', 'warning'); return;
        }
        if (!tipo) {
            CatalogTable.showNotification('Selecciona el tipo de movimiento', 'error');
            document.getElementById('movTipo')?.focus(); return;
        }
        if (!cantidad || parseInt(cantidad) <= 0) {
            CatalogTable.showNotification('La cantidad debe ser mayor a 0', 'error');
            document.getElementById('movCantidad')?.focus(); return;
        }
        if (!fecha) {
            CatalogTable.showNotification('La fecha es obligatoria', 'error');
            document.getElementById('movFecha')?.focus(); return;
        }
        if (fecha > new Date().toISOString().split('T')[0]) {
            CatalogTable.showNotification('La fecha no puede ser futura', 'error');
            document.getElementById('movFecha')?.focus(); return;
        }
 
        // Advertencia preventiva de stock negativo
        if (parseInt(tipo) === 2 && parseInt(cantidad) > this._stockActual) {
            CatalogTable.showNotification(
                `Stock insuficiente. Disponible: ${this._stockActual}`, 'error'
            );
            return;
        }
 
        const formData = new FormData();
        formData.append('modulo',             'movimientos');
        formData.append('accion',             'registrar_movimiento');
        formData.append('id_inventario',      idInventario);
        formData.append('id_tipo_movimiento', tipo);
        formData.append('cantidad',           cantidad);
        formData.append('fecha_movimiento',   fecha);
        formData.append('id_usuario',         window.SESSION_USUARIO ?? 0);
 
        CatalogTable.showLoading(true);
        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            CatalogTable.showLoading(false);
 
            if (data.success) {
                CatalogTable.showNotification(
                    `Movimiento registrado. Stock anterior: ${data.stock_previo} → Nuevo: ${data.stock_nuevo}`,
                    'success'
                );
                cerrarModal('modalMovimiento');
                setTimeout(() => window.location.reload(), 900);
            } else {
                CatalogTable.showNotification(data.message || 'Error al registrar', 'error');
            }
        } catch (err) {
            CatalogTable.showLoading(false);
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────
 
    _poblarTipos() {
        const sel = document.getElementById('movTipo');
        if (!sel || !window.CATALOGOS_MOV) return;
        sel.innerHTML = '<option value="">Seleccionar...</option>';
        (CATALOGOS_MOV.tiposMovimiento ?? []).forEach(t => {
            const opt       = document.createElement('option');
            opt.value       = t.id_tipo_movimiento;
            opt.textContent = t.tipo_movimiento;
            sel.appendChild(opt);
        });
    },
 
    _setVal(id, val) {
        const el = document.getElementById(id);
        if (el) el.value = val ?? '';
    },
 
    _getVal(id) {
        return document.getElementById(id)?.value?.trim() ?? '';
    },
 
    _limpiar() {
        ['movIdInventario', 'movCodigo', 'movLote', 'movTipo',
         'movCantidad', 'movFecha'].forEach(id => this._setVal(id, ''));
        this._ocultarResultado();
        this._stockActual = 0;
        if (document.getElementById('movCantidadHint'))
            document.getElementById('movCantidadHint').textContent = '';
    },
};
 
// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS
// ─────────────────────────────────────────────────────────────────────────────
 
document.addEventListener('DOMContentLoaded', () => {
 
    document.getElementById('btnGuardarMovimiento')
        ?.addEventListener('click', () => movimientoController.guardar());
 
    // Buscar con Enter en el campo código
    document.getElementById('movCodigo')
        ?.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                movimientoController.buscarProducto();
            }
        });
 
    // Buscar con Enter en el campo lote
    document.getElementById('movLote')
        ?.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                movimientoController.buscarProducto();
            }
        });
 
    // Código a mayúsculas
    document.getElementById('movCodigo')
        ?.addEventListener('input', e => {
            e.target.value = e.target.value.toUpperCase();
        });
 
    // Actualizar hint y preview al cambiar tipo
    document.getElementById('movTipo')
        ?.addEventListener('change', () => movimientoController._actualizarHint());
 
    // Actualizar preview al cambiar cantidad
    document.getElementById('movCantidad')
        ?.addEventListener('input', () => movimientoController.actualizarPreview());
 
    CatalogTable.init();
});