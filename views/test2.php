<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "views está en: " . __DIR__ . "<br>";
echo "¿config existe?: ";
var_dump(is_dir(__DIR__ . '/../config'));

echo "¿models existe?: ";
var_dump(is_dir(__DIR__ . '/../models'));

echo "¿controllers existe?: ";
var_dump(is_dir(__DIR__ . '/../controllers'));