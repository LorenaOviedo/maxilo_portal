<?php
/* MODAL DE PROVEEDOR REUTILIZABLE — VER / EDITAR / NUEVO */
$modal_prov_id = 'modalProveedor';
?>
 
<!-- Overlay -->
<div id="<?php echo $modal_prov_id; ?>-overlay" class="modal-overlay"></div>
 
<!-- Contenedor del modal -->
<div id="<?php echo $modal_prov_id; ?>" class="modal-container">
 
    <!-- Header -->
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title">
                Proveedor
                <span class="highlight" id="modalProveedorNombre"></span>
            </h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close"
                onclick="cerrarModal('<?php echo $modal_prov_id; ?>')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <!-- Tabs -->
    <div class="modal-tabs">
        <button class="modal-tab active" data-tab="tabProvDatos"
            onclick="cambiarTab('<?php echo $modal_prov_id; ?>', 'tabProvDatos')">
            Datos<br>Generales
        </button>
        <button class="modal-tab" data-tab="tabProvContacto"
            onclick="cambiarTab('<?php echo $modal_prov_id; ?>', 'tabProvContacto')">
            Contacto y<br>Dirección
        </button>
        <button class="modal-tab" data-tab="tabProvComercial"
            onclick="cambiarTab('<?php echo $modal_prov_id; ?>', 'tabProvComercial')">
            Condiciones<br>Comerciales
        </button>
    </div>
 
    <!-- Body -->
    <div class="modal-body">
 
        <!-- ── Tab 1: Datos Generales ──────────────────────────────────── -->
        <div id="tabProvDatos" class="modal-tab-content active">
            <form class="modal-form" id="formProveedor" autocomplete="off">
                <input type="hidden" name="id_proveedor"       id="provId">
                <input type="hidden" name="id_direccion_actual" id="provIdDireccion">
 
                <!-- RFC + Tipo persona -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">RFC <span class="required">*</span></label>
                        <input type="text" name="rfc" id="provRFC" class="form-input"
                            maxlength="13" placeholder="Ej: LOMP800101ABC"
                            style="text-transform:uppercase;" autocomplete="off">
                        <small class="form-hint">12 dígitos (persona física) o 13 (moral)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de persona <span class="required">*</span></label>
                        <select name="tipo_persona" id="provTipoPersona" class="form-select">
                            <option value="">Seleccionar...</option>
                            <option value="Moral">Moral</option>
                            <option value="Física">Física</option>
                        </select>
                    </div>
                </div>
 
                <!-- Razón social -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Razón social <span class="required">*</span></label>
                        <input type="text" name="razon_social" id="provRazonSocial" class="form-input"
                            placeholder="Nombre completo o razón social"
                            style="text-transform:uppercase;" autocomplete="off">
                    </div>
                </div>
 
                <!-- Tipo producto/servicio -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Tipo de producto / servicio</label>
                        <select name="id_tipo_producto_proveedor" id="provTipoProducto" class="form-select">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                </div>
 
            </form>
        </div><!-- /#tabProvDatos -->
 
        <!-- ── Tab 2: Contacto y Dirección ────────────────────────────── -->
        <div id="tabProvContacto" class="modal-tab-content">
            <form class="modal-form" id="formProvContacto" autocomplete="off">
 
                <div class="form-section-title">Datos de contacto</div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" id="provTelefono" class="form-input"
                            maxlength="10" placeholder="10 dígitos" autocomplete="off">
                        <input type="hidden" name="id_tipo_contacto_telefono" id="provIdTipoTel">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email" id="provEmail" class="form-input"
                            placeholder="correo@empresa.com" autocomplete="off">
                        <input type="hidden" name="id_tipo_contacto_email" id="provIdTipoEmail">
                    </div>
                </div>
 
                <div class="form-section-title" style="margin-top:16px;">Dirección fiscal</div>
 
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Calle</label>
                        <input type="text" name="calle" id="provCalle" class="form-input" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Número Exterior</label>
                        <input type="text" name="numero_exterior" id="provNumExt" class="form-input" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Número Interior</label>
                        <input type="text" name="numero_interior" id="provNumInt" class="form-input" autocomplete="off">
                    </div>
                </div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Código postal</label>
                        <input type="text" name="codigo_postal" id="provCP" class="form-input"
                            maxlength="5" placeholder="5 dígitos" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Colonia</label>
                        <input type="text" name="colonia" id="provColonia" class="form-input"
                            list="provListaColonias" autocomplete="off">
                        <datalist id="provListaColonias"></datalist>
                        <input type="hidden" name="id_cp" id="provIdCp">
                    </div>
                </div>
 
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <input type="text" name="estado" id="provEstado" class="form-input" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Municipio</label>
                        <input type="text" name="municipio" id="provMunicipio" class="form-input" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">País</label>
                        <input type="text" class="form-input" value="MEXICO" readonly>
                    </div>
                </div>
 
            </form>
        </div><!-- /#tabProvContacto -->
 
        <!-- ── Tab 3: Condiciones Comerciales ─────────────────────────── -->
        <div id="tabProvComercial" class="modal-tab-content">
            <form class="modal-form" id="formProvComercial" autocomplete="off">
 
                <div class="form-section-title">Condiciones de pago</div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Términos de pago</label>
                        <select name="terminos_pago" id="provTerminosPago" class="form-select">
                            <option value="">Seleccionar...</option>
                            <option value="Contado">Contado</option>
                            <option value="15 días">15 días</option>
                            <option value="30 días">30 días</option>
                            <option value="45 días">45 días</option>
                            <option value="60 días">60 días</option>
                            <option value="90 días">90 días</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Días de crédito</label>
                        <input type="number" name="dias_credito" id="provDiasCredito"
                            class="form-input" min="0" max="365" placeholder="0" autocomplete="off">
                    </div>
                </div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Límite de crédito ($)</label>
                        <input type="number" name="limite_credito" id="provLimiteCredito"
                            class="form-input" min="0" step="0.01" placeholder="0.00" autocomplete="off">
                    </div>
                </div>
 
            </form>
        </div><!-- /#tabProvComercial -->
 
    </div><!-- /.modal-body -->
 
    <!-- Footer -->
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel"
            onclick="cerrarModal('<?php echo $modal_prov_id; ?>')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnGuardarProveedor">
            Guardar cambios
        </button>
    </div>
 
</div><!-- /#modalProveedor -->