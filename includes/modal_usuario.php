<?php /* MODAL USUARIO — CREAR / EDITAR */ ?>
 
<div id="modalUsuario-overlay" class="modal-overlay"></div>
 
<div id="modalUsuario" class="modal-container">
 
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title" id="modalUsuarioTitulo">Nuevo Usuario</h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close"
                onclick="cerrarModal('modalUsuario')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <div class="modal-body">
        <form class="modal-form" id="formUsuario" autocomplete="off">
            <input type="hidden" id="usrId" name="id_usuario">
 
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Nombre completo <span class="required">*</span>
                    </label>
                    <input type="text" id="usrNombre" name="nombre_usuario"
                        class="form-input" maxlength="100"
                        placeholder="Nombre completo"
                        style="text-transform:uppercase;" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Usuario <span class="required">*</span>
                    </label>
                    <input type="text" id="usrUsuario" name="usuario"
                        class="form-input" maxlength="15"
                        placeholder="Ej: jlopez" autocomplete="off">
                    <small class="form-hint">Máximo 15 caracteres, sin espacios</small>
                </div>
            </div>
 
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Correo electrónico <span class="required">*</span>
                    </label>
                    <input type="email" id="usrEmail" name="email"
                        class="form-input" maxlength="150"
                        placeholder="correo@ejemplo.com" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Rol <span class="required">*</span>
                    </label>
                    <select id="usrRol" name="id_rol" class="form-select">
                        <option value="">Seleccionar rol...</option>
                    </select>
                </div>
            </div>
 
            <!-- Contraseña — solo al crear -->
            <div id="usrGrupoPass">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">
                            Contraseña <span class="required">*</span>
                        </label>
                        <input type="password" id="usrPass" name="contrasena"
                            class="form-input"
                            placeholder="Mínimo 8 caracteres"
                            autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            Confirmar contraseña <span class="required">*</span>
                        </label>
                        <input type="password" id="usrPassConfirm"
                            class="form-input"
                            placeholder="Repite la contraseña"
                            autocomplete="new-password">
                    </div>
                </div>
            </div>
 
        </form>
    </div>
 
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel"
            onclick="cerrarModal('modalUsuario')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnGuardarUsuario">
            <i class="ri-save-line"></i> Guardar
        </button>
    </div>
 
</div><!-- /#modalUsuario -->