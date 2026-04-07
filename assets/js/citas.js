/**
 * Módulo de Citas - JavaScript
 */

'use strict';
 
// ─────────────────────────────────────────────
//  CONFIGURACIÓN
// ─────────────────────────────────────────────
const API = '../controllers/CitasController.php';
 
// IDs reales de la tabla EstadosCita en BD
const ESTATUS_MAP = {
    'Pendiente':            1,
    'Confirmada':           2,
    'Reprogramada':         3,
    'En curso':             4,
    'No asistió':           5,
    'Cancelada':            6,
    'Atendida':             7,
    'Pagada':               8,
    'Registro diagnóstico': 9,
};
 
// ─────────────────────────────────────────────
//  ESTADO GLOBAL
// ─────────────────────────────────────────────
let estadoApp = {
    fechaSeleccionada: null,
    mesActual: new Date(),
    diasConCitas: {},
    citaIdEditar: null,
    filtroEstatus: 'todas',
};
 
// ─────────────────────────────────────────────
//  DOM
// ─────────────────────────────────────────────
const $ = id => document.getElementById(id);
const $q = sel => document.querySelector(sel);
const $qa = sel => document.querySelectorAll(sel);
 
// ─────────────────────────────────────────────
//  UTILIDADES
// ─────────────────────────────────────────────
 
function formatDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}
 
function formatDateDisplay(str) {
    if (!str) return '';
    const [y, m, d] = str.split('-');
    return `${d}/${m}/${y}`;
}
 
function formatTime(str) {
    if (!str) return '';
    return str.substring(0, 5);
}
 
function getBadgeClass(estatus) {
    const map = {
        'Pendiente':            'badge-warning',
        'Confirmada':           'badge-success',
        'Reprogramada':         'badge-info',
        'En curso':             'badge-primary',
        'No asistió':           'badge-secondary',
        'Cancelada':            'badge-danger',
        'Atendida':             'badge-success',
        'Pagada':               'badge-pagada',
        'Registro diagnóstico': 'badge-secondary',
    };
    return map[estatus] ?? 'badge-secondary';
}
 
function toast(mensaje, tipo = 'info') {
    const container = $('toastContainer');
    if (!container) return;
 
    const colors = {
        success: '#28a745', error: '#dc3545',
        warning: '#f57c00', info: '#20a89e',
    };
    const icons = {
        success: 'ri-checkbox-circle-line', error: 'ri-error-warning-line',
        warning: 'ri-alert-line',           info:  'ri-information-line',
    };
 
    const el = document.createElement('div');
    el.style.cssText = `
        display:flex;align-items:center;gap:10px;
        background:${colors[tipo] ?? colors.info};color:#fff;
        padding:14px 18px;border-radius:10px;
        box-shadow:0 4px 16px rgba(0,0,0,.2);
        font-size:14px;font-weight:500;
        animation:toastIn .3s ease forwards;
        margin-bottom:8px;min-width:260px;max-width:380px;cursor:pointer;
    `;
    el.innerHTML = `<i class="${icons[tipo] ?? icons.info}" style="font-size:18px;flex-shrink:0"></i><span>${mensaje}</span>`;
    el.addEventListener('click', () => removeToast(el));
    container.appendChild(el);
    setTimeout(() => removeToast(el), 4000);
}
 
function removeToast(el) {
    el.style.animation = 'toastOut .3s ease forwards';
    setTimeout(() => el.remove(), 300);
}
 
/**
 * Petición al controlador.
 * CRÍTICO: params (action, id) SIEMPRE van en la URL como query string
 * porque el router PHP los lee de $_GET, sin importar si es GET o POST.
 * El body JSON va por separado en opts.body.
 */
async function apiFetch(params = {}, method = 'GET', body = null) {
    const url = new URL(API, window.location.href);
 
    // Siempre agregar params a la URL (action, id los necesita el router PHP)
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
 
    const opts = { method, headers: {} };
    if (body) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
    }
 
    const res = await fetch(url.toString(), opts);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}
 
// ─────────────────────────────────────────────
//  CALENDARIO
// ─────────────────────────────────────────────
 
const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
               'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
 
async function cargarDiasConCitas(mes, anio) {
    try {
        const data = await apiFetch({ action: 'dias_con_citas', mes, anio });
        estadoApp.diasConCitas = {};
        if (data.success && Array.isArray(data.data)) {
            data.data.forEach(item => {
                estadoApp.diasConCitas[item.fecha] = item;
            });
        }
    } catch (e) {
        console.error('Error cargando días con citas:', e);
    }
}
 
function renderCalendario() {
    const mes    = estadoApp.mesActual;
    const anio   = mes.getFullYear();
    const numMes = mes.getMonth();
 
    $('calendarTitle').textContent = `${MESES[numMes]} ${anio}`;
 
    const body     = $('calendarBody');
    body.innerHTML = '';
 
    const primerDia = new Date(anio, numMes, 1);
    const ultimoDia = new Date(anio, numMes + 1, 0);
    const hoy       = new Date(); hoy.setHours(0, 0, 0, 0);
 
    for (let i = 0; i < primerDia.getDay(); i++) {
        const v = document.createElement('div');
        v.className = 'calendar-day empty';
        body.appendChild(v);
    }
 
    for (let d = 1; d <= ultimoDia.getDate(); d++) {
        const fecha    = new Date(anio, numMes, d); fecha.setHours(0,0,0,0);
        const fechaStr = formatDate(fecha);
        const info     = estadoApp.diasConCitas[fechaStr];
 
        const btn = document.createElement('button');
        btn.className     = 'calendar-day';
        btn.textContent   = d;
        btn.dataset.fecha = fechaStr;
 
        if (fecha.getTime() === hoy.getTime()) btn.classList.add('today');
 
        if (estadoApp.fechaSeleccionada &&
            formatDate(estadoApp.fechaSeleccionada) === fechaStr) {
            btn.classList.add('selected');
        }
 
        if (info) {
            const total = parseInt(info.total ?? 0);
            btn.classList.add(total === 0 ? 'disponible' : total < 4 ? 'poca' : 'ocupado');
            const dot = document.createElement('span');
            dot.className = 'day-dot';
            btn.appendChild(dot);
        }
 
        btn.addEventListener('click', () => seleccionarFecha(fecha));
        body.appendChild(btn);
    }
}
 
async function seleccionarFecha(fecha) {
    estadoApp.fechaSeleccionada = fecha;
 
    $qa('.calendar-day').forEach(b => b.classList.remove('selected'));
    const fechaStr  = formatDate(fecha);
    const btnActivo = $q(`.calendar-day[data-fecha="${fechaStr}"]`);
    if (btnActivo) btnActivo.classList.add('selected');
 
    $('citasFechaSeleccionada').textContent =
        fecha.toLocaleDateString('es-MX', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
 
    await cargarCitasDelDia(fechaStr);
}
 
async function navegarMes(delta) {
    const m = estadoApp.mesActual;
    estadoApp.mesActual = new Date(m.getFullYear(), m.getMonth() + delta, 1);
    await cargarDiasConCitas(estadoApp.mesActual.getMonth() + 1, estadoApp.mesActual.getFullYear());
    renderCalendario();
}
 
// ─────────────────────────────────────────────
//  CITAS DEL DÍA
// ─────────────────────────────────────────────
 
async function cargarCitasDelDia(fecha) {
    const lista    = $('citasLista');
    lista.innerHTML = '<div style="text-align:center;padding:30px;color:#6c757d;"><i class="ri-loader-4-line spin" style="font-size:28px;"></i></div>';
 
    try {
        const params = { action: 'index', fecha };
        if (estadoApp.filtroEstatus !== 'todas') params.estatus = estadoApp.filtroEstatus;
 
        const data = await apiFetch(params);
        if (!data.success) throw new Error(data.message);
        renderCitasLista(data.data ?? []);
    } catch (e) {
        console.error('Error cargando citas:', e);
        lista.innerHTML = `
            <div class="citas-empty">
                <i class="ri-calendar-close-line"></i>
                <p>Error al cargar citas</p>
                <small>${e.message}</small>
            </div>`;
    }
}
 
function renderCitasLista(citas) {
    const lista = $('citasLista');
 
    if (!citas.length) {
        lista.innerHTML = `
            <div class="citas-empty">
                <i class="ri-calendar-line"></i>
                <p>Sin citas para este día</p>
                <small>Haz clic en "Nueva cita" para agregar una</small>
            </div>`;
        return;
    }
 
    lista.innerHTML = citas.map(c => citaCard(c)).join('');
 
    // Botones editar/eliminar — stopPropagation para no abrir detalle
    lista.querySelectorAll('[data-action]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const { action, id } = btn.dataset;
            if (action === 'editar')   abrirModalEditar(id);
            if (action === 'eliminar') confirmarEliminar(id);
        });
    });
 
    // Click en card → detalle
    lista.querySelectorAll('.cita-card').forEach(card => {
        card.addEventListener('click', () => abrirDetalle(card.dataset.id));
    });
}
 
function citaCard(c) {
    const estatus    = c.estatus_cita ?? 'Pendiente';
    const badgeClass = getBadgeClass(estatus);
 
    return `
    <div class="cita-card" data-id="${c.id_cita}">
        <div class="cita-hora">${formatTime(c.hora_inicio)}</div>
        <div class="cita-info">
            <div class="cita-paciente"><i class="ri-user-line"></i> ${c.nombre_paciente ?? '—'}</div>
            <div class="cita-especialista"><i class="ri-stethoscope-line"></i> ${c.nombre_especialista ?? '—'}</div>
            <div class="cita-motivo"><i class="ri-heart-pulse-line"></i> ${c.motivo_consulta ?? '—'}</div>
        </div>
        <div class="cita-acciones">
            <span class="badge ${badgeClass}">${estatus}</span>
            <div class="cita-btns">
                <button class="btn-icon btn-icon-warning" title="Editar"
                    data-action="editar" data-id="${c.id_cita}">
                    <i class="ri-edit-line"></i>
                </button>
                <button class="btn-icon btn-icon-danger" title="Eliminar"
                    data-action="eliminar" data-id="${c.id_cita}">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    </div>`;
}
 
// ─────────────────────────────────────────────
//  CATÁLOGOS
// ─────────────────────────────────────────────
 
async function cargarCatalogos() {
    try {
        const [resPac, resEsp, resMot] = await Promise.all([
            apiFetch({ action: 'get_pacientes' }),
            apiFetch({ action: 'get_especialistas' }),
            apiFetch({ action: 'get_motivos' }),
        ]);
 
        // Loguear si algún catálogo falla para facilitar diagnóstico
        if (!resPac.success)  console.error('Catálogo pacientes:', resPac.message ?? resPac);
        if (!resEsp.success)  console.error('Catálogo especialistas:', resEsp.message ?? resEsp);
        if (!resMot.success)  console.error('Catálogo motivos:', resMot.message ?? resMot);
 
        poblarSelect('selectPaciente',     resPac.data ?? [], 'numero_paciente',    'nombre_completo');
        poblarSelect('selectEspecialista', resEsp.data ?? [], 'id_especialista',    'nombre_completo');
        poblarSelect('selectMotivo',       resMot.data ?? [], 'id_motivo_consulta', 'motivo_consulta');
 
        const totalOpts = (resPac.data?.length ?? 0) + (resEsp.data?.length ?? 0) + (resMot.data?.length ?? 0);
        console.log(`✔ Catálogos cargados: ${resPac.data?.length ?? 0} pacientes, ${resEsp.data?.length ?? 0} especialistas, ${resMot.data?.length ?? 0} motivos`);
        if (totalOpts === 0) toast('No se cargaron datos en los catálogos — revisa la consola', 'warning');
 
    } catch (e) {
        console.error('Error cargando catálogos:', e);
        toast('Error al cargar catálogos', 'error');
    }
}
 
function poblarSelect(selectId, items, valKey, labelKey) {
    const sel = $(selectId);
    if (!sel) return;
 
    const placeholder = sel.options[0]?.textContent ?? 'Seleccionar...';
    sel.innerHTML = '';
 
    const optDef = document.createElement('option');
    optDef.value       = '';
    optDef.textContent = placeholder;
    sel.appendChild(optDef);
 
    items.forEach(item => {
        const opt = document.createElement('option');
        opt.value       = item[valKey];
        opt.textContent = item[labelKey];
        sel.appendChild(opt);
    });
}
 
/** Busca la <option> por valor y la selecciona */
function setSelectValue(selectId, value) {
    const sel = $(selectId);
    if (!sel) return;
    const opt = Array.from(sel.options).find(o => o.value === String(value));
    if (opt) {
        sel.value = String(value);
    } else {
        console.warn(`setSelectValue: "${value}" no encontrado en #${selectId}`);
    }
}
 
// ─────────────────────────────────────────────
//  MODAL NUEVA CITA
// ─────────────────────────────────────────────
 
function abrirModalNueva() {
    estadoApp.citaIdEditar = null;
    $('modalTitle').textContent       = 'Nueva Cita';
    $('formCita').reset();
    $('citaId').value                 = '';
    $('groupEstatus').style.display   = 'none';
    $('alertConflicto').style.display = 'none';
 
    if (estadoApp.fechaSeleccionada) {
        $('inputFecha').value = formatDate(estadoApp.fechaSeleccionada);
    }
    abrirModal('modalCita');
}
 
// ─────────────────────────────────────────────
//  MODAL EDITAR CITA
// ─────────────────────────────────────────────
 
async function abrirModalEditar(id) {
    estadoApp.citaIdEditar            = id;
    $('modalTitle').textContent       = 'Editar Cita';
    $('alertConflicto').style.display = 'none';
    $('groupEstatus').style.display   = '';
 
    try {
        const data = await apiFetch({ action: 'show', id });
        if (!data.success) throw new Error(data.message);
 
        const c = data.data;
 
        // Abrir modal PRIMERO — necesario para que los selects estén en el DOM
        abrirModal('modalCita');
 
        // Campos de texto/fecha — asignación inmediata
        $('citaId').value         = c.id_cita;
        $('inputFecha').value     = c.fecha_cita;
        $('inputHora').value      = formatTime(c.hora_inicio);
        $('selectDuracion').value = String(c.duracion_aproximada ?? 60);
        $('inputCosto').value     = c.costo_total ?? '';
 
        // Selects con FK — doble requestAnimationFrame para garantizar que
        // el modal esté visible Y los <option> del catálogo ya estén en el DOM
        requestAnimationFrame(() => requestAnimationFrame(() => {
            setSelectValue('selectPaciente',     String(c.numero_paciente));
            setSelectValue('selectEspecialista', String(c.id_especialista));
            setSelectValue('selectMotivo',       String(c.id_motivo_consulta));
            setSelectValue('selectTipoPaciente', c.paciente_primera_vez == 1 ? 'Primera vez' : 'Seguimiento');
            setSelectValue('selectEstatus',      c.estatus_cita ?? 'Pendiente');
        }));
 
    } catch (e) {
        toast(`Error al cargar la cita: ${e.message}`, 'error');
    }
}
 
// ─────────────────────────────────────────────
//  GUARDAR CITA
// ─────────────────────────────────────────────
 
async function guardarCita() {
    const form = $('formCita');
    $('alertConflicto').style.display = 'none';
 
    if (!form.reportValidity()) return;
 
    const payload = {
        id_paciente:         $('selectPaciente').value,
        tipoPaciente:        $('selectTipoPaciente').value,
        id_especialista:     $('selectEspecialista').value,
        id_motivo_consulta:  $('selectMotivo').value,
        fecha_cita:          $('inputFecha').value,
        hora_inicio:         $('inputHora').value,
        duracion_aproximada: $('selectDuracion').value,
        costo_estimado:      $('inputCosto').value || null,
    };
 
    if (estadoApp.citaIdEditar) {
        payload.estatus = $('selectEstatus').value;
    }
 
    const errores = validarFormCita(payload);
    if (errores.length) { mostrarAlerta(errores.join(' · ')); return; }
 
    const btnGuardar = $('btnGuardarCita');
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="ri-loader-4-line spin"></i> Guardando...';
 
    try {
        const action = estadoApp.citaIdEditar ? 'update' : 'store';
        const params = { action };
        if (estadoApp.citaIdEditar) params.id = estadoApp.citaIdEditar;
 
        const data = await apiFetch(params, 'POST', payload);
 
        if (!data.success) {
            if (data.message?.toLowerCase().includes('horario') ||
                data.message?.toLowerCase().includes('conflicto')) {
                mostrarAlerta(data.message);
            } else {
                toast(data.message, 'error');
            }
            return;
        }
 
        toast(estadoApp.citaIdEditar ? 'Cita actualizada' : 'Cita creada', 'success');
        cerrarModal('modalCita');
        await refrescarVista();
 
    } catch (e) {
        toast(`Error: ${e.message}`, 'error');
    } finally {
        btnGuardar.disabled  = false;
        btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar Cita';
    }
}
 
function validarFormCita(data) {
    const errores = [];
    if (!data.id_paciente)        errores.push('Selecciona un paciente.');
    if (!data.tipoPaciente)       errores.push('Selecciona el tipo de paciente.');
    if (!data.id_especialista)    errores.push('Selecciona un especialista.');
    if (!data.id_motivo_consulta) errores.push('Selecciona el motivo de consulta.');
    if (!data.fecha_cita)         errores.push('Ingresa la fecha.');
    if (!data.hora_inicio)        errores.push('Ingresa la hora.');
    return errores;
}
 
function mostrarAlerta(msg) {
    $('alertMessage').textContent     = msg;
    $('alertConflicto').style.display = 'flex';
}
 
// ─────────────────────────────────────────────
//  MODAL DETALLE
// ─────────────────────────────────────────────
 
async function abrirDetalle(id) {
    const content   = $('detalleContent');
    content.innerHTML = '<div style="text-align:center;padding:30px;"><i class="ri-loader-4-line spin" style="font-size:28px;color:#6c757d;"></i></div>';
    abrirModal('modalDetalle');
 
    try {
        const data = await apiFetch({ action: 'show', id });
        if (!data.success) throw new Error(data.message);
 
        const c          = data.data;
        const estatus    = c.estatus_cita ?? 'Pendiente';
        const badgeClass = getBadgeClass(estatus);
 
        const todosEstatus = ['Confirmada','Reprogramada','En curso','No asistió','Cancelada','Atendida','Pagada'];
        const botonesHtml  = todosEstatus
            .map(e => `<button class="btn-estatus${estatus === e ? ' active' : ''}"
                data-estatus="${e}" data-id="${c.id_cita}">${e}</button>`)
            .join('');
 
        content.innerHTML = `
            <div class="detalle-grid">
                <div class="detalle-row">
                    <span class="detalle-label"><i class="ri-user-line"></i> Paciente</span>
                    <span class="detalle-value">${c.nombre_paciente ?? '—'}</span>
                </div>
                <div class="detalle-row">
                    <span class="detalle-label"><i class="ri-file-list-line"></i> Tipo</span>
                    <span class="detalle-value">${c.paciente_primera_vez == 1 ? 'Primera vez' : 'Seguimiento'}</span>
                </div>
                <div class="detalle-row">
                    <span class="detalle-label"><i class="ri-stethoscope-line"></i> Especialista</span>
                    <span class="detalle-value">${c.nombre_especialista ?? '—'}</span>
                </div>
                <div class="detalle-row">
                    <span class="detalle-label"><i class="ri-heart-pulse-line"></i> Motivo</span>
                    <span class="detalle-value">${c.motivo_consulta ?? '—'}</span>
                </div>
                <div class="detalle-row">
                    <span class="detalle-label"><i class="ri-calendar-line"></i> Fecha</span>
                    <span class="detalle-value">${formatDateDisplay(c.fecha_cita)}</span>
                </div>
                <div class="detalle-row">
                    <span class="detalle-label"><i class="ri-time-line"></i> Hora</span>
                    <span class="detalle-value">${formatTime(c.hora_inicio)}</span>
                </div>
                <div class="detalle-row">
                    <span class="detalle-label"><i class="ri-timer-line"></i> Duración</span>
                    <span class="detalle-value">${c.duracion_aproximada ?? 60} min</span>
                </div>
                <div class="detalle-row">
                    <span class="detalle-label"><i class="ri-money-dollar-circle-line"></i> Costo</span>
                    <span class="detalle-value">${c.costo_total ? '$' + parseFloat(c.costo_total).toFixed(2) : '—'}</span>
                </div>
                <div class="detalle-row">
                    <span class="detalle-label"><i class="ri-checkbox-circle-line"></i> Estatus</span>
                    <span class="detalle-value"><span class="badge ${badgeClass}">${estatus}</span></span>
                </div>
            </div>
            <div class="detalle-cambiar-estatus">
                <label class="form-label">Cambiar estatus:</label>
                <div class="estatus-btns">${botonesHtml}</div>
            </div>`;
 
        $('btnEditarDesdeDetalle').onclick = () => {
            cerrarModal('modalDetalle');
            abrirModalEditar(c.id_cita);
        };
 
        content.querySelectorAll('.btn-estatus').forEach(btn => {
            btn.addEventListener('click', async () => {
                await cambiarEstatus(btn.dataset.id, btn.dataset.estatus);
                cerrarModal('modalDetalle');
                await refrescarVista();
            });
        });
 
    } catch (e) {
        content.innerHTML = `<p style="color:#dc3545;text-align:center;">Error: ${e.message}</p>`;
    }
}
 
// ─────────────────────────────────────────────
//  CAMBIAR ESTATUS
// ─────────────────────────────────────────────
 
async function cambiarEstatus(id, nuevoEstatus) {
    try {
        // action e id → URL (PHP los lee de $_GET)
        // estatus     → body JSON (PHP lo lee de php://input)
        const data = await apiFetch(
            { action: 'cambiar_estatus', id },
            'POST',
            { estatus: nuevoEstatus }
        );
        if (data.success) {
            toast(`Estatus: ${nuevoEstatus}`, 'success');
        } else {
            toast(data.message ?? 'Error al cambiar estatus', 'error');
        }
    } catch (e) {
        toast(`Error: ${e.message}`, 'error');
    }
}
 
// ─────────────────────────────────────────────
//  ELIMINAR CITA
// ─────────────────────────────────────────────
 
let _idPendienteEliminar = null;
 
function confirmarEliminar(id) {
    _idPendienteEliminar = id;
    abrirModal('modalEliminar');
}
 
async function eliminarCita() {
    if (!_idPendienteEliminar) return;
 
    const btn    = $('btnConfirmarEliminar');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line spin"></i> Eliminando...';
 
    try {
        const data = await apiFetch(
            { action: 'destroy', id: _idPendienteEliminar },
            'POST'
        );
        if (data.success) {
            toast('Cita eliminada', 'success');
            cerrarModal('modalEliminar');
            await refrescarVista();
        } else {
            toast(data.message, 'error');
        }
    } catch (e) {
        toast(`Error: ${e.message}`, 'error');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="ri-delete-bin-line"></i> Eliminar';
        _idPendienteEliminar = null;
    }
}
 
// ─────────────────────────────────────────────
//  MODALES
// ─────────────────────────────────────────────
 
function abrirModal(id) {
    const modal = $(id);
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}
 
function cerrarModal(id) {
    const modal = $(id);
    if (!modal) return;
    modal.classList.remove('active');
    if (!document.querySelector('.modal-overlay.active')) {
        document.body.style.overflow = '';
    }
}
 
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) cerrarModal(e.target.id);
});
 
// ─────────────────────────────────────────────
//  FILTROS
// ─────────────────────────────────────────────
 
function initFiltros() {
    $qa('.filtro-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            $qa('.filtro-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            estadoApp.filtroEstatus = btn.dataset.estatus;
            if (estadoApp.fechaSeleccionada) {
                await cargarCitasDelDia(formatDate(estadoApp.fechaSeleccionada));
            }
        });
    });
}
 
// ─────────────────────────────────────────────
//  REFRESCAR
// ─────────────────────────────────────────────
 
async function refrescarVista() {
    const mes  = estadoApp.mesActual.getMonth() + 1;
    const anio = estadoApp.mesActual.getFullYear();
    await cargarDiasConCitas(mes, anio);
    renderCalendario();
    if (estadoApp.fechaSeleccionada) {
        await cargarCitasDelDia(formatDate(estadoApp.fechaSeleccionada));
    }
}
 
// ─────────────────────────────────────────────
//  ESTILOS
// ─────────────────────────────────────────────
 
function injectStyles() {
    const style = document.createElement('style');
    style.textContent = `
        #toastContainer {
            position:fixed;bottom:24px;right:24px;
            z-index:10000;display:flex;flex-direction:column-reverse;
        }
        @keyframes toastIn  { from{transform:translateX(110%);opacity:0} to{transform:translateX(0);opacity:1} }
        @keyframes toastOut { from{transform:translateX(0);opacity:1} to{transform:translateX(110%);opacity:0} }
 
        .spin { animation:spinAnim 1s linear infinite;display:inline-block; }
        @keyframes spinAnim { to{transform:rotate(360deg)} }
 
        .modal-overlay        { display:none; }
        .modal-overlay.active { display:flex; }
 
        .calendar-day            { position:relative; }
        .calendar-day.disponible { background:#e8f5e9!important;color:#2e7d32!important; }
        .calendar-day.poca       { background:#fff8e1!important;color:#f57f17!important; }
        .calendar-day.ocupado    { background:#ffebee!important;color:#c62828!important; }
        .calendar-day.today      { font-weight:700;border:2px solid var(--primary,#20a89e); }
        .calendar-day.selected   { background:var(--primary,#20a89e)!important;color:#fff!important; }
        .day-dot {
            position:absolute;bottom:3px;left:50%;transform:translateX(-50%);
            width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.7;
        }
 
        .cita-card {
            display:grid;grid-template-columns:60px 1fr auto;
            gap:12px;align-items:center;cursor:pointer;
            background:#fff;border:1px solid #e9ecef;
            border-radius:10px;padding:14px 16px;margin-bottom:10px;
            transition:box-shadow .2s,transform .2s;
        }
        .cita-card:hover     { box-shadow:0 4px 16px rgba(0,0,0,.1);transform:translateY(-1px); }
        .cita-hora           { font-size:16px;font-weight:700;color:var(--primary,#20a89e);text-align:center; }
        .cita-paciente       { font-weight:600;font-size:14px;margin-bottom:3px; }
        .cita-especialista,
        .cita-motivo         { font-size:13px;color:#6c757d; }
        .cita-acciones       { display:flex;flex-direction:column;align-items:flex-end;gap:8px; }
        .cita-btns           { display:flex;gap:6px; }
 
        .btn-icon          { width:30px;height:30px;border-radius:6px;border:none;cursor:pointer;
                             display:flex;align-items:center;justify-content:center;
                             font-size:15px;transition:opacity .2s; }
        .btn-icon:hover    { opacity:.8; }
        .btn-icon-warning  { background:#fff8e1;color:#e65100; }
        .btn-icon-danger   { background:#ffebee;color:#c62828; }
 
        .citas-empty       { text-align:center;padding:40px 20px;color:#adb5bd; }
        .citas-empty i     { font-size:48px;display:block;margin-bottom:12px; }
        .citas-empty p     { font-size:15px;font-weight:600;margin:0 0 6px;color:#6c757d; }
        .citas-empty small { font-size:13px; }
 
        .detalle-grid        { display:flex;flex-direction:column;gap:10px;margin-bottom:20px; }
        .detalle-row         { display:flex;justify-content:space-between;align-items:center;
                               padding:8px 12px;background:#f8f9fa;border-radius:6px; }
        .detalle-label       { font-size:13px;color:#6c757d; }
        .detalle-label i     { margin-right:6px; }
        .detalle-value       { font-size:13px;font-weight:600;color:#212529; }
 
        .detalle-cambiar-estatus             { border-top:1px solid #e9ecef;padding-top:16px; }
        .detalle-cambiar-estatus .form-label { font-size:13px;color:#6c757d;margin-bottom:10px;display:block; }
        .estatus-btns { display:flex;gap:6px;flex-wrap:wrap; }
        .btn-estatus  {
            padding:5px 12px;border-radius:6px;border:2px solid #dee2e6;
            background:#fff;cursor:pointer;font-size:12px;font-weight:500;
            transition:all .2s;color:#495057;
        }
        .btn-estatus:hover,
        .btn-estatus.active { background:var(--primary,#20a89e);color:#fff;border-color:var(--primary,#20a89e); }
 
        .badge           { padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;white-space:nowrap; }
        .badge-success   { background:#e8f5e9;color:#2e7d32; }
        .badge-warning   { background:#fff8e1;color:#e65100; }
        .badge-danger    { background:#ffebee;color:#c62828; }
        .badge-info      { background:#e3f2fd;color:#1565c0; }
        .badge-primary   { background:#e8eaf6;color:#283593; }
        .badge-pagada    { background:#f3e5f5;color:#6a1b9a; }
        .badge-secondary { background:#f1f3f5;color:#495057; }
 
        .form-alert   {
            display:flex;align-items:center;gap:10px;
            padding:12px 16px;background:#fff8e1;border:1px solid #ffe082;
            border-radius:8px;color:#e65100;font-size:14px;margin-top:12px;
        }
        .form-alert i { font-size:18px;flex-shrink:0; }
 
        .confirm-content { text-align:center;padding:10px; }
        .confirm-icon    { font-size:52px;color:#dc3545;margin-bottom:12px; }
        .confirm-text    { font-size:16px;font-weight:600;color:#212529;margin-bottom:6px; }
        .confirm-subtext { font-size:14px;color:#6c757d; }
    `;
    document.head.appendChild(style);
}
 
// ─────────────────────────────────────────────
//  INICIALIZACIÓN
// ─────────────────────────────────────────────
 
async function init() {
    injectStyles();
    await cargarCatalogos();
 
    const hoy = new Date();
    await cargarDiasConCitas(hoy.getMonth() + 1, hoy.getFullYear());
    renderCalendario();
    await seleccionarFecha(hoy);
 
    $('btnPrevMes')?.addEventListener('click', () => navegarMes(-1));
    $('btnNextMes')?.addEventListener('click', () => navegarMes(1));
    $('btnNuevaCita')?.addEventListener('click', abrirModalNueva);
 
    initFiltros();
 
    $('btnCerrarModal')?.addEventListener('click',    () => cerrarModal('modalCita'));
    $('btnCancelarModal')?.addEventListener('click',  () => cerrarModal('modalCita'));
    $('btnGuardarCita')?.addEventListener('click',    guardarCita);
 
    $('btnCerrarDetalle')?.addEventListener('click',  () => cerrarModal('modalDetalle'));
 
    $('btnCerrarEliminar')?.addEventListener('click',    () => cerrarModal('modalEliminar'));
    $('btnCancelarEliminar')?.addEventListener('click',  () => cerrarModal('modalEliminar'));
    $('btnConfirmarEliminar')?.addEventListener('click', eliminarCita);
 
    console.log('Módulo Citas inicializado');
}
 
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
 