/**
 * Módulo de Citas - JavaScript
 */

'use strict';
 
// ─────────────────────────────────────────────
//  CONFIGURACIÓN
// ─────────────────────────────────────────────
const API = '../controllers/CitasController.php';   // Ruta relativa al controlador
 
// Mapeo de estatus de BD → id_estatus_cita
// Ajusta los IDs según los valores reales en tu tabla EstadosCita
const ESTATUS_MAP = {
    'Pendiente':             1,
    'Confirmada':            2,
    'Reprogramada':          3,
    'En curso':              4,
    'No asistió':            5,
    'Cancelada':             6,
    'Atendida':              7,
    'Pagada':                8,
    'Registro diagnóstico':  9,
};
 
// ─────────────────────────────────────────────
//  ESTADO GLOBAL
// ─────────────────────────────────────────────
let estadoApp = {
    fechaSeleccionada: null,   // Date object
    mesActual: new Date(),     // Date object — primer día del mes visible
    diasConCitas: {},          // { "YYYY-MM-DD": { total, pendientes, confirmadas } }
    citaIdEditar: null,        // ID de cita en edición (null = nueva)
    filtroEstatus: 'todas',
};
 
// ─────────────────────────────────────────────
//  DOM — referencias cacheadas
// ─────────────────────────────────────────────
const $ = id => document.getElementById(id);
const $q = sel => document.querySelector(sel);
const $qa = sel => document.querySelectorAll(sel);
 
// ─────────────────────────────────────────────
//  UTILIDADES
// ─────────────────────────────────────────────
 
/** Formatea Date → "YYYY-MM-DD" */
function formatDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}
 
/** Formatea "YYYY-MM-DD" → "DD/MM/YYYY" */
function formatDateDisplay(str) {
    if (!str) return '';
    const [y, m, d] = str.split('-');
    return `${d}/${m}/${y}`;
}
 
/** Formatea "HH:MM:SS" → "HH:MM" */
function formatTime(str) {
    if (!str) return '';
    return str.substring(0, 5);
}
 
/** Toast de notificación */
function toast(mensaje, tipo = 'info') {
    const container = $('toastContainer');
    if (!container) return;
 
    const colors = {
        success: '#28a745',
        error:   '#dc3545',
        warning: '#ffc107',
        info:    '#20a89e',
    };
    const icons = {
        success: 'ri-checkbox-circle-line',
        error:   'ri-error-warning-line',
        warning: 'ri-alert-line',
        info:    'ri-information-line',
    };
 
    const el = document.createElement('div');
    el.className = 'toast-item';
    el.style.cssText = `
        display:flex; align-items:center; gap:10px;
        background:${colors[tipo] ?? colors.info};
        color:#fff; padding:14px 18px; border-radius:10px;
        box-shadow:0 4px 16px rgba(0,0,0,.2);
        font-size:14px; font-weight:500;
        animation: toastIn .3s ease forwards;
        margin-bottom:8px; min-width:260px; max-width:380px;
        cursor:pointer;
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
 
/** Mostrar/ocultar spinner dentro de un contenedor */
function setLoading(contenedor, show) {
    if (!contenedor) return;
    let sp = contenedor.querySelector('.inline-spinner');
    if (show && !sp) {
        sp = document.createElement('div');
        sp.className = 'inline-spinner';
        sp.style.cssText = 'text-align:center;padding:30px;color:#6c757d;';
        sp.innerHTML = '<i class="ri-loader-4-line spin" style="font-size:28px;"></i>';
        contenedor.innerHTML = '';
        contenedor.appendChild(sp);
    } else if (!show && sp) {
        sp.remove();
    }
}
 
/** Petición HTTP al controlador */
async function apiFetch(params = {}, method = 'GET', body = null) {
    const url = new URL(API, window.location.href);
    if (method === 'GET') {
        Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
    }
 
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
    const mes = estadoApp.mesActual;
    const anio = mes.getFullYear();
    const numMes = mes.getMonth(); // 0-based
 
    // Título
    $('calendarTitle').textContent = `${MESES[numMes]} ${anio}`;
 
    const body = $('calendarBody');
    body.innerHTML = '';
 
    const primerDia = new Date(anio, numMes, 1);
    const ultimoDia = new Date(anio, numMes + 1, 0);
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
 
    // Días vacíos al inicio (domingo = 0)
    const offsetInicio = primerDia.getDay();
    for (let i = 0; i < offsetInicio; i++) {
        const vacio = document.createElement('div');
        vacio.className = 'calendar-day empty';
        body.appendChild(vacio);
    }
 
    for (let d = 1; d <= ultimoDia.getDate(); d++) {
        const fecha = new Date(anio, numMes, d);
        fecha.setHours(0, 0, 0, 0);
        const fechaStr = formatDate(fecha);
        const info = estadoApp.diasConCitas[fechaStr];
 
        const btn = document.createElement('button');
        btn.className = 'calendar-day';
        btn.textContent = d;
        btn.dataset.fecha = fechaStr;
 
        // Clase de hoy
        if (fecha.getTime() === hoy.getTime()) btn.classList.add('today');
 
        // Clase de seleccionado
        if (estadoApp.fechaSeleccionada &&
            formatDate(estadoApp.fechaSeleccionada) === fechaStr) {
            btn.classList.add('selected');
        }
 
        // Indicador de disponibilidad
        if (info) {
            const total = parseInt(info.total ?? 0);
            if (total === 0) {
                btn.classList.add('disponible');
            } else if (total < 4) {
                btn.classList.add('poca');
            } else {
                btn.classList.add('ocupado');
            }
 
            // Dot indicator
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
 
    // Actualizar UI del calendario
    $qa('.calendar-day').forEach(btn => btn.classList.remove('selected'));
    const fechaStr = formatDate(fecha);
    const btnActivo = $q(`.calendar-day[data-fecha="${fechaStr}"]`);
    if (btnActivo) btnActivo.classList.add('selected');
 
    // Mostrar fecha en panel
    const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    $('citasFechaSeleccionada').textContent =
        fecha.toLocaleDateString('es-MX', opts);
 
    await cargarCitasDelDia(fechaStr);
}
 
async function navegarMes(delta) {
    const m = estadoApp.mesActual;
    estadoApp.mesActual = new Date(m.getFullYear(), m.getMonth() + delta, 1);
    await cargarDiasConCitas(
        estadoApp.mesActual.getMonth() + 1,
        estadoApp.mesActual.getFullYear()
    );
    renderCalendario();
}
 
// ─────────────────────────────────────────────
//  CITAS DEL DÍA
// ─────────────────────────────────────────────
 
async function cargarCitasDelDia(fecha) {
    const lista = $('citasLista');
    setLoading(lista, true);
 
    try {
        const params = { action: 'index', fecha };
        if (estadoApp.filtroEstatus !== 'todas') {
            params.estatus = estadoApp.filtroEstatus;
        }
 
        const data = await apiFetch(params);
 
        if (!data.success) throw new Error(data.message);
 
        renderCitasLista(data.data ?? []);
    } catch (e) {
        console.error('Error cargando citas:', e);
        $('citasLista').innerHTML = `
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
 
    // Bind eventos de cada card
    lista.querySelectorAll('[data-action]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const action = btn.dataset.action;
            const id = btn.dataset.id;
            if (action === 'ver')     abrirDetalle(id);
            if (action === 'editar')  abrirModalEditar(id);
            if (action === 'eliminar') confirmarEliminar(id);
        });
    });
 
    // Click en la card completa → ver detalle
    lista.querySelectorAll('.cita-card').forEach(card => {
        card.addEventListener('click', () => abrirDetalle(card.dataset.id));
    });
}
 
/** Clase CSS del badge según estatus real de la BD */
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
 
function citaCard(c) {
    const estatus = c.estatus_cita ?? 'Pendiente';
    const hora    = formatTime(c.hora_inicio);
    const nombre  = c.nombre_paciente ?? '—';
    const especialista = c.nombre_especialista ?? '—';
    const motivo  = c.motivo_consulta ?? '—';
 
    const badgeClass = getBadgeClass(estatus);
 
    return `
    <div class="cita-card" data-id="${c.id_cita}" style="cursor:pointer;">
        <div class="cita-hora">${hora}</div>
        <div class="cita-info">
            <div class="cita-paciente">
                <i class="ri-user-line"></i> ${nombre}
            </div>
            <div class="cita-especialista">
                <i class="ri-stethoscope-line"></i> ${especialista}
            </div>
            <div class="cita-motivo">
                <i class="ri-tooth-line"></i> ${motivo}
            </div>
        </div>
        <div class="cita-acciones">
            <span class="badge ${badgeClass}">${estatus}</span>
            <div class="cita-btns">
                <button class="btn-icon btn-icon-primary" title="Ver detalle"
                    data-action="ver" data-id="${c.id_cita}">
                    <i class="ri-eye-line"></i>
                </button>
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
//  CATÁLOGOS PARA SELECTS
// ─────────────────────────────────────────────
 
async function cargarCatalogos() {
    try {
        const [resPacientes, resEspecialistas, resMotivos] = await Promise.all([
            apiFetch({ action: 'get_pacientes' }),
            apiFetch({ action: 'get_especialistas' }),
            apiFetch({ action: 'get_motivos' }),
        ]);
 
        poblarSelect('selectPaciente', resPacientes.data ?? [], 'numero_paciente', 'nombre_completo');
        poblarSelect('selectEspecialista', resEspecialistas.data ?? [], 'id_especialista', 'nombre_completo');
        poblarSelect('selectMotivo', resMotivos.data ?? [], 'id_motivo_consulta', 'motivo_consulta');
    } catch (e) {
        console.error('Error cargando catálogos:', e);
        toast('Error al cargar catálogos', 'error');
    }
}
 
function poblarSelect(selectId, items, valKey, labelKey) {
    const sel = $(selectId);
    if (!sel) return;
    const primerOption = sel.options[0]; // "Seleccionar..."
    sel.innerHTML = '';
    sel.appendChild(primerOption);
    items.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item[valKey];
        opt.textContent = item[labelKey];
        sel.appendChild(opt);
    });
}
 
// ─────────────────────────────────────────────
//  MODAL NUEVA / EDITAR CITA
// ─────────────────────────────────────────────
 
function abrirModalNueva() {
    estadoApp.citaIdEditar = null;
    $('modalTitle').textContent = 'Nueva Cita';
    $('formCita').reset();
    $('citaId').value = '';
    $('groupEstatus').style.display = 'none';
    $('alertConflicto').style.display = 'none';
 
    // Pre-llenar fecha seleccionada
    if (estadoApp.fechaSeleccionada) {
        $('inputFecha').value = formatDate(estadoApp.fechaSeleccionada);
    }
 
    abrirModal('modalCita');
}
 
async function abrirModalEditar(id) {
    estadoApp.citaIdEditar = id;
    $('modalTitle').textContent = 'Editar Cita';
    $('alertConflicto').style.display = 'none';
    $('groupEstatus').style.display = '';
 
    try {
        const data = await apiFetch({ action: 'show', id });
        if (!data.success) throw new Error(data.message);
 
        const c = data.data;
 
        // 1. Abrir modal primero — necesario para que los <select> estén activos en el DOM
        abrirModal('modalCita');
 
        // 2. Campos de texto/fecha — se asignan inmediatamente
        $('citaId').value         = c.id_cita;
        $('inputFecha').value     = c.fecha_cita;
        $('inputHora').value      = formatTime(c.hora_inicio);
        $('selectDuracion').value = String(c.duracion_aproximada ?? 60);
        $('inputCosto').value     = c.costo_total ?? '';
 
        // 3. Selects con FK — esperar un frame para que los <option> ya existan
        //    (cargarCatalogos los carga async al inicio; si el modal estuvo cerrado
        //     los options siguen en el DOM, pero .value no encuentra nada si se asigna
        //     antes de que el navegador pinte el modal)
        requestAnimationFrame(() => {
            setSelectValue('selectPaciente',     String(c.numero_paciente));
            setSelectValue('selectEspecialista', String(c.id_especialista));
            setSelectValue('selectMotivo',       String(c.id_motivo_consulta));
            setSelectValue('selectTipoPaciente', c.paciente_primera_vez == 1 ? 'Primera vez' : 'Seguimiento');
            setSelectValue('selectEstatus',      c.estatus_cita ?? 'Pendiente');
        });
 
    } catch (e) {
        toast(`Error al cargar la cita: ${e.message}`, 'error');
    }
}
 
async function guardarCita() {
    const form = $('formCita');
    $('alertConflicto').style.display = 'none';
 
    // Validación HTML5
    if (!form.reportValidity()) return;
 
    const tipoPaciente = $('selectTipoPaciente').value;
    const payload = {
        id_paciente:          $('selectPaciente').value,
        tipoPaciente:         tipoPaciente,
        id_especialista:      $('selectEspecialista').value,
        id_motivo_consulta:   $('selectMotivo').value,
        fecha_cita:           $('inputFecha').value,
        hora_inicio:          $('inputHora').value,
        duracion_aproximada:  $('selectDuracion').value,
        costo_estimado:       $('inputCosto').value || null,
    };
 
    // Solo incluir estatus en modo edición
    if (estadoApp.citaIdEditar) {
        payload.estatus = $('selectEstatus').value;
    }
 
    // Validación frontend adicional
    const errores = validarFormCita(payload);
    if (errores.length) {
        mostrarAlerta(errores.join(' · '));
        return;
    }
 
    const btnGuardar = $('btnGuardarCita');
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="ri-loader-4-line spin"></i> Guardando...';
 
    try {
        const action = estadoApp.citaIdEditar ? 'update' : 'store';
        const params = { action };
        if (estadoApp.citaIdEditar) params.id = estadoApp.citaIdEditar;
 
        const data = await apiFetch(params, 'POST', payload);
 
        if (!data.success) {
            // Detectar conflicto de horario
            if (data.message?.toLowerCase().includes('horario') ||
                data.message?.toLowerCase().includes('conflicto')) {
                mostrarAlerta(data.message);
            } else {
                toast(data.message, 'error');
            }
            return;
        }
 
        toast(
            estadoApp.citaIdEditar ? 'Cita actualizada correctamente' : 'Cita creada correctamente',
            'success'
        );
        cerrarModal('modalCita');
        await refrescarVista();
 
    } catch (e) {
        toast(`Error: ${e.message}`, 'error');
    } finally {
        btnGuardar.disabled = false;
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
    const alert = $('alertConflicto');
    $('alertMessage').textContent = msg;
    alert.style.display = 'flex';
}
 
// ─────────────────────────────────────────────
//  MODAL DETALLE
// ─────────────────────────────────────────────
 
async function abrirDetalle(id) {
    const content = $('detalleContent');
    content.innerHTML = '<div style="text-align:center;padding:30px;"><i class="ri-loader-4-line spin" style="font-size:28px;color:#6c757d;"></i></div>';
    abrirModal('modalDetalle');
 
    try {
        const data = await apiFetch({ action: 'show', id });
        if (!data.success) throw new Error(data.message);
 
        const c = data.data;
        const estatus = c.estatus_cita ?? 'Pendiente';
        const badgeClass = getBadgeClass(estatus);
 
        content.innerHTML = `
            <div class="detalle-grid">
                <div class="detalle-row">
                    <span class="detalle-label"><i class="ri-user-line"></i> Paciente</span>
                    <span class="detalle-value">${c.nombre_paciente ?? '—'}</span>
                </div>
                <div class="detalle-row">
                    <span class="detalle-label"><i class="ri-file-list-line"></i> Tipo Paciente</span>
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
                <label class="form-label">Cambiar estatus rápido:</label>
                <div class="estatus-btns">
                    <button class="btn-estatus ${estatus==='Confirmada'?'active':''}"
                        data-estatus="Confirmada" data-id="${c.id_cita}">Confirmada</button>
                    <button class="btn-estatus ${estatus==='Reprogramada'?'active':''}"
                        data-estatus="Reprogramada" data-id="${c.id_cita}">Reprogramada</button>
                    <button class="btn-estatus ${estatus==='En curso'?'active':''}"
                        data-estatus="En curso" data-id="${c.id_cita}">En curso</button>
                    <button class="btn-estatus ${estatus==='No asistió'?'active':''}"
                        data-estatus="No asistió" data-id="${c.id_cita}">No asistió</button>
                    <button class="btn-estatus ${estatus==='Cancelada'?'active':''}"
                        data-estatus="Cancelada" data-id="${c.id_cita}">Cancelada</button>
                    <button class="btn-estatus ${estatus==='Atendida'?'active':''}"
                        data-estatus="Atendida" data-id="${c.id_cita}">Atendida</button>
                    <button class="btn-estatus ${estatus==='Pagada'?'active':''}"
                        data-estatus="Pagada" data-id="${c.id_cita}">Pagada</button>
                </div>
            </div>
        `;
 
        // Botón editar desde detalle
        $('btnEditarDesdeDetalle').dataset.id = c.id_cita;
        $('btnEditarDesdeDetalle').onclick = () => {
            cerrarModal('modalDetalle');
            abrirModalEditar(c.id_cita);
        };
 
        // Botones de cambio rápido de estatus
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
        const data = await apiFetch({ action: 'cambiar_estatus', id }, 'POST', { estatus: nuevoEstatus });
        if (data.success) {
            toast(`Estatus cambiado a "${nuevoEstatus}"`, 'success');
        } else {
            toast(data.message, 'error');
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
 
    const btn = $('btnConfirmarEliminar');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line spin"></i> Eliminando...';
 
    try {
        const data = await apiFetch({ action: 'destroy', id: _idPendienteEliminar }, 'POST');
        if (data.success) {
            toast('Cita eliminada correctamente', 'success');
            cerrarModal('modalEliminar');
            await refrescarVista();
        } else {
            toast(data.message, 'error');
        }
    } catch (e) {
        toast(`Error: ${e.message}`, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-delete-bin-line"></i> Eliminar';
        _idPendienteEliminar = null;
    }
}
 
// ─────────────────────────────────────────────
//  HELPERS DE MODAL
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
    // Solo restaurar scroll si no hay otro modal abierto
    if (!document.querySelector('.modal-overlay.active')) {
        document.body.style.overflow = '';
    }
}
 
// Cerrar modal al click en overlay (fuera del contenido)
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) {
        cerrarModal(e.target.id);
    }
});
 
// ─────────────────────────────────────────────
//  REFRESCAR VISTA
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
//  ESTILOS DINÁMICOS
// ─────────────────────────────────────────────
 
function injectStyles() {
    const style = document.createElement('style');
    style.textContent = `
        /* ---- Toast ---- */
        #toastContainer {
            position:fixed; bottom:24px; right:24px;
            z-index:10000; display:flex; flex-direction:column-reverse;
        }
        @keyframes toastIn {
            from { transform:translateX(110%); opacity:0; }
            to   { transform:translateX(0);    opacity:1; }
        }
        @keyframes toastOut {
            from { transform:translateX(0);    opacity:1; }
            to   { transform:translateX(110%); opacity:0; }
        }
 
        /* ---- Spinner ---- */
        .spin { animation: spinAnim 1s linear infinite; display:inline-block; }
        @keyframes spinAnim { to { transform:rotate(360deg); } }
 
        /* ---- Modal activo ---- */
        .modal-overlay { display:none; }
        .modal-overlay.active { display:flex; }
 
        /* ---- Calendar days ---- */
        .calendar-day { position:relative; }
        .calendar-day.disponible  { background:#e8f5e9 !important; color:#2e7d32 !important; }
        .calendar-day.poca        { background:#fff8e1 !important; color:#f57f17 !important; }
        .calendar-day.ocupado     { background:#ffebee !important; color:#c62828 !important; }
        .calendar-day.today       { font-weight:700; border:2px solid var(--primary, #20a89e); }
        .calendar-day.selected    { background:var(--primary, #20a89e) !important; color:#fff !important; }
        .day-dot {
            position:absolute; bottom:3px; left:50%; transform:translateX(-50%);
            width:5px; height:5px; border-radius:50%;
            background:currentColor; opacity:.7;
        }
 
        /* ---- Cita Card ---- */
        .cita-card {
            display:grid; grid-template-columns:60px 1fr auto;
            gap:12px; align-items:center;
            background:#fff; border:1px solid #e9ecef;
            border-radius:10px; padding:14px 16px; margin-bottom:10px;
            transition:box-shadow .2s, transform .2s;
        }
        .cita-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.1); transform:translateY(-1px); }
        .cita-hora { font-size:16px; font-weight:700; color:var(--primary, #20a89e); text-align:center; }
        .cita-paciente   { font-weight:600; font-size:14px; margin-bottom:3px; }
        .cita-especialista, .cita-motivo { font-size:13px; color:#6c757d; }
        .cita-especialista i, .cita-motivo i { margin-right:4px; }
        .cita-acciones { display:flex; flex-direction:column; align-items:flex-end; gap:8px; }
        .cita-btns { display:flex; gap:6px; }
 
        /* ---- Icon buttons ---- */
        .btn-icon {
            width:30px; height:30px; border-radius:6px; border:none;
            cursor:pointer; display:flex; align-items:center; justify-content:center;
            font-size:15px; transition:opacity .2s;
        }
        .btn-icon:hover { opacity:.8; }
        .btn-icon-primary { background:#e3f2fd; color:#1565c0; }
        .btn-icon-warning { background:#fff8e1; color:#e65100; }
        .btn-icon-danger  { background:#ffebee; color:#c62828; }
 
        /* ---- Empty state ---- */
        .citas-empty {
            text-align:center; padding:40px 20px; color:#adb5bd;
        }
        .citas-empty i { font-size:48px; display:block; margin-bottom:12px; }
        .citas-empty p { font-size:15px; font-weight:600; margin:0 0 6px; color:#6c757d; }
        .citas-empty small { font-size:13px; }
 
        /* ---- Detalle ---- */
        .detalle-grid { display:flex; flex-direction:column; gap:10px; margin-bottom:20px; }
        .detalle-row  { display:flex; justify-content:space-between; align-items:center;
                        padding:8px 12px; background:#f8f9fa; border-radius:6px; }
        .detalle-label { font-size:13px; color:#6c757d; }
        .detalle-label i { margin-right:6px; }
        .detalle-value { font-size:13px; font-weight:600; color:#212529; }
 
        /* ---- Cambiar estatus rápido ---- */
        .detalle-cambiar-estatus { border-top:1px solid #e9ecef; padding-top:16px; }
        .detalle-cambiar-estatus .form-label { font-size:13px; color:#6c757d; margin-bottom:10px; display:block; }
        .estatus-btns { display:flex; gap:8px; flex-wrap:wrap; }
        .btn-estatus {
            padding:6px 14px; border-radius:6px; border:2px solid #dee2e6;
            background:#fff; cursor:pointer; font-size:13px; font-weight:500;
            transition:all .2s; color:#495057;
        }
        .btn-estatus:hover, .btn-estatus.active {
            background:var(--primary, #20a89e); color:#fff; border-color:var(--primary, #20a89e);
        }
 
        /* ---- Badges ---- */
        .badge { padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap; }
        .badge-success   { background:#e8f5e9; color:#2e7d32; }
        .badge-warning   { background:#fff8e1; color:#e65100; }
        .badge-danger    { background:#ffebee; color:#c62828; }
        .badge-info      { background:#e3f2fd; color:#1565c0; }
        .badge-primary   { background:#e8eaf6; color:#283593; }
        .badge-pagada    { background:#f3e5f5; color:#6a1b9a; }
        .badge-secondary { background:#f1f3f5; color:#495057; }
 
        /* ---- Alert conflicto ---- */
        .form-alert {
            display:flex; align-items:center; gap:10px;
            padding:12px 16px; background:#fff8e1; border:1px solid #ffe082;
            border-radius:8px; color:#e65100; font-size:14px; margin-top:12px;
        }
        .form-alert i { font-size:18px; flex-shrink:0; }
 
        /* ---- Confirm modal ---- */
        .confirm-content { text-align:center; padding:10px; }
        .confirm-icon { font-size:52px; color:#dc3545; margin-bottom:12px; }
        .confirm-text { font-size:16px; font-weight:600; color:#212529; margin-bottom:6px; }
        .confirm-subtext { font-size:14px; color:#6c757d; }
    `;
    document.head.appendChild(style);
}
 
// ─────────────────────────────────────────────
//  INICIALIZACIÓN
// ─────────────────────────────────────────────
 
async function init() {
    injectStyles();
 
    // Cargar catálogos para los selects del modal
    await cargarCatalogos();
 
    // Cargar días con citas del mes actual y renderizar calendario
    const hoy = new Date();
    await cargarDiasConCitas(hoy.getMonth() + 1, hoy.getFullYear());
    renderCalendario();
 
    // Seleccionar hoy automáticamente
    await seleccionarFecha(hoy);
 
    // ── Navegación de mes
    $('btnPrevMes')?.addEventListener('click', () => navegarMes(-1));
    $('btnNextMes')?.addEventListener('click', () => navegarMes(1));
 
    // ── Botón nueva cita
    $('btnNuevaCita')?.addEventListener('click', abrirModalNueva);
 
    // ── Filtros
    initFiltros();
 
    // ── Modal cita — cerrar
    $('btnCerrarModal')?.addEventListener('click',   () => cerrarModal('modalCita'));
    $('btnCancelarModal')?.addEventListener('click', () => cerrarModal('modalCita'));
 
    // ── Modal cita — guardar
    $('btnGuardarCita')?.addEventListener('click', guardarCita);
 
    // ── Modal detalle — cerrar
    $('btnCerrarDetalle')?.addEventListener('click',  () => cerrarModal('modalDetalle'));
    $('btnCerrarDetalle2')?.addEventListener('click', () => cerrarModal('modalDetalle'));
 
    // ── Modal eliminar — cerrar / confirmar
    $('btnCerrarEliminar')?.addEventListener('click',   () => cerrarModal('modalEliminar'));
    $('btnCancelarEliminar')?.addEventListener('click', () => cerrarModal('modalEliminar'));
    $('btnConfirmarEliminar')?.addEventListener('click', eliminarCita);
 
    console.log('Módulo Citas inicializado');
}
 
// Arrancar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}