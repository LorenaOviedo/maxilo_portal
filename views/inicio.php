<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

// Verificar permiso específico al módulo
verificarPermiso('inicio');
 
$page_title = 'Bienvenido';
$page_css   = ['inicio.css'];
$page_js    = [];
 
$nombre = $_SESSION['nombre_completo'] ?? $_SESSION['usuario'] ?? 'Usuario';
$rol    = $_SESSION['rol'] ?? '';
 
// Saludo según la hora
$hora   = (int) date('H');
if ($hora >= 6 && $hora < 12) {
    $saludo = 'Buenos días';
} elseif ($hora >= 12 && $hora < 19) {
    $saludo = 'Buenas tardes';
} else {
    $saludo = 'Buenas noches';
}
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
<main class="main-content inicio-main">
 
    <div class="inicio-wrapper">
 
        <!-- Saludo -->
        <div class="inicio-saludo">
            <h1 class="inicio-saludo-texto">
                <?php echo $saludo; ?>,
                <span class="inicio-nombre">
                    <?php echo htmlspecialchars(explode(' ', $nombreUsuario)[0]); ?>
                </span>
            </h1>
            <p class="inicio-rol">
                <?php echo htmlspecialchars($rol); ?>
                &nbsp;·&nbsp;
                <?php echo date('l, d \d\e F \d\e Y'); ?>
            </p>
        </div>
 
        <!-- Imagen de la clínica -->
        <div class="inicio-imagen-wrapper">
            <img
                src="<?php echo asset('img/clinica.jpg'); ?>"
                alt="Maxilofacial Texcoco"
                class="inicio-imagen"
                onerror="this.closest('.inicio-imagen-wrapper').classList.add('sin-imagen')"
            >
            <div class="inicio-imagen-overlay">
                <div class="inicio-imagen-texto">
                    <div class="inicio-clinica-nombre">Maxilofacial Texcoco</div>
                    <div class="inicio-clinica-sub">
                        Ortodoncia &nbsp;·&nbsp; Cirugía Maxilofacial &nbsp;·&nbsp; Patología Oral
                    </div>
                </div>
            </div>
        </div>
 
    </div>
 
</main>
 
<?php include '../includes/footer.php'; ?>