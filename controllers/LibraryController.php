<?php

class LibraryController
{
    public function __construct(
        private \Twig\Environment $twig,
        private LibraryManager $library,
        private BooksManager $books,
        private ?CSRFTokenManager $csrf = null,
        private ?ScoresManager $scores = null
    ) {}

    private function requireUser(): void
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = null;
            header('Location:?route=login');
            exit;
        }
    }

    public function library(): void
    {
        $this->requireUser();

        $userId = (int)$_SESSION['user_id'];

        $filter = $_GET['filter'] ?? 'all';
        $statut = null; $favori = null;
        switch ($filter) {
            case 'a_lire':   $statut = 'a_lire';   break;
            case 'en_cours': $statut = 'en_cours'; break;
            case 'termine':  $statut = 'termine';  break;
            case 'favoris':  $favori = true;       break;
            default:         $filter = 'all';
        }

        $entries = $this->library->findAll($userId, $statut, $favori);

        $items = [];
        foreach ($entries as $lib) {
            $book = $this->books->findOne($lib->getBookId());
            if ($book) {
                $stats = $this->scores?->getBookScores($lib->getBookId()) ?? ['avg'=>null,'votes'=>0];
                $items[] = [
                    'book'    => $book,
                    'statut'  => $lib->getStatut(),
                    'favori'  => $lib->isFavori(),
                    'comment' => $lib->getComment(),
                    'avg'     => $stats['avg'] ?? null,
                    'votes'   => $stats['votes'] ?? 0,
                ];
            }
        }

        $counts = $this->library->getCounts($userId);

        echo $this->twig->render('pages/library.html.twig', [
            'items'          => $items,
            'active_filter'  => $filter,
            'library_counts' => $counts,
            'error'          => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }


    public function add(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location:?route=login'); exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = "Requête invalide.";
            header('Location:?route=home'); exit;
        }

        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = "CSRF invalide.";
            header('Location:?route=home'); exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $bookId = (int)($_POST['book_id'] ?? 0);

        if ($bookId <= 0 || !$this->books->findOne($bookId)) {
            $_SESSION['error'] = "Livre introuvable.";
            header('Location:?route=home'); exit;
        }

        $entry = new Library($userId, $bookId, 'a_lire', false, '');

        if ($this->library->createOrUpdate($entry)) {
            $_SESSION['success'] = "Ajouté à ta bibliothèque ✅";
        } else {
            $_SESSION['error'] = "Impossible d'ajouter ce livre.";
        }

        header('Location:?route=book&id=' . $bookId); exit;
    }

    public function saveFromBook(): void
    {
        $this->requireUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Requête invalide.'; 
            header('Location:?route=home'); 
            exit;
        }

        if (!$this->csrf || !$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'CSRF invalide.'; 
            header('Location:?route=home'); 
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $bookId = (int)($_POST['book_id'] ?? 0);
        if ($bookId <= 0) {
            $_SESSION['error'] = 'Livre invalide.'; 
            header('Location:?route=home'); 
            exit;
        }

        $statut  = $_POST['statut']  ?? 'a_lire';
        $favori  = isset($_POST['favori']) && $_POST['favori'] === '1';
        $comment = trim($_POST['comment'] ?? '');

        try {
            $entry = new Library($userId, $bookId, $statut, $favori, $comment);
            $ok = $this->library->createOrUpdate($entry);
            $_SESSION['success'] = $ok ? 'Préférences enregistrées.' : 'Échec de l’enregistrement.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Entrées invalides.';
        }

        header('Location:?route=book&id=' . $bookId); 
        exit;
    }

    public function remove(): void
    {
        $this->requireUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Requête invalide.'; header('Location:?route=home'); exit;
        }
        if (!$this->csrf || !$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'CSRF invalide.'; header('Location:?route=home'); exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $bookId = (int)($_POST['book_id'] ?? 0);
        if ($bookId <= 0) { $_SESSION['error'] = 'Livre invalide.'; header('Location:?route=home'); exit; }

        $ok = $this->library->deleteByIds($userId, $bookId);
        if ($ok) {
            $_SESSION['success'] = 'Œuvre retirée de ta bibliothèque.';
        } else {
            $_SESSION['error'] = 'Impossible de retirer cette œuvre.';
        }

        $filter = $_POST['filter'] ?? null;
        $dest = '?route=library' . ($filter ? '&filter=' . urlencode($filter) : '');
        header('Location:' . $dest); exit;
    }
}