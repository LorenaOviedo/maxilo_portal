<?php
ob_start(); // Capturar output para evitar "headers already sent"
 
// No cache en el login tampoco
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
//session_start();
 
// Procesar logout si se recibe el parámetro
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    require_once 'controllers/AuthController.php';
    $auth = new AuthController();
    $auth->logout();
    header('Location: index.php');
    exit;
}
 
// Si ya está logueado, redirigir según rol
if (isset($_SESSION['usuario_id'])) {
    require_once 'controllers/AuthController.php';
    $auth = new AuthController();
    ob_end_clean();
    header('Location: views/' . $auth->getPaginaInicio());
    exit;
}
 
// Procesar el login si se envió el formulario
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // VERIFICACIÓN RECAPTCHA PRIMERO
    require_once 'config/recaptcha.php';
    $recaptchaResult = verificarRecaptcha();
    
    if (!$recaptchaResult['success']) {
        // RECAPTCHA falló
        $error = $recaptchaResult['error'];
    } else {
        // RECAPTCHA válido, proceder con login
        require_once 'controllers/AuthController.php';
        $auth = new AuthController();
        $resultado = $auth->login($_POST['usuario'], $_POST['password']);
 
        if ($resultado['success']) {
            ob_end_clean();
            header('Location: views/' . $auth->getPaginaInicio());
            exit;
        } else {
            $error = $resultado['message'];
        }
    }
    // FIN DE VERIFICACIÓN
}
 
?>
<!DOCTYPE html>
<html lang="es">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Maxilofacial Texcoco</title>
    <link rel="stylesheet" href="assets/css/login.css">
 
    <!-- Iconos de Remixicon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Script de reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
 
<body>
    <div class="container">
        <div class="login-box">
            <div class="logo-container">
                <img src="assets/img/logo_maxilo.png" alt="Maxilofacial Texcoco Logo" class="logo">
            </div>
 
            <h1 class="welcome-title">BIENVENIDO</h1>
 
            <div class="form-container">
                <h2 class="form-title">INICIO DE SESIÓN</h2>
 
                <?php if ($error): ?>
                    <div class="error-message" id="errorMessage">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
 
                <form id="loginForm" method="POST" action="">
                    <div class="form-group">
                        <input type="text" id="usuario" name="usuario" class="form-input"
                            placeholder="Usuario/Correo eléctronico" required autocomplete="username">
                        <span class="error-field" id="usuarioError"></span>
                    </div>
 
                    <div class="form-group">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Contraseña"
                            required autocomplete="current-password">
                        <span class="error-field" id="passwordError"></span>
                    </div>
 
                    <!-- Widget de RECAPTCHA -->
                    <div style="display: flex; justify-content: center; margin: 25px 0;">
                        <?php
                        require_once 'config/recaptcha.php';
                        echo renderRecaptcha();
                        ?>
                    </div>
 
                    <button type="submit" class="btn-submit">Ingresar</button>
 
                    <a href="views/recuperar-password.php" class="link-recuperar">
                        Recuperar contraseña
                    </a>
                </form>
            </div>
        </div>
 
        <div class="image-box">
        </div>
    </div>
 
    <script src="assets/js/login.js"></script>
</body>
 
</html>