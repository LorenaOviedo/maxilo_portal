/**
 * Módulo de Configuración
 * Controlador del módulo de configuración.
 * Depende de: CatalogTable, API_URL, CATALOGOS_CFG, SESION_ID
 */
 
// ─────────────────────────────────────────────────────────────────────────────
// SWITCH DE TABS
// ─────────────────────────────────────────────────────────────────────────────
 
function cfgSwitchTab(tabId) {
    document.querySelectorAll('.cfg-tab').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabId);
    });
    document.querySelectorAll('.cfg-tab-content').forEach(div => {
        div.classList.toggle('active', div.id === tabId);
    });
}
 
// ─────────────────────────────────────────────────────────────────────────────
// CONTROLADOR PRINCIPAL
// ─────────────────────────────────────────────────────────────────────────────
 
const cfgController = {
 
    _modoEdicionUsuario: false,
    _idUsuarioActual: null,
 
    // ─────────────────────────────────────────────────────────────────────
    // PERFIL
    // ─────────────────────────────────────────────────────────────────────
 
    async guardarPerfil() {
        const nombre = document.getElementById('perfilNombre')?.value?.trim().toUpperCase() ?? '';
        const email  = document.getElementById('perfilEmail')?.value?.trim().toLowerCase() ?? '';
 
        if (!nombre) {
            CatalogTable.showNotification('El nombre es obligatorio', 'error'); return;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            CatalogTable.showNotification('Ingresa un correo electrónico válido', 'error'); return;
        }
 
        const formData = new FormData();
        formData.append('modulo',         'configuracion');
        formData.append('accion',         'actualizar_perfil');
        formData.append('id_usuario',     SESION_ID);
        formData.append('nombre_usuario', nombre);
        formData.append('email',          email);
 
        await this._post(formData, resp => {
            if (resp.success) {
                CatalogTable.showNotification('Perfil actualizado correctamente', 'success');
                // Actualizar nombre visible en el avatar
                const av = document.querySelector('.cfg-avatar:not(.cfg-avatar-sm)');
                if (av) av.textContent = nombre.charAt(0);
                document.querySelector('.cfg-perfil-nombre').textContent = nombre;
                document.querySelector('.cfg-perfil-email').textContent  = email;
            } else {
                CatalogTable.showNotification(resp.message || 'Error al actualizar', 'error');
            }
        });
    },
 
    resetPerfil() {
        document.getElementById('perfilNombre').value = window.NOMBRE_ORIG ?? '';
        document.getElementById('perfilEmail').value  = window.EMAIL_ORIG  ?? '';
    },
 
    async cambiarContrasena() {
        const actual     = document.getElementById('passActual')?.value ?? '';
        const nueva      = document.getElementById('passNueva')?.value ?? '';
        const confirmar  = document.getElementById('passConfirmar')?.value ?? '';
 
        if (!actual) {
            CatalogTable.showNotification('Ingresa tu contraseña actual', 'error'); return;
        }
        if (nueva.length < 8) {
            CatalogTable.showNotification('La nueva contraseña debe tener al menos 8 caracteres', 'error'); return;
        }
        if (nueva !== confirmar) {
            CatalogTable.showNotification('Las contraseñas no coinciden', 'error'); return;
        }
 
        const formData = new FormData();
        formData.append('modulo',     'configuracion');
        formData.append('accion',     'cambiar_contrasena');
        formData.append('id_usuario', SESION_ID);
        formData.append('actual',     actual);
        formData.append('nueva',      nueva);
 
        await this._post(formData, resp => {
            if (resp.success) {
                CatalogTable.showNotification(resp.message, 'success');
                document.getElementById('passActual').value    = '';
                document.getElementById('passNueva').value     = '';
                document.getElementById('passConfirmar').value = '';
            } else {
                CatalogTable.showNotification(resp.message || 'Error', 'error');
            }
        });
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // USUARIOS
    // ─────────────────────────────────────────────────────────────────────
 
    abrirUsuario(id = null) {
        this._modoEdicionUsuario = !!id;
        this._idUsuarioActual    = id;
 
        // Limpiar formulario
        ['usrId','usrNombre','usrUsuario','usrEmail','usrRol',
         'usrPass','usrPassConfirm'].forEach(i => {
            const el = document.getElementById(i);
            if (el) el.value = '';
        });
 
        // Poblar roles
        const selRol = document.getElementById('usrRol');
        if (selRol) {
            selRol.innerHTML = '<option value="">Seleccionar rol...</option>';
            (CATALOGOS_CFG.roles ?? []).forEach(r => {
                const opt       = document.createElement('option');
                opt.value       = r.id_rol;
                opt.textContent = r.nombre_rol;
                selRol.appendChild(opt);
            });
        }
 
        // Contraseña solo obligatoria al crear
        const grpPass = document.getElementById('usrGrupoPass');
        if (grpPass) grpPass.style.display = id ? 'none' : '';
 
        document.getElementById('modalUsuarioTitulo').textContent =
            id ? 'Editar Usuario' : 'Nuevo Usuario';
        document.getElementById('usrUsuario').disabled = !!id;
 
        abrirModal('modalUsuario');
 
        if (id) this._cargarUsuario(id);
    },
 
    async _cargarUsuario(id) {
        try {
            const r    = await fetch(`${API_URL}?modulo=configuracion&accion=get_usuario&id=${id}`);
            const data = await r.json();
            if (!data.success) return;
 
            const u = data.usuario;
            document.getElementById('usrId').value     = u.id_usuario ?? '';
            document.getElementById('usrNombre').value = u.nombre_usuario ?? '';
            document.getElementById('usrUsuario').value= u.usuario  ?? '';
            document.getElementById('usrEmail').value  = u.email    ?? '';
 
            requestAnimationFrame(() => requestAnimationFrame(() => {
                document.getElementById('usrRol').value = String(u.id_rol ?? '');
            }));
        } catch(err) {
            CatalogTable.showNotification('Error al cargar usuario', 'error');
        }
    },
 
    async guardarUsuario() {
        const nombre   = document.getElementById('usrNombre')?.value?.trim().toUpperCase() ?? '';
        const usuario  = document.getElementById('usrUsuario')?.value?.trim().toLowerCase() ?? '';
        const email    = document.getElementById('usrEmail')?.value?.trim().toLowerCase() ?? '';
        const rol      = document.getElementById('usrRol')?.value ?? '';
        const pass     = document.getElementById('usrPass')?.value ?? '';
        const passConf = document.getElementById('usrPassConfirm')?.value ?? '';
 
        if (!nombre) {
            CatalogTable.showNotification('El nombre es obligatorio', 'error'); return;
        }
        if (!usuario || /\s/.test(usuario)) {
            CatalogTable.showNotification('El usuario es obligatorio y no puede tener espacios', 'error'); return;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            CatalogTable.showNotification('Ingresa un correo válido', 'error'); return;
        }
        if (!rol) {
            CatalogTable.showNotification('Selecciona un rol', 'error'); return;
        }
        if (!this._modoEdicionUsuario) {
            if (pass.length < 8) {
                CatalogTable.showNotification('La contraseña debe tener al menos 8 caracteres', 'error'); return;
            }
            if (pass !== passConf) {
                CatalogTable.showNotification('Las contraseñas no coinciden', 'error'); return;
            }
        }
 
        const formData = new FormData();
        formData.append('modulo',         'configuracion');
        formData.append('accion',         this._modoEdicionUsuario ? 'actualizar_usuario' : 'crear_usuario');
        if (this._modoEdicionUsuario) formData.append('id_usuario', this._idUsuarioActual);
        formData.append('nombre_usuario', nombre);
        formData.append('usuario',        usuario);
        formData.append('email',          email);
        formData.append('id_rol',         rol);
        if (!this._modoEdicionUsuario) formData.append('contrasena', pass);
 
        await this._post(formData, resp => {
            if (resp.success) {
                CatalogTable.showNotification(resp.message, 'success');
                cerrarModal('modalUsuario');
                setTimeout(() => window.location.reload(), 800);
            } else {
                CatalogTable.showNotification(resp.message || 'Error', 'error');
            }
        });
    },
 
    async toggleEstatus(id, nombre) {
        if (!confirm(`¿Cambiar el estatus del usuario "${nombre}"?`)) return;
 
        const formData = new FormData();
        formData.append('modulo',     'configuracion');
        formData.append('accion',     'toggle_estatus');
        formData.append('id_usuario', id);
 
        await this._post(formData, resp => {
            if (resp.success) {
                CatalogTable.showNotification(resp.message, 'success');
                setTimeout(() => window.location.reload(), 700);
            } else {
                CatalogTable.showNotification(resp.message || 'Error', 'error');
            }
        });
    },
 
    async resetContrasena(id, nombre) {
        const nueva = prompt(`Nueva contraseña para "${nombre}" (mínimo 8 caracteres):`);
        if (!nueva) return;
        if (nueva.length < 8) {
            CatalogTable.showNotification('La contraseña debe tener al menos 8 caracteres', 'error');
            return;
        }
 
        const formData = new FormData();
        formData.append('modulo',     'configuracion');
        formData.append('accion',     'reset_contrasena');
        formData.append('id_usuario', id);
        formData.append('nueva',      nueva);
 
        await this._post(formData, resp => {
            CatalogTable.showNotification(resp.message, resp.success ? 'success' : 'error');
        });
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // PERMISOS
    // ─────────────────────────────────────────────────────────────────────
 
    async guardarPermiso(checkbox) {
        const idRol    = checkbox.dataset.rol;
        const idModulo = checkbox.dataset.modulo;
        const activo   = checkbox.checked;
 
        const formData = new FormData();
        formData.append('modulo',     'configuracion');
        formData.append('accion',     activo ? 'agregar_permiso' : 'quitar_permiso');
        formData.append('id_rol',     idRol);
        formData.append('id_modulo',  idModulo);
 
        checkbox.disabled = true;
        await this._post(formData, resp => {
            checkbox.disabled = false;
            if (!resp.success) {
                checkbox.checked = !activo; // revertir
                CatalogTable.showNotification(resp.message || 'Error al actualizar permiso', 'error');
            }
        });
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // HELPER HTTP
    // ─────────────────────────────────────────────────────────────────────
 
    async _post(formData, callback) {
        CatalogTable.showLoading(true);
        try {
            const r    = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await r.json();
            CatalogTable.showLoading(false);
            callback(data);
        } catch (err) {
            CatalogTable.showLoading(false);
            console.error('cfgController._post:', err);
            CatalogTable.showNotification('Error de conexión', 'error');
        }
    },
};
 
// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS
// ─────────────────────────────────────────────────────────────────────────────
 
/**
 * Visor de contraseña — mostrar mientras se mantiene presionado el ojo
 */
function agregarVisorContrasena(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
 
    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'position:relative; display:block;';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
 
    const btn = document.createElement('button');
    btn.type     = 'button';
    btn.tabIndex = -1;
    btn.setAttribute('aria-label', 'Mostrar contraseña');
    btn.innerHTML = '<i class="ri-eye-line"></i>';
    btn.style.cssText = `
        position:absolute; right:10px; top:50%; transform:translateY(-50%);
        background:none; border:none; cursor:pointer; color:#adb5bd;
        font-size:17px; line-height:1; padding:4px;
        display:flex; align-items:center; user-select:none;
        transition: color .2s;
    `;
    wrapper.appendChild(btn);
    input.style.paddingRight = '38px';
 
    const mostrar = (e) => {
        e.preventDefault();
        input.type    = 'text';
        btn.style.color = '#20a89e';
        btn.innerHTML   = '<i class="ri-eye-off-line"></i>';
    };
    const ocultar = () => {
        input.type    = 'password';
        btn.style.color = '#adb5bd';
        btn.innerHTML   = '<i class="ri-eye-line"></i>';
    };
 
    btn.addEventListener('mousedown',  mostrar);
    btn.addEventListener('mouseup',    ocultar);
    btn.addEventListener('mouseleave', ocultar);
    btn.addEventListener('touchstart', mostrar, { passive: false });
    btn.addEventListener('touchend',   ocultar);
}
 
document.addEventListener('DOMContentLoaded', () => {
 
    document.getElementById('btnGuardarUsuario')
        ?.addEventListener('click', () => cfgController.guardarUsuario());
 
    // Visor de contraseña en cambiar contraseña
    agregarVisorContrasena('passActual');
    agregarVisorContrasena('passNueva');
    agregarVisorContrasena('passConfirmar');
 
    // Visor en modal de nuevo usuario
    agregarVisorContrasena('usrPass');
    agregarVisorContrasena('usrPassConfirm');
 
    // Nombre a mayúsculas en tiempo real
    document.getElementById('usrNombre')
        ?.addEventListener('input', e => {
            e.target.value = e.target.value.toUpperCase();
        });
 
    // Usuario sin espacios
    document.getElementById('usrUsuario')
        ?.addEventListener('input', e => {
            e.target.value = e.target.value.replace(/\s/g, '').toLowerCase();
        });
});