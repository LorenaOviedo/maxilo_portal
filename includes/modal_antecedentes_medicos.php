<?php
// MODAL DE ANTECEDENTE MÉDICO - ALTA Y EDICIÓN
$modal_id = 'modalAntecedenteMedico';
?>
 
<!-- Overlay -->
<div id="<?php echo $modal_id; ?>-overlay" class="modal-overlay"></div>
 
<!-- Contenedor del modal -->
<div id="<?php echo $modal_id; ?>" class="modal-container modal-notabs">
 
    <!-- Header -->
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title" id="modalAntecedenteMedicoTitulo">Nuevo antecedente médico</h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close" onclick="cerrarModal('<?php echo $modal_id; ?>')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <!-- Body -->
    <div class="modal-body">
        <form class="modal-form" id="formAntecedenteMedico" novalidate>
            <input type="hidden" id="antecedente_id" name="id_antecedente">
 
            <!-- F1: Nombre, tipo -->
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Nombre del antecedente <span class="required">*</span></label>
                    <input
                        type="text"
                        id="antecedente_nombre"
                        name="nombre_antecedente"
                        class="form-input"
                        placeholder="Ej: Alergia a penicilina"
                        maxlength="150"
                        required
                    >
                    <span class="field-error" id="err_antecedente_nombre"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo <span class="required">*</span></label>
                    <select id="antecedente_tipo" name="tipo" class="form-select" required>
                        <option value="">Seleccionar tipo...</option>
                        <option value="Alérgico">Alérgico</option>
                        <option value="Patológico">Patológico</option>
                        <option value="Farmacológico">Farmacológico</option>
                        <option value="Quirúrgico">Quirúrgico</option>
                        <option value="Hereditario">Hereditario</option>
                        <option value="Traumático">Traumático</option>
                        <option value="Otro">Otro</option>
                    </select>
                    <span class="field-error" id="err_antecedente_tipo"></span>
                </div>
            </div>
 
            <!-- F2: Implica alerta médica -->
            <div class="form-row cols-1">
                <div class="form-group form-group-check">
                    <label class="form-label-check">
                        <input
                            type="checkbox"
                            id="antecedente_alerta"
                            name="implica_alerta_medica"
                            value="1"
                            class="form-checkbox"
                        >
                        Este antecedente implica alerta médica
                    </label>
                </div>
            </div>
 
        </form>
    </div>
 
    <!-- Footer -->
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="cerrarModal('<?php echo $modal_id; ?>')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnGuardarAntecedenteMedico">
            <i class="fas fa-save"></i>
            Guardar
        </button>
    </div>
 
</div>