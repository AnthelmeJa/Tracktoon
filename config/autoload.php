<?php
// Composer (Twig, Dotenv, etc.)
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Charger .env UNIQUEMENT s'il existe (dev local / Docker local)
$envDir = __DIR__ . '/..';

if (file_exists($envDir . '/.env')) {
    $dotenv = Dotenv::createImmutable($envDir);
    // safeLoad() = ne lève pas d'exception si des variables manquent
    $dotenv->safeLoad();
}

// MODELS
require_once __DIR__ . '/../models/Users.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/Scores.php';
require_once __DIR__ . '/../models/Library.php';
require_once __DIR__ . '/../models/Gender.php';

// MANAGERS
require_once __DIR__ . '/../managers/AbstractManager.php';
require_once __DIR__ . '/../managers/UsersManager.php';
require_once __DIR__ . '/../managers/BooksManager.php';
require_once __DIR__ . '/../managers/ScoresManager.php';
require_once __DIR__ . '/../managers/LibraryManager.php';
require_once __DIR__ . '/../managers/GendersManager.php';

// SERVICES
require_once __DIR__ . '/../services/CSRFTokenManager.php';
require_once __DIR__ . '/../services/Routeur.php';

// CONTROLLERS
require_once __DIR__ . '/../controllers/UsersController.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../controllers/HomeController.php';
require_once __DIR__ . '/../controllers/LibraryController.php';
require_once __DIR__ . '/../controllers/BookController.php';