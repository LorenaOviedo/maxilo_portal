/**
 * CONTROLADOR DE ODONTOGRAMA
 *
 * Responsabilidad: lógica de negocio, estado reactivo Vue y
 * comunicación con el servidor.
 *
 * Depende de: odontogramaModel.js (debe cargarse antes en el HTML)
 * Se monta sobre: #app-odontograma (dentro del tab del modal de paciente)
 */

const odontogramaController = {
 
    _numeroPaciente: null,
    _catalogos:      null,
    _appInstance:    null,
 
    async cargar(numeroPaciente) {
        const num = parseInt(numeroPaciente);
 
        // Siempre desmontar para garantizar estado limpio
        this._desmontar();
 
        this._numeroPaciente = num;
        await this._inicializar();
 
        // Re-habilitar inputs dentro de #app-odontograma que Modal.setReadOnly()
        // pudo haber deshabilitado al abrir el modal en modo lectura
        document.querySelectorAll('#app-odontograma input, #app-odontograma select, #app-odontograma textarea')
            .forEach(el => { el.disabled = false; });
 
        this._montarVue(num);
    },
 
    limpiar() {
        this._numeroPaciente = null;
        this._desmontar();
    },
 
    async _inicializar() {
        if (this._catalogos) return;
        try {
            const r    = await fetch(`${API_URL}?modulo=odontograma&accion=get_catalogos_odontograma`);
            const data = await r.json();
            if (data.success) this._catalogos = data;
            else console.warn('odontogramaController: catálogos no disponibles', data.message);
        } catch (err) {
            console.warn('odontogramaController: error al cargar catálogos', err);
        }
    },
 
    // _poblarSelects() eliminado — solo poblaba el select de especialista
 
    _poblarSelectsPanel() {
        const selAnom = document.getElementById('odontAnomalia');
        if (selAnom && this._catalogos?.anomalias) {
            selAnom.innerHTML = '<option value="">Seleccionar...</option>' +
                this._catalogos.anomalias.map(a =>
                    `<option value="${a.id}">${escHtml(a.nombre)}</option>`
                ).join('');
        }
 
        const divCaras = document.getElementById('odontCarasGrid');
        if (divCaras && this._catalogos?.caras) {
            divCaras.innerHTML = this._catalogos.caras.map(c => `
                <div>
                    <input type="checkbox" id="cara-od-${c.id}"
                           value="${c.id}" class="cara-check odont-cara-cb">
                    <label for="cara-od-${c.id}" class="cara-label">${escHtml(c.nombre)}</label>
                </div>`).join('');
        }
 
        const selProc = document.getElementById('odontProc');
        if (selProc && this._catalogos?.procedimientos) {
            selProc.innerHTML = '<option value="">Seleccionar...</option>' +
                this._catalogos.procedimientos.map(p =>
                    `<option value="${p.id}">${escHtml(p.nombre)}</option>`
                ).join('');
        }
 
        const selEst = document.getElementById('odontEstatus');
        if (selEst && this._catalogos?.estatus) {
            selEst.innerHTML = '<option value="">Seleccionar...</option>' +
                this._catalogos.estatus.map(e =>
                    `<option value="${e.id}">${escHtml(e.nombre)}</option>`
                ).join('');
        }
    },
 
    _poblarSelectEditarEstatus(idRegistro, idEstatusActual) {
        const sel = document.getElementById(`odontEditarEstatus_${idRegistro}`);
        if (!sel || !this._catalogos?.estatus) return;
        sel.innerHTML = this._catalogos.estatus.map(e =>
            `<option value="${e.id}" ${e.id == idEstatusActual ? 'selected' : ''}>
                ${escHtml(e.nombre)}
             </option>`
        ).join('');
    },
 
    _poblarSelectEditarProcedimiento(idRegistro, idProcedimientoActual) {
        const sel = document.getElementById(`odontEditarProc_${idRegistro}`);
        if (!sel || !this._catalogos?.procedimientos) return;
        sel.innerHTML = this._catalogos.procedimientos.map(p =>
            `<option value="${p.id}" ${p.id == idProcedimientoActual ? 'selected' : ''}>
                ${escHtml(p.nombre)}
             </option>`
        ).join('');
    },
 
    _resetearPanel() {
        ['odontAnomalia', 'odontProc', 'odontEstatus'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        document.querySelectorAll('.odont-cara-cb').forEach(cb => cb.checked = false);
    },
 
    _montarVue(numeroPaciente) {
        if (this._appInstance) {
            try { this._appInstance.unmount(); } catch(e) {}
            this._appInstance = null;
        }
 
        const { createApp, ref, computed } = Vue;
        const self = this;
        const pacienteId = numeroPaciente;
 
        this._appInstance = createApp({
            setup() {
 
                const arcadaSuperior = odontogramaModel.construirArcada(
                    odontogramaModel.numerosSuperior, 'Superior'
                );
                const arcadaInferior = odontogramaModel.construirArcada(
                    odontogramaModel.numerosInferior, 'Inferior'
                );
 
                const dienteActivo          = ref(null);
                const registros             = ref({});
                const cargando              = ref(false);
                const notif                 = ref({ visible: false, texto: '', tipo: 'success' });
                const editandoEstatus       = ref(new Set());
                const editandoProcedimiento = ref(new Set());
 
                const registrosDiente = computed(() =>
                    dienteActivo.value
                        ? (registros.value[dienteActivo.value.numero] ?? [])
                        : []
                );
 
                function estadoDiente(numero) {
                    const regs = registros.value[numero];
                    if (!regs || !regs.length)                                return 'sano';
                    if (regs.every(r => r.estatus_hallazgo === 'Tratado'))    return 'tratado';
                    if (regs.some(r  => r.estatus_hallazgo === 'En proceso')) return 'anomalia';
                    return 'atencion';
                }
 
                function estaEditando(idOdontograma) {
                    return editandoEstatus.value.has(idOdontograma);
                }
 
                function estaEditandoProcedimiento(idOdontograma) {
                    return editandoProcedimiento.value.has(idOdontograma);
                }
 
                function seleccionarDiente(pieza) {
                    dienteActivo.value          = pieza;
                    editandoEstatus.value       = new Set();
                    editandoProcedimiento.value = new Set();
                    self._nextTick(() => self._poblarSelectsPanel());
                }
 
                function cancelar() {
                    dienteActivo.value          = null;
                    editandoEstatus.value       = new Set();
                    editandoProcedimiento.value = new Set();
                }
 
                function toggleEditarEstatus(idOdontograma, idEstatusActual) {
                    const set = new Set(editandoEstatus.value);
                    if (set.has(idOdontograma)) {
                        set.delete(idOdontograma);
                        editandoEstatus.value = set;
                    } else {
                        set.add(idOdontograma);
                        editandoEstatus.value = set;
                        setTimeout(() =>
                            self._poblarSelectEditarEstatus(idOdontograma, idEstatusActual)
                        , 100);
                    }
                }
 
                function toggleEditarProcedimiento(idOdontograma, idProcedimientoActual) {
                    const set = new Set(editandoProcedimiento.value);
                    if (set.has(idOdontograma)) {
                        set.delete(idOdontograma);
                        editandoProcedimiento.value = set;
                    } else {
                        set.add(idOdontograma);
                        editandoProcedimiento.value = set;
                        setTimeout(() =>
                            self._poblarSelectEditarProcedimiento(idOdontograma, idProcedimientoActual)
                        , 100);
                    }
                }
 
                async function guardarEstatus(idOdontograma) {
                    const sel     = document.getElementById(`odontEditarEstatus_${idOdontograma}`);
                    const nuevoId = parseInt(sel?.value || '0');
                    if (!nuevoId) { mostrarNotif('Selecciona un estatus', 'error'); return; }
 
                    const resultado = await self._actualizarEstatusEnServidor(
                        idOdontograma, nuevoId, pacienteId
                    );
 
                    if (resultado?.success) {
                        await self._cargarRegistros(registros, cargando, pacienteId);
                        const set = new Set(editandoEstatus.value);
                        set.delete(idOdontograma);
                        editandoEstatus.value = set;
                        mostrarNotif('Estatus actualizado', 'success');
                    } else {
                        mostrarNotif(resultado?.message ?? 'Error al actualizar', 'error');
                    }
                }
 
                async function guardarProcedimiento(idOdontograma) {
                    const sel     = document.getElementById(`odontEditarProc_${idOdontograma}`);
                    const nuevoId = parseInt(sel?.value || '0');
                    if (!nuevoId) { mostrarNotif('Selecciona un procedimiento', 'error'); return; }
 
                    const resultado = await self._actualizarProcedimientoEnServidor(
                        idOdontograma, nuevoId, pacienteId
                    );
 
                    if (resultado?.success) {
                        await self._cargarRegistros(registros, cargando, pacienteId);
                        const set = new Set(editandoProcedimiento.value);
                        set.delete(idOdontograma);
                        editandoProcedimiento.value = set;
                        mostrarNotif('Procedimiento actualizado', 'success');
                    } else {
                        mostrarNotif(resultado?.message ?? 'Error al actualizar', 'error');
                    }
                }
 
                async function guardarRegistro() {
                    const idAnomalia      = parseInt(document.getElementById('odontAnomalia')?.value  || '0');
                    const idProcedimiento = parseInt(document.getElementById('odontProc')?.value      || '0');
                    const idEstatus       = parseInt(document.getElementById('odontEstatus')?.value   || '0');
                    const numeroPieza     = dienteActivo.value?.numero;
                    const idCaras         = [...document.querySelectorAll('.odont-cara-cb:checked')]
                                            .map(cb => parseInt(cb.value));
 
                    // id_especialista eliminado — ya no se valida ni se envía
                    if (!idAnomalia)      { mostrarNotif('Selecciona una anomalía', 'error');      return; }
                    if (!idCaras.length)  { mostrarNotif('Selecciona al menos una cara', 'error'); return; }
                    if (!idProcedimiento) { mostrarNotif('Selecciona un procedimiento', 'error');  return; }
                    if (!idEstatus)       { mostrarNotif('Selecciona un estatus', 'error');        return; }
 
                    const cat = self._catalogos;
                    const filasLocales = idCaras.map(idCara => ({
                        id_odontograma:       null,
                        numero_posicion:      numeroPieza,
                        nombre_anomalia:      cat.anomalias.find(a => a.id == idAnomalia)?.nombre ?? '',
                        cara:                 cat.caras.find(c => c.id == idCara)?.nombre ?? '',
                        nombre_procedimiento: cat.procedimientos.find(p => p.id == idProcedimiento)?.nombre ?? '',
                        id_estatus_hallazgo:  idEstatus,
                        estatus_hallazgo:     cat.estatus.find(e => e.id == idEstatus)?.nombre ?? '',
                        fecha_cita:           new Date().toISOString().slice(0, 10),
                        _pendiente:           true,
                    }));
 
                    if (!registros.value[numeroPieza]) registros.value[numeroPieza] = [];
                    registros.value[numeroPieza].unshift(...filasLocales);
                    mostrarNotif('Guardando...', 'info');
 
                    const resultado = await self._guardarEnServidor({
                        numero_paciente:     pacienteId,
                        numero_pieza:        numeroPieza,
                        id_anomalia:         idAnomalia,
                        id_caras:            idCaras,
                        id_procedimiento:    idProcedimiento,
                        id_estatus_hallazgo: idEstatus,
                        // id_especialista eliminado
                    });
 
                    if (resultado?.success) {
                        await self._cargarRegistros(registros, cargando, pacienteId);
                        mostrarNotif(`Pieza ${numeroPieza} registrada`, 'success');
                        self._resetearPanel();
                    } else {
                        registros.value[numeroPieza] = (registros.value[numeroPieza] ?? [])
                            .filter(r => !r._pendiente);
                        mostrarNotif(resultado?.message ?? 'Error al guardar', 'error');
                    }
                }
 
                async function eliminarRegistro(idx) {
                    if (!confirm('¿Eliminar este registro?')) return;
                    const num  = dienteActivo.value.numero;
                    const fila = registros.value[num]?.[idx];
                    if (!fila) return;
 
                    registros.value[num].splice(idx, 1);
                    if (!registros.value[num].length) delete registros.value[num];
 
                    const resultado = await self._eliminarEnServidor(
                        fila.id_odontograma, pacienteId
                    );
 
                    if (!resultado?.success) {
                        await self._cargarRegistros(registros, cargando, pacienteId);
                        mostrarNotif(resultado?.message ?? 'Error al eliminar', 'error');
                    } else {
                        mostrarNotif('Registro eliminado', 'success');
                    }
                }
 
                function mostrarNotif(texto, tipo = 'success') {
                    notif.value = { visible: true, texto, tipo };
                    setTimeout(() => { notif.value.visible = false; }, 2800);
                }
 
                self._cargarRegistros(registros, cargando, pacienteId);
 
                return {
                    arcadaSuperior, arcadaInferior,
                    dienteActivo, registros, registrosDiente,
                    cargando, notif,
                    estadoDiente, estaEditando, estaEditandoProcedimiento,
                    seleccionarDiente, cancelar,
                    guardarRegistro, eliminarRegistro,
                    toggleEditarEstatus, guardarEstatus,
                    toggleEditarProcedimiento, guardarProcedimiento,
                };
            },
        });
 
        const appEl = document.getElementById('app-odontograma');
        if (appEl._templateOriginal === undefined) {
            appEl._templateOriginal = appEl.innerHTML;
        }
        appEl.innerHTML = appEl._templateOriginal;
        this._appInstance.mount(appEl);
    },
 
    _nextTick(fn) { setTimeout(fn, 50); },
 
    _desmontar() {
        if (this._appInstance) {
            this._appInstance.unmount();
            this._appInstance = null;
        }
    },
 
    async _cargarRegistros(registros, cargando, numeroPaciente) {
        if (!numeroPaciente) return;
        try {
            cargando.value = true;
            const r = await fetch(
                `${API_URL}?modulo=odontograma&accion=get_by_paciente_odontograma&numero_paciente=${numeroPaciente}`
            );
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            const data = await r.json();
            if (data.success) registros.value = data.registros ?? {};
            else console.warn('odontogramaController:', data.message);
        } catch (err) {
            console.info('odontogramaController: modo local —', err.message);
        } finally {
            cargando.value = false;
        }
    },
 
    async _guardarEnServidor(payload) {
        try {
            const r = await fetch(
                `${API_URL}?modulo=odontograma&accion=guardar_odontograma`,
                { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }
            );
            return await r.json();
        } catch (err) { return null; }
    },
 
    async _actualizarEstatusEnServidor(idOdontograma, idEstatus, numeroPaciente) {
        try {
            const r = await fetch(
                `${API_URL}?modulo=odontograma&accion=actualizar_estatus_odontograma`,
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_odontograma:      idOdontograma,
                        id_estatus_hallazgo: idEstatus,
                        numero_paciente:     numeroPaciente,
                    }),
                }
            );
            return await r.json();
        } catch (err) { return null; }
    },
 
    async _actualizarProcedimientoEnServidor(idOdontograma, idProcedimiento, numeroPaciente) {
        try {
            const r = await fetch(
                `${API_URL}?modulo=odontograma&accion=actualizar_procedimiento_odontograma`,
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_odontograma:   idOdontograma,
                        id_procedimiento: idProcedimiento,
                        numero_paciente:  numeroPaciente,
                    }),
                }
            );
            return await r.json();
        } catch (err) { return null; }
    },
 
    async _eliminarEnServidor(idOdontograma, numeroPaciente) {
        try {
            const r = await fetch(
                `${API_URL}?modulo=odontograma&accion=eliminar_odontograma`,
                { method: 'POST', headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({ id_odontograma: idOdontograma, numero_paciente: numeroPaciente }) }
            );
            return await r.json();
        } catch (err) { return null; }
    },
};