<?php
ob_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
 
$token       = trim($_GET['token'] ?? '');
$mensaje     = '';
$exito       = false;
$tokenValido = false;
$idUsuario   = null;
$idToken     = null;
 
if (empty($token)) {
    $mensaje = 'Enlace inválido o expirado.';
} else {
    $db        = getDB();
    $tokenHash = hash('sha256', $token);
 
    $stmt = $db->prepare("
        SELECT id_token, id_usuario, fecha_expiracion, usado
        FROM tokenrecuperacion
        WHERE token_recuperacion = :token
        LIMIT 1
    ");
    $stmt->execute([':token' => $tokenHash]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if (!$registro) {
        $mensaje = 'El enlace no es válido.';
    } elseif ($registro['usado']) {
        $mensaje = 'Este enlace ya fue utilizado. Solicita uno nuevo.';
    } elseif (strtotime($registro['fecha_expiracion']) < time()) {
        $mensaje = 'El enlace ha expirado. Solicita uno nuevo.';
    } else {
        $tokenValido = true;
        $idUsuario   = $registro['id_usuario'];
        $idToken     = $registro['id_token'];
    }
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValido) {
    $nueva     = $_POST['nueva']     ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
 
    if (strlen($nueva) < 8) {
        $mensaje = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($nueva !== $confirmar) {
        $mensaje = 'Las contraseñas no coinciden.';
    } else {
        $db = getDB();
 
        $db->prepare(
            "UPDATE usuario SET contrasena = :hash WHERE id_usuario = :id"
        )->execute([
            ':hash' => password_hash($nueva, PASSWORD_BCRYPT),
            ':id'   => $idUsuario,
        ]);
 
        $db->prepare(
            "UPDATE tokenrecuperacion SET usado = 1 WHERE id_token = :id"
        )->execute([':id' => $idToken]);
 
        $exito       = true;
        $tokenValido = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña — Sistema Maxilofacial Texcoco</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <style>
        .back-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: #008B8B;
            font-size: 14px;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .back-link:hover { color: #006666; text-decoration: underline; }
        .success-box {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-left: 4px solid #28a745;
            border-radius: 4px;
            padding: 14px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .error-box {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-left: 4px solid #dc3545;
            border-radius: 4px;
            padding: 14px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
 
    <div class="container">
        <div class="login-box">
            <div class="logo-container">
                <img src="../assets/img/logo_maxilo.png" alt="Maxilofacial Texcoco Logo" class="logo">
            </div>
 
            <h1 class="welcome-title">RESTABLECER</h1>
 
            <div class="form-container">
 
                <?php if ($exito): ?>
                    <div class="success-box">
                        ¡Tu contraseña fue restablecida correctamente!
                        Ya puedes iniciar sesión con tu nueva contraseña.
                    </div>
                    <a href="../index.php" class="btn-submit"
                        style="display:block;text-align:center;text-decoration:none;margin-top:8px;">
                        Iniciar sesión
                    </a>
 
                <?php elseif (!$tokenValido): ?>
                    <div class="error-box">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                    <a href="recuperar-password.php" class="back-link">
                        Solicitar nuevo enlace
                    </a>
 
                <?php else: ?>
                    <?php if ($mensaje): ?>
                        <div class="error-message"><?php echo htmlspecialchars($mensaje); ?></div>
                    <?php endif; ?>
 
                    <form method="POST" action="">
                        <div class="form-group">
                            <input type="password" name="nueva" class="form-input"
                                placeholder="Nueva contraseña (mínimo 8 caracteres)"
                                required minlength="8" autocomplete="new-password">
                        </div>
 
                        <div class="form-group">
                            <input type="password" name="confirmar" class="form-input"
                                placeholder="Confirmar nueva contraseña"
                                required minlength="8" autocomplete="new-password">
                        </div>
 
                        <button type="submit" class="btn-submit">
                            Guardar nueva contraseña
                        </button>
                    </form>
 
                    <a href="../index.php" class="back-link">← Volver al inicio de sesión</a>
                <?php endif; ?>
            </div>
        </div>
 
        <div class="image-box"></div>
    </div>
 
</body>
</html>
 