<?php /* MODAL REGISTRO DE MOVIMIENTO */ ?>
 
<div id="modalMovimiento-overlay" class="modal-overlay"></div>
 
<div id="modalMovimiento" class="modal-container">
 
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
 
    <div class="modal-body">
        <form class="modal-form" id="formMovimiento" autocomplete="off">
 
            <input type="hidden" id="movIdInventario" name="id_inventario">
 
            <!-- ── Paso 1: Producto + Lote ─────────────────────────── -->
            <div class="form-section-title">
                <i class="ri-box-3-line"></i> Seleccionar producto y lote
            </div>
 
            <!-- Buscador de producto con autocompletado -->
            <div class="form-row cols-1">
                <div class="form-group">
                    <label class="form-label">
                        Producto <span class="required">*</span>
                    </label>
                    <div style="position:relative;">
                        <input type="text" id="movProdInput"
                            class="form-input"
                            placeholder="Escriba nombre o código del producto..."
                            autocomplete="off">
                        <input type="hidden" id="movProdValue">
                        <div id="movProdDropdown" class="pac-dropdown" style="display:none;"></div>
                    </div>
                </div>
            </div>
 
            <!-- Select de lote — aparece solo al seleccionar un producto -->
            <div class="form-row cols-1" id="movGrupoLote" style="display:none;">
                <div class="form-group">
                    <label class="form-label">
                        Lote <span class="required">*</span>
                    </label>
                    <select id="movLoteSelect" class="form-select">
                        <option value="">Seleccionar lote...</option>
                    </select>
                    <small class="form-hint">
                        Solo se muestran los lotes registrados para este producto
                    </small>
                </div>
            </div>
 
            <!-- Botón seleccionar -->
            <div id="movGrupoBtn" style="display:none; margin-bottom:16px;">
                <button type="button" class="btn-search" id="btnSeleccionar"
                    onclick="movimientoController.seleccionar()">
                    <i class="ri-check-line"></i> Seleccionar
                </button>
            </div>
 
            <!-- Tarjeta de confirmación de selección -->
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
                            <div style="font-size:12px; color:#6c757d; margin-top:2px;"
                                id="movResLote">—</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:12px; color:#6c757d;">Stock actual</div>
                            <div style="font-size:22px; font-weight:700; color:#20a89e;"
                                id="movResStock">—</div>
                        </div>
                    </div>
                    <div style="margin-top:10px;">
                        <a href="#"
                            onclick="movimientoController.resetSeleccion(); return false;"
                            style="font-size:12px; color:#6c757d; text-decoration:none;">
                            <i class="ri-refresh-line"></i> Cambiar producto / lote
                        </a>
                    </div>
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
 
                <!-- Preview stock resultante -->
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
 
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel"
            onclick="cerrarModal('modalMovimiento')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save"
            id="btnGuardarMovimiento" style="display:none;">
            <i class="ri-save-line"></i> Registrar movimiento
        </button>
    </div>
 
</div><!-- /#modalMovimiento -->