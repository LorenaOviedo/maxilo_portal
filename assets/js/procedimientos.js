document.addEventListener('DOMContentLoaded', () => {
    console.log('1. DOM listo');
    console.log('2. ESPECIALIDADES:', window.ESPECIALIDADES);
    console.log('3. select:', document.getElementById('proc_especialidad'));
    
    const select = document.getElementById('proc_especialidad');
    if (select && window.ESPECIALIDADES) {
        console.log('4. Poblando select con', ESPECIALIDADES.length, 'especialidades');
        ESPECIALIDADES.forEach(e => {
            console.log('   agregando:', e);
            const opt = document.createElement('option');
            opt.value = e.id_especialidad;
            opt.textContent = e.nombre;
            select.appendChild(opt);
        });
        console.log('5. Select tiene ahora', select.options.length, 'opciones');
    } else {
        console.log('4. FALLÓ — select existe:', !!select, '| ESPECIALIDADES existe:', !!window.ESPECIALIDADES);
    }
});
/**
 * procedimientos.js
 * Sistema Maxilofacial Texcoco
 *
 * Lógica específica del catálogo de Procedimientos Dentales.
 * Depende de: modal.js, catalogos-tabla.js
 *
 * La URL del API se inyecta desde PHP en procedimientos.php:
 *   <script>const API_URL = '<?php echo ajax_url('api.php'); ?>';</script>
 * antes de cargar este archivo.
 */
 
const MODAL_ID = 'modalProcedimiento';
 
// ── Poblar select de especialidades ───────────────────────────────
// ESPECIALIDADES se inyecta desde PHP en procedimientos.php
(function poblarEspecialidades() {
    const select = document.getElementById('proc_especialidad');
    if (!select || !window.ESPECIALIDADES) return;
 
    ESPECIALIDADES.forEach(e => {
        const opt       = document.createElement('option');
        opt.value       = e.id_especialidad;
        opt.textContent = e.nombre;
        select.appendChild(opt);
    });
});
 
// ── Helpers ───────────────────────────────────────────────────────
 
function cargarProcedimiento(id, callback) {
    fetch(`${API_URL}?modulo=procedimientos&accion=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                CatalogTable.showNotification('No se pudo cargar el procedimiento', 'error');
                return;
            }
            callback(data.procedimiento);
        })
        .catch(() => CatalogTable.showNotification('Error al obtener los datos', 'error'));
}
 
function mapearDatos(p) {
    return {
        id_procedimiento      : p.id_procedimiento,
        nombre_procedimiento  : p.nombre_procedimiento,
        tipo                  : p.tipo             ?? '',
        id_especialidad       : p.id_especialidad,
        precio_base           : p.precio_base,
        tiempo_estimado       : p.tiempo_estimado  ?? '',
        id_estatus            : p.id_estatus,
        requiere_autorizacion : parseInt(p.requiere_autorizacion) === 1,
        descripcion           : p.descripcion      ?? '',
    };
}
 
// ── Botones de la tabla ────────────────────────────────────────────
 
function abrirModalNuevo() {
    nuevoEnModal(MODAL_ID);
    document.getElementById('modalProcedimientoTitulo').textContent = 'Nuevo procedimiento';
    document.getElementById('proc_id').value = '';
}
 
function abrirModalVer(id) {
    cargarProcedimiento(id, p => {
        verEnModal(MODAL_ID, mapearDatos(p));
        document.getElementById('modalProcedimientoTitulo').textContent = 'Ver procedimiento';
    });
}
 
function abrirModalEditar(id) {
    cargarProcedimiento(id, p => {
        editarEnModal(MODAL_ID, mapearDatos(p));
        document.getElementById('modalProcedimientoTitulo').textContent = 'Editar procedimiento';
    });
}
 
// ── Guardar ────────────────────────────────────────────────────────
 
document.getElementById('btnGuardarProcedimiento')?.addEventListener('click', function () {
    if (!validarFormulario()) return;
 
    const form     = document.getElementById('formProcedimiento');
    const id       = document.getElementById('proc_id').value;
    const formData = new FormData(form);
 
    if (!document.getElementById('proc_autorizacion').checked) {
        formData.set('requiere_autorizacion', '0');
    }
 
    formData.append('modulo', 'procedimientos');
    formData.append('accion', id ? 'update' : 'create');
 
    CatalogTable.showLoading(true);
 
    fetch(API_URL, { method: 'POST', body: formData })
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
 
// ── Cambiar estatus ────────────────────────────────────────────────
 
function cambiarEstatusConfirmar(id, nuevoEstatus, nombre) {
    const accion = nuevoEstatus === 1 ? 'activar' : 'desactivar';
    if (!confirm(`¿Deseas ${accion} el procedimiento "${nombre}"?`)) return;
 
    const formData = new FormData();
    formData.append('modulo',           'procedimientos');
    formData.append('accion',           'status');
    formData.append('id_procedimiento', id);
    formData.append('id_estatus',       nuevoEstatus);
 
    CatalogTable.showLoading(true);
 
    fetch(API_URL, { method: 'POST', body: formData })
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
 
// ── Validación ─────────────────────────────────────────────────────
 
function validarFormulario() {
    let valido = true;
 
    const campos = [
        { id: 'proc_nombre',       err: 'err_nombre',      msg: 'El nombre es obligatorio'    },
        { id: 'proc_especialidad', err: 'err_especialidad', msg: 'Selecciona una especialidad' },
        { id: 'proc_precio',       err: 'err_precio',       msg: 'El precio es obligatorio'    },
    ];
 
    campos.forEach(c => {
        const el  = document.getElementById(c.id);
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
 
    const precio = parseFloat(document.getElementById('proc_precio').value);
    if (!isNaN(precio) && precio <= 0) {
        document.getElementById('err_precio').textContent = 'El precio debe ser mayor a 0';
        document.getElementById('proc_precio').classList.add('input-error');
        valido = false;
    }
 
    return valido;
}

document.addEventListener('DOMContentLoaded', () => {
    const thNombre = document.querySelector('.data-table th[data-sort="id_procedimiento"]');
    if (thNombre) thNombre.classList.add('sort-asc');
});