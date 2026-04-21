<?php /* MODAL PAGOS — REGISTRAR Y VER DETALLE */ ?>

<div id="modalPago-overlay" class="modal-overlay"></div>

<div id="modalPago" class="modal-container">

    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title">Registrar Pago</h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close" onclick="cerrarModal('modalPago')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="modal-body">
        <form class="modal-form" id="formPago" autocomplete="off">

            <!-- ── Selección de cita ──────────────────────────────── -->
            <div class="form-section-title">
                <i class="ri-calendar-check-line"></i> Cita a cobrar
            </div>

            <div class="form-row cols-1">
                <div class="form-group">
                    <label class="form-label">
                        Cita atendida <span class="required">*</span>
                    </label>
                    <select id="pagoCita" name="id_cita" class="form-select">
                        <option value="">Seleccionar cita...</option>
                    </select>
                    <small class="form-hint">
                        Solo se muestran citas con estatus "Atendida" sin pago registrado
                    </small>
                </div>
            </div>

            <!-- Tarjeta resumen de la cita seleccionada -->
            <div id="pagoCitaInfo" style="display:none; margin-bottom:16px;">
                <div style="background:#f0faf9; border:1px solid #b2dfdb;
                    border-radius:8px; padding:14px 16px;">
                    <div style="display:flex; justify-content:space-between;
                        flex-wrap:wrap; gap:8px;">
                        <div>
                            <div style="font-weight:700; font-size:14px;" id="pagoCitaPaciente">—</div>
                            <div style="font-size:12px; color:#6c757d; margin-top:3px;" id="pagoCitaEspecialista">—
                            </div>
                            <div style="font-size:12px; color:#6c757d; margin-top:2px;" id="pagoCitaMotivo">—</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:11px; color:#6c757d;">Costo de cita</div>
                            <div style="font-size:20px; font-weight:700; color:#20a89e;" id="pagoCitaCosto">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Datos del pago ─────────────────────────────────── -->
            <div class="form-section-title" style="margin-top:4px;">
                <i class="ri-secure-payment-line"></i> Datos del pago
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Fecha de pago <span class="required">*</span>
                    </label>
                    <input type="date" id="pagoFecha" name="fecha_pago" class="form-input" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Método de pago <span class="required">*</span>
                    </label>
                    <select id="pagoMetodo" name="id_metodo_pago" class="form-select">
                        <option value="">Seleccionar método...</option>
                    </select>
                </div>
            </div>

            <!-- Referencia — aparece solo si el método lo requiere -->
            <div class="form-row cols-1" id="pagoGrupoRef" style="display:none;">
                <div class="form-group">
                    <label class="form-label">
                        Referencia / Número de autorización
                    </label>
                    <input type="text" id="pagoReferencia" name="referencia_pago" class="form-input" maxlength="100"
                        placeholder="Ej: 123456789" autocomplete="off">
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Monto total ($) <span class="required">*</span>
                    </label>
                    <input type="number" id="pagoMontoTotal" name="monto_total" class="form-input" min="0.01"
                        step="0.01" placeholder="0.00" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Monto neto ($) <span class="required">*</span>
                    </label>
                    <input type="number" id="pagoMontoNeto" name="monto_neto" class="form-input" min="0.01" step="0.01"
                        placeholder="0.00" autocomplete="off">
                    <small class="form-hint">Después de descuentos</small>
                </div>
            </div>

            <!-- Indicador de descuento -->
            <div id="pagoDescuento" style="display:none; margin-bottom:12px;">
                <div style="background:#e8f5e9; border-radius:6px;
                    padding:8px 14px; font-size:13px; color:#2e7d32;">
                    <i class="ri-price-tag-3-line"></i>
                    Descuento aplicado: <strong id="pagoDescuentoMonto">$0.00</strong>
                </div>
            </div>

            <div class="form-row cols-1">
                <div class="form-group">
                    <label class="form-label">Observaciones</label>
                    <textarea id="pagoObservaciones" name="observaciones" class="form-input" rows="2"
                        placeholder="Notas adicionales..." style="resize:vertical;"></textarea>
                </div>
            </div>

        </form>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="cerrarModal('modalPago')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnGuardarPago">
            <i class="ri-save-line"></i> Registrar pago
        </button>
    </div>

</div><!-- /#modalPago -->


<!-- ══════════════════════════════════════════════════════
     MODAL VER DETALLE DEL PAGO
════════════════════════════════════════════════════════ -->
<div id="modalDetallePago-overlay" class="modal-overlay"></div>

<div id="modalDetallePago" class="modal-container">

    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title">
                Detalle de Pago
                <span class="highlight" id="detPagoRecibo"></span>
            </h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close" onclick="cerrarModal('modalDetallePago')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="modal-body">
        <div class="modal-form">

            <div class="form-section-title">Datos de la cita</div>
            <div class="inv-detalle-grid">
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Paciente</span>
                    <span class="inv-detalle-value" id="detPagoPaciente">—</span>
                </div>
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Especialista</span>
                    <span class="inv-detalle-value" id="detPagoEspecialista">—</span>
                </div>
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Fecha de cita</span>
                    <span class="inv-detalle-value" id="detPagoFechaCita">—</span>
                </div>
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Motivo consulta</span>
                    <span class="inv-detalle-value" id="detPagoMotivo">—</span>
                </div>
            </div>

            <div class="form-section-title" style="margin-top:20px;">Datos del pago</div>
            <div class="inv-detalle-grid">
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Número de recibo</span>
                    <span class="inv-detalle-value" id="detPagoNumRecibo">—</span>
                </div>
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Fecha de pago</span>
                    <span class="inv-detalle-value" id="detPagoFecha">—</span>
                </div>
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Método de pago</span>
                    <span class="inv-detalle-value" id="detPagoMetodo">—</span>
                </div>
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Referencia</span>
                    <span class="inv-detalle-value" id="detPagoReferencia">—</span>
                </div>
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Monto total</span>
                    <span class="inv-detalle-value" id="detPagoTotal">—</span>
                </div>
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Monto neto</span>
                    <span class="inv-detalle-value" id="detPagoNeto">—</span>
                </div>
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Estatus</span>
                    <span class="inv-detalle-value" id="detPagoEstatus">—</span>
                </div>
                <div class="inv-detalle-row inv-detalle-full">
                    <span class="inv-detalle-label">Observaciones</span>
                    <span class="inv-detalle-value" id="detPagoObservaciones"
                        style="color:#6c757d;font-style:italic;">—</span>
                </div>
            </div>

        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="cerrarModal('modalDetallePago')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-secondary" id="btnImprimirRecibo" style="background:#ffffff;"
            onclick="pagoController._imprimirActual()"
            style="display:none;">
            <i class="ri-printer-line"></i> Imprimir recibo
        </button>
    </div>

</div><!-- /#modalDetallePago -->

<style>
    /* Reutilizar estilos del inventario */
    .inv-detalle-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .inv-detalle-full {
        grid-column: 1 / -1;
    }

    .inv-detalle-row {
        display: flex;
        flex-direction: column;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px 14px;
        gap: 3px;
    }

    .inv-detalle-label {
        font-size: 11px;
        font-weight: 600;
        color: #adb5bd;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .inv-detalle-value {
        font-size: 14px;
        font-weight: 500;
        color: #212529;
    }

    @media (max-width: 480px) {
        .inv-detalle-grid {
            grid-template-columns: 1fr;
        }

        .inv-detalle-full {
            grid-column: 1;
        }
    }
</style>


<!-- ══════════════════════════════════════════════════════
     MODAL EDITAR PAGO
════════════════════════════════════════════════════════ -->
<div id="modalEditarPago-overlay" class="modal-overlay"></div>

<div id="modalEditarPago" class="modal-container">

    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title">
                Editar Pago
                <span class="highlight" id="editPagoRecibo"></span>
            </h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close" onclick="cerrarModal('modalEditarPago')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="modal-body">
        <form class="modal-form" id="formEditarPago" autocomplete="off">
            <input type="hidden" id="editPagoId" name="id_pago">

            <!-- Resumen no editable -->
            <div id="editPagoInfo" style="background:#f8f9fa; border-radius:8px;
                padding:12px 16px; margin-bottom:20px;">
                <div style="font-size:11px;color:#6c757d;">Paciente</div>
                <div style="font-weight:700;" id="editPagoPaciente">—</div>
                <div style="font-size:12px;color:#6c757d;" id="editPagoFechaCita">—</div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Método de pago <span class="required">*</span>
                    </label>
                    <select id="editPagoMetodo" name="id_metodo_pago" class="form-select">
                        <option value="">Seleccionar...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Referencia / N° autorización</label>
                    <input type="text" id="editPagoReferencia" name="referencia_pago" class="form-input" maxlength="100"
                        placeholder="Ej: 123456789" autocomplete="off">
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Monto total ($) <span class="required">*</span>
                    </label>
                    <input type="number" id="editPagoMontoTotal" name="monto_total" class="form-input" min="0.01"
                        step="0.01" placeholder="0.00" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Monto neto ($) <span class="required">*</span>
                    </label>
                    <input type="number" id="editPagoMontoNeto" name="monto_neto" class="form-input" min="0.01"
                        step="0.01" placeholder="0.00" autocomplete="off">
                    <small class="form-hint">Después de descuentos</small>
                </div>
            </div>

            <!-- Indicador de descuento -->
            <div id="editPagoDescuento" style="display:none; margin-bottom:12px;">
                <div style="background:#e8f5e9; border-radius:6px;
                    padding:8px 14px; font-size:13px; color:#2e7d32;">
                    <i class="ri-price-tag-3-line"></i>
                    Descuento: <strong id="editPagoDescuentoMonto">$0.00</strong>
                </div>
            </div>

            <div class="form-row cols-1">
                <div class="form-group">
                    <label class="form-label">Observaciones</label>
                    <textarea id="editPagoObservaciones" name="observaciones" class="form-input" rows="2"
                        placeholder="Notas adicionales..." style="resize:vertical;"></textarea>
                </div>
            </div>

        </form>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="cerrarModal('modalEditarPago')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnActualizarPago">
            <i class="ri-save-line"></i> Guardar cambios
        </button>
    </div>

</div><!-- /#modalEditarPago -->