<?php
/*Header del sistema de gestión integral Maxilofacial Texcoco*/
// Enlace configuración
if (!defined('SITE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Datos del usuario de la sesión
$nombreUsuario = $_SESSION['nombre_completo'] ?? 'Usuario';
$rol = $_SESSION['rol'] ?? 'usuario';
$email = $_SESSION['email'] ?? '';

// Generar iniciales para el avatar
$iniciales = '';
$nombres = explode(' ', $nombreUsuario);
foreach ($nombres as $nombre) {
    $iniciales .= strtoupper(substr($nombre, 0, 1));
    if (strlen($iniciales) >= 2) break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    <title><?php echo $page_title ?? SITE_NAME; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo asset('img/logo_maxilo.png'); ?>">
    
    <!-- CSS general -->
    <link rel="stylesheet" href="<?php echo asset('css/dashboard.css'); ?>?v=<?php echo SITE_VERSION; ?>">
    
    <!-- CSS de la página actual -->
    <?php if (isset($page_css)): ?>
        <?php foreach ((array)$page_css as $css): ?>
            <link rel="stylesheet" href="<?php echo asset('css/' . $css); ?>?v=<?php echo SITE_VERSION; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Paquete de iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    
    <!-- CSS -->
    <?php if (isset($additional_css)): ?>
        <?php echo $additional_css; ?>
    <?php endif; ?>
</head>
<body>
    <div class="dashboard-container">
        <!-- dentro de header.php -->
<?php if (basename($_SERVER['PHP_SELF']) === 'dashboard.php'): ?>
  <div class="header-welcome">
    Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>
  </div>
<?php endif; ?>
        <!-- Navbar -->
        <nav class="navbar">
            <div class="navbar-brand">
                <img src="<?php echo asset('img/logo_maxilo.png'); ?>" alt="Logo" class="navbar-logo">
                <div class="navbar-title-wrapper">
                    <span class="navbar-title"><?php echo SITE_NAME; ?></span>
                    <span class="navbar-subtitle"><?php echo SITE_DESCRIPTION; ?></span>
                </div>
            </div>
           <!-- dentro de header.php -->
<?php if (basename($_SERVER['PHP_SELF']) === 'dashboard.php'): ?>
  <div class="header-welcome">
    Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>
  </div>
<?php endif; ?> 
            <div class="navbar-user">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($nombreUsuario); ?></span>
                    <span class="user-role"><?php echo ucfirst(htmlspecialchars($rol)); ?></span>
                </div>
                <div class="user-avatar" title="<?php echo htmlspecialchars($nombreUsuario); ?>">
                    <?php echo $iniciales; ?>
                </div>
            </div>
        </nav>