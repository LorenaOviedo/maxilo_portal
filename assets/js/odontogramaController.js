/**
 * CONTROLADOR - OdontogramaController.js
 * Sistema Maxilofacial Texcoco
 *
 * Responsabilidad: lógica de negocio, estado reactivo Vue y
 * comunicación con el servidor.
 * Depende de: OdontogramaModel.js (debe cargarse antes)
 * Se monta sobre: #tabOdontograma  (dentro del modal de paciente)
 */

const OdontogramaController = {

  // ─────────────────────────────────────────
  // Instancia Vue activa (para poder destruirla
  // si el modal se cierra y se reabre)
  // ─────────────────────────────────────────
  _appInstance: null,

  // ─────────────────────────────────────────
  // PUNTO DE ENTRADA
  // Llamar desde modal_paciente.php al abrir
  // el tab de odontograma:
  // OdontogramaController.montar(numeroPaciente)
  // ─────────────────────────────────────────

  /**
   * Monta la app Vue en el tab de odontograma
   * @param {number|string} numeroPaciente - para cargar datos del servidor
   */
  montar(numeroPaciente) {
    // Evitar montar dos veces
    if (this._appInstance) return;

    const { createApp, ref, computed } = Vue;

    const self = this; // referencia al controlador para usarla dentro del setup

    this._appInstance = createApp({
      setup() {

        // ── Exponer catálogos del Modelo a la Vista ──
        const catalogoAnomalias      = odontogramaModel.catalogoAnomalias;
        const catalogoCaras          = odontogramaModel.catalogoCaras;
        const catalogoProcedimientos = odontogramaModel.catalogoProcedimientos;
        const catalogoEstatus        = odontogramaModel.catalogoEstatus;

        // ── Construir arcadas desde el Modelo ──
        const arcadaSuperior = odontogramaModel.construirArcada(
          odontogramaModel.numerosSuperior, 'Superior'
        );
        const arcadaInferior = odontogramaModel.construirArcada(
          odontogramaModel.numerosInferior, 'Inferior'
        );

        // ── Estado reactivo ──

        // Pieza actualmente seleccionada { numero, nombre, arcada, emoji }
        const dienteActivo = ref(null);

        // Todos los registros del paciente, indexados por número de pieza
        // Estructura: { [numeroPieza]: [ { anomalia, caras, procedimiento, estatus }, ... ] }
        const registros = ref({});

        // Formulario de nuevo registro
        const form = ref(odontogramaModel.registroVacio());

        // Notificación temporal
        const notif = ref({ visible: false, texto: '' });

        // Estado de carga inicial
        const cargando = ref(false);

        // ── Computadas ──

        /** Registros de la pieza actualmente seleccionada */
        const registrosDiente = computed(() =>
          dienteActivo.value
            ? (registros.value[dienteActivo.value.numero] || [])
            : []
        );

        /** Validación del formulario antes de permitir guardar */
        const formularioValido = computed(() =>
          form.value.anomalia.trim() !== '' &&
          form.value.estatus.trim()  !== ''
        );

        // ── Lógica de color por estado ──

        /**
         * Determina el estado visual de un diente según sus registros
         * @param {number} numero
         * @returns {'sano'|'tratado'|'anomalia'|'atencion'}
         */
        function estadoDiente(numero) {
          const regs = registros.value[numero];
          if (!regs || !regs.length)                        return 'sano';
          if (regs.every(r => r.estatus === 'Tratado'))     return 'tratado';
          if (regs.some(r  => r.estatus === 'En proceso'))  return 'anomalia';
          return 'atencion'; // alguno pendiente
        }

        // ── Acciones de la Vista ──

        /** Seleccionar un diente → abre el panel lateral */
        function seleccionarDiente(pieza) {
          dienteActivo.value = pieza;
          form.value = odontogramaModel.registroVacio();
        }

        /** Cerrar panel lateral sin guardar */
        function cancelar() {
          dienteActivo.value = null;
          form.value = odontogramaModel.registroVacio();
        }

        /** Guardar un nuevo registro en la pieza activa */
        async function guardarRegistro() {
          if (!formularioValido.value) return;

          const num = dienteActivo.value.numero;

          const nuevoRegistro = {
            anomalia:      form.value.anomalia,
            caras:         [...form.value.caras],
            procedimiento: form.value.procedimiento,
            estatus:       form.value.estatus,
          };

          // Actualizar estado local inmediatamente (optimistic update)
          if (!registros.value[num]) registros.value[num] = [];
          registros.value[num].push(nuevoRegistro);

          mostrarNotif(`Registro guardado en pieza ${num}`);
          form.value = odontogramaModel.registroVacio();

          // Persistir en el servidor
          await self._guardarEnServidor(numeroPaciente, num, nuevoRegistro);
        }

        /** Eliminar un registro por índice de la pieza activa */
        async function eliminarRegistro(idx) {
          if (!confirm('¿Eliminar este registro?')) return;

          const num = dienteActivo.value.numero;
          const registro = registros.value[num][idx];

          registros.value[num].splice(idx, 1);
          if (!registros.value[num].length) delete registros.value[num];

          mostrarNotif('Registro eliminado');

          // Persistir eliminación en el servidor
          await self._eliminarEnServidor(numeroPaciente, num, registro);
        }

        /** Mostrar notificación temporal */
        function mostrarNotif(texto) {
          notif.value = { visible: true, texto };
          setTimeout(() => { notif.value.visible = false; }, 2500);
        }

        // ── Carga inicial de datos del paciente ──
        self._cargarRegistros(numeroPaciente, registros, cargando);

        // ── Exponer a la Vista ──
        return {
          // Catálogos
          catalogoAnomalias,
          catalogoCaras,
          catalogoProcedimientos,
          catalogoEstatus,
          // Arcadas
          arcadaSuperior,
          arcadaInferior,
          // Estado
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

  /**
   * Desmonta la instancia Vue (llamar al cerrar el modal)
   */
  desmontar() {
    if (this._appInstance) {
      this._appInstance.unmount();
      this._appInstance = null;
    }
  },

  // ─────────────────────────────────────────
  // COMUNICACIÓN CON EL SERVIDOR
  // Endpoints pendientes de implementar en PHP
  // ─────────────────────────────────────────

  /**
   * Carga los registros del odontograma desde el servidor
   * @param {number}  numeroPaciente
   * @param {Ref}     registros  - ref reactiva donde se cargan los datos
   * @param {Ref}     cargando   - ref booleana de estado de carga
   */
  async _cargarRegistros(numeroPaciente, registros, cargando) {
    if (!numeroPaciente) return;

    try {
      cargando.value = true;

      const response = await fetch(
        `api/odontograma.php?action=getByPaciente&numero_paciente=${numeroPaciente}`
      );

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();

      if (data.success) {
        // El servidor devuelve: { success: true, registros: { [numeroPieza]: [...] } }
        registros.value = data.registros;
      } else {
        console.warn('odontogramaController: no se pudieron cargar registros', data.message);
      }
    } catch (err) {
      // En desarrollo sin servidor activo, silenciar el error
      console.info('odontogramaController: servidor no disponible, modo local activo');
    } finally {
      cargando.value = false;
    }
  },

  /**
   * Persiste un nuevo registro en el servidor
   * @param {number} numeroPaciente
   * @param {number} numeroPieza
   * @param {object} registro
   */
  async _guardarEnServidor(numeroPaciente, numeroPieza, registro) {
    try {
      const response = await fetch('api/odontograma.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action:          'guardar',
          numero_paciente: numeroPaciente,
          numero_pieza:    numeroPieza,
          ...registro,
        }),
      });

      const data = await response.json();

      if (!data.success) {
        console.error('odontogramaController: error al guardar', data.message);
        CatalogTable.showNotification('Error al guardar en servidor', 'error');
      }
    } catch (err) {
      console.info('odontogramaController: guardado solo en local (servidor no disponible)');
    }
  },

  /**
   * Elimina un registro en el servidor
   * @param {number} numeroPaciente
   * @param {number} numeroPieza
   * @param {object} registro
   */
  async _eliminarEnServidor(numeroPaciente, numeroPieza, registro) {
    try {
      const response = await fetch('api/odontograma.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action:          'eliminar',
          numero_paciente: numeroPaciente,
          numero_pieza:    numeroPieza,
          ...registro,
        }),
      });

      const data = await response.json();

      if (!data.success) {
        console.error('odontogramaController: error al eliminar', data.message);
      }
    } catch (err) {
      console.info('odontogramaController: eliminación solo en local');
    }
  },
};