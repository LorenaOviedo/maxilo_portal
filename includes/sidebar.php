<?php
// Sidebar: menú lateral del sistema

if (!defined('SITE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Cargar módulos directamente desde BD en cada request
// para garantizar que los cambios de permisos se reflejen inmediatamente
$esAdmin = false;
$modulosNombres = [];

if (isset($_SESSION['usuario_id'])) {
    try {
        $dbSidebar = getDB();

        // Obtener id_rol y nombre del rol del usuario actual
        $stmtRol = $dbSidebar->prepare("
            SELECT u.id_rol, LOWER(r.nombre_rol) AS nombre_rol
            FROM   usuario u
            JOIN   rol     r ON r.id_rol = u.id_rol
            WHERE  u.id_usuario = :id
        ");
        $stmtRol->execute([':id' => (int) $_SESSION['usuario_id']]);
        $infoRol = $stmtRol->fetch(PDO::FETCH_ASSOC);

        if ($infoRol) {
            $rolDB = $infoRol['nombre_rol'] ?? '';
            $esAdmin = (strpos($rolDB, 'admin') !== false);

            // Cargar módulos permitidos del rol
            $stmtMod = $dbSidebar->prepare("
                SELECT LOWER(m.modulo) AS modulo
                FROM   rolpermiso rp
                JOIN   modulos    m ON m.id_modulo = rp.id_modulo
                WHERE  rp.id_rol = :id_rol
            ");
            $stmtMod->execute([':id_rol' => (int) $infoRol['id_rol']]);
            $modulosNombres = array_column(
                $stmtMod->fetchAll(PDO::FETCH_ASSOC),
                'modulo'
            );
        }
    } catch (Exception $e) {
        error_log('Sidebar: error cargando módulos — ' . $e->getMessage());
    }
}

// Helper — verificar si el módulo está permitido
function tieneAcceso(string $modulo): bool
{
    global $modulosNombres;
    // Todos los roles respetan rolpermiso — incluyendo admin
    foreach ($modulosNombres as $m) {
        if (
            strpos(strtolower($m), strtolower($modulo)) !== false ||
            strpos(strtolower($modulo), strtolower($m)) !== false
        ) {
            return true;
        }
    }
    return false;
}

// Determinar página activa
$current_page = basename($_SERVER['PHP_SELF'], '.php');

function is_active(string $page): string
{
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>

<div id="sidebarOverlay" class="sidebar-overlay"></div>
<aside class="sidebar" id="sidebar">
    <!-- Botón cerrar - Móvil -->
    <button class="sidebar-close" id="sidebarClose" aria-label="Cerrar menú">
        <i class="ri-close-line"></i>
    </button>

    <ul class="menu">

        <?php if (tieneAcceso('inicio')): ?>
            <li class="menu-item <?php echo is_active('inicio'); ?>">
                <a href="<?php echo view_url('inicio.php'); ?>">
                    <span class="menu-icon"><i class="ri-home-2-line"></i></span>
                    <span class="menu-text">Inicio</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (tieneAcceso('dashboard')): ?>
            <li class="menu-item <?php echo is_active('dashboard'); ?>">
                <a href="<?php echo view_url('dashboard.php'); ?>">
                    <span class="menu-icon"><i class="ri-dashboard-line"></i></span>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (tieneAcceso('catalogos')): ?>
            <li class="menu-item <?php echo is_active('catalogos'); ?>">
                <a href="<?php echo view_url('catalogos.php'); ?>">
                    <span class="menu-icon"><i class="ri-book-open-line"></i></span>
                    <span class="menu-text">Catálogos</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (tieneAcceso('pacientes')): ?>
            <li class="menu-item <?php echo is_active('pacientes'); ?>">
                <a href="<?php echo view_url('pacientes.php'); ?>">
                    <span class="menu-icon"><i class="ri-emotion-happy-line"></i></span>
                    <span class="menu-text">Pacientes</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (tieneAcceso('especialistas')): ?>
            <li class="menu-item <?php echo is_active('especialistas'); ?>">
                <a href="<?php echo view_url('especialistas.php'); ?>">
                    <span class="menu-icon"><i class="ri-group-line"></i></span>
                    <span class="menu-text">Especialistas</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (tieneAcceso('citas')): ?>
            <li class="menu-item <?php echo is_active('citas'); ?>">
                <a href="<?php echo view_url('citas.php'); ?>">
                    <span class="menu-icon"><i class="ri-calendar-line"></i></span>
                    <span class="menu-text">Citas</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (tieneAcceso('compras')): ?>
            <li class="menu-item <?php echo is_active('compras_proveedores'); ?>">
                <a href="<?php echo view_url('compras_proveedores.php'); ?>">
                    <span class="menu-icon"><i class="ri-store-2-line"></i></span>
                    <span class="menu-text">Compras y proveedores</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (tieneAcceso('inventarios')): ?>
            <li class="menu-item <?php echo is_active('catalogos_inventario'); ?>">
                <a href="<?php echo view_url('catalogos_inventario.php'); ?>">
                    <span class="menu-icon"><i class="ri-todo-line"></i></span>
                    <span class="menu-text">Inventario</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (tieneAcceso('pagos')): ?>
            <li class="menu-item <?php echo is_active('pagos'); ?>">
                <a href="<?php echo view_url('pagos.php'); ?>">
                    <span class="menu-icon"><i class="ri-money-dollar-circle-line"></i></span>
                    <span class="menu-text">Pagos</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (tieneAcceso('reportes')): ?>
            <li class="menu-item <?php echo is_active('reportes'); ?>">
                <a href="<?php echo view_url('reportes.php'); ?>">
                    <span class="menu-icon"><i class="ri-pie-chart-2-line"></i></span>
                    <span class="menu-text">Reportes</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (tieneAcceso('configuracion')): ?>
            <li class="menu-item <?php echo is_active('configuracion'); ?>">
                <a href="<?php echo view_url('configuracion.php'); ?>">
                    <span class="menu-icon"><i class="ri-settings-3-line"></i></span>
                    <span class="menu-text">Configuración</span>
                </a>
            </li>
        <?php endif; ?>

    </ul>

    <!-- Botón cerrar sesión -->
    <div class="sidebar-footer only-mobile">
        <a href="<?php echo url('index.php?logout=1'); ?>" class="sidebar-logout">
            <span class="menu-text">Cerrar sesión</span>
            <span class="logout-icon"><i class="ri-logout-box-r-line"></i></span>
        </a>
    </div>
</aside>