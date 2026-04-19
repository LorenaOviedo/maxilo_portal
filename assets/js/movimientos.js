/**
 * Submódulo de movimientos de inventario.
 * Controlador del módulo de movimientos de inventario.
 * Depende de: CatalogTable, API_URL, CATALOGOS_MOV, SESSION_USUARIO
 */

const movimientoController = {
 
    _stockActual: 0,
    _productos:   [],   // cache de productos con inventario
 
    // ─────────────────────────────────────────────────────────────────────
    // ABRIR MODAL
    // ─────────────────────────────────────────────────────────────────────
 
    async abrir() {
        this._limpiar();
        this._poblarTipos();
 
        // Cargar productos con inventario si aún no están en cache
        await this._cargarProductos();
 
        abrirModal('modalMovimiento');
 
        // Fecha por defecto = hoy
        const hoy = new Date().toISOString().split('T')[0];
        this._setVal('movFecha', hoy);
 
        // Inicializar buscador de producto
        this._bindProductoSearch();
 
        setTimeout(() => document.getElementById('movProdInput')?.focus(), 150);
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // CARGAR PRODUCTOS CON INVENTARIO
    // ─────────────────────────────────────────────────────────────────────
 
    async _cargarProductos() {
        if (this._productos.length > 0) return; // ya en cache
        try {
            const r    = await fetch(`${API_URL}?modulo=movimientos&accion=get_productos_inventario`);
            const data = await r.json();
            if (data.success) {
                this._productos = data.productos ?? [];
                console.log(`✔ Productos con inventario: ${this._productos.length}`);
            }
        } catch (err) {
            console.error('movimientoController._cargarProductos:', err);
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // BUSCADOR DE PRODUCTO (autocompletado igual que pacientes en citas)
    // ─────────────────────────────────────────────────────────────────────
 
    _bindProductoSearch() {
        const input    = document.getElementById('movProdInput');
        const hidden   = document.getElementById('movProdValue');
        const dropdown = document.getElementById('movProdDropdown');
        if (!input) return;
 
        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            hidden.value = '';
            this._ocultarLote();
            this._ocultarResultado();
 
            if (q.length < 1) { this._cerrarDropdown(); return; }
 
            const resultados = this._productos.filter(p =>
                p.nombre_producto.toLowerCase().includes(q) ||
                p.codigo_producto.toLowerCase().includes(q)
            ).slice(0, 10);
 
            this._renderDropdown(resultados, input, hidden, dropdown);
        });
 
        input.addEventListener('blur', () =>
            setTimeout(() => this._cerrarDropdown(), 200)
        );
        input.addEventListener('focus', () => {
            if (input.value.trim().length > 0 && !hidden.value) {
                input.dispatchEvent(new Event('input'));
            }
        });
    },
 
    _renderDropdown(resultados, input, hidden, dropdown) {
        if (!resultados.length) {
            dropdown.innerHTML =
                '<div class="pac-drop-item pac-drop-empty">Sin resultados</div>';
            dropdown.style.display = 'block';
            return;
        }
 
        dropdown.innerHTML = resultados.map(p => `
            <div class="pac-drop-item"
                data-id="${p.id_producto}"
                data-nombre="${escHtml(p.nombre_producto)}"
                data-codigo="${escHtml(p.codigo_producto)}">
                <div style="font-weight:600;">${escHtml(p.nombre_producto)}</div>
                <div style="font-size:11px;color:#6c757d;">
                    [${escHtml(p.codigo_producto)}]
                    ${p.marca ? ' · ' + escHtml(p.marca) : ''}
                </div>
            </div>`).join('');
 
        dropdown.querySelectorAll('.pac-drop-item[data-id]').forEach(item => {
            item.addEventListener('mousedown', async () => {
                hidden.value  = item.dataset.id;
                input.value   = item.dataset.nombre;
                this._cerrarDropdown();
                await this._cargarLotes(
                    parseInt(item.dataset.id),
                    item.dataset.nombre,
                    item.dataset.codigo
                );
            });
        });
 
        dropdown.style.display = 'block';
    },
 
    _cerrarDropdown() {
        const d = document.getElementById('movProdDropdown');
        if (d) d.style.display = 'none';
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // CARGAR LOTES DEL PRODUCTO SELECCIONADO
    // ─────────────────────────────────────────────────────────────────────
 
    async _cargarLotes(idProducto, nombre, codigo) {
        try {
            const r    = await fetch(
                `${API_URL}?modulo=movimientos&accion=get_lotes&id_producto=${idProducto}`
            );
            const data = await r.json();
 
            if (!data.success || !data.lotes?.length) {
                CatalogTable.showNotification(
                    'Este producto no tiene lotes registrados en inventario', 'warning'
                );
                return;
            }
 
            // Poblar select de lotes
            const sel = document.getElementById('movLoteSelect');
            sel.innerHTML = '<option value="">Seleccionar lote...</option>';
            data.lotes.forEach(l => {
                const opt = document.createElement('option');
                opt.value = JSON.stringify({
                    id_inventario: l.id_inventario,
                    lote:          l.lote,
                    stock:         l.stock,
                    stock_minimo:  l.stock_minimo,
                    fecha_cad:     l.fecha_caducidad,
                    nombre,
                    codigo,
                });
                const cad = l.fecha_caducidad
                    ? ` — Cad: ${this._formatFecha(l.fecha_caducidad)}` : '';
                opt.textContent = `${l.lote} (Stock: ${l.stock}${cad})`;
                sel.appendChild(opt);
            });
 
            // Mostrar select de lote y botón
            document.getElementById('movGrupoLote').style.display = '';
            document.getElementById('movGrupoBtn').style.display  = '';
 
        } catch (err) {
            console.error('movimientoController._cargarLotes:', err);
            CatalogTable.showNotification('Error al cargar lotes', 'error');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // SELECCIONAR — confirmar producto + lote y mostrar tarjeta + formulario
    // ─────────────────────────────────────────────────────────────────────
 
    seleccionar() {
        const sel = document.getElementById('movLoteSelect');
        if (!sel?.value) {
            CatalogTable.showNotification('Selecciona un lote', 'warning');
            sel?.focus();
            return;
        }
 
        const datos = JSON.parse(sel.value);
        this._stockActual = parseInt(datos.stock ?? 0);
        this._setVal('movIdInventario', datos.id_inventario);
 
        // Mostrar tarjeta de confirmación
        document.getElementById('movResNombre').textContent = datos.nombre;
        document.getElementById('movResCodigo').textContent =
            `[${datos.codigo}]`;
        document.getElementById('movResLote').textContent =
            `Lote: ${datos.lote}` +
            (datos.fecha_cad ? ` · Cad: ${this._formatFecha(datos.fecha_cad)}` : '');
        document.getElementById('movResStock').textContent = this._stockActual;
 
        // Ocultar controles de selección, mostrar resultado y formulario
        document.getElementById('movProdInput').disabled  = true;
        document.getElementById('movGrupoLote').style.display = 'none';
        document.getElementById('movGrupoBtn').style.display  = 'none';
        document.getElementById('movResultado').style.display = 'block';
        document.getElementById('movFormDatos').style.display = 'block';
        document.getElementById('btnGuardarMovimiento').style.display = '';
 
        this._actualizarHint();
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // RESETEAR SELECCIÓN (volver al paso 1)
    // ─────────────────────────────────────────────────────────────────────
 
    resetSeleccion() {
        this._setVal('movIdInventario', '');
        this._setVal('movProdValue', '');
        this._setVal('movTipo', '');
        this._setVal('movCantidad', '');
        this._stockActual = 0;
 
        const input = document.getElementById('movProdInput');
        if (input) { input.value = ''; input.disabled = false; }
 
        this._ocultarLote();
        this._ocultarResultado();
 
        document.getElementById('movFormDatos').style.display         = 'none';
        document.getElementById('movPreview').style.display           = 'none';
        document.getElementById('btnGuardarMovimiento').style.display = 'none';
 
        setTimeout(() => input?.focus(), 100);
    },
 
    _ocultarLote() {
        const grupoLote = document.getElementById('movGrupoLote');
        const grupoBtn  = document.getElementById('movGrupoBtn');
        const sel       = document.getElementById('movLoteSelect');
        if (grupoLote) grupoLote.style.display = 'none';
        if (grupoBtn)  grupoBtn.style.display  = 'none';
        if (sel) sel.innerHTML = '<option value="">Seleccionar lote...</option>';
    },
 
    _ocultarResultado() {
        const el = document.getElementById('movResultado');
        if (el) el.style.display = 'none';
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
            preview.style.display = 'none'; return;
        }
 
        let resultado, color;
        if (tipo === 1) {
            resultado = this._stockActual + cantidad;
            color     = '#2e7d32';
        } else if (tipo === 2) {
            resultado = this._stockActual - cantidad;
            color     = resultado < 0 ? '#c62828' : '#e65100';
        } else if (tipo === 3) {
            resultado = cantidad;
            color     = '#1565c0';
        } else {
            preview.style.display = 'none'; return;
        }
 
        previewStock.textContent = tipo === 2 && resultado < 0
            ? `${resultado} ⚠ Stock insuficiente`
            : resultado;
        previewStock.style.color = color;
        preview.style.display    = 'block';
    },
 
    _actualizarHint() {
        const tipo  = parseInt(this._getVal('movTipo'));
        const hint  = document.getElementById('movCantidadHint');
        const label = document.getElementById('movCantidadLabel');
        if (!hint || !label) return;
 
        const textos = {
            1: { label: 'Cantidad a ingresar',  hint: 'Unidades que entran al almacén' },
            2: { label: 'Cantidad a retirar',    hint: `Máximo disponible: ${this._stockActual}` },
            3: { label: 'Nuevo stock (ajuste)',  hint: 'El stock quedará exactamente en este valor' },
        };
        const cfg = textos[tipo] ?? { label: 'Cantidad', hint: '' };
        label.innerHTML   = `${cfg.label} <span class="required">*</span>`;
        hint.textContent  = cfg.hint;
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
 
        if (!idInventario) {
            CatalogTable.showNotification('Selecciona primero un producto y lote', 'warning');
            return;
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
                    `Movimiento registrado. Stock: ${data.stock_previo} → ${data.stock_nuevo}`,
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
 
    _formatFecha(str) {
        if (!str) return '';
        const [y, m, d] = str.split('-');
        return `${d}/${m}/${y}`;
    },
 
    _limpiar() {
        ['movIdInventario', 'movProdValue', 'movTipo', 'movCantidad', 'movFecha']
            .forEach(id => this._setVal(id, ''));
 
        const input = document.getElementById('movProdInput');
        if (input) { input.value = ''; input.disabled = false; }
 
        this._ocultarLote?.();
        this._ocultarResultado?.();
 
        const formDatos  = document.getElementById('movFormDatos');
        const preview    = document.getElementById('movPreview');
        const btnGuardar = document.getElementById('btnGuardarMovimiento');
        const hint       = document.getElementById('movCantidadHint');
 
        if (formDatos)  formDatos.style.display  = 'none';
        if (preview)    preview.style.display     = 'none';
        if (btnGuardar) btnGuardar.style.display  = 'none';
        if (hint)       hint.textContent          = '';
 
        this._stockActual = 0;
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
 
    document.getElementById('btnGuardarMovimiento')
        ?.addEventListener('click', () => movimientoController.guardar());
 
    // Actualizar hint y preview al cambiar tipo
    document.getElementById('movTipo')
        ?.addEventListener('change', () => movimientoController._actualizarHint());
 
    // Actualizar preview al cambiar cantidad
    document.getElementById('movCantidad')
        ?.addEventListener('input', () => movimientoController.actualizarPreview());
 
    CatalogTable.init();
});