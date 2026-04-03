<?php
//MODAL DE PROCEDIMIENTOS DENTALES - ALTA Y EDICIÓN

$modal_id = 'modalProcedimiento';
?>
 
<!-- Overlay -->
<div id="<?php echo $modal_id; ?>-overlay" class="modal-overlay"></div>
 
<!-- Contenedor del modal (sin tabs clase modal-notabs) -->
<div id="<?php echo $modal_id; ?>" class="modal-container modal-notabs">
 
    <!-- Header -->
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title" id="modalProcedimientoTitulo">Nuevo procedimiento</h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close" onclick="cerrarModal('<?php echo $modal_id; ?>')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <!-- Body -->
    <div class="modal-body">
        <form class="modal-form" id="formProcedimiento" novalidate>
            <input type="hidden" id="proc_id" name="id_procedimiento">
 
            <!-- F1: Nombre, tipo -->
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Nombre del procedimiento <span class="required">*</span></label>
                    <input
                        type="text"
                        id="proc_nombre"
                        name="nombre_procedimiento"
                        class="form-input"
                        placeholder="Ej: PROFILAXIS DENTAL"
                        maxlength="150"
                        required
                    >
                    <span class="field-error" id="err_nombre"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select id="proc_tipo" name="tipo" class="form-select">
                        <option value="">Seleccionar tipo...</option>
                        <option value="PREVENTIVO">Preventivo</option>
                        <option value="ELECTIVO">Electivo</option>
                        <option value="QUIRÚRGICO">Quirúrgico</option>
                        <option value="RESTAURATIVO">Restaurativo</option>
                        <option value="ORTODÓNTICO">Ortodóntico</option>
                        <option value="DIAGNÓSTICO">Diagnóstico</option>
                    </select>
                </div>
            </div>
 
            <!-- F2: Especialidad, precio -->
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Especialidad <span class="required">*</span></label>
                    <select id="proc_especialidad" name="id_especialidad" class="form-select" required>
                        <option value="">Seleccionar especialidad...</option>
                    </select>
                    <span class="field-error" id="err_especialidad"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Precio base <span class="required">*</span></label>
                    <input
                        type="number"
                        id="proc_precio"
                        name="precio_base"
                        class="form-input"
                        placeholder="0.00"
                        min="0"
                        step="0.01"
                        required
                    >
                    <span class="field-error" id="err_precio"></span>
                </div>
            </div>
 
            <!-- F3: Tiempo estimado, requiere autorización -->
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Tiempo estimado (minutos)</label>
                    <input
                        type="number"
                        id="proc_tiempo"
                        name="tiempo_estimado"
                        class="form-input"
                        placeholder="Ej: 60"
                        min="1"
                    >
                </div>
                <div class="form-group">
                    <label class="form-label">Estatus <span class="required">*</span></label>
                    <select id="proc_estatus" name="id_estatus" class="form-select" required>
                        <option value="1">Activo</option>
                        <option value="2">Inactivo</option>
                    </select>
                </div>
            </div>
 
            <!-- F4: Requiere autorización -->
            <div class="form-row cols-1">
                <div class="form-group form-group-check">
                    <label class="form-label-check">
                        <input
                            type="checkbox"
                            id="proc_autorizacion"
                            name="requiere_autorizacion"
                            value="1"
                            class="form-checkbox"
                        >
                        Este procedimiento requiere autorización
                    </label>
                </div>
            </div>
 
            <!-- F5: Descripción -->
            <div class="form-row cols-1">
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea
                        id="proc_descripcion"
                        name="descripcion"
                        class="form-input form-textarea"
                        rows="3"
                        placeholder="Describe brevemente el procedimiento..."
                        maxlength="500"
                    ></textarea>
                </div>
            </div>
 
        </form>
    </div>
 
    <!-- Footer -->
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="cerrarModal('<?php echo $modal_id; ?>')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnGuardarProcedimiento">
            <i class="fas fa-save"></i>
            Guardar
        </button>
    </div>
 
</div>