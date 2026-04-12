<?php /* MODAL FACTURACIÓN — 2 PASOS */ ?>
 
<div id="modalFactura-overlay" class="modal-overlay"></div>
 
<div id="modalFactura" class="modal-container">
 
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title" id="modalFacturaTitulo">Solicitar Factura</h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close"
                onclick="cerrarModal('modalFactura')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <div class="modal-body">
 
        <!-- ── Vista: Solicitar (paso 1) ──────────────────────────── -->
        <div id="facturaVistaSolicitar">
            <form class="modal-form" id="formFactura" autocomplete="off">
                <input type="hidden" id="factIdPago"     name="id_pago">
                <input type="hidden" id="factIdCita"     name="id_cita">
                <input type="hidden" id="factNumPaciente" name="numero_paciente">
 
                <!-- Resumen del pago -->
                <div id="factPagoInfo" style="background:#f8f9fa; border-radius:8px;
                    padding:12px 16px; margin-bottom:20px;">
                    <div style="font-size:12px;color:#6c757d;">Pago a facturar</div>
                    <div style="font-weight:700;" id="factPagoPaciente">—</div>
                    <div style="font-size:13px;color:#6c757d;" id="factPagoDetalle">—</div>
                </div>
 
                <div class="form-section-title">
                    <i class="ri-file-text-line"></i> Datos fiscales
                </div>
 
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">
                            RFC <span class="required">*</span>
                        </label>
                        <input type="text" id="factRFC" name="rfc"
                            class="form-input" maxlength="13"
                            placeholder="Ej: LOMP800101ABC"
                            style="text-transform:uppercase;" autocomplete="off">
                        <small class="form-hint">12 dígitos (persona física) o 13 (moral)</small>
                    </div>
                </div>
 
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">
                            Razón social <span class="required">*</span>
                        </label>
                        <input type="text" id="factRazonSocial" name="razon_social"
                            class="form-input" maxlength="200"
                            placeholder="Nombre completo o razón social"
                            style="text-transform:uppercase;" autocomplete="off">
                    </div>
                </div>
 
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">
                            Uso de CFDI <span class="required">*</span>
                        </label>
                        <select id="factCFDI" name="cfdi" class="form-select">
                            <option value="">Seleccionar uso...</option>
                            <option value="G03">G03 — Gastos en general</option>
                            <option value="D01" selected>D01 — Honorarios médicos, dentales y gastos hospitalarios</option>
                            <option value="D07">D07 — Primas por seguros de gastos médicos</option>
                            <option value="I08">I08 — Gastos médicos por incapacidad o discapacidad</option>
                            <option value="G01">G01 — Adquisición de mercancías</option>
                            <option value="P01">P01 — Por definir</option>
                        </select>
                    </div>
                </div>
 
            </form>
        </div>
 
        <!-- ── Vista: Completar timbrado (paso 2) ─────────────────── -->
        <div id="facturaVistaCompletar" style="display:none;">
            <form class="modal-form" id="formCompletarFactura" autocomplete="off">
                <input type="hidden" id="factIdSolicitud" name="id_solicitud_factura">
 
                <div style="background:#e8f5e9; border-radius:8px; padding:12px 16px;
                    margin-bottom:20px; font-size:13px; color:#2e7d32;">
                    <i class="ri-checkbox-circle-line"></i>
                    Solicitud enviada. Registra los datos del XML timbrado por el PAC.
                </div>
 
                <div class="form-section-title">
                    <i class="ri-government-line"></i> Datos del timbrado
                </div>
 
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">
                            Folio fiscal (UUID) <span class="required">*</span>
                        </label>
                        <input type="text" id="factFolioFiscal" name="folio_fiscal"
                            class="form-input" maxlength="50"
                            placeholder="Ej: 6D8F2A1B-..." autocomplete="off">
                    </div>
                </div>
 
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">
                            Fecha de timbrado <span class="required">*</span>
                        </label>
                        <input type="datetime-local" id="factFechaTimb"
                            name="fecha_facturacion" class="form-input" autocomplete="off">
                    </div>
                </div>
 
            </form>
        </div>
 
        <!-- ── Vista: Detalle solicitud existente ─────────────────── -->
        <div id="facturaVistaDetalle" style="display:none;">
            <div class="modal-form">
                <div class="form-section-title">Datos fiscales</div>
                <div class="inv-detalle-grid">
                    <div class="inv-detalle-row">
                        <span class="inv-detalle-label">RFC</span>
                        <span class="inv-detalle-value" id="factDetRFC">—</span>
                    </div>
                    <div class="inv-detalle-row">
                        <span class="inv-detalle-label">Estatus</span>
                        <span class="inv-detalle-value" id="factDetEstatus">—</span>
                    </div>
                    <div class="inv-detalle-row inv-detalle-full">
                        <span class="inv-detalle-label">Razón social</span>
                        <span class="inv-detalle-value" id="factDetRazonSocial">—</span>
                    </div>
                    <div class="inv-detalle-row inv-detalle-full">
                        <span class="inv-detalle-label">Folio fiscal (UUID)</span>
                        <span class="inv-detalle-value" id="factDetFolioFiscal"
                            style="font-family:monospace;font-size:12px;">—</span>
                    </div>
                    <div class="inv-detalle-row">
                        <span class="inv-detalle-label">Fecha timbrado</span>
                        <span class="inv-detalle-value" id="factDetFechaTimbrado">—</span>
                    </div>
                    <div class="inv-detalle-row">
                        <span class="inv-detalle-label">CFDI</span>
                        <span class="inv-detalle-value" id="factDetCFDI">—</span>
                    </div>
                </div>
            </div>
        </div>
 
    </div><!-- /.modal-body -->
 
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel"
            onclick="cerrarModal('modalFactura')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save"
            id="btnGuardarFactura" style="display:none;">
            <i class="ri-send-plane-line"></i> Solicitar factura
        </button>
        <button type="button" class="btn-modal-save"
            id="btnCompletarFactura" style="display:none;background:#28a745;">
            <i class="ri-check-double-line"></i> Registrar timbrado
        </button>
    </div>
 
</div><!-- /#modalFactura -->