/**
 * pagos.js
 * Controlador del módulo de pagos.
 * Depende de: CatalogTable, API_URL, CATALOGOS_PAGO
 */

const pagoController = {
  _pagoActual: null, // datos del pago visible en el modal detalle

  // ─────────────────────────────────────────────────────────────────────
  // ABRIR MODAL REGISTRO
  // ─────────────────────────────────────────────────────────────────────

  abrir() {
    this._limpiar();
    this._poblarSelects();
    abrirModal("modalPago");

    // Fecha por defecto = hoy
    this._setVal("pagoFecha", new Date().toISOString().split("T")[0]);
    setTimeout(() => document.getElementById("pagoCita")?.focus(), 150);
  },

  // ─────────────────────────────────────────────────────────────────────
  // VER DETALLE
  // ─────────────────────────────────────────────────────────────────────

  async ver(id) {
    this._resetDetalle();
    abrirModal("modalDetallePago");

    try {
      const r = await fetch(`${API_URL}?modulo=pagos&accion=get&id=${id}`);
      const data = await r.json();

      if (!data.success) {
        CatalogTable.showNotification("Error al cargar el pago", "error");
        cerrarModal("modalDetallePago");
        return;
      }

      const p = data.pago;
      this._pagoActual = p; // guardar para impresión
      document.getElementById("detPagoRecibo").textContent =
        p.numero_recibo ?? "";

      // Cita
      this._setText("detPagoPaciente", p.nombre_paciente ?? "—");
      this._setText("detPagoEspecialista", p.nombre_especialista ?? "—");
      this._setText(
        "detPagoFechaCita",
        p.fecha_cita
          ? this._fmtFecha(p.fecha_cita) +
              " · " +
              (p.hora_inicio ?? "").substring(0, 5)
          : "—",
      );
      this._setText("detPagoMotivo", p.motivo_consulta ?? "—");

      // Pago
      this._setText("detPagoNumRecibo", p.numero_recibo ?? "—");
      this._setText(
        "detPagoFecha",
        p.fecha_pago ? this._fmtFecha(p.fecha_pago) : "—",
      );
      this._setText("detPagoMetodo", p.metodo_pago ?? "—");
      this._setText("detPagoReferencia", p.referencia_pago ?? "—");
      this._setText(
        "detPagoTotal",
        p.monto_total ? "$" + this._fmtNum(p.monto_total) : "—",
      );
      this._setText("detPagoObservaciones", p.observaciones ?? "—");

      // Monto neto con descuento
      const neto = parseFloat(p.monto_neto ?? 0);
      const total = parseFloat(p.monto_total ?? 0);
      let netoTxt = "$" + this._fmtNum(neto);
      if (neto < total) {
        netoTxt += ` (desc. $${this._fmtNum(total - neto)})`;
      }
      this._setText("detPagoNeto", netoTxt);

      // Estatus con badge
      const badgeCls =
        p.estatus === "Pagado" ? "badge-success" : "badge-warning";
      document.getElementById("detPagoEstatus").innerHTML =
        `<span class="badge ${badgeCls}">${p.estatus ?? "—"}</span>`;
    } catch (err) {
      console.error("pagoController.ver:", err);
      CatalogTable.showNotification("Error de conexión", "error");
      cerrarModal("modalDetallePago");
    }
  },

  // ─────────────────────────────────────────────────────────────────────
  // IMPRIMIR RECIBO
  // ─────────────────────────────────────────────────────────────────────

  _imprimirActual() {
    if (this._pagoActual) {
      console.log("Imprimiendo pago:", this._pagoActual);
      this.imprimirRecibo(this._pagoActual);
    } else {
      CatalogTable.showNotification(
        "No hay datos del pago para imprimir",
        "error",
      );
    }
  },

  imprimirRecibo(p) {
    const fmt = (n) =>
      "$" +
      parseFloat(n).toLocaleString("es-MX", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

    // 1. Poblar los datos en el DOM oculto
    document.getElementById("printRecibo").textContent = p.numero_recibo ?? "—";
    document.getElementById("printFechaPago").textContent = p.fecha_pago
      ? this._fmtFecha(p.fecha_pago)
      : "—";
    document.getElementById("printPaciente").textContent =
      p.nombre_paciente ?? "—";
    document.getElementById("printEspecialista").textContent =
      p.nombre_especialista ?? "—";
    document.getElementById("printFechaCita").textContent = p.fecha_cita
      ? this._fmtFecha(p.fecha_cita) +
        " " +
        (p.hora_inicio ?? "").substring(0, 5)
      : "—";
    document.getElementById("printMotivo").textContent =
      p.motivo_consulta ?? "—";
    document.getElementById("printMetodo").textContent = p.metodo_pago ?? "—";
    document.getElementById("printTotal").textContent = fmt(p.monto_total);
    document.getElementById("printNeto").textContent = fmt(p.monto_neto);

    // Manejo de Descuento y Observaciones
    const rowDesc = document.getElementById("printRowDesc");
    if (parseFloat(p.monto_neto) < parseFloat(p.monto_total)) {
      document.getElementById("printDesc").textContent =
        "-" + fmt(p.monto_total - p.monto_neto);
      if (rowDesc) rowDesc.style.display = "flex";
    } else {
      if (rowDesc) rowDesc.style.display = "none";
    }

    // 2. Obtener el contenido HTML
    const contenidoRecibo = document.getElementById("reciboImprimir").innerHTML;

    // 3. Abrir ventana e inyectar TODO (HTML + CSS)
    const ventana = window.open("", "_blank", "width=900,height=900");

    ventana.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Imprimir Recibo</title>
            <style>
                /* ESTILOS INTEGRADOS PARA GARANTIZAR EL DISEÑO */
                body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 0; padding: 20px; color: #1a1a1a; }
                .recibo-container { width: 100%; max-width: 800px; margin: 0 auto; }
                
                .recibo-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
                .brand-info { display: flex; align-items: center; gap: 15px; }
                .recibo-logo { width: 80px; height: auto; }
                .clinica-datos h1 { font-size: 24px; color: #2c3e50; margin: 0; }
                .clinica-datos p { font-size: 10px; color: #34495e; margin: 0; font-weight: bold; }
                .doctor-nombre { font-size: 18px; font-weight: bold; color: #1a3a8a; }

                .titulo-documento { text-align: center; font-size: 20px; margin: 20px 0; letter-spacing: 1px; }
                .folio-fecha { text-align: right; margin-bottom: 20px; font-size: 13px; }
                
                .recibo-seccion { margin-bottom: 20px; }
                .recibo-seccion h3 { font-size: 13px; border-bottom: 1px dashed #ccc; padding-bottom: 5px; margin-bottom: 10px; }
                
                .linea-datos { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; }
                .total-final { display: flex; justify-content: space-between; border-top: 1px solid #000; padding-top: 10px; margin-top: 20px; font-weight: bold; font-size: 16px; }

                .status-pagado { text-align: center; margin: 40px 0; font-size: 24px; font-weight: 900; letter-spacing: 2px; }
                
                .recibo-footer { margin-top: 50px; text-align: center; }
                .footer-blue-bar { border-top: 2px solid #1a3a8a; padding-top: 10px; color: #1a3a8a; font-size: 11px; }
                
                @media print {
                    @page { size: portrait; margin: 10mm; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            ${contenidoRecibo}
            <script>
                window.onload = function() {
                    // Damos un tiempo extra para que el logo cargue
                    setTimeout(() => {
                        window.print();
                        window.close();
                    }, 300);
                };
            <\/script>
        </body>
        </html>
    `);
    ventana.document.close();
  },
  // ─────────────────────────────────────────────────────────────────────
  // FACTURACIÓN — ABRIR MODAL
  // ─────────────────────────────────────────────────────────────────────

  async abrirFactura(idPago, idCita) {
    this._resetModalFactura();
    this._setVal("factIdPago", idPago);
    this._setVal("factIdCita", idCita);

    // Cargar datos del pago para mostrar el resumen
    try {
      const r = await fetch(`${API_URL}?modulo=pagos&accion=get&id=${idPago}`);
      const data = await r.json();
      if (data.success) {
        const p = data.pago;
        this._setVal("factNumPaciente", p.numero_paciente ?? "");
        document.getElementById("factPagoPaciente").textContent =
          p.nombre_paciente ?? "—";
        document.getElementById("factPagoDetalle").textContent =
          `${p.numero_recibo} · $${this._fmtNum(p.monto_neto)} · ${p.metodo_pago}`;

        // Pre-llenar RFC/razón si el paciente ya tiene datos guardados
        if (p.rfc_facturacion) {
          this._setVal("factRFC", p.rfc_facturacion);
          this._setVal("factRazonSocial", p.razon_social_facturacion ?? "");
        }
      }
    } catch (err) {
      console.error("pagoController.abrirFactura:", err);
    }

    // Mostrar paso 1
    document.getElementById("facturaVistaSolicitar").style.display = "";
    document.getElementById("facturaVistaCompletar").style.display = "none";
    document.getElementById("facturaVistaDetalle").style.display = "none";
    document.getElementById("btnGuardarFactura").style.display = "";
    document.getElementById("btnCompletarFactura").style.display = "none";
    document.getElementById("modalFacturaTitulo").textContent =
      "Solicitar Factura";

    abrirModal("modalFactura");
  },

  async verFactura(idPago) {
    this._resetModalFactura();

    try {
      const r = await fetch(
        `${API_URL}?modulo=pagos&accion=get_solicitud_factura&id_pago=${idPago}`,
      );
      const data = await r.json();

      if (!data.success || !data.solicitud) {
        CatalogTable.showNotification("No se encontró la solicitud", "error");
        return;
      }

      const sf = data.solicitud;
      this._setVal("factIdSolicitud", sf.id_solicitud_factura);

      const esTimbrada = sf.folio_fiscal && sf.folio_fiscal.trim() !== "";

      if (esTimbrada) {
        // Mostrar detalle completo
        document.getElementById("factDetRFC").textContent = sf.rfc ?? "—";
        document.getElementById("factDetRazonSocial").textContent =
          sf.razon_social ?? "—";
        document.getElementById("factDetEstatus").innerHTML =
          `<span class="badge badge-success">${sf.estatus_factura}</span>`;
        document.getElementById("factDetFolioFiscal").textContent =
          sf.folio_fiscal ?? "—";
        document.getElementById("factDetFechaTimbrado").textContent =
          sf.fecha_facturacion
            ? sf.fecha_facturacion.substring(0, 16).replace("T", " ")
            : "—";
        document.getElementById("factDetCFDI").textContent = sf.cfdi ?? "—";

        document.getElementById("facturaVistaDetalle").style.display = "";
        document.getElementById("facturaVistaSolicitar").style.display = "none";
        document.getElementById("facturaVistaCompletar").style.display = "none";
        document.getElementById("btnGuardarFactura").style.display = "none";
        document.getElementById("btnCompletarFactura").style.display = "none";
        document.getElementById("modalFacturaTitulo").textContent =
          "Detalle de Factura";
      } else {
        // Pendiente de timbrar — mostrar paso 2
        document.getElementById("facturaVistaCompletar").style.display = "";
        document.getElementById("facturaVistaSolicitar").style.display = "none";
        document.getElementById("facturaVistaDetalle").style.display = "none";
        document.getElementById("btnCompletarFactura").style.display = "";
        document.getElementById("btnGuardarFactura").style.display = "none";
        document.getElementById("modalFacturaTitulo").textContent =
          "Registrar Timbrado";

        // Fecha actual como valor por defecto
        const now = new Date();
        const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
          .toISOString()
          .slice(0, 16);
        this._setVal("factFechaTimb", local);
      }

      abrirModal("modalFactura");
    } catch (err) {
      console.error("pagoController.verFactura:", err);
      CatalogTable.showNotification("Error de conexión", "error");
    }
  },

  async guardarFactura() {
    const idPago = this._getVal("factIdPago");
    const rfc = this._getVal("factRFC").toUpperCase();
    const razonSocial = this._getVal("factRazonSocial").toUpperCase();
    const cfdi = this._getVal("factCFDI");
    const numPaciente = this._getVal("factNumPaciente");

    if (!rfc) {
      CatalogTable.showNotification("El RFC es obligatorio", "error");
      document.getElementById("factRFC")?.focus();
      return;
    }
    if (!/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i.test(rfc)) {
      CatalogTable.showNotification("El formato del RFC no es válido", "error");
      document.getElementById("factRFC")?.focus();
      return;
    }
    if (!razonSocial) {
      CatalogTable.showNotification("La razón social es obligatoria", "error");
      document.getElementById("factRazonSocial")?.focus();
      return;
    }
    if (!cfdi) {
      CatalogTable.showNotification("Selecciona el uso de CFDI", "error");
      document.getElementById("factCFDI")?.focus();
      return;
    }

    const formData = new FormData();
    formData.append("modulo", "pagos");
    formData.append("accion", "crear_solicitud_factura");
    formData.append("id_pago", idPago);
    formData.append("numero_paciente", numPaciente);
    formData.append("rfc", rfc);
    formData.append("razon_social", razonSocial);
    formData.append("cfdi", cfdi);

    CatalogTable.showLoading(true);
    try {
      const r = await fetch(API_URL, { method: "POST", body: formData });
      const texto = await r.text();
      console.log("Respuesta guardarFactura (status", r.status, "):", texto);
      let data;
      try {
        data = JSON.parse(texto);
      } catch (e) {
        CatalogTable.showLoading(false);
        CatalogTable.showNotification(
          "Error del servidor: respuesta no es JSON",
          "error",
        );
        return;
      }
      CatalogTable.showLoading(false);

      if (data.success) {
        CatalogTable.showNotification(
          "Solicitud de factura registrada",
          "success",
        );
        cerrarModal("modalFactura");
        setTimeout(() => window.location.reload(), 900);
      } else {
        CatalogTable.showNotification(
          data.message || "Error desconocido",
          "error",
        );
      }
    } catch (err) {
      CatalogTable.showLoading(false);
      console.error("guardarFactura error:", err);
      CatalogTable.showNotification(
        "Error de conexión: " + err.message,
        "error",
      );
    }
  },

  async completarFactura() {
    const idSolicitud = this._getVal("factIdSolicitud");
    const folioFiscal = this._getVal("factFolioFiscal").toUpperCase();
    const fechaTimb = this._getVal("factFechaTimb");

    if (!folioFiscal) {
      CatalogTable.showNotification("El folio fiscal es obligatorio", "error");
      document.getElementById("factFolioFiscal")?.focus();
      return;
    }
    if (!fechaTimb) {
      CatalogTable.showNotification(
        "La fecha de timbrado es obligatoria",
        "error",
      );
      document.getElementById("factFechaTimb")?.focus();
      return;
    }

    const formData = new FormData();
    formData.append("modulo", "pagos");
    formData.append("accion", "completar_factura");
    formData.append("id_solicitud_factura", idSolicitud);
    formData.append("folio_fiscal", folioFiscal);
    formData.append("fecha_facturacion", fechaTimb);

    CatalogTable.showLoading(true);
    try {
      const r = await fetch(API_URL, { method: "POST", body: formData });
      const data = await r.json();
      CatalogTable.showLoading(false);

      if (data.success) {
        CatalogTable.showNotification(
          "Factura registrada correctamente",
          "success",
        );
        cerrarModal("modalFactura");
        setTimeout(() => window.location.reload(), 900);
      } else {
        CatalogTable.showNotification(data.message || "Error", "error");
      }
    } catch (err) {
      CatalogTable.showLoading(false);
      CatalogTable.showNotification("Error de conexión", "error");
    }
  },

  _resetModalFactura() {
    [
      "factIdPago",
      "factIdCita",
      "factNumPaciente",
      "factRFC",
      "factRazonSocial",
      "factCFDI",
      "factIdSolicitud",
      "factFolioFiscal",
      "factFechaTimb",
    ].forEach((id) => this._setVal(id, ""));
  },

  // ─────────────────────────────────────────────────────────────────────
  // GUARDAR
  // ─────────────────────────────────────────────────────────────────────

  async guardar() {
    const idCita = this._getVal("pagoCita");
    const fecha = this._getVal("pagoFecha");
    const idMetodo = this._getVal("pagoMetodo");
    const referencia = this._getVal("pagoReferencia");
    const montoTotal = this._getVal("pagoMontoTotal");
    const montoNeto = this._getVal("pagoMontoNeto");

    // ── Validaciones ──────────────────────────────────────────────────
    if (!idCita) {
      CatalogTable.showNotification("Selecciona una cita", "error");
      document.getElementById("pagoCita")?.focus();
      return;
    }
    if (!fecha) {
      CatalogTable.showNotification("La fecha de pago es obligatoria", "error");
      document.getElementById("pagoFecha")?.focus();
      return;
    }
    if (fecha > new Date().toISOString().split("T")[0]) {
      CatalogTable.showNotification(
        "La fecha de pago no puede ser futura",
        "error",
      );
      document.getElementById("pagoFecha")?.focus();
      return;
    }
    if (!idMetodo) {
      CatalogTable.showNotification("Selecciona el método de pago", "error");
      document.getElementById("pagoMetodo")?.focus();
      return;
    }

    // Referencia requerida según método
    const metodo = (CATALOGOS_PAGO.metodosPago ?? []).find(
      (m) => m.id_metodo_pago == idMetodo,
    );
    if (metodo?.requiere_referencia == 1 && !referencia) {
      CatalogTable.showNotification(
        "Este método de pago requiere un número de referencia",
        "error",
      );
      document.getElementById("pagoReferencia")?.focus();
      return;
    }

    if (!montoTotal || parseFloat(montoTotal) <= 0) {
      CatalogTable.showNotification(
        "El monto total debe ser mayor a 0",
        "error",
      );
      document.getElementById("pagoMontoTotal")?.focus();
      return;
    }
    if (!montoNeto || parseFloat(montoNeto) <= 0) {
      CatalogTable.showNotification(
        "El monto neto debe ser mayor a 0",
        "error",
      );
      document.getElementById("pagoMontoNeto")?.focus();
      return;
    }
    if (parseFloat(montoNeto) > parseFloat(montoTotal)) {
      CatalogTable.showNotification(
        "El monto neto no puede ser mayor al monto total",
        "error",
      );
      document.getElementById("pagoMontoNeto")?.focus();
      return;
    }

    // ── Enviar ────────────────────────────────────────────────────────
    const formData = new FormData();
    formData.append("modulo", "pagos");
    formData.append("accion", "crear_pago");
    formData.append("id_cita", idCita);
    formData.append("fecha_pago", fecha);
    formData.append("id_metodo_pago", idMetodo);
    formData.append("referencia_pago", referencia);
    formData.append("monto_total", montoTotal);
    formData.append("monto_neto", montoNeto);
    formData.append("observaciones", this._getVal("pagoObservaciones"));

    CatalogTable.showLoading(true);
    try {
      const r = await fetch(API_URL, { method: "POST", body: formData });
      const data = await r.json();
      CatalogTable.showLoading(false);

      if (data.success) {
        CatalogTable.showNotification(
          `Pago registrado. Recibo: ${data.numero_recibo}`,
          "success",
        );
        cerrarModal("modalPago");
        setTimeout(() => window.location.reload(), 900);
      } else {
        CatalogTable.showNotification(
          data.message || "Error al registrar",
          "error",
        );
      }
    } catch (err) {
      CatalogTable.showLoading(false);
      CatalogTable.showNotification("Error de conexión", "error");
    }
  },

  // ─────────────────────────────────────────────────────────────────────
  // POBLAR SELECTS
  // ─────────────────────────────────────────────────────────────────────

  _poblarSelects() {
    if (!window.CATALOGOS_PAGO) return;

    // Métodos de pago
    const selMetodo = document.getElementById("pagoMetodo");
    if (selMetodo) {
      selMetodo.innerHTML = '<option value="">Seleccionar método...</option>';
      (CATALOGOS_PAGO.metodosPago ?? []).forEach((m) => {
        const opt = document.createElement("option");
        opt.value = m.id_metodo_pago;
        opt.textContent = m.metodo_pago;
        opt.dataset.requiereRef = m.requiere_referencia;
        selMetodo.appendChild(opt);
      });
    }

    // Citas atendidas sin pago
    const selCita = document.getElementById("pagoCita");
    if (selCita) {
      selCita.innerHTML = '<option value="">Seleccionar cita...</option>';
      (CATALOGOS_PAGO.citasAtendidas ?? []).forEach((c) => {
        const opt = document.createElement("option");
        opt.value = c.id_cita;
        opt.dataset.paciente = c.nombre_paciente ?? "";
        opt.dataset.especialista = c.nombre_especialista ?? "";
        opt.dataset.motivo = c.motivo_consulta ?? "";
        opt.dataset.costo = c.costo_total ?? "0";
        opt.dataset.fecha = c.fecha_cita ?? "";
        opt.dataset.hora = (c.hora_inicio ?? "").substring(0, 5);
        opt.textContent =
          `${this._fmtFecha(c.fecha_cita)} ${(c.hora_inicio ?? "").substring(0, 5)}` +
          ` — ${c.nombre_paciente}`;
        selCita.appendChild(opt);
      });
    }
  },

  // ─────────────────────────────────────────────────────────────────────
  // EVENTOS INTERNOS
  // ─────────────────────────────────────────────────────────────────────

  _onCitaChange() {
    const sel = document.getElementById("pagoCita");
    const opt = sel?.options[sel.selectedIndex];
    const info = document.getElementById("pagoCitaInfo");

    if (!opt?.value) {
      if (info) info.style.display = "none";
      this._setVal("pagoMontoTotal", "");
      this._setVal("pagoMontoNeto", "");
      return;
    }

    // Mostrar tarjeta de resumen
    document.getElementById("pagoCitaPaciente").textContent =
      opt.dataset.paciente ?? "—";
    document.getElementById("pagoCitaEspecialista").textContent =
      opt.dataset.especialista ?? "—";
    document.getElementById("pagoCitaMotivo").textContent =
      opt.dataset.motivo ?? "—";

    const costo = parseFloat(opt.dataset.costo ?? 0);
    document.getElementById("pagoCitaCosto").textContent =
      costo > 0 ? "$" + this._fmtNum(costo) : "Sin costo registrado";

    if (info) info.style.display = "block";

    // Pre-llenar montos con el costo de la cita
    if (costo > 0) {
      this._setVal("pagoMontoTotal", costo.toFixed(2));
      this._setVal("pagoMontoNeto", costo.toFixed(2));
    }
  },

  _onMetodoChange() {
    const sel = document.getElementById("pagoMetodo");
    const opt = sel?.options[sel.selectedIndex];
    const grp = document.getElementById("pagoGrupoRef");
    if (!grp) return;

    const requiere = opt?.dataset.requiereRef == "1";
    grp.style.display = requiere ? "" : "none";
    if (!requiere) this._setVal("pagoReferencia", "");
  },

  _onMontoChange() {
    const total = parseFloat(this._getVal("pagoMontoTotal") || 0);
    const neto = parseFloat(this._getVal("pagoMontoNeto") || 0);
    const grp = document.getElementById("pagoDescuento");
    const monto = document.getElementById("pagoDescuentoMonto");

    if (grp && monto) {
      const descuento = total - neto;
      if (descuento > 0.001 && neto > 0) {
        monto.textContent = "$" + this._fmtNum(descuento);
        grp.style.display = "block";
      } else {
        grp.style.display = "none";
      }
    }
  },

  // ─────────────────────────────────────────────────────────────────────
  // HELPERS
  // ─────────────────────────────────────────────────────────────────────

  _setVal(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val ?? "";
  },

  _getVal(id) {
    return document.getElementById(id)?.value?.trim() ?? "";
  },

  _setText(id, txt) {
    const el = document.getElementById(id);
    if (el) el.textContent = txt ?? "—";
  },

  _fmtFecha(str) {
    if (!str) return "—";
    const [y, m, d] = str.split("-");
    return `${d}/${m}/${y}`;
  },

  _fmtNum(n) {
    return parseFloat(n).toLocaleString("es-MX", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  },

  _limpiar() {
    [
      "pagoCita",
      "pagoFecha",
      "pagoMetodo",
      "pagoReferencia",
      "pagoMontoTotal",
      "pagoMontoNeto",
      "pagoObservaciones",
    ].forEach((id) => this._setVal(id, ""));

    const info = document.getElementById("pagoCitaInfo");
    const grpRef = document.getElementById("pagoGrupoRef");
    const grpDesc = document.getElementById("pagoDescuento");
    if (info) info.style.display = "none";
    if (grpRef) grpRef.style.display = "none";
    if (grpDesc) grpDesc.style.display = "none";
  },

  _resetDetalle() {
    [
      "detPagoRecibo",
      "detPagoPaciente",
      "detPagoEspecialista",
      "detPagoFechaCita",
      "detPagoMotivo",
      "detPagoNumRecibo",
      "detPagoFecha",
      "detPagoMetodo",
      "detPagoReferencia",
      "detPagoTotal",
      "detPagoNeto",
      "detPagoEstatus",
      "detPagoObservaciones",
    ].forEach((id) => this._setText(id, "—"));
  },
};

// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS
// ─────────────────────────────────────────────────────────────────────────────

document.addEventListener("DOMContentLoaded", () => {
  document
    .getElementById("btnGuardarPago")
    ?.addEventListener("click", () => pagoController.guardar());

  document
    .getElementById("btnGuardarFactura")
    ?.addEventListener("click", () => pagoController.guardarFactura());

  document
    .getElementById("btnCompletarFactura")
    ?.addEventListener("click", () => pagoController.completarFactura());

  // RFC a mayúsculas
  document.getElementById("factRFC")?.addEventListener("input", (e) => {
    e.target.value = e.target.value.toUpperCase();
  });
  document.getElementById("factRazonSocial")?.addEventListener("input", (e) => {
    e.target.value = e.target.value.toUpperCase();
  });

  document
    .getElementById("pagoCita")
    ?.addEventListener("change", () => pagoController._onCitaChange());

  document
    .getElementById("pagoMetodo")
    ?.addEventListener("change", () => pagoController._onMetodoChange());

  document
    .getElementById("pagoMontoTotal")
    ?.addEventListener("input", () => pagoController._onMontoChange());

  document
    .getElementById("pagoMontoNeto")
    ?.addEventListener("input", () => pagoController._onMontoChange());

  CatalogTable.init();
});
