<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "1. config<br>";
require_once __DIR__ . '/../config/config.php';

echo "2. database<br>";
require_once __DIR__ . '/../config/database.php';

echo "3. Dashboard.php existe: ";
var_dump(file_exists(__DIR__ . '/../models/Dashboard.php'));

echo "4. cargando modelo<br>";
require_once __DIR__ . '/../models/Dashboard.php';

echo "5. conectando BD<br>";
$db = getDB();

echo "6. instanciando<br>";
$dash = new Dashboard($db);

echo "7. resumen<br>";
$datos = $dash->resumen();

echo "<pre>";
print_r($datos);
echo "</pre>";