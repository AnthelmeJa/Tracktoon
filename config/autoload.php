<?php
// Composer (Twig, Dotenv, etc.)
require_once __DIR__ . '/../vendor/autoload.php';

// MODELS
require_once __DIR__ . '/../models/Users.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/Scores.php';
require_once __DIR__ . '/../models/Library.php';

// MANAGERS
require_once __DIR__ . '/../managers/AbstractManager.php';
require_once __DIR__ . '/../managers/UsersManager.php';
require_once __DIR__ . '/../managers/BooksManager.php';
require_once __DIR__ . '/../managers/ScoresManager.php';
require_once __DIR__ . '/../managers/LibraryManager.php';

// SERVICES
require_once __DIR__ . '/../services/CSRFTokenManager.php';
require_once __DIR__ . '/../services/Routeur.php';

// CONTROLLERS
require_once __DIR__ . '/../controllers/UsersController.php';