<?php

class UserController
{
    public function __construct(
        private \Twig\Environment $twig,
        private UsersManager $users,
        private CSRFTokenManager $csrf
    ) {}

    public function login(): void
    {
        echo $this->twig->render('pages/login.html.twig', [
            'csrf_token' => $this->csrf->getToken(),
            'error'      => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    public function checkLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Requête invalide';
            $this->redirect('?route=login'); return;
        }
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'CSRF invalide';
            $this->redirect('?route=login'); return;
        }

        $mail = strtolower(trim($_POST['mail'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($mail === '' || $password === '') {
            $_SESSION['error'] = 'Champs manquants';
            $this->redirect('?route=login'); return;
        }

        $user = $this->users->findByMail($mail);

        if ($user && $user->verifyPassword($password)) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user->getId();
            $_SESSION['role']    = $user->getRole();

            if (in_array($_SESSION['role'], ['admin','super_admin'], true)) {
                $this->redirect('?route=admin'); return;
            }
            $this->redirect('?route=home'); return;
        }

        $_SESSION['error'] = 'Identifiants invalides';
        $this->redirect('?route=login');
    }

    public function register(): void
    {
        echo $this->twig->render('pages/register.html.twig', [
            'csrf_token' => $this->csrf->getToken(),
            'error'      => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    public function checkRegister(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Requête invalide';
            $this->redirect('?route=register'); return;
        }
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'CSRF invalide';
            $this->redirect('?route=register'); return;
        }

        $pseudo    = trim($_POST['pseudo'] ?? '');
        $mail      = strtolower(trim($_POST['mail'] ?? ''));
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';

        if ($pseudo === '' || $mail === '' || $password === '' || $confirm === '') {
            $_SESSION['error'] = 'Champs manquants';
            $this->redirect('?route=register'); return;
        }
        if ($password !== $confirm) {
            $_SESSION['error'] = 'Les mots de passe ne correspondent pas';
            $this->redirect('?route=register'); return;
        }
        if ($this->users->findByMail($mail)) {
            $_SESSION['error'] = 'Utilisateur déjà existant';
            $this->redirect('?route=register'); return;
        }

        try {
            $user = new Users($pseudo, $mail, $password, 'user');
            $created = $this->users->create($user);
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Entrées invalides';
            $this->redirect('?route=register'); return;
        }

        if ($created) {
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['role']    = $user->getRole();
            $this->redirect('?route=home'); return;
        }

        $_SESSION['error'] = 'Erreur à la création';
        $this->redirect('?route=register');
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirect('?route=home');
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}