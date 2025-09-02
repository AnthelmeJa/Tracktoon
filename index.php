<?php
require __DIR__ . '/config/autoload.php';

session_start();

// .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$router = new Router();
$router->handleRequest($_GET);