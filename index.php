<?php
session_start();

// Autoload + Dotenv + require des classes
require __DIR__ . '/config/autoload.php';

$router = new Router();

$router->handleRequest($_GET);