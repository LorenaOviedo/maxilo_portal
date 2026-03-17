<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

try {
    // Verificar tabla Usuario
    $stmt = $db->query("SELECT id_usuario, usuario, id_rol, id_estatus FROM Usuario LIMIT 5");
    $usuarios = $stmt->fetchAll();
    echo "Usuarios encontrados: " . count($usuarios) . "<br>";
    var_dump($usuarios);

} catch (PDOException $e) {
    echo "ERROR en Usuario: " . $e->getMessage() . "<br>";
}

try {
    // Verificar tabla Rol
    $stmt2 = $db->query("SELECT * FROM Rol");
    $roles = $stmt2->fetchAll();
    echo "Roles encontrados: " . count($roles) . "<br>";
    var_dump($roles);

} catch (PDOException $e) {
    echo "ERROR en Rol: " . $e->getMessage() . "<br>";
}