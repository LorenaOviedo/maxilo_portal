<?php
echo 'PHP funciona';
require_once __DIR__ . '/../config/config.php';
echo 'Config OK';
require_once __DIR__ . '/../config/database.php';
echo 'DB OK';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
echo 'PHPMailer OK';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';
echo 'SMTP OK';
require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
echo 'Exception OK';
require_once __DIR__ . '/../config/mailer.php';
echo 'Mailer OK';