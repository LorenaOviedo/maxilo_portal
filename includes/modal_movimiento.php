<?php /* MODAL REGISTRO DE MOVIMIENTO */ ?>
 
<div id="modalMovimiento-overlay" class="modal-overlay"></div>
 
<div id="modalMovimiento" class="modal-container">
 
    <!-- Header -->
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title">Nuevo Movimiento de Inventario</h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close"
                onclick="cerrarModal('modalMovimiento')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <!-- Body -->
    <div class="modal-body">
        <form class="modal-form" id="formMovimiento" autocomplete="off">
 
            <input type="hidden" id="movIdInventario" name="id_inventario">
 
            <!-- ── Paso 1: Buscar producto ─────────────────────────── -->
            <div class="form-section-title">
                <i class="ri-search-line"></i> Buscar producto
            </div>
 
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Código de producto <span class="required">*</span>
                    </label>
                    <input type="text" id="movCodigo" class="form-input"
                        placeholder="Ej: PROD-001"
                        style="text-transform:uppercase;" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label">Lote<span class="required">*</span></label>
                    <input type="text" id="movLote" class="form-input"
                        placeholder="Ej: LOT-2024-001 (opcional)" autocomplete="off">
                </div>
            </div>
 
            <div style="margin-bottom:16px;">
                <button type="button" class="btn-search" id="btnBuscarProducto"
                    onclick="movimientoController.buscarProducto()">
                    <i class="ri-search-line"></i> Buscar
                </button>
            </div>
 
            <!-- ── Resultado de búsqueda ───────────────────────────── -->
            <div id="movResultado" style="display:none; margin-bottom:20px;">
                <div style="background:#f0faf9; border:1px solid #b2dfdb;
                    border-radius:8px; padding:14px 16px;">
                    <div style="display:flex; justify-content:space-between;
                        align-items:flex-start; flex-wrap:wrap; gap:8px;">
                        <div>
                            <div style="font-weight:700; font-size:15px;"
                                id="movResNombre">—</div>
                            <div style="font-size:12px; color:#6c757d; margin-top:3px;"
                                id="movResCodigo">—</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:12px; color:#6c757d;">Stock actual</div>
                            <div style="font-size:22px; font-weight:700;"
                                id="movResStock">—</div>
                        </div>
                    </div>
                    <div style="margin-top:8px; font-size:12px; color:#6c757d;"
                        id="movResLote"></div>
                </div>
            </div>
 
            <!-- Mensaje si no se encuentra -->
            <div id="movNoEncontrado" style="display:none; margin-bottom:16px;">
                <div style="background:#fff8e1; border:1px solid #ffe082;
                    border-radius:8px; padding:12px 16px; color:#e65100; font-size:14px;">
                    <i class="ri-error-warning-line"></i>
                    No se encontró ningún producto con ese código y lote.
                </div>
            </div>
 
            <!-- ── Paso 2: Datos del movimiento ────────────────────── -->
            <div id="movFormDatos" style="display:none;">
 
                <div class="form-section-title" style="margin-top:4px;">
                    <i class="ri-exchange-box-line"></i> Datos del movimiento
                </div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">
                            Tipo de movimiento <span class="required">*</span>
                        </label>
                        <select id="movTipo" name="id_tipo_movimiento" class="form-select">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            Fecha <span class="required">*</span>
                        </label>
                        <input type="date" id="movFecha" name="fecha_movimiento"
                            class="form-input" autocomplete="off">
                    </div>
                </div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label" id="movCantidadLabel">
                            Cantidad <span class="required">*</span>
                        </label>
                        <input type="number" id="movCantidad" name="cantidad"
                            class="form-input" min="1" placeholder="0" autocomplete="off">
                        <small class="form-hint" id="movCantidadHint"></small>
                    </div>
                </div>
 
                <!-- Preview del resultado -->
                <div id="movPreview" style="display:none; margin-top:8px;">
                    <div style="background:#f8f9fa; border-radius:8px;
                        padding:12px 16px; font-size:14px;">
                        <span style="color:#6c757d;">Stock resultante: </span>
                        <strong id="movPreviewStock" style="font-size:16px;">—</strong>
                    </div>
                </div>
 
            </div><!-- /#movFormDatos -->
 
        </form>
    </div><!-- /.modal-body -->
 
    <!-- Footer -->
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel"
            onclick="cerrarModal('modalMovimiento')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnGuardarMovimiento"
            style="display:none;">
            <i class="ri-save-line"></i> Registrar movimiento
        </button>
    </div>
 
</div><!-- /#modalMovimiento -->