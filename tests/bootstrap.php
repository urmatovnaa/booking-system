<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Загружаем переменные окружения для тестов
if (file_exists(dirname(__DIR__).'/.env.test')) {
    (new Dotenv())->loadEnv(dirname(__DIR__).'/.env.test');
}

// Устанавливаем APP_ENV
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';

// Для SQLite in-memory устанавливаем DATABASE_URL
if (!isset($_ENV['DATABASE_URL'])) {
    $_ENV['DATABASE_URL'] = 'sqlite:///:memory:';
    $_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';
}