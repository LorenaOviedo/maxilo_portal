<?php
// MODAL DE MOTIVO DE CONSULTA - ALTA Y EDICIÓN
$modal_id = 'modalMotivoConsulta';
?>
 
<!-- Overlay -->
<div id="<?php echo $modal_id; ?>-overlay" class="modal-overlay"></div>
 
<!-- Contenedor del modal -->
<div id="<?php echo $modal_id; ?>" class="modal-container modal-notabs">
 
    <!-- Header -->
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title" id="modalMotivoConsultaTitulo">Nuevo motivo de consulta</h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close" onclick="cerrarModal('<?php echo $modal_id; ?>')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <!-- Body -->
    <div class="modal-body">
        <form class="modal-form" id="formMotivoConsulta" novalidate>
            <input type="hidden" id="motivo_id" name="id_motivo_consulta">
 
            <!-- F1: Motivo -->
            <div class="form-row cols-1">
                <div class="form-group">
                    <label class="form-label">Motivo de consulta <span class="required">*</span></label>
                    <input
                        type="text"
                        id="motivo_nombre"
                        name="motivo_consulta"
                        class="form-input"
                        placeholder="Ej: DOLOR DENTAL"
                        maxlength="100"
                        required
                    >
                    <span class="field-error" id="err_motivo_nombre"></span>
                </div>
            </div>
 
            <!-- F2: Descripción -->
            <div class="form-row cols-1">
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea
                        id="motivo_descripcion"
                        name="descripcion"
                        class="form-input form-textarea"
                        rows="3"
                        placeholder="Describe brevemente el motivo de consulta..."
                        maxlength="200"
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
        <button type="button" class="btn-modal-save" id="btnGuardarMotivoConsulta">
            <i class="fas fa-save"></i>
            Guardar
        </button>
    </div>
 
</div>