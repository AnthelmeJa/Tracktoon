<?php

class AdminController
{
    public function __construct(
        private \Twig\Environment $twig,
        private UsersManager $users,
        private BooksManager $books,
        private CSRFTokenManager $csrf
    ) {}

    private function requireAdmin(): void {
        if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? 'user', ['admin','super_admin'], true)) {
            $_SESSION['error'] = "Accès refusé.";
            header('Location: ?route=home'); exit;
        }
    }

    public function admin(): void
    {
        $this->requireAdmin();

        echo $this->twig->render('pages/admin.html.twig', [
            'csrf_token'  => $this->csrf->getToken(),
            'error'       => $_SESSION['error']  ?? null,
            'success'     => $_SESSION['success']?? null,
            'found_user'  => $_SESSION['found_user'] ?? null,
            'found_book'  => $_SESSION['found_book'] ?? null,
        ]);
        unset($_SESSION['error'], $_SESSION['success'], $_SESSION['found_user'], $_SESSION['found_book']);
    }

    public function addBook(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Requête invalide.'; header('Location:?route=admin'); exit;
        }

        $title = trim($_POST['title'] ?? '');
        $type  = trim($_POST['type']  ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $imageRaw = trim($_POST['image'] ?? '');
        $image = ($imageRaw !== '' && filter_var($imageRaw, FILTER_VALIDATE_URL)) ? $imageRaw : null;
        $chap  = trim($_POST['chapter'] ?? '');
        $chapter = ($chap === '' ? null : (int)$chap);
        $genders = array_values(array_filter(
            array_unique(
                array_map(fn($g) => mb_strtolower(trim($g)), explode(',', $_POST['genders'] ?? ''))
            ),
            fn($g) => $g !== ''
        ));

        try {
            $book = new Book($title, $type, $desc, $image, $chapter, $genders);
            $this->books->create($book);
            $_SESSION['success'] = 'Livre ajouté.';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Entrées invalides ou type/chapitre incorrect.';
        }
        header('Location:?route=admin'); exit;
    }

    public function findBook(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Requête invalide.'; header('Location:?route=admin'); exit;
        }

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $_SESSION['error'] = 'Titre manquant.'; header('Location:?route=admin'); exit;
        }

        $book = $this->books->findOneByTitleExact($title);
        if ($book) {
            $_SESSION['found_book'] = [
                'id'      => $book->getId(),
                'title'   => $book->getTitle(),
                'type'    => $book->getType(),
                'chapter' => $book->getChapter(),
                'genders' => $book->getGenders(),
            ];
            $_SESSION['success'] = 'Livre trouvé.';
        } else {
            $_SESSION['error'] = 'Aucun livre avec ce titre.';
            unset($_SESSION['found_book']);
        }

        header('Location:?route=admin'); exit;
    }

    public function updateBook(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Requête invalide.'; header('Location:?route=admin'); exit;
        }

        $id    = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $type  = trim($_POST['type']  ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $imageRaw = trim($_POST['image'] ?? '');
        $image = ($imageRaw !== '' && filter_var($imageRaw, FILTER_VALIDATE_URL)) ? $imageRaw : null;
        $chap  = trim($_POST['chapter'] ?? '');
        $chapter = ($chap === '' ? null : (int)$chap);
        $genders = array_values(array_filter(
            array_unique(
                array_map(fn($g) => mb_strtolower(trim($g)), explode(',', $_POST['genders'] ?? ''))
            ),
            fn($g) => $g !== ''
        ));

        if ($id <= 0) { $_SESSION['error'] = 'ID book invalide.'; header('Location:?route=admin'); exit; }

        try {
            // (optionnel) vérifier que le book existe
            $existing = $this->books->findOne($id);
            if (!$existing) { $_SESSION['error'] = 'Livre introuvable.'; header('Location:?route=admin'); exit; }

            $book = new Book($title ?: $existing->getTitle(),
                            $type  ?: $existing->getType(),
                            $desc  ?: $existing->getDescription(),
                            $image !== null ? $image : $existing->getImage(),
                            $chapter !== null ? $chapter : $existing->getChapter(),
                            $genders ?: $existing->getGenders());
            $book->setId($id);

            if ($this->books->update($book)) {
                $_SESSION['success'] = 'Livre mis à jour.';
            } else {
                $_SESSION['error'] = 'Échec mise à jour.';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Entrées invalides.';
        }
        header('Location:?route=admin'); exit;
    }

    public function deleteBook(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Requête invalide.'; header('Location:?route=admin'); exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { $_SESSION['error'] = 'ID book invalide.'; header('Location:?route=admin'); exit; }

        try {
            if ($this->books->deleteById($id)) {
                $_SESSION['success'] = 'Livre supprimé.';
            } else {
                $_SESSION['error'] = 'Échec suppression.';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Suppression impossible.';
        }
        header('Location:?route=admin'); exit;
    }
    
    public function updateUser(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Requête invalide.'; header('Location:?route=admin'); exit;
        }

        $id     = (int)($_POST['id'] ?? 0);
        $pseudo = trim($_POST['pseudo'] ?? '');
        $mail   = strtolower(trim($_POST['mail'] ?? ''));
        $role   = trim($_POST['role'] ?? 'user');
        $pass   = $_POST['password'] ?? ''; // vide => ne pas changer

        try {
            $current = $this->users->findOne($id);
            if (!$current) { $_SESSION['error'] = 'Utilisateur introuvable.'; header('Location:?route=admin'); exit; }

            // Reconstruire un Users
            $user = new Users($pseudo ?: $current->getPseudo(),
                              $mail   ?: $current->getMail(),
                              $pass   ?: $current->getPassword(), // setPassword gère $hashed=false, donc:
                              $role);
            $user->setId($id);

            // si pas de changement de mdp, on réinjecte tel quel (hash déjà en base)
            if ($pass === '') {
                $user->setPassword($current->getPassword(), true); // true => déjà hashé
            }

            if ($this->users->update($user)) {
                $_SESSION['success'] = 'Utilisateur mis à jour.';
            } else {
                $_SESSION['error'] = 'Échec mise à jour.';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Entrées invalides.';
        }
        header('Location:?route=admin'); exit;
    }

    public function deleteUser(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Requête invalide.'; header('Location:?route=admin'); exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $u = $this->users->findOne($id);
        if (!$u) { $_SESSION['error'] = 'Utilisateur introuvable.'; header('Location:?route=admin'); exit; }

        $this->users->delete($u);
        $_SESSION['success'] = 'Utilisateur supprimé.';
        header('Location:?route=admin'); exit;
    }

    public function findUser(): void
    {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Requête invalide.'; header('Location:?route=admin'); exit;
        }

        $mail = strtolower(trim($_POST['mail'] ?? ''));
        $u = $this->users->findByMail($mail);
        if ($u) {
            $_SESSION['found_user'] = [
                'id' => $u->getId(),
                'pseudo' => $u->getPseudo(),
                'mail' => $u->getMail(),
                'role' => $u->getRole(),
            ];
        } else {
            $_SESSION['error'] = 'Aucun utilisateur avec cet email.';
        }
        header('Location:?route=admin'); exit;
    }
}