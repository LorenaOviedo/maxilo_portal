<?php /* MODAL PRODUCTO — VER / EDITAR / NUEVO */ ?>
 
<div id="modalProducto-overlay" class="modal-overlay"></div>
 
<div id="modalProducto" class="modal-container">
 
    <!-- Header -->
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title">
                Producto
                <span class="highlight" id="modalProductoNombre"></span>
            </h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close"
                onclick="cerrarModal('modalProducto')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <!-- Tabs -->
    <div class="modal-tabs">
        <button class="modal-tab active" data-tab="tabProdDatos"
            onclick="cambiarTab('modalProducto', 'tabProdDatos')">
            Datos del Producto
        </button>
        <button class="modal-tab" data-tab="tabProdInventario"
            onclick="cambiarTab('modalProducto', 'tabProdInventario')">
            Datos del lote y Control de stock
        </button>
    </div>
 
    <!-- Body -->
    <div class="modal-body">
 
        <!-- ── Tab 1: Datos del Producto ──────────────────────────────── -->
        <div id="tabProdDatos" class="modal-tab-content active">
            <form class="modal-form" id="formProducto" autocomplete="off">
                <input type="hidden" id="prodId" name="id_producto">
 
                <!-- Código + Tipo -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">
                            Código de producto <span class="required">*</span>
                        </label>
                        <input type="text" id="prodCodigo" name="codigo_producto"
                            class="form-input" maxlength="50"
                            placeholder="Ej: PROD-001"
                            style="text-transform:uppercase;" autocomplete="off">
                        <small class="form-hint">Solo letras, números y guiones</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            Tipo de producto <span class="required">*</span>
                        </label>
                        <select id="prodTipo" name="id_tipo_producto" class="form-select">
                            <option value="">Seleccionar tipo...</option>
                        </select>
                    </div>
                </div>
 
                <!-- Nombre -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">
                            Nombre del producto <span class="required">*</span>
                        </label>
                        <input type="text" id="prodNombre" name="nombre_producto"
                            class="form-input" maxlength="150"
                            placeholder="Nombre completo del producto"
                            style="text-transform:uppercase;" autocomplete="off">
                    </div>
                </div>
 
                <!-- Marca + Registro sanitario -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Marca</label>
                        <input type="text" id="prodMarca" name="marca"
                            class="form-input" maxlength="100"
                            placeholder="Ej: Generica" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Registro sanitario</label>
                        <input type="text" id="prodRegistro" name="registro_sanitario"
                            class="form-input" maxlength="50"
                            placeholder="Ej: INVIMA-2024-001" autocomplete="off">
                    </div>
                </div>
 
                <!-- Precio -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">
                            Precio de compra ($) <span class="required">*</span>
                        </label>
                        <input type="number" id="prodPrecio" name="precio_compra"
                            class="form-input" min="0" step="0.01"
                            placeholder="0.00" autocomplete="off">
                    </div>
                </div>
 
                <!-- Descripción -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <textarea id="prodDescripcion" name="descripcion"
                            class="form-input" rows="3"
                            placeholder="Descripción adicional del producto..."
                            style="resize:vertical;"></textarea>
                    </div>
                </div>
 
            </form>
        </div><!-- /#tabProdDatos -->
 
        <!-- ── Tab 2: Inventario ───────────────────────────────────────── -->
        <div id="tabProdInventario" class="modal-tab-content">
            <form class="modal-form" id="formProdInventario" autocomplete="off">
 
                <div class="form-section-title">Control de stock</div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Stock mínimo</label>
                        <input type="number" id="prodStockMin" name="stock_minimo"
                            class="form-input" min="0" placeholder="0" autocomplete="off">
                        <small class="form-hint">Alerta cuando el stock llegue a este nivel</small>
                    </div>
                </div>
 
                <div class="form-section-title" style="margin-top:16px;">Lote y fechas</div>
 
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Número de lote<span class="required">*</span></label>
                        <input type="text" id="prodLote" name="lote"
                            class="form-input" maxlength="50"
                            placeholder="Ej: LOT-2024-001" autocomplete="off">
                    </div>
                </div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Fecha de fabricación<span class="required">*</span></label>
                        <input type="date" id="prodFechaFab" name="fecha_fabricacion"
                            class="form-input" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha de caducidad<span class="required">*</span></label>
                        <input type="date" id="prodFechaCad" name="fecha_caducidad"
                            class="form-input" autocomplete="off">
                    </div>
                </div>
 
            </form>
        </div><!-- /#tabProdInventario -->
 
    </div><!-- /.modal-body -->
 
    <!-- Footer -->
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel"
            onclick="cerrarModal('modalProducto')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnGuardarProducto">
            Guardar cambios
        </button>
    </div>
 
</div><!-- /#modalProducto -->