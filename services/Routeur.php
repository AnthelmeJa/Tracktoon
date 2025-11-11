<?php

class Router
{
    private \Twig\Environment $twig;
    private UserController $uc;
    private AdminController $ac;
    private HomeController $hc;
    private LibraryController $lc;
    private BookController $bc;

    private UsersManager $users;
    private CSRFTokenManager $csrf;
    private BooksManager $books;
    private LibraryManager $library;

    public function __construct()
    {
        $loader     = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        $this->twig = new \Twig\Environment($loader, ['autoescape' => 'html']);

        
        $this->twig->addGlobal('logged', isset($_SESSION['user_id']));
        $this->twig->addGlobal('role', $_SESSION['role'] ?? null);
        $this->twig->addGlobal('session', $_SESSION);

        $this->users   = new UsersManager();
        $this->csrf    = new CSRFTokenManager();
        $this->books   = new BooksManager();
        $this->library = new LibraryManager();

        $this->uc = new UserController($this->twig, $this->users, $this->csrf);
        $this->ac = new AdminController($this->twig, $this->users, $this->books, $this->csrf);
        $this->hc = new HomeController($this->twig, $this->books, $this->csrf);
        $this->lc = new LibraryController($this->twig, $this->library, $this->books, $this->csrf, new ScoresManager());
        $this->bc = new BookController($this->twig, $this->books, new ScoresManager(), $this->library, $this->csrf);
    }

    public function handleRequest(array $get): void
    {
        $route = $get['route'] ?? 'home';

        if ($route === 'home') {
            $this->hc->home($get);
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
        else if ($route === 'contact') {
            $this->hc->contact();
        }
        else if ($route === 'contact-send') {
            $this->hc->contactSend();
        }
        else if ($route === 'library') {
            $this->lc->library();
        }
        else if ($route === 'library-add') {
            $this->lc->add();
        }
        else if ($route === 'library-save') {
            $this->lc->saveFromBook();
        }
        else if ($route === 'library-remove') {
            $this->lc->remove();
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
        else if ($route === 'admin-find-book') {
            $this->ac->findBook();
        }
        else if ($route === 'admin-update-book') {
            $this->ac->updateBook();
        }
        else if ($route === 'admin-delete-book') {
            $this->ac->deleteBook();
        }
        else if ($route === 'book') {
            if (!empty($get['id'])) {
                $this->bc->show((int)$get['id']);
            } else {
                echo $this->twig->render('pages/book_not_found.html.twig', ['id' => null]);
            }
        }
        else if ($route === 'book-rate') {
            $this->bc->rate();
        }
        else if ($route === 'faq') {
            $this->hc->faq();
        }
        else if ($route === 'legal') {
            $this->hc->legal();
        }
        else {
            $this->hc->home($get);
        }
    }
}