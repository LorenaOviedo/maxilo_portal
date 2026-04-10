<?php /* MODAL INVENTARIO PARA SOLO LECTURA */ ?>
 
<div id="modalInventario-overlay" class="modal-overlay"></div>
 
<div id="modalInventario" class="modal-container">
 
    <!-- Header -->
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title">
                Detalle de inventario
                <span class="highlight" id="modalInvNombre"></span>
            </h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close"
                onclick="cerrarModal('modalInventario')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <!-- Body — sin tabs, todo en una sola vista -->
    <div class="modal-body">
        <div class="modal-form">
 
            <!-- Producto -->
            <div class="form-section-title">Datos del producto</div>
 
            <div class="inv-detalle-grid">
 
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Código</span>
                    <span class="inv-detalle-value" id="invDetCodigo">—</span>
                </div>
 
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Nombre</span>
                    <span class="inv-detalle-value" id="invDetNombre">—</span>
                </div>
 
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Tipo</span>
                    <span class="inv-detalle-value" id="invDetTipo">—</span>
                </div>
 
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Marca</span>
                    <span class="inv-detalle-value" id="invDetMarca">—</span>
                </div>
 
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Precio compra</span>
                    <span class="inv-detalle-value" id="invDetPrecio">—</span>
                </div>
 
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Registro sanitario</span>
                    <span class="inv-detalle-value" id="invDetRegistro">—</span>
                </div>
 
                <div class="inv-detalle-row inv-detalle-full">
                    <span class="inv-detalle-label">Descripción</span>
                    <span class="inv-detalle-value" id="invDetDescripcion"
                        style="color:#6c757d;font-style:italic;">—</span>
                </div>
 
            </div>
 
            <!-- Inventario -->
            <div class="form-section-title" style="margin-top:20px;">Estado de inventario</div>
 
            <div class="inv-detalle-grid">
 
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Stock actual</span>
                    <span class="inv-detalle-value" id="invDetStock">—</span>
                </div>
 
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Stock mínimo</span>
                    <span class="inv-detalle-value" id="invDetStockMin">—</span>
                </div>
 
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Lote</span>
                    <span class="inv-detalle-value" id="invDetLote">—</span>
                </div>
 
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Fecha fabricación</span>
                    <span class="inv-detalle-value" id="invDetFechaFab">—</span>
                </div>
 
                <div class="inv-detalle-row">
                    <span class="inv-detalle-label">Fecha caducidad</span>
                    <span class="inv-detalle-value" id="invDetFechaCad">—</span>
                </div>
 
            </div>
 
        </div>
    </div><!-- /.modal-body -->
 
    <!-- Footer — solo cerrar -->
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel"
            onclick="cerrarModal('modalInventario')">
            Cerrar
        </button>
    </div>
 
</div><!-- /#modalInventario -->