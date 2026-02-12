<?php
/**
 * Sidebar: menú lateral del sistema
 */
// Si no está incluido config.php
if (!defined('SITE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$rol = $_SESSION['rol'] ?? 'usuario';

// Determinar qué menú está activo
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Función helper- para determinar si un menú está activo
function is_active($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="menu">
                <li class="menu-item <?php echo is_active('catalogos'); ?>">
                    <a href="<?php echo view_url('catalogos.php'); ?>">
                        <span class="menu-icon"><i class="hgi hgi-stroke hgi-catalogue"></i></span>
                        <span class="menu-text">Catálogos</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('pacientes'); ?>">
                    <a href="<?php echo view_url('pacientes.php'); ?>">
                        <span class="menu-icon"><i class="hgi hgi-stroke hgi-smile"></i></span>
                        <span class="menu-text">Pacientes</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('especialistas'); ?>">
                    <a href="<?php echo view_url('especialistas.php'); ?>">
                        <span class="menu-icon"><i class="hgi hgi-stroke hgi-user-multiple"></i></span>
                        <span class="menu-text">Especialistas</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('citas'); ?>">
                    <a href="<?php echo view_url('citas.php'); ?>">
                        <span class="menu-icon"><i class="hgi hgi-stroke hgi-calendar-03"></i></span>
                        <span class="menu-text">Citas</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('compras'); ?>">
                    <a href="<?php echo view_url('compras.php'); ?>">
                        <span class="menu-icon"><i class="hgi hgi-stroke hgi-package"></i></span>
                        <span class="menu-text">Compras y proveedores</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('inventario'); ?>">
                    <a href="<?php echo view_url('inventario.php'); ?>">
                        <span class="menu-icon"><i class="hgi hgi-stroke hgi-quiz-03"></i></span>
                        <span class="menu-text">Inventario</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('pagos'); ?>">
                    <a href="<?php echo view_url('pagos.php'); ?>">
                        <span class="menu-icon"><i class="hgi hgi-stroke hgi-dollar-02"></i></span>
                        <span class="menu-text">Pagos</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('reportes'); ?>">
                    <a href="<?php echo view_url('reportes.php'); ?>">
                        <span class="menu-icon"><i class="hgi hgi-stroke hgi-waterfall-down-01"></i></span>
                        <span class="menu-text">Reportes</span>
                    </a>
                </li>
                
                <?php if ($rol === 'admin'): ?>
                <li class="menu-item <?php echo is_active('configuracion'); ?>">
                    <a href="<?php echo view_url('configuracion.php'); ?>">
                        <span class="menu-icon"><i class="hgi hgi-stroke hgi-settings-01"></i></span>
                        <span class="menu-text">Configuración</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <!-- boton de cierre de sesión -->
            <div class="sidebar-footer">
                <a href="<?php echo url('index.php?logout=1'); ?>" class="sidebar-logout">
                    <span class="menu-text">Cerrar sesión</span>
                    <span class="logout-icon"><i class="hgi hgi-stroke hgi-logout-square-01"></i></span>
                </a>
            </div>
        </aside>