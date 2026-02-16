<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

session_start();

// Procesar logout si se recibe el parámetro
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    require_once 'controllers/AuthController.php';
    $auth = new AuthController();
    $auth->logout();
    // Después de destruir la sesión se recarga index.php para mostrar el formulario de login
    header('Location: index.php');
    exit;
}

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: views/dashboard.php');
    exit;
}

// Procesar el login si se envió el formulario
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'controllers/AuthController.php';
    $auth = new AuthController();
    $resultado = $auth->login($_POST['usuario'], $_POST['password']);
    
    if ($resultado['success']) {
        header('Location: views/dashboard.php');
        exit;
    } else {
        $error = $resultado['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Maxilofacial Texcoco</title>
    <link rel="stylesheet" href="assets/css/login.css">
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
                        <input 
                            type="text" 
                            id="usuario" 
                            name="usuario" 
                            class="form-input" 
                            placeholder="Usuario/Correo eléctronico"
                            required
                            autocomplete="username"
                        >
                        <span class="error-field" id="usuarioError"></span>
                    </div>
                    
                    <div class="form-group">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Contraseña"
                            required
                            autocomplete="current-password"
                        >
                        <span class="error-field" id="passwordError"></span>
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