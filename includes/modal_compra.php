<?php /* MODAL ORDEN DE COMPRA */ ?>
 
<div id="modalCompra-overlay" class="modal-overlay"></div>
 
<div id="modalCompra" class="modal-container modal-lg">
 
    <!-- Header -->
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title">
                Orden de Compra
                <span class="highlight" id="modalCompraFolio"></span>
            </h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close"
                onclick="cerrarModal('modalCompra')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <!-- Tabs -->
    <div class="modal-tabs">
        <button class="modal-tab active" data-tab="tabOCDatos"
            onclick="cambiarTab('modalCompra', 'tabOCDatos')">
            Datos<br>Generales
        </button>
        <button class="modal-tab" data-tab="tabOCDetalle"
            onclick="cambiarTab('modalCompra', 'tabOCDetalle')">
            Detalle de<br>Productos
        </button>
        <button class="modal-tab" data-tab="tabOCTotales"
            onclick="cambiarTab('modalCompra', 'tabOCTotales')">
            Totales y<br>Observaciones
        </button>
    </div>
 
    <!-- Body -->
    <div class="modal-body">
 
        <!-- ── Tab 1: Datos Generales ──────────────────────────────────── -->
        <div id="tabOCDatos" class="modal-tab-content active">
            <form class="modal-form" id="formCompra" autocomplete="off">
                <input type="hidden" id="ocId" name="id_compra">
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Folio de orden <span class="required">*</span></label>
                        <input type="text" id="ocFolio" name="folio_compra" class="form-input"
                            placeholder="Ej: OC-2026-001"
                            style="text-transform:uppercase;" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de compra <span class="required">*</span></label>
                        <select id="ocTipoCompra" name="id_tipo_compra" class="form-select">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                </div>
 
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Proveedor <span class="required">*</span></label>
                        <select id="ocProveedor" name="id_proveedor" class="form-select">
                            <option value="">Seleccionar proveedor...</option>
                        </select>
                    </div>
                </div>
 
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Fecha de emisión <span class="required">*</span></label>
                        <input type="date" id="ocFechaEmision" name="fecha_emision" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Entrega estimada</label>
                        <input type="date" id="ocFechaEntregaEst" name="fecha_entrega_estimada" class="form-input">
                    </div>
                    <div class="form-group" id="groupFechaEntrega" style="display:none;">
                        <label class="form-label">Fecha de entrega real</label>
                        <input type="date" id="ocFechaEntrega" name="fecha_entrega" class="form-input">
                    </div>
                </div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Moneda <span class="required">*</span></label>
                        <select id="ocMoneda" name="id_moneda" class="form-select">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estatus</label>
                        <select id="ocEstatus" name="id_estatus_orden_compra" class="form-select">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                </div>
 
            </form>
        </div><!-- /#tabOCDatos -->
 
        <!-- ── Tab 2: Detalle de Productos ────────────────────────────── -->
        <div id="tabOCDetalle" class="modal-tab-content">
            <div class="modal-form">
 
                <div class="form-section-title">Productos de la orden</div>
 
                <!-- Toolbar agregar producto -->
                <div class="tab-toolbar" style="margin-bottom:12px;">
                    <button type="button" class="btn-modal-add" id="btnAgregarProducto"
                        onclick="compraController.mostrarFilaProducto()">
                        <i class="ri-add-line"></i> Agregar producto
                    </button>
                </div>
 
                <!-- Fila nueva entrada (oculta por defecto) -->
                <div id="rowNuevoProducto" class="proc-add-row"
                    style="display:none; flex-wrap:wrap; gap:8px; margin-bottom:12px; align-items:flex-end;">
 
                    <div style="flex:2; min-width:200px;">
                        <label class="form-label" style="font-size:12px;">Producto</label>
                        <select id="ocSelectProducto" class="form-select">
                            <option value="">Seleccionar producto...</option>
                        </select>
                    </div>
                    <div style="flex:0 0 100px;">
                        <label class="form-label" style="font-size:12px;">Cantidad</label>
                        <input type="number" id="ocCantidad" class="form-input"
                            min="1" placeholder="1" autocomplete="off">
                    </div>
                    <div style="flex:0 0 130px;">
                        <label class="form-label" style="font-size:12px;">Precio unitario</label>
                        <input type="number" id="ocPrecioUnitario" class="form-input"
                            min="0" step="0.01" placeholder="0.00" autocomplete="off">
                    </div>
                    <div style="display:flex; gap:4px; padding-bottom:2px;">
                        <button type="button" class="btn-confirmar-proc"
                            onclick="compraController.confirmarProducto()">
                            <i class="ri-check-line"></i>
                        </button>
                        <button type="button" class="btn-cancelar-proc"
                            onclick="compraController.ocultarFilaProducto()">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                </div>
 
                <!-- Tabla de detalle -->
                <table class="plan-table" id="tablaDetalle">
                    <thead>
                        <tr>
                            <th>PRODUCTO</th>
                            <th class="text-center">CANTIDAD</th>
                            <th class="text-right">PRECIO UNIT.</th>
                            <th class="text-right">SUBTOTAL</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="bodyDetalle">
                        <tr id="rowSinProductos">
                            <td colspan="5" style="text-align:center; color:#adb5bd; padding:16px;">
                                Sin productos agregados
                            </td>
                        </tr>
                    </tbody>
                </table>
 
                <input type="hidden" id="ocDetalleJson" name="detalle_json" value="[]">
 
            </div>
        </div><!-- /#tabOCDetalle -->
 
        <!-- ── Tab 3: Totales y Observaciones ─────────────────────────── -->
        <div id="tabOCTotales" class="modal-tab-content">
            <div class="modal-form">
 
                <div class="form-section-title">IVA y totales</div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Tasa IVA (%)</label>
                        <select id="ocTasaIva" name="tasa_iva" class="form-select">
                            <option value="0">0% — Exento</option>
                            <option value="8">8%</option>
                            <option value="16" selected>16%</option>
                        </select>
                    </div>
                </div>
 
                <!-- Resumen de totales -->
                <div class="totales-resumen" style="margin-top:20px;">
                    <div class="totales-row">
                        <span>Subtotal:</span>
                        <span id="resSubtotal">$0.00</span>
                    </div>
                    <div class="totales-row">
                        <span id="resIvaLabel">IVA (16%):</span>
                        <span id="resIva">$0.00</span>
                    </div>
                    <div class="totales-row totales-total">
                        <span>TOTAL:</span>
                        <span id="resTotal">$0.00</span>
                    </div>
                </div>
 
                <div class="form-section-title" style="margin-top:20px;">Observaciones</div>
                <div class="form-group">
                    <textarea id="ocObservaciones" name="observaciones" class="form-input"
                        rows="4" placeholder="Notas adicionales sobre la orden..."
                        style="resize:vertical;"></textarea>
                </div>
 
            </div>
        </div><!-- /#tabOCTotales -->
 
    </div><!-- /.modal-body -->
 
    <!-- Footer -->
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel"
            onclick="cerrarModal('modalCompra')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnGuardarCompra">
            <i class="ri-save-line"></i> Guardar orden
        </button>
    </div>
 
</div><!-- /#modalCompra -->
 
<style>
/* Totales */
.totales-resumen {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 16px 20px;
    max-width: 360px;
    margin-left: auto;
}
.totales-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 14px;
    color: #495057;
    border-bottom: 1px solid #e9ecef;
}
.totales-row:last-child { border-bottom: none; }
.totales-total {
    font-size: 16px;
    font-weight: 700;
    color: #212529;
    padding-top: 10px;
}
/* Modal grande */
.modal-lg { max-width: 860px; }
.text-right { text-align: right !important; }
</style>