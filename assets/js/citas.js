/**
 * Módulo de Citas - JavaScript
 * Sistema Maxilofacial Texcoco
 */

// ==================== ESTADO GLOBAL ====================
const CitasApp = {
    fechaSeleccionada: new Date(),
    mesActual: new Date().getMonth() + 1,
    anioActual: new Date().getFullYear(),
    citasFecha: [],
    citasFiltradas: [],
    filtroActivo: 'todas',
    citaEditandoId: null,
    diasConCitas: {},
    apiUrl: '../controllers/CitasController.php'
};

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', () => {
    inicializarEventos();
    cargarSelects();
    renderCalendario(CitasApp.mesActual, CitasApp.anioActual);
    seleccionarFecha(new Date());
});

// ==================== CALENDARIO ====================

async function renderCalendario(mes, anio) {
    const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                   'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    document.getElementById('calendarTitle').textContent = `${meses[mes - 1]} ${anio}`;

    await cargarDiasConCitas(mes, anio);

    const body = document.getElementById('calendarBody');
    body.innerHTML = '';

    const primerDia   = new Date(anio, mes - 1, 1).getDay();
    const diasEnMes   = new Date(anio, mes, 0).getDate();
    const hoy         = new Date();
    const fechaSelec  = CitasApp.fechaSeleccionada;

    // Espacios vacíos
    for (let i = 0; i < primerDia; i++) {
        const el = document.createElement('div');
        el.className = 'calendar-day empty';
        body.appendChild(el);
    }

    // Días del mes
    for (let dia = 1; dia <= diasEnMes; dia++) {
        const el = document.createElement('div');
        el.className = 'calendar-day';
        el.textContent = dia;

        const esHoy = hoy.getDate() === dia &&
                      hoy.getMonth() + 1 === mes &&
                      hoy.getFullYear() === anio;

        const esSeleccionado = fechaSelec.getDate() === dia &&
                               fechaSelec.getMonth() + 1 === mes &&
                               fechaSelec.getFullYear() === anio;

        if (esHoy) el.classList.add('today');
        if (esSeleccionado) el.classList.add('selected');

        // Disponibilidad
        const datosDia = CitasApp.diasConCitas[dia];
        if (datosDia) {
            const total = parseInt(datosDia.total);
            if (total >= 8) el.classList.add('no-disponible');
            else if (total >= 4) el.classList.add('poca-disponibilidad');
            else el.classList.add('disponible');
        }

        el.addEventListener('click', () => {
            seleccionarFecha(new Date(anio, mes - 1, dia));
        });

        body.appendChild(el);
    }
}

async function cargarDiasConCitas(mes, anio) {
    try {
        const resp = await fetch(`${CitasApp.apiUrl}?action=dias_con_citas&mes=${mes}&anio=${anio}`);
        const data = await resp.json();
        CitasApp.diasConCitas = data.success ? data.data : {};
    } catch (e) {
        CitasApp.diasConCitas = {};
    }
}

function seleccionarFecha(fecha) {
    CitasApp.fechaSeleccionada = fecha;

    const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('citasFechaSeleccionada').textContent =
        fecha.toLocaleDateString('es-MX', opciones);

    renderCalendario(CitasApp.mesActual, CitasApp.anioActual);
    cargarCitasDia(formatFecha(fecha));
}

function formatFecha(fecha) {
    const y = fecha.getFullYear();
    const m = String(fecha.getMonth() + 1).padStart(2, '0');
    const d = String(fecha.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

// ==================== CITAS DEL DÍA ====================

async function cargarCitasDia(fecha) {
    const lista = document.getElementById('citasLista');
    lista.innerHTML = `
        <div class="citas-loading">
            <i class="ri-loader-4-line spin"></i>
            <span>Cargando citas...</span>
        </div>`;
    try {
        const resp = await fetch(`${CitasApp.apiUrl}?action=index&fecha=${fecha}`);
        const data = await resp.json();
        CitasApp.citasFecha = data.success ? data.data : [];
        aplicarFiltro(CitasApp.filtroActivo);
    } catch (e) {
        lista.innerHTML = `
            <div class="citas-empty">
                <i class="ri-wifi-off-line"></i>
                <span>Error al cargar las citas</span>
            </div>`;
    }
}

function aplicarFiltro(filtro) {
    CitasApp.filtroActivo = filtro;
    CitasApp.citasFiltradas = filtro === 'todas'
        ? CitasApp.citasFecha
        : CitasApp.citasFecha.filter(c => c.estatus.toLowerCase() === filtro.toLowerCase());
    renderCitas(CitasApp.citasFiltradas);
}

function renderCitas(citas) {
    const lista = document.getElementById('citasLista');

    if (!citas.length) {
        lista.innerHTML = `
            <div class="citas-empty">
                <i class="ri-calendar-close-line"></i>
                <span>No hay citas para este día</span>
            </div>`;
        return;
    }

    lista.innerHTML = '';
    citas.forEach(c => lista.appendChild(crearCitaCard(c)));
}

function crearCitaCard(cita) {
    const estatus = (cita.estatus || 'pendiente').toLowerCase();
    const hora    = cita.hora_inicio ? cita.hora_inicio.substring(0, 5) : '--:--';

    const card = document.createElement('div');
    card.className = `cita-card ${estatus}`;
    card.innerHTML = `
        <div class="cita-card-header">
            <div class="cita-hora">
                <i class="ri-time-line"></i>
                ${hora}
                <span class="cita-duracion">${cita.duracion_aproximada ?? 60} MIN</span>
            </div>
            <span class="estatus-badge ${estatus}">${cita.estatus}</span>
        </div>
        <div class="cita-card-body">
            <div class="cita-paciente">${cita.nombre_paciente ?? 'Paciente no encontrado'}</div>
            <div class="cita-motivo">${cita.motivo_consulta ?? ''}</div>
        </div>
        <div class="cita-card-footer">
            <span class="cita-especialista">
                <i class="ri-stethoscope-line"></i>
                ${cita.nombre_especialista ?? 'Sin asignar'}
            </span>
            <div class="cita-actions">
                <button class="cita-action-btn view" title="Ver detalle"
                    onclick="event.stopPropagation(); verDetalle(${cita.id_cita})">
                    <i class="ri-eye-line"></i>
                </button>
                <button class="cita-action-btn edit" title="Editar"
                    onclick="event.stopPropagation(); abrirEditar(${cita.id_cita})">
                    <i class="ri-edit-line"></i>
                </button>
                <button class="cita-action-btn delete" title="Eliminar"
                    onclick="event.stopPropagation(); confirmarEliminar(${cita.id_cita})">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>`;

    card.addEventListener('click', () => verDetalle(cita.id_cita));
    return card;
}

// ==================== SELECTS ====================

async function cargarSelects() {
    try {
        // Pacientes
        const rP = await fetch(`${CitasApp.apiUrl}?action=get_pacientes`);
        const dP = await rP.json();
        if (dP.success) llenarSelect('selectPaciente', dP.data, 'numero_paciente', 'nombre_completo');

        // Especialistas
        const rE = await fetch(`${CitasApp.apiUrl}?action=get_especialistas`);
        const dE = await rE.json();
        if (dE.success) llenarSelect('selectEspecialista', dE.data, 'id_especialista', 'nombre_completo');

        // Motivos
        const rM = await fetch(`${CitasApp.apiUrl}?action=get_motivos`);
        const dM = await rM.json();
        if (dM.success) llenarSelect('selectMotivo', dM.data, 'id_motivo_consulta', 'motivo_consulta');

    } catch (e) {
        console.error('Error al cargar selects:', e);
    }
}

function llenarSelect(selectId, items, valueKey, labelKey) {
    const select = document.getElementById(selectId);
    items.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item[valueKey];
        opt.textContent = item[labelKey];
        select.appendChild(opt);
    });
}

// ==================== MODAL NUEVA CITA ====================

function abrirNuevaCita() {
    CitasApp.citaEditandoId = null;
    document.getElementById('modalTitle').textContent = 'Nueva Cita';
    document.getElementById('formCita').reset();
    document.getElementById('groupEstatus').style.display = 'none';
    document.getElementById('alertConflicto').style.display = 'none';
    document.getElementById('inputFecha').value = formatFecha(CitasApp.fechaSeleccionada);
    abrirModal('modalCita');
}

async function abrirEditar(id) {
    try {
        const resp = await fetch(`${CitasApp.apiUrl}?action=show&id=${id}`);
        const data = await resp.json();

        if (!data.success) { showToast('Error al cargar la cita', 'error'); return; }

        const c = data.data;
        CitasApp.citaEditandoId = id;

        document.getElementById('modalTitle').textContent = 'Editar Cita';
        document.getElementById('citaId').value              = c.id_cita;
        document.getElementById('selectPaciente').value      = c.id_paciente;
        document.getElementById('selectEspecialista').value  = c.id_especialista;
        document.getElementById('selectMotivo').value        = c.id_motivo_consulta;
        document.getElementById('selectTipoPaciente').value  = c.tipoPaciente;
        document.getElementById('inputFecha').value          = c.fecha_cita;
        document.getElementById('inputHora').value           = c.hora_inicio ? c.hora_inicio.substring(0, 5) : '';
        document.getElementById('selectDuracion').value      = c.duracion_aproximada ?? 60;
        document.getElementById('inputCosto').value          = c.costo_estimado ?? '';
        document.getElementById('selectEstatus').value       = c.estatus;
        document.getElementById('groupEstatus').style.display  = 'block';
        document.getElementById('alertConflicto').style.display = 'none';

        cerrarModal('modalDetalle');
        abrirModal('modalCita');
    } catch (e) {
        showToast('Error al cargar la cita', 'error');
    }
}

async function guardarCita() {
    const form  = document.getElementById('formCita');
    const alert = document.getElementById('alertConflicto');
    const btn   = document.getElementById('btnGuardarCita');

    if (!form.checkValidity()) { form.reportValidity(); return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line spin"></i> Guardando...';
    alert.style.display = 'none';

    const data = Object.fromEntries(new FormData(form).entries());

    try {
        const url = CitasApp.citaEditandoId
            ? `${CitasApp.apiUrl}?action=update&id=${CitasApp.citaEditandoId}`
            : `${CitasApp.apiUrl}?action=store`;

        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data).toString()
        });

        const result = await resp.json();

        if (result.success) {
            cerrarModal('modalCita');
            showToast(result.message, 'success');
            await cargarCitasDia(formatFecha(CitasApp.fechaSeleccionada));
            await renderCalendario(CitasApp.mesActual, CitasApp.anioActual);
        } else {
            alert.style.display = 'flex';
            document.getElementById('alertMessage').textContent = result.message;
        }
    } catch (e) {
        showToast('Error al guardar la cita', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-save-line"></i> Guardar Cita';
    }
}

// ==================== MODAL DETALLE ====================

async function verDetalle(id) {
    try {
        const resp = await fetch(`${CitasApp.apiUrl}?action=show&id=${id}`);
        const data = await resp.json();
        if (!data.success) { showToast('Error al cargar el detalle', 'error'); return; }

        const c       = data.data;
        const hora    = c.hora_inicio ? c.hora_inicio.substring(0, 5) : '--:--';
        const estatus = (c.estatus || '').toLowerCase();

        document.getElementById('detalleContent').innerHTML = `
            <div class="detalle-grid">
                <div class="detalle-item full">
                    <span class="detalle-label">Paciente</span>
                    <span class="detalle-value">${c.nombre_paciente ?? '-'}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Fecha</span>
                    <span class="detalle-value">${formatFechaDisplay(c.fecha_cita)}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Hora</span>
                    <span class="detalle-value">${hora} (${c.duracion_aproximada ?? 60} min)</span>
                </div>
                <div class="detalle-item full">
                    <span class="detalle-label">Especialista</span>
                    <span class="detalle-value">${c.nombre_especialista ?? '-'}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Motivo</span>
                    <span class="detalle-value">${c.motivo_consulta ?? '-'}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Tipo de Paciente</span>
                    <span class="detalle-value">${c.tipoPaciente ?? '-'}</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Estatus</span>
                    <span class="detalle-value">
                        <span class="estatus-badge ${estatus}">${c.estatus}</span>
                    </span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Costo Estimado</span>
                    <span class="detalle-value">
                        ${c.costo_estimado ? '$' + parseFloat(c.costo_estimado).toFixed(2) : 'No especificado'}
                    </span>
                </div>
            </div>`;

        document.getElementById('btnEditarDesdeDetalle').onclick = () => abrirEditar(id);
        abrirModal('modalDetalle');
    } catch (e) {
        showToast('Error al cargar el detalle', 'error');
    }
}

function formatFechaDisplay(fecha) {
    if (!fecha) return '-';
    const [y, m, d] = fecha.split('-');
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    return `${d} ${meses[parseInt(m) - 1]} ${y}`;
}

// ==================== ELIMINAR CITA ====================

let citaAEliminarId = null;

function confirmarEliminar(id) {
    citaAEliminarId = id;
    abrirModal('modalEliminar');
}

async function eliminarCita() {
    if (!citaAEliminarId) return;

    const btn = document.getElementById('btnConfirmarEliminar');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line spin"></i> Eliminando...';

    try {
        const resp = await fetch(`${CitasApp.apiUrl}?action=destroy&id=${citaAEliminarId}`, {
            method: 'POST'
        });
        const data = await resp.json();

        if (data.success) {
            cerrarModal('modalEliminar');
            showToast(data.message, 'success');
            await cargarCitasDia(formatFecha(CitasApp.fechaSeleccionada));
            await renderCalendario(CitasApp.mesActual, CitasApp.anioActual);
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('Error al eliminar la cita', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-delete-bin-line"></i> Eliminar';
        citaAEliminarId = null;
    }
}

// ==================== MODALES ====================

function abrirModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('active');
    // Restaurar scroll solo si no hay otros modales abiertos
    const abiertos = document.querySelectorAll('.modal-overlay.active');
    if (!abiertos.length) document.body.style.overflow = '';
}

// ==================== EVENTOS ====================

function inicializarEventos() {
    // Nueva cita
    document.getElementById('btnNuevaCita').addEventListener('click', abrirNuevaCita);

    // Guardar
    document.getElementById('btnGuardarCita').addEventListener('click', guardarCita);

    // Cerrar modales
    const cerrarPares = [
        ['btnCerrarModal',    'modalCita'],
        ['btnCancelarModal',  'modalCita'],
        ['btnCerrarDetalle',  'modalDetalle'],
        ['btnCerrarDetalle2', 'modalDetalle'],
        ['btnCerrarEliminar', 'modalEliminar'],
        ['btnCancelarEliminar','modalEliminar'],
    ];
    cerrarPares.forEach(([btnId, modalId]) => {
        document.getElementById(btnId).addEventListener('click', () => cerrarModal(modalId));
    });

    // Confirmar eliminar
    document.getElementById('btnConfirmarEliminar').addEventListener('click', eliminarCita);

    // Click fuera del modal
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) cerrarModal(overlay.id);
        });
    });

    // ESC cierra modales
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active')
                .forEach(m => cerrarModal(m.id));
        }
    });

    // Navegación del calendario
    document.getElementById('btnPrevMes').addEventListener('click', () => {
        CitasApp.mesActual--;
        if (CitasApp.mesActual < 1) { CitasApp.mesActual = 12; CitasApp.anioActual--; }
        renderCalendario(CitasApp.mesActual, CitasApp.anioActual);
    });

    document.getElementById('btnNextMes').addEventListener('click', () => {
        CitasApp.mesActual++;
        if (CitasApp.mesActual > 12) { CitasApp.mesActual = 1; CitasApp.anioActual++; }
        renderCalendario(CitasApp.mesActual, CitasApp.anioActual);
    });

    // Filtros de estatus
    document.querySelectorAll('.filtro-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            aplicarFiltro(btn.dataset.estatus);
        });
    });
}

// ==================== TOAST ====================

function showToast(message, type = 'info') {
    const icons = {
        success: 'ri-checkbox-circle-line',
        error:   'ri-close-circle-line',
        warning: 'ri-error-warning-line',
        info:    'ri-information-line'
    };

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<i class="${icons[type] || icons.info}"></i><span>${message}</span>`;
    document.getElementById('toastContainer').appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}