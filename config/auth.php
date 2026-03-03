<?php
//session_start() es llamado en config.php

// Si no hay sesión activa, se redirige al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . url('index.php'));
    exit();
}

// Verificar expiración
if (isset($_SESSION['ultimo_acceso'])) {
    if (time() - $_SESSION['ultimo_acceso'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header("Location: " . url('index.php') . "?expired=1");
        exit();
    }
}

// Actualizar último acceso
$_SESSION['ultimo_acceso'] = time();

// Headers para evitar caché en páginas protegidas
//header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
//header("Pragma: no-cache");
//header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");