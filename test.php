<?php
session_start();
echo '<pre>';
echo 'SESSION rol: ' . ($_SESSION['rol'] ?? 'NO DEFINIDO') . "\n";
echo 'SESSION id_rol: ' . ($_SESSION['id_rol'] ?? 'NO DEFINIDO') . "\n";
echo 'SESSION usuario_id: ' . ($_SESSION['usuario_id'] ?? 'NO DEFINIDO') . "\n";
echo 'SESSION modulos_nombres: ';
print_r($_SESSION['modulos_nombres'] ?? 'NO DEFINIDO');
echo "\n";
echo 'SESSION modulos: ';
print_r($_SESSION['modulos'] ?? 'NO DEFINIDO');
echo "\n";
echo 'SESSION completa: ';
print_r($_SESSION);
echo '</pre>';
 