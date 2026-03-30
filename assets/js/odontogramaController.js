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
  _numeroPaciente: null,
  _catalogos: null, // { anomalias, caras, procedimientos, estatus, especialistas }
  _appInstance: null, // instancia Vue activa

  // ─────────────────────────────────────────────────────────────────────────
  // Llamar desde modal_ver_paciente.php al activar el tab:
  //   odontogramaController.cargar(numeroPaciente)
  // ─────────────────────────────────────────────────────────────────────────

  async cargar(numeroPaciente) {
    this._numeroPaciente = parseInt(numeroPaciente);
    await this._inicializar();
    this._montarVue();
    setTimeout(() => this._poblarSelects(), 100);
  },

  limpiar() {
    this._numeroPaciente = null;
    this._desmontar();
    // Resetear toolbar
    const sel = document.getElementById("odontEspecialista");
    if (sel) sel.value = "";
  },

  // ─────────────────────────────────────────────────────────────────────────
  // INICIALIZACIÓN DE CATÁLOGOS — idéntico a planesController.inicializar()
  // ─────────────────────────────────────────────────────────────────────────

  async _inicializar() {
    if (this._catalogos) return; // ya cargados, no repetir

    try {
      const r = await fetch(
        `${API_URL}?modulo=odontograma&accion=get_catalogos_odontograma`,
      );
      const data = await r.json();
      if (!data.success) {
        console.warn(
          "odontogramaController: no se pudieron cargar catálogos",
          data.message,
        );
        return;
      }
      this._catalogos = data;
    } catch (err) {
      console.warn("odontogramaController: error al cargar catálogos", err);
    }
  },

  // ─────────────────────────────────────────────────────────────────────────
  // POBLAR SELECTS — mismo patrón que planesController._poblarSelects()
  // ─────────────────────────────────────────────────────────────────────────

  _poblarSelects() {
    // ── Especialistas (toolbar principal) ────────────────────────────────
    const selEsp = document.getElementById("odontEspecialista");
    if (selEsp && this._catalogos?.especialistas) {
      // ── Guardar valor actual antes de repoblar ──
      const valorActual = selEsp.value;

      selEsp.innerHTML =
        '<option value="">Seleccionar especialista...</option>';
      this._catalogos.especialistas.forEach((e) => {
        const opt = document.createElement("option");
        opt.value = e.id;
        opt.textContent = e.nombre_completo;
        selEsp.appendChild(opt);
      });

      // ── Restaurar valor si existía ──
      if (valorActual) selEsp.value = valorActual;
    }
  },

  // ─────────────────────────────────────────────────────────────────────────
  // GENERAR OPTIONS para selects dentro de Vue (igual que _optionsProcedimientos en planes)
  // Vue no puede usar v-for sobre catálogos que no son reactivos,
  // así que generamos el HTML de las options como string y lo inyectamos.
  // ─────────────────────────────────────────────────────────────────────────

  _optionsAnomalias() {
    if (!this._catalogos?.anomalias) return "";
    return this._catalogos.anomalias
      .map((a) => `<option value="${a.id}">${escHtml(a.nombre)}</option>`)
      .join("");
  },

  _optionsCaras() {
    if (!this._catalogos?.caras) return "";
    return this._catalogos.caras
      .map(
        (c) => `
        <div>
          <input type="checkbox"
                 id="cara-od-${c.id}"
                 value="${c.id}"
                 class="cara-check odont-cara-cb">
          <label for="cara-od-${c.id}" class="cara-label">${escHtml(c.nombre)}</label>
        </div>`,
      )
      .join("");
  },

  _optionsProcedimientos() {
    if (!this._catalogos?.procedimientos) return "";
    return this._catalogos.procedimientos
      .map((p) => `<option value="${p.id}">${escHtml(p.nombre)}</option>`)
      .join("");
  },

  _optionsEstatus() {
    if (!this._catalogos?.estatus) return "";
    return this._catalogos.estatus
      .map((e) => `<option value="${e.id}">${escHtml(e.nombre)}</option>`)
      .join("");
  },

  // ─────────────────────────────────────────────────────────────────────────
  // MONTAR VUE — solo para arcadas, panel y estado reactivo
  // Los selects del formulario se pueblan con DOM vanilla justo después de montar
  // ─────────────────────────────────────────────────────────────────────────

  _montarVue() {
    if (this._appInstance) return; // ya montado

    const { createApp, ref, computed } = Vue;
    const self = this;

    this._appInstance = createApp({
      setup() {
        // Arcadas (estáticas)
        const arcadaSuperior = odontogramaModel.construirArcada(
          odontogramaModel.numerosSuperior,
          "Superior",
        );
        const arcadaInferior = odontogramaModel.construirArcada(
          odontogramaModel.numerosInferior,
          "Inferior",
        );

        // Estado reactivo
        const dienteActivo = ref(null);
        const registros = ref({});
        const cargando = ref(false);
        const notif = ref({ visible: false, texto: "", tipo: "success" });

        // Computadas
        const registrosDiente = computed(() =>
          dienteActivo.value
            ? (registros.value[dienteActivo.value.numero] ?? [])
            : [],
        );

        // Estado visual de cada diente
        function estadoDiente(numero) {
          const regs = registros.value[numero];
          if (!regs || !regs.length) return "sano";
          if (regs.every((r) => r.estatus_tratamiento === "Tratado"))
            return "tratado";
          if (regs.some((r) => r.estatus_tratamiento === "En proceso"))
            return "anomalia";
          return "atencion";
        }

        // Seleccionar diente → poblar selects del panel con DOM vanilla
        function seleccionarDiente(pieza) {
          dienteActivo.value = pieza;

          // Poblar selects del panel lateral justo después de que Vue renderice
          self._nextTick(() => self._poblarSelectsPanel());
        }

        function cancelar() {
          dienteActivo.value = null;
        }

        async function guardarRegistro() {
          // Leer valores de los selects DOM (no de v-model)
          const idAnomalia = parseInt(
            document.getElementById("odontAnomalia")?.value || "0",
          );
          const idProcedimiento = parseInt(
            document.getElementById("odontProc")?.value || "0",
          );
          const idEstatus = parseInt(
            document.getElementById("odontEstatus")?.value || "0",
          );
          const idEspecialista = parseInt(
            document.getElementById("odontEspecialista")?.value || "0",
          );
          const numeroPieza = dienteActivo.value?.numero;

          // Leer checkboxes de caras
          const idCaras = [
            ...document.querySelectorAll(".odont-cara-cb:checked"),
          ].map((cb) => parseInt(cb.value));

          // Validar
          if (!idEspecialista) {
            mostrarNotif(
              "Selecciona un especialista en la parte superior",
              "error",
            );
            return;
          }
          if (!idAnomalia) {
            mostrarNotif("Selecciona una anomalía", "error");
            return;
          }
          if (!idCaras.length) {
            mostrarNotif("Selecciona al menos una cara", "error");
            return;
          }
          if (!idProcedimiento) {
            mostrarNotif("Selecciona un procedimiento", "error");
            return;
          }
          if (!idEstatus) {
            mostrarNotif("Selecciona un estatus", "error");
            return;
          }

          // Optimistic update
          const cat = self._catalogos;
          const filasLocales = idCaras.map((idCara) => ({
            id_odontograma: null,
            numero_posicion: numeroPieza,
            nombre_anomalia:
              cat.anomalias.find((a) => a.id == idAnomalia)?.nombre ?? "",
            cara: cat.caras.find((c) => c.id == idCara)?.nombre ?? "",
            nombre_procedimiento:
              cat.procedimientos.find((p) => p.id == idProcedimiento)?.nombre ??
              "",
            estatus_tratamiento:
              cat.estatus.find((e) => e.id == idEstatus)?.nombre ?? "",
            nombre_especialista:
              cat.especialistas.find((e) => e.id == idEspecialista)
                ?.nombre_completo ?? "",
            fecha_cita: new Date().toISOString().slice(0, 10),
            _pendiente: true,
          }));

          if (!registros.value[numeroPieza]) registros.value[numeroPieza] = [];
          registros.value[numeroPieza].unshift(...filasLocales);

          mostrarNotif("Guardando...", "info");

          // Persistir en servidor
          const resultado = await self._guardarEnServidor({
            numero_paciente: self._numeroPaciente,
            id_especialista: idEspecialista,
            numero_pieza: numeroPieza,
            id_anomalia: idAnomalia,
            id_caras: idCaras,
            id_procedimiento: idProcedimiento,
          });

          if (resultado?.success) {
            await self._cargarRegistros(registros, cargando);
            mostrarNotif(
              `Pieza ${numeroPieza} registrada correctamente`,
              "success",
            );
            // Resetear selects del panel
            self._resetearPanel();
          } else {
            // Revertir
            registros.value[numeroPieza] = (
              registros.value[numeroPieza] ?? []
            ).filter((r) => !r._pendiente);
            mostrarNotif(resultado?.message ?? "Error al guardar", "error");
          }
        }

        async function eliminarRegistro(idx) {
          if (!confirm("¿Eliminar este registro?")) return;

          const num = dienteActivo.value.numero;
          const fila = registros.value[num]?.[idx];
          if (!fila) return;

          registros.value[num].splice(idx, 1);
          if (!registros.value[num].length) delete registros.value[num];

          const resultado = await self._eliminarEnServidor(
            fila.id_odontograma,
            self._numeroPaciente,
          );

          if (!resultado?.success) {
            await self._cargarRegistros(registros, cargando);
            mostrarNotif(resultado?.message ?? "Error al eliminar", "error");
          } else {
            mostrarNotif("Registro eliminado", "success");
          }
        }

        function mostrarNotif(texto, tipo = "success") {
          notif.value = { visible: true, texto, tipo };
          setTimeout(() => {
            notif.value.visible = false;
          }, 2800);
        }

        // Carga inicial de registros
        self._cargarRegistros(registros, cargando);

        return {
          arcadaSuperior,
          arcadaInferior,
          dienteActivo,
          registros,
          registrosDiente,
          cargando,
          notif,
          estadoDiente,
          seleccionarDiente,
          cancelar,
          guardarRegistro,
          eliminarRegistro,
        };
      },
    }).mount("#app-odontograma");
  },

  // Poblar los selects del panel lateral (se llaman DESPUÉS de que Vue renderiza el panel)
  _poblarSelectsPanel() {
    const selAnom = document.getElementById("odontAnomalia");
    if (selAnom && this._catalogos?.anomalias) {
      selAnom.innerHTML =
        '<option value="">Seleccionar...</option>' + this._optionsAnomalias();
    }

    const divCaras = document.getElementById("odontCarasGrid");
    if (divCaras) {
      divCaras.innerHTML = this._optionsCaras();
    }

    const selProc = document.getElementById("odontProc");
    if (selProc && this._catalogos?.procedimientos) {
      selProc.innerHTML =
        '<option value="">Seleccionar...</option>' +
        this._optionsProcedimientos();
    }

    const selEst = document.getElementById("odontEstatus");
    if (selEst && this._catalogos?.estatus) {
      selEst.innerHTML =
        '<option value="">Seleccionar...</option>' + this._optionsEstatus();
    }
  },

  _resetearPanel() {
    ["odontAnomalia", "odontProc", "odontEstatus"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = "";
    });
    document
      .querySelectorAll(".odont-cara-cb")
      .forEach((cb) => (cb.checked = false));
  },

  // Helper: ejecutar callback en el próximo ciclo de render
  _nextTick(fn) {
    setTimeout(fn, 0);
  },

  _desmontar() {
    if (this._appInstance) {
      this._appInstance.unmount();
      this._appInstance = null;
    }
  },

  // ─────────────────────────────────────────────────────────────────────────
  // COMUNICACIÓN CON EL SERVIDOR
  // ─────────────────────────────────────────────────────────────────────────

  async _cargarRegistros(registros, cargando) {
    if (!this._numeroPaciente) return;
    try {
      cargando.value = true;
      const r = await fetch(
        `${API_URL}?modulo=odontograma&accion=get_by_paciente_odontograma&numero_paciente=${this._numeroPaciente}`,
      );
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      const data = await r.json();
      if (data.success) registros.value = data.registros ?? {};
      else console.warn("odontogramaController:", data.message);
    } catch (err) {
      console.info("odontogramaController: modo local —", err.message);
    } finally {
      cargando.value = false;
    }
  },

  async _guardarEnServidor(payload) {
    try {
      const r = await fetch(
        `${API_URL}?modulo=odontograma&accion=guardar_odontograma`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        },
      );
      return await r.json();
    } catch (err) {
      console.error("odontogramaController._guardarEnServidor:", err);
      return null;
    }
  },

  async _eliminarEnServidor(idOdontograma, numeroPaciente) {
    try {
      const r = await fetch(
        `${API_URL}?modulo=odontograma&accion=eliminar_odontograma`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            id_odontograma: idOdontograma,
            numero_paciente: numeroPaciente,
          }),
        },
      );
      return await r.json();
    } catch (err) {
      console.error("odontogramaController._eliminarEnServidor:", err);
      return null;
    }
  },
};
