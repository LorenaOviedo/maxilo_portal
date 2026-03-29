/**
 * planes.js
 * Sistema Maxilofacial Texcoco
 *
 * Lógica del tab Planes de Tratamiento dentro del modal de paciente.
 * Depende de: catalogos-tabla.js (CatalogTable), API_URL
 *
 * AGREGAR en footer.php:
 * <script src="<?php echo asset('js/planes.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
 *
 * AGREGAR en modal_ver_paciente.php script (en cambiarTab):
 * if (tabId === 'tabPlanes') {
 *     const num = document.getElementById('formPaciente').dataset.numeroPaciente;
 *     planesController.cargar(num);
 * }
 */

const planesController = {

    _numeroPaciente: null,
    _catalogos: null,
    _procsTemp: [],        // procedimientos del nuevo plan (antes de guardar)
    _readonly: false,

    // ── Inicializar catálogos (llamar una vez al abrir el modal) ──
    async inicializar() {
        if (this._catalogos) return; // ya cargados

        try {
            const r    = await fetch(`${API_URL}?modulo=planes&accion=get_catalogos_planes`);
            const data = await r.json();
            if (!data.success) return;

            this._catalogos = data;
        } catch (err) {
            console.warn('planesController: no se pudieron cargar catálogos', err);
        }
    },

    // ── Cargar planes del paciente ────────────────────────────────
    async cargar(numeroPaciente, readonly = false) {
        this._numeroPaciente = parseInt(numeroPaciente);
        this._readonly       = readonly;

        await this.inicializar();
        this._poblarSelects();
        this._mostrarLoading(true);

        try {
            const r    = await fetch(
                `${API_URL}?modulo=planes&accion=get_by_paciente&numero_paciente=${this._numeroPaciente}`
            );
            const data = await r.json();
            this._mostrarLoading(false);

            if (data.success) {
                this._renderPlanes(data.planes);
            }
        } catch (err) {
            this._mostrarLoading(false);
            console.warn('planesController: error al cargar planes', err);
        }
    },

    // ── Limpiar al cerrar modal ───────────────────────────────────
    limpiar() {
        this._numeroPaciente = null;
        this._procsTemp      = [];
        this._ocultarFormulario();
        document.getElementById('listaPlanesContainer').innerHTML = `
            <div id="planesSinDatos" style="text-align:center; padding:30px; color:#adb5bd;">
                <i class="ri-file-list-3-line" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                <p>Sin planes de tratamiento registrados</p>
            </div>`;
    },

    // ── Renderizar lista de planes ────────────────────────────────
    _renderPlanes(planes) {
        const contenedor = document.getElementById('listaPlanesContainer');
        if (!planes || !planes.length) {
            contenedor.innerHTML = `
                <div id="planesSinDatos" style="text-align:center; padding:30px; color:#adb5bd;">
                    <i class="ri-file-list-3-line" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                    <p>Sin planes de tratamiento registrados</p>
                </div>`;
            return;
        }

        contenedor.innerHTML = planes.map(plan => `
            <div class="plan-card form-card" data-id="${plan.id_plan_tratamiento}">
                <div class="plan-card-header">
                    <div class="plan-card-info">
                        <span class="plan-nombre">Plan #${plan.id_plan_tratamiento}</span>
                        <span class="plan-meta">
                            <strong>Especialista:</strong> ${escHtml(plan.especialista)}
                        </span>
                        <span class="plan-meta">
                            <strong>Creado:</strong> ${formatFecha(plan.fecha_creacion)}
                        </span>
                        <span class="plan-meta">
                            <strong>Estatus:</strong>
                            <span class="badge-estatus ${badgeClase(plan.estatus_tratamiento)}">
                                ${plan.estatus_tratamiento.toUpperCase()}
                            </span>
                        </span>
                        ${plan.notas ? `<span class="plan-descripcion">${escHtml(plan.notas)}</span>` : ''}
                    </div>
                    <div class="plan-card-totales">
                        <span><strong>Total estimado:</strong>
                            $${parseFloat(plan.costo_total).toLocaleString('es-MX', {minimumFractionDigits:2})}
                        </span>
                        ${!this._readonly ? `
                        <select class="select-estatus-plan" data-id="${plan.id_plan_tratamiento}"
                                onchange="planesController.actualizarEstatus(this)">
                            ${this._optionsEstatus(plan.id_estatus_tratamiento)}
                        </select>` : ''}
                    </div>
                </div>

                <!-- Tabla de procedimientos -->
                <table class="plan-table">
                    <thead>
                        <tr>
                            <th>PROCEDIMIENTO</th>
                            <th>PIEZA</th>
                            <th>PRECIO BASE</th>
                            <th>PRECIO ESPECIAL</th>
                            ${!this._readonly ? '<th>ACCIONES</th>' : ''}
                        </tr>
                    </thead>
                    <tbody>
                        ${plan.detalle.length ? plan.detalle.map(d => `
                            <tr>
                                <td>${escHtml(d.nombre_procedimiento)}</td>
                                <td class="text-center">${d.numero_pieza || '—'}</td>
                                <td>$${parseFloat(d.precio_base).toLocaleString('es-MX',{minimumFractionDigits:2})}</td>
                                <td>${d.costo_descuento
                                    ? '$'+parseFloat(d.costo_descuento).toLocaleString('es-MX',{minimumFractionDigits:2})
                                    : '—'}</td>
                                ${!this._readonly ? `
                                <td class="acciones-cell">
                                    <button class="btn-accion eliminar"
                                        onclick="planesController.eliminarProcedimiento(${d.id_detalle_plan}, ${plan.id_plan_tratamiento})">
                                        <i class="ri-delete-bin-6-line"></i>
                                    </button>
                                </td>` : ''}
                            </tr>`).join('') : `
                            <tr>
                                <td colspan="${this._readonly ? 4 : 5}"
                                    style="text-align:center; color:#adb5bd; padding:12px;">
                                    Sin procedimientos
                                </td>
                            </tr>`}
                    </tbody>
                </table>

                ${!this._readonly ? `
                <!-- Agregar procedimiento a plan existente -->
                <div class="proc-add-inline" id="addProc_${plan.id_plan_tratamiento}" style="display:none;">
                    <select class="form-select proc-select" id="procSelExist_${plan.id_plan_tratamiento}">
                        <option value="">Seleccionar procedimiento...</option>
                        ${this._optionsProcedimientos()}
                    </select>
                    <input type="number" class="form-input proc-pieza"
                        id="procPiezaExist_${plan.id_plan_tratamiento}"
                        placeholder="No. pieza" min="11" max="48">
                    <input type="number" class="form-input proc-descuento"
                        id="procDescExist_${plan.id_plan_tratamiento}"
                        placeholder="Precio especial" step="0.01" min="0">
                    <button class="btn-confirmar-proc"
                        onclick="planesController.confirmarProcExistente(${plan.id_plan_tratamiento})">
                        <i class="ri-check-line"></i>
                    </button>
                    <button class="btn-cancelar-proc"
                        onclick="document.getElementById('addProc_${plan.id_plan_tratamiento}').style.display='none'">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
                <button class="btn-agregar-proc"
                    onclick="document.getElementById('addProc_${plan.id_plan_tratamiento}').style.display='flex'"
                    style="margin-top:8px;">
                    <i class="ri-add-line"></i> Agregar procedimiento
                </button>` : ''}
            </div>`).join('');
    },

    // ── Guardar nuevo plan ────────────────────────────────────────
    async guardar() {
        const fecha       = document.getElementById('planFecha').value.trim();
        const especialista= document.getElementById('planEspecialista').value;
        const estatus     = document.getElementById('planEstatus').value;
        const notas       = document.getElementById('planNotas').value.trim();

        // Validaciones
        if (!fecha) {
            CatalogTable.showNotification('La fecha de creación es requerida', 'error');
            return;
        }
        if (!especialista) {
            CatalogTable.showNotification('El especialista es requerido', 'error');
            return;
        }
        if (!notas && this._procsTemp.length === 0) {
            CatalogTable.showNotification('Agrega al menos un procedimiento al plan', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('modulo',                'planes');
        formData.append('accion',                'crear_plan');
        formData.append('numero_paciente',        this._numeroPaciente);
        formData.append('fecha_creacion',         fecha);
        formData.append('id_especialista',        especialista);
        formData.append('id_estatus_tratamiento', estatus || 1);
        formData.append('notas',                  notas);
        formData.append('procedimientos_json',    JSON.stringify(this._procsTemp));

        CatalogTable.showLoading(true);

        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            CatalogTable.showLoading(false);

            if (data.success) {
                CatalogTable.showNotification('Plan creado correctamente', 'success');
                this._ocultarFormulario();
                this.cargar(this._numeroPaciente, this._readonly);
            } else {
                CatalogTable.showNotification(data.message || 'Error al guardar', 'error');
            }
        } catch (err) {
            CatalogTable.showLoading(false);
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },

    // ── Actualizar estatus de plan existente ──────────────────────
    async actualizarEstatus(select) {
        const idPlan  = parseInt(select.dataset.id);
        const estatus = parseInt(select.value);

        const formData = new FormData();
        formData.append('modulo',                'planes');
        formData.append('accion',                'cambiar_estatus_plan');
        formData.append('id_plan_tratamiento',    idPlan);
        formData.append('id_estatus_tratamiento', estatus);

        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            if (data.success) {
                CatalogTable.showNotification('Estatus actualizado', 'success');
                this.cargar(this._numeroPaciente, this._readonly);
            } else {
                CatalogTable.showNotification(data.message || 'Error al actualizar', 'error');
            }
        } catch (err) {
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },

    // ── Eliminar procedimiento de plan existente ──────────────────
    async eliminarProcedimiento(idDetalle, idPlan) {
        if (!confirm('¿Eliminar este procedimiento del plan?')) return;

        const formData = new FormData();
        formData.append('modulo',          'planes');
        formData.append('accion',          'eliminar_procedimiento');
        formData.append('id_detalle_plan', idDetalle);

        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            if (data.success) {
                CatalogTable.showNotification('Procedimiento eliminado', 'success');
                this.cargar(this._numeroPaciente, this._readonly);
            } else {
                CatalogTable.showNotification(data.message || 'Error al eliminar', 'error');
            }
        } catch (err) {
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },

    // ── Agregar procedimiento a plan existente ────────────────────
    async confirmarProcExistente(idPlan) {
        const procSel = document.getElementById(`procSelExist_${idPlan}`);
        const pieza   = document.getElementById(`procPiezaExist_${idPlan}`);
        const desc    = document.getElementById(`procDescExist_${idPlan}`);

        if (!procSel.value) {
            CatalogTable.showNotification('Selecciona un procedimiento', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('modulo',             'planes');
        formData.append('accion',             'agregar_procedimiento');
        formData.append('id_plan_tratamiento', idPlan);
        formData.append('id_procedimiento',    procSel.value);
        if (pieza.value)  formData.append('numero_pieza',    pieza.value);
        if (desc.value)   formData.append('costo_descuento', desc.value);

        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            if (data.success) {
                CatalogTable.showNotification('Procedimiento agregado', 'success');
                this.cargar(this._numeroPaciente, this._readonly);
            } else {
                CatalogTable.showNotification(data.message || 'Error al agregar', 'error');
            }
        } catch (err) {
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },

    // ── Procedimientos temporales (nuevo plan) ────────────────────
    agregarProcTemp() {
        const sel  = document.getElementById('procSelect');
        const pieza= document.getElementById('procPieza');
        const desc = document.getElementById('procDescuento');

        if (!sel.value) {
            CatalogTable.showNotification('Selecciona un procedimiento', 'warning');
            return;
        }

        const proc = this._catalogos?.procedimientos?.find(p => p.id_procedimiento == sel.value);
        if (!proc) return;

        this._procsTemp.push({
            id_procedimiento:  parseInt(sel.value),
            nombre:            proc.nombre_procedimiento,
            precio_base:       parseFloat(proc.precio_base),
            numero_pieza:      pieza.value || null,
            costo_descuento:   desc.value  || null,
        });

        sel.value   = '';
        pieza.value = '';
        desc.value  = '';
        document.getElementById('rowAgregarProc').style.display = 'none';

        this._renderProcsTemp();
    },

    quitarProcTemp(idx) {
        this._procsTemp.splice(idx, 1);
        this._renderProcsTemp();
    },

    _renderProcsTemp() {
        const tbody = document.getElementById('bodyProcsPlan');
        const total = this._procsTemp.reduce((sum, p) =>
            sum + (p.costo_descuento ? parseFloat(p.costo_descuento) : p.precio_base), 0);

        document.getElementById('totalPlan').textContent =
            '$' + total.toLocaleString('es-MX', { minimumFractionDigits: 2 });

        if (!this._procsTemp.length) {
            tbody.innerHTML = `
                <tr id="rowSinProcs">
                    <td colspan="5" style="text-align:center; color:#adb5bd; padding:16px;">
                        Sin procedimientos agregados
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = this._procsTemp.map((p, i) => `
            <tr>
                <td>${escHtml(p.nombre)}</td>
                <td class="text-center">${p.numero_pieza || '—'}</td>
                <td>$${p.precio_base.toLocaleString('es-MX',{minimumFractionDigits:2})}</td>
                <td>${p.costo_descuento
                    ? '$'+parseFloat(p.costo_descuento).toLocaleString('es-MX',{minimumFractionDigits:2})
                    : '—'}</td>
                <td class="acciones-cell">
                    <button class="btn-accion eliminar" onclick="planesController.quitarProcTemp(${i})">
                        <i class="ri-delete-bin-6-line"></i>
                    </button>
                </td>
            </tr>`).join('');
    },

    // ── Helpers UI ────────────────────────────────────────────────
    _mostrarLoading(show) {
        const el = document.getElementById('planesLoading');
        if (el) el.style.display = show ? 'block' : 'none';
    },

    _ocultarFormulario() {
        const form = document.getElementById('formNuevoPlanContainer');
        if (form) form.style.display = 'none';
        this._procsTemp = [];
        this._renderProcsTemp();
        // Limpiar campos
        ['planFecha','planNotas'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        ['planEspecialista','planEstatus'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    },

    _poblarSelects() {
        // Especialistas
        const selEsp = document.getElementById('planEspecialista');
        if (selEsp && this._catalogos?.especialistas) {
            this._catalogos.especialistas.forEach(e => {
                const opt = document.createElement('option');
                opt.value       = e.id_especialista;
                opt.textContent = e.nombre_completo;
                selEsp.appendChild(opt);
            });
        }
        // Estatus
        const selEst = document.getElementById('planEstatus');
        if (selEst && this._catalogos?.estatus) {
            this._catalogos.estatus.forEach(e => {
                const opt = document.createElement('option');
                opt.value       = e.id_estatus_tratamiento;
                opt.textContent = e.estatus_tratamiento;
                selEst.appendChild(opt);
            });
        }
        // Procedimientos del nuevo plan
        const selProc = document.getElementById('procSelect');
        if (selProc && this._catalogos?.procedimientos) {
            this._catalogos.procedimientos.forEach(p => {
                const opt = document.createElement('option');
                opt.value       = p.id_procedimiento;
                opt.textContent = `${p.nombre_procedimiento} — $${parseFloat(p.precio_base).toLocaleString('es-MX',{minimumFractionDigits:2})}`;
                opt.dataset.precio = p.precio_base;
                selProc.appendChild(opt);
            });
        }
    },

    _optionsEstatus(seleccionado) {
        if (!this._catalogos?.estatus) return '';
        return this._catalogos.estatus.map(e =>
            `<option value="${e.id_estatus_tratamiento}"
                ${e.id_estatus_tratamiento == seleccionado ? 'selected' : ''}>
                ${e.estatus_tratamiento}
            </option>`
        ).join('');
    },

    _optionsProcedimientos() {
        if (!this._catalogos?.procedimientos) return '';
        return this._catalogos.procedimientos.map(p =>
            `<option value="${p.id_procedimiento}" data-precio="${p.precio_base}">
                ${escHtml(p.nombre_procedimiento)} — $${parseFloat(p.precio_base).toLocaleString('es-MX',{minimumFractionDigits:2})}
            </option>`
        ).join('');
    },
};

// ── Helpers globales ──────────────────────────────────────────────
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

function formatFecha(fecha) {
    if (!fecha) return '—';
    const [y, m, d] = fecha.split('-');
    return `${d}/${m}/${y}`;
}

function badgeClase(estatus) {
    const mapa = {
        'Programado': 'programado',
        'En curso':   'en-curso',
        'Pausado':    'pausado',
        'Terminado':  'completado',
    };
    return mapa[estatus] || 'pendiente';
}

function imprimirPlanes() {
    window.print();
}

// ── Eventos del formulario de nuevo plan ──────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // Abrir formulario
    document.getElementById('btnNuevoPlan')?.addEventListener('click', () => {
        const form = document.getElementById('formNuevoPlanContainer');
        if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
        // Establecer fecha de hoy por defecto
        const hoy = new Date().toISOString().split('T')[0];
        const inputFecha = document.getElementById('planFecha');
        if (inputFecha && !inputFecha.value) inputFecha.value = hoy;
    });

    // Cancelar nuevo plan
    document.getElementById('btnCancelarPlan')?.addEventListener('click', () => {
        planesController._ocultarFormulario();
    });

    // Guardar nuevo plan
    document.getElementById('btnGuardarPlan')?.addEventListener('click', () => {
        planesController.guardar();
    });

    // Mostrar fila agregar procedimiento
    document.getElementById('btnAgregarProcPlan')?.addEventListener('click', () => {
        const row = document.getElementById('rowAgregarProc');
        if (row) row.style.display = 'flex';
    });

    // Confirmar procedimiento temp
    document.getElementById('btnConfirmarProc')?.addEventListener('click', () => {
        planesController.agregarProcTemp();
    });

    // Cancelar procedimiento temp
    document.getElementById('btnCancelarProc')?.addEventListener('click', () => {
        const row = document.getElementById('rowAgregarProc');
        if (row) row.style.display = 'none';
    });
});