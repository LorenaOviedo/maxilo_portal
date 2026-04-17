<?php
$base = __DIR__ . '/../vendor/phpmailer/';
echo 'Ruta: ' . $base . '<br>';
echo 'Existe carpeta: ' . (is_dir($base) ? 'SI' : 'NO') . '<br>';
echo 'Archivos: <br>';
foreach (glob($base . '*') as $f) {
    echo basename($f) . ' — ' . filesize($f) . ' bytes<br>';
}
?>