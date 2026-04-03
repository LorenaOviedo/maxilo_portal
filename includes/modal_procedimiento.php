<?php
//MODAL DE PROCEDIMIENTOS DENTALES - ALTA Y EDICIÓN

$modal_id = 'modalProcedimiento';
?>
 
<!-- Overlay -->
<div id="<?php echo $modal_esp_id; ?>-overlay" class="modal-overlay"></div>
 
<!-- Contenedor del modal -->
<div id="<?php echo $modal_esp_id; ?>" class="modal-container">
 
    <!-- Header -->
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title">
                Detalles del Especialista
                <span class="highlight" id="modalEspecialistaNombre"></span>
            </h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close"
                onclick="cerrarModal('<?php echo $modal_esp_id; ?>')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <!-- Tabs -->
    <div class="modal-tabs">
        <button class="modal-tab active" data-tab="tabEspPersonal"
            onclick="cambiarTab('<?php echo $modal_esp_id; ?>', 'tabEspPersonal')">
            Información<br>Personal
        </button>
        <button class="modal-tab" data-tab="tabEspContacto"
            onclick="cambiarTab('<?php echo $modal_esp_id; ?>', 'tabEspContacto')">
            Información<br>de Contacto
        </button>
        <button class="modal-tab" data-tab="tabEspEducacion"
            onclick="cambiarTab('<?php echo $modal_esp_id; ?>', 'tabEspEducacion')">
            Educación
        </button>
    </div>
 
    <!-- Body -->
    <div class="modal-body">
 
        <!-- ── Tab 1: Información Personal ───────────────────────────────── -->
        <div id="tabEspPersonal" class="modal-tab-content active">
            <form class="modal-form" id="formEspecialista">
                <input type="hidden" name="id_especialista" id="espId">
 
                <!-- F1: Nombre, apellido paterno -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Nombre(s) <span class="required">*</span></label>
                        <input type="text" name="nombre" id="espNombre" class="form-input"
                            pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$"
                            title="Solo letras y espacios">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido paterno <span class="required">*</span></label>
                        <input type="text" name="apellido_paterno" id="espApPat" class="form-input"
                            pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$">
                    </div>
                </div>
 
                <!-- F2: Apellido materno, fecha nacimiento -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Apellido materno</label>
                        <input type="text" name="apellido_materno" id="espApMat" class="form-input"
                            pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" id="espFechaNac" class="form-input"
                            max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
 
                <!-- F3: Fecha contratación -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Fecha de contratación</label>
                        <input type="date" name="fecha_contratacion" id="espFechaCont" class="form-input"
                            max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
 
                <!-- F4: Dirección -->
                <div class="form-section-title" style="margin-top:16px;">Dirección</div>
 
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Calle</label>
                        <input type="text" name="calle" id="espCalle" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Número Exterior</label>
                        <input type="text" name="numero_exterior" id="espNumExt" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Número Interior</label>
                        <input type="text" name="numero_interior" id="espNumInt" class="form-input">
                    </div>
                </div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Código postal</label>
                        <input type="text" name="codigo_postal" id="espCP" class="form-input"
                            maxlength="5" autocomplete="off" placeholder="5 dígitos">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Colonia</label>
                        <input type="text" name="colonia" id="espColonia" class="form-input"
                            list="espListaColonias" autocomplete="off">
                        <datalist id="espListaColonias"></datalist>
                        <input type="hidden" name="id_cp" id="espIdCp">
                    </div>
                </div>
 
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <input type="text" name="estado" id="espEstado" class="form-input" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Municipio</label>
                        <input type="text" name="municipio" id="espMunicipio" class="form-input" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">País</label>
                        <input type="text" name="pais" class="form-input" value="MEXICO" readonly>
                    </div>
                </div>
 
            </form>
        </div><!-- /#tabEspPersonal -->
 
        <!-- ── Tab 2: Contacto ───────────────────────────────────────────── -->
        <div id="tabEspContacto" class="modal-tab-content">
            <form class="modal-form" id="formEspContacto">
 
                <div class="form-section-title">Datos de contacto</div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" id="espTelefono" class="form-input"
                            maxlength="10" placeholder="10 dígitos">
                        <input type="hidden" name="id_tipo_contacto_telefono"
                            id="espIdTipoTel">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email" id="espEmail" class="form-input"
                            placeholder="correo@ejemplo.com">
                        <input type="hidden" name="id_tipo_contacto_email"
                            id="espIdTipoEmail">
                    </div>
                </div>
 
            </form>
        </div><!-- /#tabEspContacto -->
 
        <!-- ── Tab 3: Educación ──────────────────────────────────────────── -->
        <div id="tabEspEducacion" class="modal-tab-content">
            <div class="modal-form">
 
                <div class="form-section-title">Especialidades y formación académica</div>
 
                <!-- Toolbar para agregar especialidad -->
                <div class="tab-toolbar" style="margin-bottom:12px;">
                    <button type="button" class="btn-modal-add" id="btnAgregarEsp"
                        onclick="especialistaController.mostrarFilaEsp()">
                        <i class="ri-add-line"></i> Agregar especialidad
                    </button>
                </div>
 
                <!-- Fila para nueva especialidad (oculta por defecto) -->
                <div id="rowNuevaEsp" class="proc-add-row" style="display:none; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                    <select id="espSelectEsp" class="form-select" style="min-width:180px;">
                        <option value="">Seleccionar especialidad...</option>
                    </select>
                    <input type="text" id="espCedula" class="form-input"
                        placeholder="Cédula profesional" style="max-width:180px;">
                    <input type="text" id="espInstitucion" class="form-input"
                        placeholder="Institución de egreso" style="max-width:200px;">
                    <button type="button" class="btn-confirmar-proc"
                        onclick="especialistaController.confirmarEsp()">
                        <i class="ri-check-line"></i>
                    </button>
                    <button type="button" class="btn-cancelar-proc"
                        onclick="especialistaController.ocultarFilaEsp()">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
 
                <!-- Tabla de especialidades -->
                <table class="plan-table" id="tablaEspecialidades">
                    <thead>
                        <tr>
                            <th>ESPECIALIDAD</th>
                            <th>CÉDULA PROFESIONAL</th>
                            <th>INSTITUCIÓN</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="bodyEspecialidades">
                        <tr id="rowSinEsp">
                            <td colspan="4" style="text-align:center; color:#adb5bd; padding:16px;">
                                Sin especialidades registradas
                            </td>
                        </tr>
                    </tbody>
                </table>
 
                <!-- JSON oculto que se envía al guardar -->
                <input type="hidden" id="espEspecialidadesJson" name="especialidades_json" value="[]">
 
            </div>
        </div><!-- /#tabEspEducacion -->
 
    </div><!-- /.modal-body -->
 
    <!-- Footer -->
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel"
            onclick="cerrarModal('<?php echo $modal_esp_id; ?>')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnGuardarEspecialista">
            Guardar cambios
        </button>
    </div>
 
</div><!-- /#modalEspecialista -->