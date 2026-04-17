<?php
ob_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mailer.php';
 
$mensaje = '';
$tipo    = '';
$enviado = false;
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
 
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Ingresa un correo electrónico válido.';
        $tipo    = 'error';
    } else {
        $db = getDB();
 
        $stmt = $db->prepare(
            "SELECT id_usuario, nombre_usuario, email FROM usuario WHERE email = :email LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
 
        $enviado = true;
 
        if ($usuario) {
            $db->prepare(
                "UPDATE tokenrecuperacion SET usado = 1 WHERE id_usuario = :id AND usado = 0"
            )->execute([':id' => $usuario['id_usuario']]);
 
            $tokenPlano = bin2hex(random_bytes(32));
            $tokenHash  = hash('sha256', $tokenPlano);
            $expiracion = date('Y-m-d H:i:s', strtotime('+30 minutes'));
 
            $db->prepare("
                INSERT INTO tokenrecuperacion
                    (token_recuperacion, fecha_expiracion, id_usuario)
                VALUES
                    (:token, :expiracion, :id)
            ")->execute([
                ':token'      => $tokenHash,
                ':expiracion' => $expiracion,
                ':id'         => $usuario['id_usuario'],
            ]);
 
            enviarCorreoRecuperacion(
                $usuario['email'],
                $usuario['nombre_usuario'],
                $tokenPlano
            );
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña — Sistema Maxilofacial Texcoco</title>
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
    </style>
</head>
<body>
 
    <div class="container">
        <div class="login-box">
            <div class="logo-container">
                <img src="../assets/img/logo_maxilo.png" alt="Maxilofacial Texcoco Logo" class="logo">
            </div>
 
            <h1 class="welcome-title">RECUPERAR</h1>
 
            <div class="form-container">
                <h2 class="form-title">CONTRASEÑA</h2>
 
                <?php if ($enviado): ?>
                    <div class="success-box">
                        Si el correo está registrado en el sistema, recibirás un enlace
                        para restablecer tu contraseña en los próximos minutos.
                        Revisa también tu carpeta de spam.
                    </div>
                    <a href="../index.php" class="back-link">← Volver al inicio de sesión</a>
 
                <?php else: ?>
                    <?php if ($mensaje): ?>
                        <div class="error-message"><?php echo htmlspecialchars($mensaje); ?></div>
                    <?php endif; ?>
 
                    <p style="font-size:14px;color:#6c757d;margin-bottom:20px;text-align:center;line-height:1.5;">
                        Ingresa tu correo electrónico y te enviaremos un enlace
                        para restablecer tu contraseña.
                    </p>
 
                    <form method="POST" action="">
                        <div class="form-group">
                            <input type="email" name="email" class="form-input"
                                placeholder="Correo electrónico"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required autocomplete="email">
                        </div>
 
                        <button type="submit" class="btn-submit">
                            Enviar enlace
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