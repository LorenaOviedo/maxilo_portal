<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

$db = getDB();
echo "1. Conexión OK <br>";

$user = new User($db);
echo "2. Modelo cargado OK <br>";

// Verificar que la tabla Usuario existe y tiene datos
$stmt = $db->query("SELECT id_usuario, usuario, id_rol, id_estatus FROM Usuario LIMIT 5");
$usuarios = $stmt->fetchAll();
echo "3. Usuarios en BD: <br>";
var_dump($usuarios);

// Verificar que la tabla Rol existe y tiene datos
$stmt2 = $db->query("SELECT * FROM Rol");
$roles = $stmt2->fetchAll();
echo "4. Roles en BD: <br>";
var_dump($roles);