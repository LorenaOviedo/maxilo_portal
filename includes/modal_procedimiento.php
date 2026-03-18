<?php
//MODAL DE PROCEDIMIENTOS DENTALES - ALTA Y EDICIÓN

$modal_id = 'modalProcedimiento';
?>

<!-- Overlay -->
<div id="<?php echo $modal_id; ?>-overlay" class="modal-overlay"></div>

<!-- Contenedor del modal (sin tabs → clase modal-notabs) -->
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
                    <input type="text" id="proc_nombre" name="nombre_procedimiento" class="form-input"
                        placeholder="Ej: PROFILAXIS DENTAL" maxlength="150" required>
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
                    <input type="number" id="proc_precio" name="precio_base" class="form-input" placeholder="0.00"
                        min="0" step="0.01" required>
                    <span class="field-error" id="err_precio"></span>
                </div>
            </div>

            <!-- F3: Tiempo estimado, requiere autorización -->
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Tiempo estimado (minutos)</label>
                    <input type="number" id="proc_tiempo" name="tiempo_estimado" class="form-input" placeholder="Ej: 60"
                        min="1">
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
                        <input type="checkbox" id="proc_autorizacion" name="requiere_autorizacion" value="1"
                            class="form-checkbox">
                        Este procedimiento requiere autorización
                    </label>
                </div>
            </div>

            <!-- F5: Descripción -->
            <div class="form-row cols-1">
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea id="proc_descripcion" name="descripcion" class="form-input form-textarea" rows="3"
                        placeholder="Describe brevemente el procedimiento..." maxlength="500"></textarea>
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

<!-- ==================== JS DEL MODAL ==================== -->
<script>
    const MODAL_ID = '<?php echo $modal_id; ?>';

    // Llenar el select de especialidades con los datos de PHP
    (function poblarEspecialidades() {
        const select = document.getElementById('proc_especialidad');
        if (!select || !window.ESPECIALIDADES) return;

        ESPECIALIDADES.forEach(e => {
            const opt = document.createElement('option');
            opt.value = e.id_especialidad;
            opt.textContent = e.nombre;
            select.appendChild(opt);
        });
    })();

    // Abrir modal en modo NUEVO
    function abrirModalNuevo() {
        limpiarFormulario();
        document.getElementById('modalProcedimientoTitulo').textContent = 'Nuevo procedimiento';
        document.getElementById('proc_id').value = '';
        abrirModal(MODAL_ID);
    }

    // Abrir modal en modo EDITAR 
    function abrirModalEditar(id) {
        limpiarFormulario();
        document.getElementById('modalProcedimientoTitulo').textContent = 'Editar procedimiento';

        // Obtener datos del servidor
        fetch(`<?php echo ajax_url('api.php'); ?>?modulo=procedimientos&accion=get&id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    CatalogTable.showNotification('No se pudo cargar el procedimiento', 'error');
                    return;
                }
                const p = data.procedimiento;
                document.getElementById('proc_id').value = p.id_procedimiento;
                document.getElementById('proc_nombre').value = p.nombre_procedimiento;
                document.getElementById('proc_tipo').value = p.tipo ?? '';
                document.getElementById('proc_especialidad').value = p.id_especialidad;
                document.getElementById('proc_precio').value = p.precio_base;
                document.getElementById('proc_tiempo').value = p.tiempo_estimado ?? '';
                document.getElementById('proc_estatus').value = p.id_estatus;
                document.getElementById('proc_autorizacion').checked = parseInt(p.requiere_autorizacion) === 1;
                document.getElementById('proc_descripcion').value = p.descripcion ?? '';

                abrirModal(MODAL_ID);
            })
            .catch(() => CatalogTable.showNotification('Error al obtener los datos', 'error'));
    }

    // Guardar (crear o actualizar)
    document.getElementById('btnGuardarProcedimiento')?.addEventListener('click', function () {
        if (!validarFormulario()) return;

        const form = document.getElementById('formProcedimiento');
        const id = document.getElementById('proc_id').value;
        const url = '<?php echo ajax_url('api.php'); ?>';
        const formData = new FormData(form);

        // Checkbox no enviado si no está marcado — forzar valor
        if (!document.getElementById('proc_autorizacion').checked) {
            formData.set('requiere_autorizacion', '0');
        }

        formData.append('modulo', 'procedimientos');
        formData.append('accion', id ? 'update' : 'create');

        CatalogTable.showLoading(true);

        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                CatalogTable.showLoading(false);
                if (data.success) {
                    CatalogTable.showNotification(data.message, 'success');
                    cerrarModal(MODAL_ID);
                    setTimeout(() => location.reload(), 800);
                } else {
                    CatalogTable.showNotification(data.message || 'Error al guardar', 'error');
                }
            })
            .catch(() => {
                CatalogTable.showLoading(false);
                CatalogTable.showNotification('Error de conexión', 'error');
            });
    });

    // Cambiar estatus (activar / desactivar) 
    function cambiarEstatusConfirmar(id, nuevoEstatus, nombre) {
        const accion = nuevoEstatus === 1 ? 'activar' : 'desactivar';
        if (!confirm(`¿Deseas ${accion} el procedimiento "${nombre}"?`)) return;

        const formData = new FormData();
        formData.append('modulo', 'procedimientos');
        formData.append('accion', 'status');
        formData.append('id_procedimiento', id);
        formData.append('id_estatus', nuevoEstatus);

        CatalogTable.showLoading(true);

        fetch('<?php echo ajax_url('api.php'); ?>', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                CatalogTable.showLoading(false);
                if (data.success) {
                    CatalogTable.showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    CatalogTable.showNotification(data.message || 'Error al cambiar estatus', 'error');
                }
            })
            .catch(() => {
                CatalogTable.showLoading(false);
                CatalogTable.showNotification('Error de conexión', 'error');
            });
    }

    // Validación del formulario 
    function validarFormulario() {
        let valido = true;

        const campos = [
            { id: 'proc_nombre', err: 'err_nombre', msg: 'El nombre es obligatorio' },
            { id: 'proc_especialidad', err: 'err_especialidad', msg: 'Selecciona una especialidad' },
            { id: 'proc_precio', err: 'err_precio', msg: 'El precio es obligatorio' },
        ];

        campos.forEach(c => {
            const el = document.getElementById(c.id);
            const err = document.getElementById(c.err);
            if (!el.value.trim()) {
                err.textContent = c.msg;
                el.classList.add('input-error');
                valido = false;
            } else {
                err.textContent = '';
                el.classList.remove('input-error');
            }
        });

        // Precio debe ser mayor a 0
        const precio = parseFloat(document.getElementByIId('proc_precio').value);
        if (!isNaN(precio) && precio <= 0) {
            document.getElementById('err_precio').textContent = 'El precio debe ser mayor a 0';
            document.getElementById('proc_precio').classList.add('input-error');
            valido = false;
        }

        return valido;
    }

    //Limpiar formulario
    function limpiarFormulario() {
        document.getElementById('formProcedimiento').reset();
        document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }
</script>