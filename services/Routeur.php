<?php

class Router
{
    private \Twig\Environment $twig;
    private UserController $uc;

    public function __construct()
    {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        $this->twig = new \Twig\Environment($loader, ['autoescape' => 'html']);

        $users = new UsersManager();
        $csrf  = new CSRFTokenManager();

        $this->uc = new UserController($this->twig, $users, $csrf);
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
        else {
            echo $this->twig->render('pages/home.html.twig', [
                'logged' => isset($_SESSION['user_id']),
            ]);
        }
    }
}