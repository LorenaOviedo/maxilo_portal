<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

$db   = getDB();
$user = new User($db);
$resultado = $user->findByUsernameOrEmail('admin');

var_dump($resultado);