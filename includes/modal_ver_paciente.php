<?php
/* MODAL DE PACIENTE REUTILIZABLE VER/EDITAR */
$modal_id = 'modalPaciente';
?>

<!-- Overlay -->
<div id="<?php echo $modal_id; ?>-overlay" class="modal-overlay"></div>

<!-- Contenedor del modal-->
<div id="<?php echo $modal_id; ?>" class="modal-container">
    <!-- Header -->
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title">
                Detalles del Paciente <span class="highlight" id="modalPacienteNumero"></span>
            </h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-add" onclick="nuevoEnModal('<?php echo $modal_id; ?>')">
                <i class="fas fa-plus"></i>
                Agregar nuevo
            </button>
            <button type="button" class="btn-modal-close" onclick="cerrarModal('<?php echo $modal_id; ?>')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="modal-tabs">
        <button class="modal-tab active" data-tab="tabInfoPersonal" onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabInfoPersonal')">
            Información Personal y contacto
        </button>
        <button class="modal-tab" data-tab="tabHistorial" onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabHistorial')">
            Historial Clínico
        </button>
        <button class="modal-tab" data-tab="tabPlanes" onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabPlanes')">
            Planes de Tratamiento
        </button>
        <button class="modal-tab" data-tab="tabOdontograma" onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabOdontograma')">
            Odontograma
        </button>
        <button class="modal-tab" data-tab="tabArchivos" onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabArchivos')">
            Archivos médicos
        </button>
    </div>
    
    <!-- Body -->
    <div class="modal-body">
        <!-- Tab 1: información personal y contacto -->
        <div id="tabInfoPersonal" class="modal-tab-content active">
            <form class="modal-form" id="formPaciente">
                <!-- F1 Número, nombre, apellido paterno -->
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Número de paciente</label>
                        <input type="text" name="id" class="form-input" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre(s)</label>
                        <input type="text" name="nombre" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido paterno</label>
                        <input type="text" name="apellido_paterno" class="form-input">
                    </div>
                </div>
                
                <!-- F2 apellido materno, fecha de nacimiento -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Apellido materno</label>
                        <input type="text" name="apellido_materno" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-input">
                    </div>
                </div>
                
                <!-- F3 genero, calle y número -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Género</label>
                        <select name="genero" class="form-select">
                            <option value="">Seleccionar</option>
                            <option value="Hombre">Hombre</option>
                            <option value="Mujer">Mujer</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Calle y número</label>
                        <input type="text" name="calle" class="form-input">
                    </div>
                </div>
                
                <!-- F4 codigo postal, colonia -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Código postal</label>
                        <input type="text" name="codigo_postal" class="form-input" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Colonia</label>
                        <input type="text" name="colonia" class="form-input">
                    </div>
                </div>
                
                <!-- F5 estado, ciudad, país -->
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <input type="text" name="estado" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="ciudad" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">País</label>
                        <input type="text" name="pais" class="form-input" value="México">
                    </div>
                </div>
                
                <!-- F6 email, teléfono -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-input" maxlength="10">
                    </div>
                </div>
                
                <!-- F7 contacto de emergencia, teléfono de emergencia -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Contacto de emergencia</label>
                        <input type="text" name="contacto_emergencia" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono del contacto de emergencia</label>
                        <input type="tel" name="telefono_emergencia" class="form-input" maxlength="10">
                    </div>
                </div>
                
                <!-- F8 relación de parentesco -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Relación</label>
                        <input type="text" name="relacion" class="form-input" placeholder="Ej: Madre, Hermano, Esposo/a, etc.">
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Tab 2 historial clínico -->
        <div id="tabHistorial" class="modal-tab-content">
            <div class="modal-form">
                <p class="text-muted">Contenido del historial clínico...</p>
            </div>
        </div>
        
        <!-- Tab 3 planes de tratamiento -->
        <div id="tabPlanes" class="modal-tab-content">
            <div class="modal-form">
                <p class="text-muted">Contenido de planes de tratamiento...</p>
            </div>
        </div>
        
        <!-- Tab 4 odontograma -->
        <div id="tabOdontograma" class="modal-tab-content">
            <div class="modal-form">
                <p class="text-muted">Contenido del odontograma...</p>
            </div>
        </div>
        
        <!-- Tab 5 archivos medicos -->
        <div id="tabArchivos" class="modal-tab-content">
            <div class="modal-form">
                <p class="text-muted">Contenido de archivos médicos...</p>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="cerrarModal('<?php echo $modal_id; ?>')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnGuardarPaciente">
            Guardar cambios
        </button>
    </div>
</div>

<!-- JavaScript para modal de paciente -->
<script>
// Función para ver paciente
function verPaciente(data) {
    // Actualizar número en título
    document.getElementById('modalPacienteNumero').textContent = data.id || '';
    
    // Cargar datos para lectura
    verEnModal('<?php echo $modal_id; ?>', data);
}

// Función para editar paciente
function editarPaciente(data) {
    // Actualizar número en título
    document.getElementById('modalPacienteNumero').textContent = data.id || '';
    
    // Cargar datos en edición
    editarEnModal('<?php echo $modal_id; ?>', data);
}

// Evento de guardar paciente
document.getElementById('btnGuardarPaciente')?.addEventListener('click', function() {
    const form = document.getElementById('formPaciente');
    const formData = new FormData(form);
    
    // Convertir a objeto
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    console.log('Datos a guardar:', data);
    
    // Pendiente implementación de guardado
    
    // Cerrar modal
    CatalogTable.showNotification('Función de guardado pendiente de implementar.....', 'info');
});
</script>