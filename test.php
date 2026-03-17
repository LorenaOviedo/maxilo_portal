<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

try {
    $token    = hash('sha256', bin2hex(random_bytes(32)));
    $ip       = '127.0.0.1';
    $agent    = 'test-browser';
    $usuario  = 1; // el id_usuario del admin que existe en tu tabla usuario

    $stmt = $db->prepare("
        INSERT INTO sesion (id_usuario, token_sesion, direccion_ip, user_agent)
        VALUES (:id_usuario, :token_sesion, :direccion_ip, :user_agent)
    ");

    $stmt->bindParam(':id_usuario',   $usuario, PDO::PARAM_INT);
    $stmt->bindParam(':token_sesion', $token,   PDO::PARAM_STR);
    $stmt->bindParam(':direccion_ip', $ip,      PDO::PARAM_STR);
    $stmt->bindParam(':user_agent',   $agent,   PDO::PARAM_STR);

    $stmt->execute();
    echo "Sesión insertada correctamente. ID: " . $db->lastInsertId();

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}