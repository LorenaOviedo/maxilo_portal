<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "1. config OK<br>";
require_once __DIR__ . '/../config/config.php';

echo "2. database OK<br>";
require_once __DIR__ . '/../config/database.php';

echo "3. AuthController OK<br>";
require_once __DIR__ . '/../controllers/AuthController.php';

echo "4. auth OK<br>";
require_once __DIR__ . '/../config/auth.php';

echo "5. Dashboard model OK<br>";
require_once __DIR__ . '/../models/Dashboard.php';

echo "6. getDB OK<br>";
$db = getDB();

echo "7. instancia Dashboard OK<br>";
$dashboard = new Dashboard($db);

echo "8. resumen OK<br>";
$datos = $dashboard->resumen();

echo "<pre>";
print_r($datos);
echo "</pre>";

echo "Todo bien!";