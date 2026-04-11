/**
 * pagos.js
 * Controlador del módulo de pagos.
 * Depende de: CatalogTable, API_URL, CATALOGOS_PAGO
 */
 
const pagoController = {
 
    // ─────────────────────────────────────────────────────────────────────
    // ABRIR MODAL REGISTRO
    // ─────────────────────────────────────────────────────────────────────
 
    abrir() {
        this._limpiar();
        this._poblarSelects();
        abrirModal('modalPago');
 
        // Fecha por defecto = hoy
        this._setVal('pagoFecha', new Date().toISOString().split('T')[0]);
        setTimeout(() => document.getElementById('pagoCita')?.focus(), 150);
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // VER DETALLE
    // ─────────────────────────────────────────────────────────────────────
 
    async ver(id) {
        this._resetDetalle();
        abrirModal('modalDetallePago');
 
        try {
            const r    = await fetch(`${API_URL}?modulo=pagos&accion=get&id=${id}`);
            const data = await r.json();
 
            if (!data.success) {
                CatalogTable.showNotification('Error al cargar el pago', 'error');
                cerrarModal('modalDetallePago');
                return;
            }
 
            const p = data.pago;
            document.getElementById('detPagoRecibo').textContent = p.numero_recibo ?? '';
 
            // Cita
            this._setText('detPagoPaciente',    p.nombre_paciente    ?? '—');
            this._setText('detPagoEspecialista', p.nombre_especialista ?? '—');
            this._setText('detPagoFechaCita',
                p.fecha_cita ? this._fmtFecha(p.fecha_cita) + ' · ' + (p.hora_inicio ?? '').substring(0, 5) : '—');
            this._setText('detPagoMotivo',      p.motivo_consulta    ?? '—');
 
            // Pago
            this._setText('detPagoNumRecibo',   p.numero_recibo      ?? '—');
            this._setText('detPagoFecha',       p.fecha_pago ? this._fmtFecha(p.fecha_pago) : '—');
            this._setText('detPagoMetodo',      p.metodo_pago        ?? '—');
            this._setText('detPagoReferencia',  p.referencia_pago    ?? '—');
            this._setText('detPagoTotal',       p.monto_total ? '$' + this._fmtNum(p.monto_total) : '—');
            this._setText('detPagoObservaciones', p.observaciones   ?? '—');
 
            // Monto neto con descuento
            const neto  = parseFloat(p.monto_neto  ?? 0);
            const total = parseFloat(p.monto_total ?? 0);
            let netoTxt = '$' + this._fmtNum(neto);
            if (neto < total) {
                netoTxt += ` (desc. $${this._fmtNum(total - neto)})`;
            }
            this._setText('detPagoNeto', netoTxt);
 
            // Estatus con badge
            const badgeCls = p.estatus === 'Pagado' ? 'badge-success' : 'badge-warning';
            document.getElementById('detPagoEstatus').innerHTML =
                `<span class="badge ${badgeCls}">${p.estatus ?? '—'}</span>`;
 
        } catch (err) {
            console.error('pagoController.ver:', err);
            CatalogTable.showNotification('Error de conexión', 'error');
            cerrarModal('modalDetallePago');
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // GUARDAR
    // ─────────────────────────────────────────────────────────────────────
 
    async guardar() {
        const idCita      = this._getVal('pagoCita');
        const fecha       = this._getVal('pagoFecha');
        const idMetodo    = this._getVal('pagoMetodo');
        const referencia  = this._getVal('pagoReferencia');
        const montoTotal  = this._getVal('pagoMontoTotal');
        const montoNeto   = this._getVal('pagoMontoNeto');
 
        // ── Validaciones ──────────────────────────────────────────────────
        if (!idCita) {
            CatalogTable.showNotification('Selecciona una cita', 'error');
            document.getElementById('pagoCita')?.focus(); return;
        }
        if (!fecha) {
            CatalogTable.showNotification('La fecha de pago es obligatoria', 'error');
            document.getElementById('pagoFecha')?.focus(); return;
        }
        if (fecha > new Date().toISOString().split('T')[0]) {
            CatalogTable.showNotification('La fecha de pago no puede ser futura', 'error');
            document.getElementById('pagoFecha')?.focus(); return;
        }
        if (!idMetodo) {
            CatalogTable.showNotification('Selecciona el método de pago', 'error');
            document.getElementById('pagoMetodo')?.focus(); return;
        }
 
        // Referencia requerida según método
        const metodo = (CATALOGOS_PAGO.metodosPago ?? [])
            .find(m => m.id_metodo_pago == idMetodo);
        if (metodo?.requiere_referencia == 1 && !referencia) {
            CatalogTable.showNotification(
                'Este método de pago requiere un número de referencia', 'error'
            );
            document.getElementById('pagoReferencia')?.focus(); return;
        }
 
        if (!montoTotal || parseFloat(montoTotal) <= 0) {
            CatalogTable.showNotification('El monto total debe ser mayor a 0', 'error');
            document.getElementById('pagoMontoTotal')?.focus(); return;
        }
        if (!montoNeto || parseFloat(montoNeto) <= 0) {
            CatalogTable.showNotification('El monto neto debe ser mayor a 0', 'error');
            document.getElementById('pagoMontoNeto')?.focus(); return;
        }
        if (parseFloat(montoNeto) > parseFloat(montoTotal)) {
            CatalogTable.showNotification(
                'El monto neto no puede ser mayor al monto total', 'error'
            );
            document.getElementById('pagoMontoNeto')?.focus(); return;
        }
 
        // ── Enviar ────────────────────────────────────────────────────────
        const formData = new FormData();
        formData.append('modulo',          'pagos');
        formData.append('accion',          'crear_pago');
        formData.append('id_cita',         idCita);
        formData.append('fecha_pago',      fecha);
        formData.append('id_metodo_pago',  idMetodo);
        formData.append('referencia_pago', referencia);
        formData.append('monto_total',     montoTotal);
        formData.append('monto_neto',      montoNeto);
        formData.append('observaciones',   this._getVal('pagoObservaciones'));
 
        CatalogTable.showLoading(true);
        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            CatalogTable.showLoading(false);
 
            if (data.success) {
                CatalogTable.showNotification(
                    `Pago registrado. Recibo: ${data.numero_recibo}`, 'success'
                );
                cerrarModal('modalPago');
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
    // POBLAR SELECTS
    // ─────────────────────────────────────────────────────────────────────
 
    _poblarSelects() {
        if (!window.CATALOGOS_PAGO) return;
 
        // Métodos de pago
        const selMetodo = document.getElementById('pagoMetodo');
        if (selMetodo) {
            selMetodo.innerHTML = '<option value="">Seleccionar método...</option>';
            (CATALOGOS_PAGO.metodosPago ?? []).forEach(m => {
                const opt       = document.createElement('option');
                opt.value       = m.id_metodo_pago;
                opt.textContent = m.metodo_pago;
                opt.dataset.requiereRef = m.requiere_referencia;
                selMetodo.appendChild(opt);
            });
        }
 
        // Citas atendidas sin pago
        const selCita = document.getElementById('pagoCita');
        if (selCita) {
            selCita.innerHTML = '<option value="">Seleccionar cita...</option>';
            (CATALOGOS_PAGO.citasAtendidas ?? []).forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id_cita;
                opt.dataset.paciente    = c.nombre_paciente    ?? '';
                opt.dataset.especialista= c.nombre_especialista ?? '';
                opt.dataset.motivo      = c.motivo_consulta    ?? '';
                opt.dataset.costo       = c.costo_total        ?? '0';
                opt.dataset.fecha       = c.fecha_cita         ?? '';
                opt.dataset.hora        = (c.hora_inicio ?? '').substring(0, 5);
                opt.textContent =
                    `${this._fmtFecha(c.fecha_cita)} ${(c.hora_inicio ?? '').substring(0,5)}` +
                    ` — ${c.nombre_paciente}`;
                selCita.appendChild(opt);
            });
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // EVENTOS INTERNOS
    // ─────────────────────────────────────────────────────────────────────
 
    _onCitaChange() {
        const sel = document.getElementById('pagoCita');
        const opt = sel?.options[sel.selectedIndex];
        const info = document.getElementById('pagoCitaInfo');
 
        if (!opt?.value) {
            if (info) info.style.display = 'none';
            this._setVal('pagoMontoTotal', '');
            this._setVal('pagoMontoNeto',  '');
            return;
        }
 
        // Mostrar tarjeta de resumen
        document.getElementById('pagoCitaPaciente').textContent =
            opt.dataset.paciente ?? '—';
        document.getElementById('pagoCitaEspecialista').textContent =
            opt.dataset.especialista ?? '—';
        document.getElementById('pagoCitaMotivo').textContent =
            opt.dataset.motivo ?? '—';
 
        const costo = parseFloat(opt.dataset.costo ?? 0);
        document.getElementById('pagoCitaCosto').textContent =
            costo > 0 ? '$' + this._fmtNum(costo) : 'Sin costo registrado';
 
        if (info) info.style.display = 'block';
 
        // Pre-llenar montos con el costo de la cita
        if (costo > 0) {
            this._setVal('pagoMontoTotal', costo.toFixed(2));
            this._setVal('pagoMontoNeto',  costo.toFixed(2));
        }
    },
 
    _onMetodoChange() {
        const sel = document.getElementById('pagoMetodo');
        const opt = sel?.options[sel.selectedIndex];
        const grp = document.getElementById('pagoGrupoRef');
        if (!grp) return;
 
        const requiere = opt?.dataset.requiereRef == '1';
        grp.style.display = requiere ? '' : 'none';
        if (!requiere) this._setVal('pagoReferencia', '');
    },
 
    _onMontoChange() {
        const total = parseFloat(this._getVal('pagoMontoTotal') || 0);
        const neto  = parseFloat(this._getVal('pagoMontoNeto')  || 0);
        const grp   = document.getElementById('pagoDescuento');
        const monto = document.getElementById('pagoDescuentoMonto');
 
        if (grp && monto) {
            const descuento = total - neto;
            if (descuento > 0.001 && neto > 0) {
                monto.textContent   = '$' + this._fmtNum(descuento);
                grp.style.display   = 'block';
            } else {
                grp.style.display   = 'none';
            }
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
 
    _setText(id, txt) {
        const el = document.getElementById(id);
        if (el) el.textContent = txt ?? '—';
    },
 
    _fmtFecha(str) {
        if (!str) return '—';
        const [y, m, d] = str.split('-');
        return `${d}/${m}/${y}`;
    },
 
    _fmtNum(n) {
        return parseFloat(n).toLocaleString('es-MX', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    },
 
    _limpiar() {
        ['pagoCita', 'pagoFecha', 'pagoMetodo', 'pagoReferencia',
         'pagoMontoTotal', 'pagoMontoNeto', 'pagoObservaciones']
            .forEach(id => this._setVal(id, ''));
 
        const info = document.getElementById('pagoCitaInfo');
        const grpRef = document.getElementById('pagoGrupoRef');
        const grpDesc = document.getElementById('pagoDescuento');
        if (info)    info.style.display    = 'none';
        if (grpRef)  grpRef.style.display  = 'none';
        if (grpDesc) grpDesc.style.display = 'none';
    },
 
    _resetDetalle() {
        ['detPagoRecibo', 'detPagoPaciente', 'detPagoEspecialista',
         'detPagoFechaCita', 'detPagoMotivo', 'detPagoNumRecibo',
         'detPagoFecha', 'detPagoMetodo', 'detPagoReferencia',
         'detPagoTotal', 'detPagoNeto', 'detPagoEstatus', 'detPagoObservaciones']
            .forEach(id => this._setText(id, '—'));
    },
};
 
// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS
// ─────────────────────────────────────────────────────────────────────────────
 
document.addEventListener('DOMContentLoaded', () => {
 
    document.getElementById('btnGuardarPago')
        ?.addEventListener('click', () => pagoController.guardar());
 
    document.getElementById('pagoCita')
        ?.addEventListener('change', () => pagoController._onCitaChange());
 
    document.getElementById('pagoMetodo')
        ?.addEventListener('change', () => pagoController._onMetodoChange());
 
    document.getElementById('pagoMontoTotal')
        ?.addEventListener('input', () => pagoController._onMontoChange());
 
    document.getElementById('pagoMontoNeto')
        ?.addEventListener('input', () => pagoController._onMontoChange());
 
    CatalogTable.init();
});
 