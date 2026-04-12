<?php
// Esto ejecuta un comando de terminal desde PHP
$output = shell_exec('php composer.phar install 2>&1');
echo "<pre>$output</pre>";
?>