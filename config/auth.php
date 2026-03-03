<?php
if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . url('index.php'));
    exit();
}

if (isset($_SESSION['ultimo_acceso'])) {
    if (time() - $_SESSION['ultimo_acceso'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header("Location: " . url('index.php') . "?expired=1");
        exit();
    }
}

$_SESSION['ultimo_acceso'] = time();