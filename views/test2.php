<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "1. config<br>";
require_once __DIR__ . '/../config/config.php';

echo "2. database<br>";
require_once __DIR__ . '/../config/database.php';

echo "3. AuthController<br>";
require_once __DIR__ . '/../controllers/AuthController.php';

echo "4. auth.php<br>";
require_once __DIR__ . '/../config/auth.php';

echo "5. session_start<br>";
session_start();

echo "6. AuthController instancia<br>";
$auth = new AuthController();

echo "7. verificarSesion<br>";
var_dump($auth->verificarSesion());

echo "8. header.php existe: ";
var_dump(file_exists(__DIR__ . '/../includes/header.php'));

echo "9. sidebar.php existe: ";
var_dump(file_exists(__DIR__ . '/../includes/sidebar.php'));

echo "10. footer.php existe: ";
var_dump(file_exists(__DIR__ . '/../includes/footer.php'));