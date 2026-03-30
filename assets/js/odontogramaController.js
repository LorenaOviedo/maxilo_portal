/**
 * CONTROLADOR - odontogramaController.js
 *
 * Responsabilidad: lógica de negocio, estado reactivo Vue y
 * comunicación con el servidor.
 *
 * Depende de: odontogramaModel.js (debe cargarse antes en el HTML)
 * Se monta sobre: #app-odontograma (dentro del tab del modal de paciente)
 */

const odontogramaController = {

  _appInstance: null,

  /**
   * Monta la app Vue en #app-odontograma.
   * @param {number|string} numeroPaciente
   */
  montar(numeroPaciente) {
    if (this._appInstance) return;

    const { createApp, ref, computed } = Vue;
    const self = this;

    this._appInstance = createApp({
      setup() {

        // ── Catálogos (se pueblan al cargar) ───────────────────────────────
        const catalogoAnomalias      = ref([]);
        const catalogoCaras          = ref([]);
        const catalogoProcedimientos = ref([]);
        const catalogoEstatus        = ref([]);
        const catalogoEspecialistas  = ref([]);
        const cargandoCatalogos      = ref(true);

        // ── Arcadas (estáticas) ────────────────────────────────────────────
        const arcadaSuperior = odontogramaModel.construirArcada(
          odontogramaModel.numerosSuperior, 'Superior'
        );
        const arcadaInferior = odontogramaModel.construirArcada(
          odontogramaModel.numerosInferior, 'Inferior'
        );

        // ── Estado reactivo ────────────────────────────────────────────────

        /**
         * Especialista elegido en el formulario principal.
         * Se comparte para todos los registros de la sesión de hoy.
         */
        const idEspecialistaSeleccionado = ref(null);

        /** Pieza activa { numero, nombre, arcada, icono } */
        const dienteActivo = ref(null);

        /**
         * Registros del paciente indexados por número FDI de pieza.
         * Estructura devuelta por getByPaciente():
         * { "11": [{ id_odontograma, nombre_anomalia, cara, ... }], ... }
         */
        const registros = ref({});

        /** Formulario del panel de pieza (IDs de BD) */
        const form = ref(odontogramaModel.registroVacio());

        /** Notificación flotante temporal */
        const notif = ref({ visible: false, texto: '', tipo: 'success' });

        /** true durante la carga inicial de registros del paciente */
        const cargando = ref(false);

        // ── Computadas ─────────────────────────────────────────────────────

        /** Registros de la pieza activa */
        const registrosDiente = computed(() =>
          dienteActivo.value
            ? (registros.value[dienteActivo.value.numero] ?? [])
            : []
        );

        /**
         * Formulario válido cuando:
         *  • hay especialista seleccionado
         *  • hay anomalía
         *  • hay al menos una cara
         *  • hay procedimiento (obligatorio en BD)
         *  • hay estatus
         */
        const formularioValido = computed(() =>
          !!idEspecialistaSeleccionado.value &&
          !!form.value.id_anomalia           &&
          form.value.id_caras.length > 0     &&
          !!form.value.id_procedimiento      &&
          !!form.value.id_estatus
        );

        // ── Color visual de diente ─────────────────────────────────────────

        /**
         * @param {number} numero
         * @returns {'sano'|'tratado'|'anomalia'|'atencion'}
         */
        function estadoDiente(numero) {
          const regs = registros.value[numero];
          if (!regs || !regs.length)                                    return 'sano';
          if (regs.every(r => r.estatus_tratamiento === 'Tratado'))     return 'tratado';
          if (regs.some(r  => r.estatus_tratamiento === 'En proceso'))  return 'anomalia';
          return 'atencion'; // alguno pendiente
        }

        // ── Acciones ───────────────────────────────────────────────────────

        function seleccionarDiente(pieza) {
          dienteActivo.value = pieza;
          form.value = odontogramaModel.registroVacio();
        }

        function cancelar() {
          dienteActivo.value = null;
          form.value = odontogramaModel.registroVacio();
        }

        /**
         * Guarda el hallazgo de la pieza activa.
         * Hace optimistic-update local y luego persiste en servidor.
         * Si el servidor falla, revierte el cambio local.
         */
        async function guardarRegistro() {
          if (!formularioValido.value) return;

          const num = dienteActivo.value.numero;

          // Copia del form antes de resetearlo
          const payload = {
            id_anomalia:      form.value.id_anomalia,
            id_caras:         [...form.value.id_caras],
            id_procedimiento: form.value.id_procedimiento,
            id_estatus:       form.value.id_estatus,
          };

          // Optimistic-update: construir fila local por cada cara seleccionada
          // (igual que lo hace el servidor: 1 row Odontograma por cara)
          const filasLocales = payload.id_caras.map(idCara => ({
            id_odontograma:         null,   // aún no tenemos el id real
            numero_posicion:        num,
            id_anomalia_dental:     payload.id_anomalia,
            nombre_anomalia:        odontogramaModel.nombreAnomalia(payload.id_anomalia),
            id_cara_dental:         idCara,
            cara:                   odontogramaModel.nombresCaras([idCara]),
            id_procedimiento:       payload.id_procedimiento,
            nombre_procedimiento:   odontogramaModel.nombreProcedimiento(payload.id_procedimiento),
            id_estatus_tratamiento: payload.id_estatus,
            estatus_tratamiento:    odontogramaModel.nombreEstatus(payload.id_estatus),
            fecha_cita:             new Date().toISOString().slice(0, 10),
            id_especialista:        idEspecialistaSeleccionado.value,
            nombre_especialista:    odontogramaModel.nombreEspecialista(idEspecialistaSeleccionado.value),
            _pendiente:             true,
          }));

          if (!registros.value[num]) registros.value[num] = [];
          registros.value[num].unshift(...filasLocales);

          mostrarNotif('Guardando...', 'info');
          form.value = odontogramaModel.registroVacio();

          // Persistir en servidor
          const resultado = await self._guardarEnServidor({
            numero_paciente:  numeroPaciente,
            id_especialista:  idEspecialistaSeleccionado.value,
            numero_pieza:     num,
            id_anomalia:      payload.id_anomalia,
            id_caras:         payload.id_caras,
            id_procedimiento: payload.id_procedimiento,
          });

          if (resultado?.success) {
            // Recargar para obtener ids_odontograma reales del servidor
            await self._cargarRegistros(numeroPaciente, registros, ref(false));
            mostrarNotif(`Pieza ${num} registrada correctamente`, 'success');
          } else {
            // Revertir optimistic-update
            registros.value[num] = (registros.value[num] ?? []).filter(r => !r._pendiente);
            if (!registros.value[num].length) delete registros.value[num];
            mostrarNotif(resultado?.message ?? 'Error al guardar', 'error');
          }
        }

        /**
         * Elimina un registro por su índice en el array de la pieza activa.
         * El servidor limpia también la transacción y la cita si quedan vacías.
         * @param {number} idx
         */
        async function eliminarRegistro(idx) {
          if (!confirm('¿Eliminar este registro?')) return;

          const num  = dienteActivo.value.numero;
          const fila = registros.value[num]?.[idx];
          if (!fila) return;

          // Optimistic remove
          registros.value[num].splice(idx, 1);
          if (!registros.value[num].length) delete registros.value[num];

          const resultado = await self._eliminarEnServidor(
            fila.id_odontograma,
            numeroPaciente
          );

          if (!resultado?.success) {
            // Revertir recargando desde servidor
            await self._cargarRegistros(numeroPaciente, registros, ref(false));
            mostrarNotif(resultado?.message ?? 'Error al eliminar', 'error');
          } else {
            mostrarNotif('Registro eliminado', 'success');
          }
        }

        function mostrarNotif(texto, tipo = 'success') {
          notif.value = { visible: true, texto, tipo };
          setTimeout(() => { notif.value.visible = false; }, 2800);
        }

        // ── Carga inicial ──────────────────────────────────────────────────
        (async () => {
          // 1. Catálogos desde BD
          await odontogramaModel.cargarCatalogos();
          catalogoAnomalias.value      = odontogramaModel.catalogoAnomalias;
          catalogoCaras.value          = odontogramaModel.catalogoCaras;
          catalogoProcedimientos.value = odontogramaModel.catalogoProcedimientos;
          catalogoEstatus.value        = odontogramaModel.catalogoEstatus;
          catalogoEspecialistas.value  = odontogramaModel.catalogoEspecialistas;
          cargandoCatalogos.value      = false;

          // 2. Registros del paciente
          await self._cargarRegistros(numeroPaciente, registros, cargando);
        })();

        // ── Exponer a la Vista ─────────────────────────────────────────────
        return {
          // Catálogos
          catalogoAnomalias,
          catalogoCaras,
          catalogoProcedimientos,
          catalogoEstatus,
          catalogoEspecialistas,
          cargandoCatalogos,
          // Arcadas
          arcadaSuperior,
          arcadaInferior,
          // Estado
          idEspecialistaSeleccionado,
          dienteActivo,
          registros,
          registrosDiente,
          form,
          notif,
          cargando,
          formularioValido,
          // Métodos
          estadoDiente,
          seleccionarDiente,
          cancelar,
          guardarRegistro,
          eliminarRegistro,
        };
      },
    }).mount('#app-odontograma');
  },

  desmontar() {
    if (this._appInstance) {
      this._appInstance.unmount();
      this._appInstance = null;
    }
  },

  // ─────────────────────────────────────────────────────────────────────────
  // COMUNICACIÓN CON EL SERVIDOR
  // ─────────────────────────────────────────────────────────────────────────

  async _cargarRegistros(numeroPaciente, registros, cargando) {
    if (!numeroPaciente) return;
    try {
      cargando.value = true;
      const res = await fetch(
        `ajax/api.php?modulo=odontograma&accion=get_by_paciente_odontograma&numero_paciente=${numeroPaciente}`
      );
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      if (data.success) {
        registros.value = data.registros ?? {};
      } else {
        console.warn('odontogramaController: error al cargar —', data.message);
      }
    } catch (err) {
      console.info('odontogramaController: modo local activo —', err.message);
    } finally {
      cargando.value = false;
    }
  },

  /**
   * @param {{ numero_paciente, id_especialista, numero_pieza,
   *            id_anomalia, id_caras, id_procedimiento }} payload
   * @returns {Promise<{success, ...}|null>}
   */
  async _guardarEnServidor(payload) {
    try {
      const res = await fetch(
        'ajax/api.php?modulo=odontograma&accion=guardar_odontograma',
        {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify(payload),
        }
      );
      return await res.json();
    } catch (err) {
      console.error('odontogramaController._guardarEnServidor:', err);
      return null;
    }
  },

  /**
   * @param {number} idOdontograma
   * @param {number} numeroPaciente
   * @returns {Promise<{success, message?}|null>}
   */
  async _eliminarEnServidor(idOdontograma, numeroPaciente) {
    try {
      const res = await fetch(
        'ajax/api.php?modulo=odontograma&accion=eliminar_odontograma',
        {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ id_odontograma: idOdontograma, numero_paciente: numeroPaciente }),
        }
      );
      return await res.json();
    } catch (err) {
      console.error('odontogramaController._eliminarEnServidor:', err);
      return null;
    }
  },
};