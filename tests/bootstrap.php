<?php
// tests/bootstrap.php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// Загружаем .env файл если существует
if (file_exists(dirname(__DIR__) . '/.env.test')) {
    (new Dotenv())->load(dirname(__DIR__) . '/.env.test');
} elseif (file_exists(dirname(__DIR__) . '/.env')) {
    (new Dotenv())->load(dirname(__DIR__) . '/.env');
}

// Убеждаемся, что APP_ENV установлен в test
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';

// Для отладки
if ($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? false) {
    umask(0000);
}