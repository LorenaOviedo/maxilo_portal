<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Dashboard.php';

$db   = getDB();
$dash = new Dashboard($db);

try {
    echo "resumen...<br>";
    $datos = $dash->resumen();
    echo "<pre>";
    print_r($datos);
    echo "</pre>";
} catch (Exception $e) {
    echo "<b>ERROR:</b> " . $e->getMessage();
} catch (Error $e) {
    echo "<b>FATAL:</b> " . $e->getMessage();
}