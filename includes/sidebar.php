<?php
//Sidebar: menú lateral del sistema

// Si no está incluido config.php
if (!defined('SITE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$rol = $_SESSION['rol'] ?? 'usuario';

// Determinar qué menú está activo
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Función helper- para determinar si un menú está activo---
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
                        <span class="menu-icon"><i class="ri-book-open-line"></i></span>
                        <span class="menu-text">Catálogos</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('pacientes'); ?>">
                    <a href="<?php echo view_url('pacientes.php'); ?>">
                        <span class="menu-icon"><i class="ri-emotion-happy-line"></i></span>
                        <span class="menu-text">Pacientes</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('especialistas'); ?>">
                    <a href="<?php echo view_url('especialistas.php'); ?>">
                        <span class="menu-icon"><i class="ri-group-line"></i></span>
                        <span class="menu-text">Especialistas</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('citas'); ?>">
                    <a href="<?php echo view_url('citas.php'); ?>">
                        <span class="menu-icon"><i class="ri-calendar-line"></i></span>
                        <span class="menu-text">Citas</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('compras_proveedores'); ?>">
                    <a href="<?php echo view_url('compras_proveedores.php'); ?>">
                        <span class="menu-icon"><i class="ri-store-2-line"></i></span>
                        <span class="menu-text">Compras y proveedores</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('inventario'); ?>">
                    <a href="<?php echo view_url('inventario.php'); ?>">
                        <span class="menu-icon"><i class="ri-todo-line"></i></span>
                        <span class="menu-text">Inventario</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('pagos'); ?>">
                    <a href="<?php echo view_url('pagos.php'); ?>">
                        <span class="menu-icon"><i class="ri-money-dollar-circle-line"></i></span>
                        <span class="menu-text">Pagos</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo is_active('reportes'); ?>">
                    <a href="<?php echo view_url('reportes.php'); ?>">
                        <span class="menu-icon"><i class="ri-pie-chart-2-line"></i></span>
                        <span class="menu-text">Reportes</span>
                    </a>
                </li>
                
                <?php if ($rol === 'admin'): ?>
                <li class="menu-item <?php echo is_active('configuracion'); ?>">
                    <a href="<?php echo view_url('configuracion.php'); ?>">
                        <span class="menu-icon"><i class="ri-settings-5-line"></i></span>
                        <span class="menu-text">Configuración</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <!-- boton de cierre de sesión -->
            <div class="sidebar-footer">
                <a href="<?php echo url('index.php?logout=1'); ?>" class="sidebar-logout">
                    <span class="menu-text">Cerrar sesión</span>
                    <span class="logout-icon"><i class="ri-logout-box-r-line"></i></span>
                </a>
            </div>
        </aside>