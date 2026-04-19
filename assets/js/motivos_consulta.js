/**
 * Catalogo de Motivos de Consulta
 * Lógica del catálogo de Motivos de Consulta.
 * Depende de: modal.js, catalogos-tabla.js
 */
 
const MODAL_ID = 'modalMotivoConsulta';
 
// ── Helpers ───────────────────────────────────────────────────────
 
function cargarMotivo(id, callback) {
    fetch(`${API_URL}?modulo=motivos_consulta&accion=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                CatalogTable.showNotification('No se pudo cargar el motivo', 'error');
                return;
            }
            callback(data.motivos_consulta);
        })
        .catch(() => CatalogTable.showNotification('Error al obtener los datos', 'error'));
}
 
function mapearDatos(m) {
    return {
        id_motivo_consulta : m.id_motivo_consulta,
        motivo_consulta    : m.motivo_consulta,
        descripcion        : m.descripcion ?? '',
    };
}
 
// ── Botones de la tabla ────────────────────────────────────────────
 
function abrirModalNuevo() {
    nuevoEnModal(MODAL_ID);
    document.getElementById('modalMotivoConsultaTitulo').textContent = 'Nuevo motivo de consulta';
    document.getElementById('motivo_id').value = '';
}
 
function abrirModalVer(id) {
    cargarMotivo(id, m => {
        verEnModal(MODAL_ID, mapearDatos(m));
        document.getElementById('modalMotivoConsultaTitulo').textContent = 'Ver motivo de consulta';
    });
}
 
function abrirModalEditar(id) {
    cargarMotivo(id, m => {
        editarEnModal(MODAL_ID, mapearDatos(m));
        document.getElementById('modalMotivoConsultaTitulo').textContent = 'Editar motivo de consulta';
    });
}
 
// ── Guardar ────────────────────────────────────────────────────────
 
document.getElementById('btnGuardarMotivoConsulta')?.addEventListener('click', function () {
    if (!validarFormulario()) return;
 
    const form     = document.getElementById('formMotivoConsulta');
    const id       = document.getElementById('motivo_id').value;
    const formData = new FormData(form);
 
    formData.append('modulo', 'motivos_consulta');
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
 
// ── Validación ─────────────────────────────────────────────────────
 
function validarFormulario() {
    let valido = true;
 
    const el  = document.getElementById('motivo_nombre');
    const err = document.getElementById('err_motivo_nombre');
 
    if (!el.value.trim()) {
        err.textContent = 'El nombre del motivo es obligatorio';
        el.classList.add('input-error');
        valido = false;
    } else {
        err.textContent = '';
        el.classList.remove('input-error');
    }
 
    return valido;
}