<?php
class BookController
{
    public function __construct(
        private \Twig\Environment $twig,
        private BooksManager $books,
        private ScoresManager $scores,
        private ?LibraryManager $library = null,
        private ?CSRFTokenManager $csrf = null
    ) {}

    private function requireUser(): void
    {
        if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'user') {
            $_SESSION['error'] = "Accès réservé aux utilisateurs.";
            header('Location:?route=home'); exit;
        }
    }

public function show(int $id): void
{
    $book = $this->books->findOne($id);
    if (!$book) {
        echo $this->twig->render('pages/book_not_found.html.twig', ['id' => $id]);
        return;
    }

    $stats  = $this->scores->getBookScores($id);
    $logged = isset($_SESSION['user_id']);
    $role   = $_SESSION['role'] ?? null;

    if ($logged && $role === 'user' && $this->library) {
        $userId  = (int)$_SESSION['user_id'];
        $entry   = $this->library->findOne($userId, $id);
        $myScore = $this->scores->getUserScore($userId, $id);
        $counts = $this->library->getCounts($userId);

        echo $this->twig->render('pages/book_user.html.twig', [
            'book'          => $book,
            'stats'         => $stats,
            'entry'         => $entry,
            'my_score'      => $myScore,
            'csrf_token'    => $this->csrf?->getToken(),
            'error'         => $_SESSION['error']   ?? null,
            'success'       => $_SESSION['success'] ?? null,
            'active_filter' => $_GET['filter'] ?? 'all',
            'library_counts'  => $counts,
        ]);
        unset($_SESSION['error'], $_SESSION['success']);
        return;
    }

    echo $this->twig->render('pages/book.html.twig', [
        'book'       => $book,
        'stats'      => $stats,
        'logged'     => $logged,
        'error'      => $_SESSION['error']   ?? null,
        'success'    => $_SESSION['success'] ?? null,
        'csrf_token' => $this->csrf?->getToken(),
    ]);
    unset($_SESSION['error'], $_SESSION['success']);
}

    public function rate(): void
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
        $score  = (int)($_POST['score']   ?? 0);

        if ($bookId <= 0 || !$this->books->findOne($bookId)) {
            $_SESSION['error'] = 'Livre introuvable.'; header('Location:?route=home'); exit;
        }
        if ($score < 1 || $score > 5) {
            $_SESSION['error'] = 'La note doit être entre 1 et 5.'; header('Location:?route=book&id='.$bookId); exit;
        }

        if ($this->library && !$this->library->findOne($userId, $bookId)) {
            $_SESSION['error'] = 'Ajoute d’abord ce livre à ta bibliothèque pour le noter.';
            header('Location:?route=book&id='.$bookId); exit;
        }

        try {
            $s = new Scores($userId, $bookId, $score);
            $ok = $this->scores->createOrUpdateScore($s);
            if ($ok) {
                $_SESSION['success'] = 'Note enregistrée ✅';
            } else {
                $_SESSION['error'] = 'Impossible d’enregistrer la note.';
            }
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Entrées invalides pour la note.';
        }

        header('Location:?route=book&id='.$bookId); exit;
    }
}