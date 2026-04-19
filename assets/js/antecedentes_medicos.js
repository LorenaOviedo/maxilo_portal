/**
 * antecedentes_medicos.js
 * Lógica del catálogo de Antecedentes Médicos.
 * Depende de: modal.js, catalogos-tabla.js
 */
 
const MODAL_ID = 'modalAntecedenteMedico';
 
// ── Helpers ───────────────────────────────────────────────────────
 
function cargarAntecedente(id, callback) {
    fetch(`${API_URL}?modulo=antecedentes_medicos&accion=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                CatalogTable.showNotification('No se pudo cargar el antecedente', 'error');
                return;
            }
            callback(data.antecedente_medico);
        })
        .catch(() => CatalogTable.showNotification('Error al obtener los datos', 'error'));
}
 
function mapearDatos(a) {
    return {
        id_antecedente        : a.id_antecedente,
        nombre_antecedente    : a.nombre_antecedente,
        tipo                  : a.tipo ?? '',
        implica_alerta_medica : parseInt(a.implica_alerta_medica) === 1,
    };
}
 
// ── Botones de la tabla ────────────────────────────────────────────
 
function abrirModalNuevo() {
    nuevoEnModal(MODAL_ID);
    document.getElementById('modalAntecedenteMedicoTitulo').textContent = 'Nuevo antecedente médico';
    document.getElementById('antecedente_id').value = '';
}
 
function abrirModalVer(id) {
    cargarAntecedente(id, a => {
        verEnModal(MODAL_ID, mapearDatos(a));
        document.getElementById('modalAntecedenteMedicoTitulo').textContent = 'Ver antecedente médico';
        // Poblar checkbox manualmente después de verEnModal
        document.getElementById('antecedente_alerta').checked = parseInt(a.implica_alerta_medica) === 1;
    });
}
 
function abrirModalEditar(id) {
    cargarAntecedente(id, a => {
        editarEnModal(MODAL_ID, mapearDatos(a));
        document.getElementById('modalAntecedenteMedicoTitulo').textContent = 'Editar antecedente médico';
        // Poblar checkbox manualmente después de editarEnModal
        document.getElementById('antecedente_alerta').checked = parseInt(a.implica_alerta_medica) === 1;
    });
}
 
// ── Guardar ────────────────────────────────────────────────────────
 
document.getElementById('btnGuardarAntecedenteMedico')?.addEventListener('click', function () {
    if (!validarFormulario()) return;
 
    const form     = document.getElementById('formAntecedenteMedico');
    const id       = document.getElementById('antecedente_id').value;
    const formData = new FormData(form);
 
    // Si el checkbox no está marcado, enviar 0 explícitamente
    if (!document.getElementById('antecedente_alerta').checked) {
        formData.set('implica_alerta_medica', '0');
    }
 
    formData.append('modulo', 'antecedentes_medicos');
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
 
    const campos = [
        { id: 'antecedente_nombre', err: 'err_antecedente_nombre', msg: 'El nombre es obligatorio'  },
        { id: 'antecedente_tipo',   err: 'err_antecedente_tipo',   msg: 'El tipo es obligatorio'    },
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
 
    return valido;
}