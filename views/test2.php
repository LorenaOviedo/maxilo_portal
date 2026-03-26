<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Dashboard.php';

session_start();

$page_title = 'Dashboard';
$db         = getDB();
$dashboard  = new Dashboard($db);
$datos      = $dashboard->resumen();

echo "datos OK<br>";

echo "11. incluyendo header<br>";
include __DIR__ . '/../includes/header.php';

echo "12. incluyendo sidebar<br>";
include __DIR__ . '/../includes/sidebar.php';

echo "13. incluyendo footer<br>";
include __DIR__ . '/../includes/footer.php';

echo "Todo OK";