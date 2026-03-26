<?php
// Muestra la estructura real de carpetas
$base = '/home/u113135450/domains/portal-maxilofacial.site/';
foreach (new DirectoryIterator($base) as $item) {
    if ($item->isDot()) continue;
    echo ($item->isDir() ? '[DIR] ' : '[FILE] ') . $item->getFilename() . '<br>';
}

echo '<hr>';

// También muestra qué hay dentro de public_html
echo '<b>Dentro de public_html:</b><br>';
foreach (new DirectoryIterator($base . 'public_html/') as $item) {
    if ($item->isDot()) continue;
    echo ($item->isDir() ? '[DIR] ' : '[FILE] ') . $item->getFilename() . '<br>';
}