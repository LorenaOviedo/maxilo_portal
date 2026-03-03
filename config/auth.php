<?php
if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . SITE_URL . "index.php");
    exit();
}

if (isset($_SESSION['ultimo_acceso'])) {
    if (time() - $_SESSION['ultimo_acceso'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header("Location: " . SITE_URL . "index.php?expired=1");
        exit();
    }
}

$_SESSION['ultimo_acceso'] = time();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");