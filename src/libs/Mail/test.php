<?php

include_once __DIR__."/../../../vendor/autoload.php";
include_once __DIR__."/../helpers.php";

use Basttyy\FxDataServer\Console\Jobs\SendMail;
use Basttyy\FxDataServer\libs\Mail\BaseMailer;
use Basttyy\FxDataServer\libs\Mail\VerifyEmail;
use Dotenv\Dotenv;

$dotenv = strtolower(PHP_OS_FAMILY) === 'windows' ? Dotenv::createImmutable(__DIR__."\\..\\..\\..\\") : Dotenv::createImmutable(__DIR__.'/../../../');
$dotenv->load();
$dotenv->required(['APP_KEY', 'APP_ENV', 'DB_USER', 'DB_HOST', 'DB_NAME', 'ADMIN_APP_URI', 'USER_APP_URI', 'SERVER_APP_URI', 'FINGERPRINT_MAX_AGE', 'SECRET_TOKEN', 'SHA_TYPE'])->notEmpty();

$sendmail = new SendMail([]);

$mail = VerifyEmail::send('basttyy@gmail.com', 'Abdulbasit Mamman', 'Verify Your Email', '204837');