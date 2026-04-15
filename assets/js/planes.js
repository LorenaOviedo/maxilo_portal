/**
 * planes.js
 *
 * Lógica del tab Planes de Tratamiento dentro del modal de paciente.
 * Depende de: catalogos-tabla.js (CatalogTable), API_URL
 * }
 */

const planesController = {
  _numeroPaciente: null,
  _catalogos: null,
  _procsTemp: [],
  _readonly: false,
 
  async inicializar() {
    if (this._catalogos) return;
    try {
      const r = await fetch(
        `${API_URL}?modulo=planes&accion=get_catalogos_planes`,
      );
      const data = await r.json();
      if (!data.success) return;
      this._catalogos = data;
    } catch (err) {
      console.warn("planesController: no se pudieron cargar catálogos", err);
    }
  },
 
  async cargar(numeroPaciente, readonly = false) {
    this._numeroPaciente = parseInt(numeroPaciente);
    this._readonly = readonly;
 
    await this.inicializar();
    this._poblarSelects();
    this._mostrarLoading(true);
 
    try {
      const r = await fetch(
        `${API_URL}?modulo=planes&accion=get_by_paciente&numero_paciente=${this._numeroPaciente}`,
      );
      const data = await r.json();
      this._mostrarLoading(false);
      if (data.success) this._renderPlanes(data.planes);
    } catch (err) {
      this._mostrarLoading(false);
      console.warn("planesController: error al cargar planes", err);
    }
  },
 
  limpiar() {
    this._numeroPaciente = null;
    this._procsTemp = [];
    this._ocultarFormulario();
    document.getElementById("listaPlanesContainer").innerHTML = `
            <div id="planesSinDatos" style="text-align:center; padding:30px; color:#adb5bd;">
                <i class="ri-file-list-3-line" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                <p>Sin planes de tratamiento registrados</p>
            </div>`;
  },
 
  // ── Renderizar planes como acordeón ───────────────────────────
  _renderPlanes(planes) {
    this._planesData = planes;
    const contenedor = document.getElementById("listaPlanesContainer");
 
    if (!planes || !planes.length) {
      contenedor.innerHTML = `
                <div id="planesSinDatos" style="text-align:center; padding:30px; color:#adb5bd;">
                    <i class="ri-file-list-3-line" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                    <p>Sin planes de tratamiento registrados</p>
                </div>`;
      return;
    }
 
    contenedor.innerHTML = planes
      .map(
        (plan, idx) => `
            <div class="plan-card form-card" data-id="${plan.id_plan_tratamiento}">
 
                <!-- Cabecera del acordeón (siempre visible) -->
                <div class="plan-accordion-header"
                     onclick="planesController._toggleAcordeon(${plan.id_plan_tratamiento})">
                    <div class="plan-accordion-info">
                        <span class="plan-nombre">Plan #${plan.numero_plan}</span>
                        <span class="plan-meta">
                            <strong>Especialista:</strong> ${escHtml(plan.especialista)}
                            &nbsp;·&nbsp;
                            <strong>Creado:</strong> ${formatFecha(plan.fecha_creacion)}
                            &nbsp;·&nbsp;
                            <span class="badge-estatus ${badgeClase(plan.estatus_tratamiento)}">
                                ${plan.estatus_tratamiento.toUpperCase()}
                            </span>
                            &nbsp;·&nbsp;
                            <strong>Total:</strong>
                            $${parseFloat(plan.costo_total).toLocaleString("es-MX", { minimumFractionDigits: 2 })}
                        </span>
                    </div>
                    <div class="plan-accordion-actions" onclick="event.stopPropagation()">
                        ${
                          !this._readonly
                            ? `
                        <select class="select-estatus-plan"
                                data-id="${plan.id_plan_tratamiento}"
                                onchange="planesController.actualizarEstatus(this)">
                            ${this._optionsEstatus(plan.id_estatus_tratamiento)}
                        </select>
                        <button class="btn-eliminar-plan" title="Eliminar plan"
                                onclick="planesController.eliminarPlan(${plan.id_plan_tratamiento})">
                            <i class="ri-delete-bin-6-line"></i>
                        </button>`
                            : ""
                        }
                        <button class="btn-imprimir-plan" title="Imprimir plan"
                                onclick="event.stopPropagation(); planesController.imprimirPlan(${plan.id_plan_tratamiento})"
                                style="background:none;border:1px solid #dee2e6;border-radius:6px;padding:4px 8px;cursor:pointer;color:#6c757d;font-size:13px;display:inline-flex;align-items:center;gap:4px;">
                            <i class="ri-printer-line"></i>
                        </button>
                        <i class="ri-arrow-down-s-line plan-accordion-icon"
                           id="iconAcordeon_${plan.id_plan_tratamiento}"></i>
                    </div>
                </div>
 
                <!-- Cuerpo del acordeón (colapsado por defecto) -->
                <div class="plan-accordion-body" id="bodyAcordeon_${plan.id_plan_tratamiento}"
                     style="display:none;">
 
                    ${
                      plan.notas
                        ? `
                    <div class="plan-notas">
                        <i class="ri-file-text-line"></i> ${escHtml(plan.notas)}
                    </div>`
                        : ""
                    }
 
                    <table class="plan-table">
                        <thead>
                            <tr>
                                <th>PROCEDIMIENTO</th>
                                <th>PIEZA</th>
                                <th>PRECIO BASE</th>
                                <th>PRECIO ESPECIAL</th>
                                ${!this._readonly ? "<th></th>" : ""}
                            </tr>
                        </thead>
                        <tbody>
                            ${
                              plan.detalle.length
                                ? plan.detalle
                                    .map(
                                      (d) => `
                                <tr>
                                    <td>${escHtml(d.nombre_procedimiento)}</td>
                                    <td class="text-center">${d.numero_pieza || "—"}</td>
                                    <td>$${parseFloat(d.precio_base).toLocaleString("es-MX", { minimumFractionDigits: 2 })}</td>
                                    <td>${
                                      d.costo_descuento
                                        ? "$" +
                                          parseFloat(
                                            d.costo_descuento,
                                          ).toLocaleString("es-MX", {
                                            minimumFractionDigits: 2,
                                          })
                                        : "—"
                                    }</td>
                                    ${
                                      !this._readonly
                                        ? `
                                    <td class="acciones-cell">
                                        <button class="btn-accion eliminar"
                                            onclick="planesController.eliminarProcedimiento(${d.id_detalle_plan}, ${plan.id_plan_tratamiento})">
                                            <i class="ri-delete-bin-6-line"></i>
                                        </button>
                                    </td>`
                                        : ""
                                    }
                                </tr>`,
                                    )
                                    .join("")
                                : `
                                <tr>
                                    <td colspan="${this._readonly ? 4 : 5}"
                                        style="text-align:center; color:#adb5bd; padding:12px;">
                                        Sin procedimientos
                                    </td>
                                </tr>`
                            }
                        </tbody>
                    </table>
 
                    ${
                      !this._readonly
                        ? `
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
                        style="margin:10px 16px;">
                        <i class="ri-add-line"></i> Agregar procedimiento
                    </button>`
                        : ""
                    }
 
                </div><!-- /.plan-accordion-body -->
            </div>`,
      )
      .join("");
  },
 
 
  // ── Imprimir plan individual ─────────────────────────────────
  imprimirPlan(idPlan) {
    const plan = (this._planesData || []).find(p => p.id_plan_tratamiento == idPlan);
    if (!plan) return;
 
    const fmt  = n => '$' + parseFloat(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 });
    const anio = new Date().getFullYear();
 
    const filasDetalle = plan.detalle.length
        ? plan.detalle.map(d => `
            <tr>
                <td>${escHtml(d.nombre_procedimiento)}</td>
                <td style="text-align:center">${d.numero_pieza || '\u2014'}</td>
                <td>${fmt(d.precio_base)}</td>
                <td>${d.costo_descuento ? fmt(d.costo_descuento) : '\u2014'}</td>
                <td>${fmt(d.costo_final)}</td>
            </tr>`).join('')
        : '<tr><td colspan="5" style="text-align:center;color:#adb5bd;padding:16px;">Sin procedimientos</td></tr>';
 
    const notasHtml = plan.notas
        ? `<div class="notas-box"><div class="notas-label">Notas del plan</div>${escHtml(plan.notas)}</div>`
        : '';
 
    const ventana = window.open('', '_blank', 'width=900,height=700');
    ventana.document.write(
        '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">' +
        '<title>Plan de Tratamiento #' + plan.numero_plan + '</title>' +
        '<style>' +
        '* { box-sizing:border-box; margin:0; padding:0; }' +
        'html { -webkit-font-smoothing:antialiased; }' +
        'body { font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#1a1a1a; background:#fff; padding:12mm 14mm; }' +
        '.header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #192D8C; padding-bottom:10px; margin-bottom:16px; }' +
        '.clinica-nombre { font-size:18px; font-weight:900; color:#192D8C; text-transform:uppercase; }' +
        '.clinica-sub { font-size:9px; color:#555; text-transform:uppercase; margin-top:2px; }' +
        '.header-meta { text-align:right; font-size:10px; color:#555; line-height:1.6; }' +
        '.titulo { font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:16px; }' +
        '.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px 24px; background:#f8f9fa; border-radius:6px; padding:12px 16px; margin-bottom:16px; }' +
        '.info-item { display:flex; flex-direction:column; gap:2px; }' +
        '.info-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#555; }' +
        '.info-value { font-size:12px; font-weight:600; color:#212529; }' +
        'table { width:100%; border-collapse:collapse; font-size:11px; margin-bottom:16px; }' +
        'thead th { background:#192D8C; color:#fff; padding:7px 10px; text-align:left; font-size:10px; text-transform:uppercase; }' +
        'tbody tr:nth-child(even) { background:#f8f9ff; }' +
        'tbody td { padding:6px 10px; border-bottom:1px solid #e9ecef; }' +
        '.total-bar { display:flex; justify-content:space-between; font-weight:700; font-size:14px; border-top:2px solid #1a1a1a; border-bottom:2px solid #1a1a1a; padding:8px 0; margin-bottom:16px; }' +
        '.notas-box { background:#fffbeb; border-left:3px solid #f59e0b; border-radius:4px; padding:10px 14px; font-size:11px; color:#495057; margin-bottom:16px; }' +
        '.notas-label { font-weight:700; margin-bottom:4px; }' +
        '.footer { margin-top:20px; border-top:3px solid #192D8C; padding-top:8px; display:flex; justify-content:space-between; font-size:9px; color:#192D8C; }' +
        '@media print { body { padding:8mm 10mm; } }' +
        '</style></head><body>' +
        '<div class="header"><div>' +
        '<div class="clinica-nombre">Maxilofacial Texcoco</div>' +
        '<div class="clinica-sub">Ortodoncia &middot; Cirug&iacute;a Maxilofacial &middot; Patolog&iacute;a Oral</div>' +
        '</div><div class="header-meta">' +
        '<strong>Fecha:</strong> ' + new Date().toLocaleDateString('es-MX') + '<br>' +
        'Dr. Alfonso Ayala G&oacute;mez</div></div>' +
        '<div class="titulo">Plan de Tratamiento #' + plan.numero_plan + '</div>' +
        '<div class="info-grid">' +
        '<div class="info-item"><span class="info-label">Especialista</span><span class="info-value">' + escHtml(plan.especialista) + '</span></div>' +
        '<div class="info-item"><span class="info-label">Fecha de creaci&oacute;n</span><span class="info-value">' + formatFecha(plan.fecha_creacion) + '</span></div>' +
        '<div class="info-item"><span class="info-label">Estatus</span><span class="info-value">' + plan.estatus_tratamiento + '</span></div>' +
        '<div class="info-item"><span class="info-label">Total del plan</span><span class="info-value">' + fmt(plan.costo_total) + '</span></div>' +
        '</div>' +
        notasHtml +
        '<table><thead><tr>' +
        '<th>Procedimiento</th><th style="text-align:center">Pieza</th>' +
        '<th>Precio base</th><th>Precio especial</th><th>Costo final</th>' +
        '</tr></thead><tbody>' + filasDetalle + '</tbody></table>' +
        '<div class="total-bar"><span>TOTAL DEL PLAN</span><span>' + fmt(plan.costo_total) + '</span></div>' +
        '<div class="footer">' +
        '<span>Sistema Maxilofacial Texcoco &mdash; ' + anio + '</span>' +
        '<span>Retorno C No. 8 Fracc. San Mart&iacute;n, Texcoco, Estado de M&eacute;xico</span>' +
        '</div>' +
        '<script>window.onload=function(){window.print();window.close();};<\/script>' +
        '</body></html>'
    );
    ventana.document.close();
  },
 
  // ── Toggle acordeón ───────────────────────────────────────────
  _toggleAcordeon(idPlan) {
    const body = document.getElementById(`bodyAcordeon_${idPlan}`);
    const icon = document.getElementById(`iconAcordeon_${idPlan}`);
    if (!body) return;
 
    const abierto = body.style.display !== "none";
    body.style.display = abierto ? "none" : "block";
    icon.classList.toggle("ri-arrow-down-s-line", abierto);
    icon.classList.toggle("ri-arrow-up-s-line", !abierto);
  },
 
  // ── Eliminar plan completo ────────────────────────────────────
  async eliminarPlan(idPlan) {
    if (
      !confirm(
        "¿Eliminar este plan de tratamiento y todos sus procedimientos? Esta acción no se puede deshacer.",
      )
    )
      return;
 
    const formData = new FormData();
    formData.append("modulo", "planes");
    formData.append("accion", "eliminar_plan");
    formData.append("id_plan_tratamiento", idPlan);
 
    try {
      const r = await fetch(API_URL, { method: "POST", body: formData });
      const data = await r.json();
      if (data.success) {
        CatalogTable.showNotification(
          "Plan eliminado correctamente",
          "success",
        );
        this.cargar(this._numeroPaciente, this._readonly);
      } else {
        CatalogTable.showNotification(
          data.message || "Error al eliminar",
          "error",
        );
      }
    } catch (err) {
      CatalogTable.showNotification("Error de conexión", "error");
    }
  },
 
  // ── Guardar nuevo plan ────────────────────────────────────────
  async guardar() {
    const fecha = document.getElementById("planFecha").value.trim();
    const especialista = document.getElementById("planEspecialista").value;
    const estatus = document.getElementById("planEstatus").value;
    const notas = document.getElementById("planNotas").value.trim();
 
    // Validar fecha requerida
    if (!fecha) {
      CatalogTable.showNotification(
        "La fecha de creación es requerida",
        "error",
      );
      return;
    }
 
    // Validar que la fecha no sea futura
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
 
    const fechaDate = new Date(fecha + "T00:00:00");
    if (fechaDate < hoy) {
      CatalogTable.showNotification(
        "La fecha de creación no puede ser anterior a hoy",
        "error",
      );
      document.getElementById("planFecha").focus();
      return;
    }
 
    if (!especialista) {
      CatalogTable.showNotification("El especialista es requerido", "error");
      return;
    }
    if (this._procsTemp.length === 0) {
      CatalogTable.showNotification(
        "Agrega al menos un procedimiento al plan",
        "warning",
      );
      return;
    }
 
    const formData = new FormData();
    formData.append("modulo", "planes");
    formData.append("accion", "crear_plan");
    formData.append("numero_paciente", this._numeroPaciente);
    formData.append("fecha_creacion", fecha);
    formData.append("id_especialista", especialista);
    formData.append("id_estatus_tratamiento", estatus || 1);
    formData.append("notas", notas);
    formData.append("procedimientos_json", JSON.stringify(this._procsTemp));
 
    CatalogTable.showLoading(true);
 
    try {
      const r = await fetch(API_URL, { method: "POST", body: formData });
      const data = await r.json();
      CatalogTable.showLoading(false);
 
      if (data.success) {
        CatalogTable.showNotification("Plan creado correctamente", "success");
        this._ocultarFormulario();
        this.cargar(this._numeroPaciente, this._readonly);
      } else {
        CatalogTable.showNotification(
          data.message || "Error al guardar",
          "error",
        );
      }
    } catch (err) {
      CatalogTable.showLoading(false);
      CatalogTable.showNotification("Error de conexión", "error");
    }
  },
 
  async actualizarEstatus(select) {
    const idPlan = parseInt(select.dataset.id);
    const estatus = parseInt(select.value);
 
    const formData = new FormData();
    formData.append("modulo", "planes");
    formData.append("accion", "cambiar_estatus_plan");
    formData.append("id_plan_tratamiento", idPlan);
    formData.append("id_estatus_tratamiento", estatus);
 
    try {
      const r = await fetch(API_URL, { method: "POST", body: formData });
      const data = await r.json();
      if (data.success) {
        CatalogTable.showNotification("Estatus actualizado", "success");
        this.cargar(this._numeroPaciente, this._readonly);
      } else {
        CatalogTable.showNotification(
          data.message || "Error al actualizar",
          "error",
        );
      }
    } catch (err) {
      CatalogTable.showNotification("Error de conexión", "error");
    }
  },
 
  async eliminarProcedimiento(idDetalle, idPlan) {
    if (!confirm("¿Eliminar este procedimiento del plan?")) return;
 
    const formData = new FormData();
    formData.append("modulo", "planes");
    formData.append("accion", "eliminar_procedimiento");
    formData.append("id_detalle_plan", idDetalle);
 
    try {
      const r = await fetch(API_URL, { method: "POST", body: formData });
      const data = await r.json();
      if (data.success) {
        CatalogTable.showNotification("Procedimiento eliminado", "success");
        this.cargar(this._numeroPaciente, this._readonly);
      } else {
        CatalogTable.showNotification(
          data.message || "Error al eliminar",
          "error",
        );
      }
    } catch (err) {
      CatalogTable.showNotification("Error de conexión", "error");
    }
  },
 
  async confirmarProcExistente(idPlan) {
    const procSel = document.getElementById(`procSelExist_${idPlan}`);
    const pieza = document.getElementById(`procPiezaExist_${idPlan}`);
    const desc = document.getElementById(`procDescExist_${idPlan}`);
 
    if (!procSel.value) {
      CatalogTable.showNotification("Selecciona un procedimiento", "warning");
      return;
    }
 
    const formData = new FormData();
    formData.append("modulo", "planes");
    formData.append("accion", "agregar_procedimiento");
    formData.append("id_plan_tratamiento", idPlan);
    formData.append("id_procedimiento", procSel.value);
    if (pieza.value) formData.append("numero_pieza", pieza.value);
    if (desc.value) formData.append("costo_descuento", desc.value);
 
    try {
      const r = await fetch(API_URL, { method: "POST", body: formData });
      const data = await r.json();
      if (data.success) {
        CatalogTable.showNotification("Procedimiento agregado", "success");
        this.cargar(this._numeroPaciente, this._readonly);
      } else {
        CatalogTable.showNotification(
          data.message || "Error al agregar",
          "error",
        );
      }
    } catch (err) {
      CatalogTable.showNotification("Error de conexión", "error");
    }
  },
 
  agregarProcTemp() {
    const sel = document.getElementById("procSelect");
    const pieza = document.getElementById("procPieza");
    const desc = document.getElementById("procDescuento");
 
    if (!sel.value) {
      CatalogTable.showNotification("Selecciona un procedimiento", "warning");
      return;
    }
 
    // Validar precio especial
    if (desc.value && parseFloat(desc.value) < 0) {
      CatalogTable.showNotification(
        "El precio especial no puede ser negativo",
        "error",
      );
      return;
    }
 
    const proc = this._catalogos?.procedimientos?.find(
      (p) => p.id_procedimiento == sel.value,
    );
    if (!proc) return;
 
    this._procsTemp.push({
      id_procedimiento: parseInt(sel.value),
      nombre: proc.nombre_procedimiento,
      precio_base: parseFloat(proc.precio_base),
      numero_pieza: pieza.value || null,
      costo_descuento: desc.value || null,
    });
 
    sel.value = "";
    pieza.value = "";
    desc.value = "";
    document.getElementById("rowAgregarProc").style.display = "none";
    this._renderProcsTemp();
  },
 
  quitarProcTemp(idx) {
    this._procsTemp.splice(idx, 1);
    this._renderProcsTemp();
  },
 
  _renderProcsTemp() {
    const tbody = document.getElementById("bodyProcsPlan");
    if (!tbody) return;
 
    const total = this._procsTemp.reduce(
      (sum, p) =>
        sum +
        (p.costo_descuento ? parseFloat(p.costo_descuento) : p.precio_base),
      0,
    );
 
    const totalEl = document.getElementById("totalPlan");
    if (totalEl)
      totalEl.textContent =
        "$" + total.toLocaleString("es-MX", { minimumFractionDigits: 2 });
 
    if (!this._procsTemp.length) {
      tbody.innerHTML = `
                <tr id="rowSinProcs">
                    <td colspan="5" style="text-align:center; color:#adb5bd; padding:16px;">
                        Sin procedimientos agregados
                    </td>
                </tr>`;
      return;
    }
 
    tbody.innerHTML = this._procsTemp
      .map(
        (p, i) => `
            <tr>
                <td>${escHtml(p.nombre)}</td>
                <td class="text-center">${p.numero_pieza || "—"}</td>
                <td>$${p.precio_base.toLocaleString("es-MX", { minimumFractionDigits: 2 })}</td>
                <td>${
                  p.costo_descuento
                    ? "$" +
                      parseFloat(p.costo_descuento).toLocaleString("es-MX", {
                        minimumFractionDigits: 2,
                      })
                    : "—"
                }</td>
                <td class="acciones-cell">
                    <button class="btn-accion eliminar" onclick="planesController.quitarProcTemp(${i})">
                        <i class="ri-delete-bin-6-line"></i>
                    </button>
                </td>
            </tr>`,
      )
      .join("");
  },
 
  _mostrarLoading(show) {
    const el = document.getElementById("planesLoading");
    if (el) el.style.display = show ? "block" : "none";
  },
 
  _ocultarFormulario() {
    const form = document.getElementById("formNuevoPlanContainer");
    if (form) form.style.display = "none";
    this._procsTemp = [];
    this._renderProcsTemp();
    ["planFecha", "planNotas"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = "";
    });
    ["planEspecialista", "planEstatus"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = "";
    });
  },
 
  _poblarSelects() {
    const selEsp = document.getElementById("planEspecialista");
    if (selEsp && this._catalogos?.especialistas) {
      selEsp.innerHTML = '<option value="">Seleccionar</option>';
      this._catalogos.especialistas.forEach((e) => {
        const opt = document.createElement("option");
        opt.value = e.id_especialista;
        opt.textContent = e.nombre_completo;
        selEsp.appendChild(opt);
      });
    }
 
    const selEst = document.getElementById("planEstatus");
    if (selEst && this._catalogos?.estatus) {
      selEst.innerHTML = '<option value="">Seleccionar</option>';
      this._catalogos.estatus.forEach((e) => {
        const opt = document.createElement("option");
        opt.value = e.id_estatus_tratamiento;
        opt.textContent = e.estatus_tratamiento;
        selEst.appendChild(opt);
      });
    }
 
    const selProc = document.getElementById("procSelect");
    if (selProc && this._catalogos?.procedimientos) {
      selProc.innerHTML =
        '<option value="">Seleccionar procedimiento...</option>';
      this._catalogos.procedimientos.forEach((p) => {
        const opt = document.createElement("option");
        opt.value = p.id_procedimiento;
        opt.textContent = `${p.nombre_procedimiento} — $${parseFloat(p.precio_base).toLocaleString("es-MX", { minimumFractionDigits: 2 })}`;
        opt.dataset.precio = p.precio_base;
        selProc.appendChild(opt);
      });
    }
  },
 
  _optionsEstatus(seleccionado) {
    if (!this._catalogos?.estatus) return "";
    return this._catalogos.estatus
      .map(
        (e) =>
          `<option value="${e.id_estatus_tratamiento}"
                ${e.id_estatus_tratamiento == seleccionado ? "selected" : ""}>
                ${e.estatus_tratamiento}
            </option>`,
      )
      .join("");
  },
 
  _optionsProcedimientos() {
    if (!this._catalogos?.procedimientos) return "";
    return this._catalogos.procedimientos
      .map(
        (p) =>
          `<option value="${p.id_procedimiento}" data-precio="${p.precio_base}">
                ${escHtml(p.nombre_procedimiento)} — $${parseFloat(p.precio_base).toLocaleString("es-MX", { minimumFractionDigits: 2 })}
            </option>`,
      )
      .join("");
  },
};
 
// ── Helpers globales ──────────────────────────────────────────────
function escHtml(str) {
  const d = document.createElement("div");
  d.textContent = str ?? "";
  return d.innerHTML;
}
 
function formatFecha(fecha) {
  if (!fecha) return "—";
  const [y, m, d] = fecha.split("-");
  return `${d}/${m}/${y}`;
}
 
function badgeClase(estatus) {
  const mapa = {
    Programado: "programado",
    "En curso": "en-curso",
    Pausado: "pausado",
    Terminado: "completado",
  };
  return mapa[estatus] || "pendiente";
}
 
function imprimirPlanes() {
  window.print();
}
 
// ── Eventos ───────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("btnNuevoPlan")?.addEventListener("click", () => {
    const form = document.getElementById("formNuevoPlanContainer");
    if (!form) return;
    const visible = form.style.display !== "none";
    form.style.display = visible ? "none" : "block";
    if (!visible) {
      // Fecha de hoy por defecto al abrir
      const inputFecha = document.getElementById("planFecha");
      if (inputFecha && !inputFecha.value) {
        inputFecha.value = new Date().toISOString().split("T")[0];
      }
      // Limitar fecha máxima a hoy
      if (inputFecha) inputFecha.max = new Date().toISOString().split("T")[0];
    }
  });
 
  document.getElementById("btnCancelarPlan")?.addEventListener("click", () => {
    planesController._ocultarFormulario();
  });
 
  document.getElementById("btnGuardarPlan")?.addEventListener("click", () => {
    planesController.guardar();
  });
 
  document
    .getElementById("btnAgregarProcPlan")
    ?.addEventListener("click", () => {
      const row = document.getElementById("rowAgregarProc");
      if (row) row.style.display = "flex";
    });
 
  document.getElementById("btnConfirmarProc")?.addEventListener("click", () => {
    planesController.agregarProcTemp();
  });
 
  document.getElementById("btnCancelarProc")?.addEventListener("click", () => {
    const row = document.getElementById("rowAgregarProc");
    if (row) row.style.display = "none";
  });
});