<?php

class Router
{
    // Déclare TOUTES les propriétés utilisées
    private \Twig\Environment $twig;
    private UserController $uc;
    private AdminController $ac;

    // Si tu veux les réutiliser, déclare aussi les services
    private UsersManager $users;
    private CSRFTokenManager $csrf;
    private BooksManager $books;

    public function __construct()
    {
        $loader     = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        $this->twig = new \Twig\Environment($loader, ['autoescape' => 'html']);

        // Instancie et stocke
        $this->users = new UsersManager();
        $this->csrf  = new CSRFTokenManager();
        $this->books = new BooksManager();

        // Passe les dépendances aux contrôleurs
        $this->uc = new UserController($this->twig, $this->users, $this->csrf);
        $this->ac = new AdminController($this->twig, $this->users, $this->books, $this->csrf);
    }

    public function handleRequest(array $get): void
    {
        $route = $get['route'] ?? 'home';

        if ($route === 'home') {
            echo $this->twig->render('pages/home.html.twig', [
                'logged' => isset($_SESSION['user_id']),
            ]);
        }
        else if ($route === 'login') {
            $this->uc->login();
        }
        else if ($route === 'check-login') {
            $this->uc->checkLogin();
        }
        else if ($route === 'register') {
            $this->uc->register();
        }
        else if ($route === 'check-register') {
            $this->uc->checkRegister();
        }
        else if ($route === 'logout') {
            $this->uc->logout();
        }
        else if ($route === 'admin') {
            $this->ac->admin();
        }
        else if ($route === 'admin-add-book') {
            $this->ac->addBook();
        }
        else if ($route === 'admin-update-user') {
            $this->ac->updateUser();
        }
        else if ($route === 'admin-delete-user') {
            $this->ac->deleteUser();
        }
        else if ($route === 'admin-find-user') {
            $this->ac->findUser();
        }
        else {
            echo $this->twig->render('pages/home.html.twig', [
                'logged' => isset($_SESSION['user_id']),
            ]);
        }
    }
}