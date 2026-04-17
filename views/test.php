<?php
echo 'PHP funciona';
require_once __DIR__ . '/../config/config.php';
echo 'Config OK';
require_once __DIR__ . '/../config/database.php';
echo 'DB OK';
require_once __DIR__ . '/../config/mailer.php';
echo 'Mailer OK';