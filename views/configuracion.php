<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Configuracion.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}
 
// Verificar permiso específico al módulo
verificarPermiso('configuracion');
 
$page_title = 'Configuración';
$page_css   = ['catalogos-tabla.css', 'modal.css', 'configuracion.css'];
$page_js    = [];
 
$db    = getDB();
$model = new Configuracion($db);
 
$idUsuario   = (int) $_SESSION['usuario_id'];
$rolActual   = strtolower(trim($_SESSION['rol'] ?? ''));
$esAdmin     = (strpos($rolActual, 'admin') !== false);
 
$perfil      = $model->getPerfil($idUsuario);
$catalogos   = $model->getCatalogos();
$usuarios    = $esAdmin ? $model->getUsuarios() : [];
$matriz      = $esAdmin ? $model->getPermisosMatriz() : null;
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
<main class="main-content">
 
 
    <div class="page-header">
        <h1>Configuración</h1>
        <p class="page-description">Gestiona las opciones de configuración del sistema</p>
    </div>
 
    <!-- ── Tabs ──────────────────────────────────────────────────── -->
    <div class="cfg-tabs">
        <button class="cfg-tab active" data-tab="tabPerfil"
            onclick="cfgSwitchTab('tabPerfil')">
            <i class="ri-user-settings-line"></i> Mi Perfil
        </button>
        <?php if ($esAdmin): ?>
        <button class="cfg-tab" data-tab="tabUsuarios"
            onclick="cfgSwitchTab('tabUsuarios')">
            <i class="ri-team-line"></i> Administración de Usuarios
        </button>
        <button class="cfg-tab" data-tab="tabPermisos"
            onclick="cfgSwitchTab('tabPermisos')">
            <i class="ri-shield-keyhole-line"></i> Roles y Permisos
        </button>
        <?php endif; ?>
    </div>
 
    <!-- ══════════════════════════════════════════════════════════════
         TAB 1 — MI PERFIL
    ══════════════════════════════════════════════════════════════ -->
    <div id="tabPerfil" class="cfg-tab-content active">
 
        <!-- Avatar + resumen -->
        <div class="cfg-card">
            <div class="cfg-perfil-header">
                <div class="cfg-avatar">
                    <?php echo mb_strtoupper(mb_substr($perfil['nombre_usuario'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8'); ?>
                </div>
                <div class="cfg-perfil-info">
                    <div class="cfg-perfil-nombre">
                        <?php echo htmlspecialchars($perfil['nombre_usuario'] ?? '—'); ?>
                    </div>
                    <div class="cfg-perfil-email">
                        <?php echo htmlspecialchars($perfil['email'] ?? '—'); ?>
                    </div>
                    <span class="badge badge-success" style="margin-top:6px;">
                        <?php echo htmlspecialchars($perfil['nombre_rol'] ?? '—'); ?>
                    </span>
                </div>
            </div>
        </div>
 
        <!-- Datos editables -->
        <div class="cfg-card" style="margin-top:16px;">
            <div class="cfg-card-title">Datos personales</div>
            <form class="cfg-form" id="formPerfil" autocomplete="off">
 
                <div class="cfg-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" id="perfilNombre" name="nombre_usuario"
                            class="form-input"
                            value="<?php echo htmlspecialchars($perfil['nombre_usuario'] ?? ''); ?>"
                            autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" id="perfilEmail" name="email"
                            class="form-input"
                            value="<?php echo htmlspecialchars($perfil['email'] ?? ''); ?>"
                            autocomplete="off">
                    </div>
                </div>
 
                <div class="cfg-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Rol</label>
                        <input type="text" class="form-input"
                            value="<?php echo htmlspecialchars($perfil['nombre_rol'] ?? '—'); ?>"
                            disabled>
                    </div>
                </div>
 
                <div class="cfg-actions">
                    <button type="button" class="btn-cfg-save"
                        onclick="cfgController.guardarPerfil()">
                        Guardar
                    </button>
                    <button type="button" class="btn-cfg-cancel"
                        onclick="cfgController.resetPerfil()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
 
        <!-- Cambiar contraseña -->
        <div class="cfg-card" style="margin-top:16px;">
            <div class="cfg-card-title">Cambiar Contraseña</div>
            <form class="cfg-form" id="formPassword" autocomplete="off">
 
                <div class="cfg-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Contraseña actual</label>
                        <input type="password" id="passActual"
                            class="form-input"
                            placeholder="Ingresa tu contraseña actual"
                            autocomplete="current-password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" id="passNueva"
                            class="form-input"
                            placeholder="Mínimo 8 caracteres"
                            autocomplete="new-password">
                    </div>
                </div>
 
                <div class="cfg-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" id="passConfirmar"
                            class="form-input"
                            placeholder="Repite la nueva contraseña"
                            autocomplete="new-password">
                    </div>
                </div>
 
                <div class="cfg-actions">
                    <button type="button" class="btn-cfg-save"
                        onclick="cfgController.cambiarContrasena()">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
 
    </div><!-- /#tabPerfil -->
 
    <?php if ($esAdmin): ?>
 
    <!-- ══════════════════════════════════════════════════════════════
         TAB 2 — ADMINISTRACIÓN DE USUARIOS
    ══════════════════════════════════════════════════════════════ -->
    <div id="tabUsuarios" class="cfg-tab-content">
 
        <div class="cfg-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <div class="cfg-card-title" style="margin-bottom:0;">Usuarios del sistema</div>
                <button type="button" class="btn-add-new"
                    onclick="cfgController.abrirUsuario()">
                    <i class="ri-add-line"></i> Nuevo usuario
                </button>
            </div>
 
            <div class="table-container" style="box-shadow:none; padding:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>NOMBRE</th>
                            <th>USUARIO</th>
                            <th>EMAIL</th>
                            <th>ROL</th>
                            <th class="text-center">ESTATUS</th>
                            <th class="col-actions">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="ri-team-line"></i></div>
                                    <h3 class="empty-state-title">Sin usuarios registrados</h3>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div class="cfg-avatar cfg-avatar-sm">
                                            <?php echo mb_strtoupper(mb_substr($u['nombre_usuario'], 0, 1, 'UTF-8'), 'UTF-8'); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($u['nombre_usuario']); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($u['usuario']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="badge badge-info" style="font-size:11px;">
                                        <?php echo htmlspecialchars($u['nombre_rol']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?php echo strtolower($u['estatus']) === 'activo' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo htmlspecialchars($u['estatus']); ?>
                                    </span>
                                </td>
                                <td class="col-actions">
                                    <div class="action-buttons">
                                        <button type="button" class="btn-action btn-edit"
                                            title="Editar"
                                            onclick="cfgController.abrirUsuario(<?php echo $u['id_usuario']; ?>)">
                                            <i class="ri-edit-box-line"></i>
                                        </button>
                                        <?php if ($u['id_usuario'] !== $idUsuario): ?>
                                        <button type="button" class="btn-action <?php echo strtolower($u['estatus']) === 'activo' ? 'btn-delete' : 'btn-view'; ?>"
                                            title="<?php echo strtolower($u['estatus']) === 'activo' ? 'Desactivar' : 'Activar'; ?>"
                                            onclick="cfgController.toggleEstatus(<?php echo $u['id_usuario']; ?>, '<?php echo htmlspecialchars($u['nombre_usuario'], ENT_QUOTES); ?>')">
                                            <i class="<?php echo strtolower($u['estatus']) === 'activo' ? 'ri-forbid-line' : 'ri-check-line'; ?>"></i>
                                        </button>
                                        <button type="button" class="btn-action btn-view"
                                            title="Restablecer contraseña"
                                            onclick="cfgController.resetContrasena(<?php echo $u['id_usuario']; ?>, '<?php echo htmlspecialchars($u['nombre_usuario'], ENT_QUOTES); ?>')">
                                            <i class="ri-lock-password-line"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
 
    </div><!-- /#tabUsuarios -->
 
    <!-- ══════════════════════════════════════════════════════════════
         TAB 3 — ROLES Y PERMISOS
    ══════════════════════════════════════════════════════════════ -->
    <div id="tabPermisos" class="cfg-tab-content">
 
        <div class="cfg-card">
            <div class="cfg-card-title">Permisos por módulo</div>
            <p style="font-size:13px;color:#6c757d;margin-bottom:20px;">
                Activa o desactiva el acceso de cada rol a los módulos del sistema.
            </p>
 
            <?php if ($matriz): ?>
            <div style="overflow-x:auto;">
                <table class="permisos-table">
                    <thead>
                        <tr>
                            <th class="modulo-col">MÓDULO</th>
                            <?php foreach ($matriz['roles'] as $rol): ?>
                            <th class="text-center">
                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matriz['modulos'] as $modulo): ?>
                        <tr>
                            <td class="modulo-nombre">
                                <?php echo htmlspecialchars($modulo['modulo']); ?>
                                <?php if ($modulo['descripcion_modulo']): ?>
                                <div style="font-size:11px;color:#adb5bd;">
                                    <?php echo htmlspecialchars($modulo['descripcion_modulo']); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <?php foreach ($matriz['roles'] as $rol): ?>
                            <td class="text-center">
                                <label class="cfg-switch">
                                    <input type="checkbox"
                                        data-rol="<?php echo $rol['id_rol']; ?>"
                                        data-modulo="<?php echo $modulo['id_modulo']; ?>"
                                        <?php echo isset($matriz['asignados'][$rol['id_rol']][$modulo['id_modulo']]) ? 'checked' : ''; ?>
                                        onchange="cfgController.guardarPermiso(this)">
                                    <span class="cfg-slider"></span>
                                </label>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
 
    </div><!-- /#tabPermisos -->
 
    <?php endif; ?>
 
</main>
 
<script>
var API_URL        = '<?php echo ajax_url('Api.php'); ?>';
var CATALOGOS_CFG  = <?php echo json_encode($catalogos); ?>;
var SESION_ID      = <?php echo $idUsuario; ?>;
var NOMBRE_ORIG    = <?php echo json_encode($perfil['nombre_usuario'] ?? ''); ?>;
var EMAIL_ORIG     = <?php echo json_encode($perfil['email'] ?? ''); ?>;
</script>
 
<?php include '../includes/modal_usuario.php'; ?>
<script src="<?php echo asset('js/configuracion.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
 
<?php include '../includes/footer.php'; ?>