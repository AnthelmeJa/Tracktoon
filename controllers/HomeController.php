<?php

class HomeController
{
    public function __construct(
        private \Twig\Environment $twig,
        private BooksManager $books,
        private CSRFTokenManager $csrf
    ) {}
    

    public function home(array $get): void
    {
        $trending = $this->books->getTrending(30);

        $title      = isset($get['q']) ? trim($get['q']) : null;
        $type       = isset($get['type']) ? trim($get['type']) : null;
        $genre      = isset($get['genre']) ? trim($get['genre']) : null;
        $minChapter = isset($get['min']) && $get['min'] !== '' ? (int)$get['min'] : null;

        $hasFilters = ($title || $type || $genre || $minChapter !== null);
        $results = $hasFilters
            ? $this->books->findAllFiltered($title, $type, $genre, $minChapter, 50)
            : [];

        echo $this->twig->render('pages/home.html.twig', [
            'logged'  => isset($_SESSION['user_id']),
            'role'    => $_SESSION['role'] ?? null,
            'trending'=> $trending,
            'filters' => [
                'q'    => $title ?? '',
                'type' => $type ?? '',
                'genre'=> $genre ?? '',
                'min'  => $minChapter ?? '',
            ],
            'results' => $results,
        ]);
    }

    public function contact(): void
    {
        $prefills = [
            'name'    => $_SESSION['contact_name']  ?? '',
            'email'   => $_SESSION['contact_email'] ?? '',
            'message' => '',
        ];

        echo $this->twig->render('pages/contact.html.twig', [
            'csrf_token' => $this->csrf->getToken(),
            'error'      => $_SESSION['error']   ?? null,
            'success'    => $_SESSION['success'] ?? null,
            'prefills'   => $prefills,
        ]);
        unset($_SESSION['error'], $_SESSION['success']);
    }

    public function contactSend(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Requête invalide.'; header('Location:?route=contact'); exit;
        }
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'CSRF invalide.'; header('Location:?route=contact'); exit;
        }

        if (!empty($_POST['website'] ?? '')) {
            $_SESSION['success'] = 'Message envoyé.'; header('Location:?route=contact'); exit;
        }

        $now = time();
        if (isset($_SESSION['last_contact']) && ($now - (int)$_SESSION['last_contact']) < 60) {
            $_SESSION['error'] = "Merci d'attendre un peu avant de renvoyer un message.";
            header('Location:?route=contact'); exit;
        }

        $name    = trim((string)($_POST['name'] ?? ''));
        $email   = trim((string)($_POST['email'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        if ($name === '' || $email === '' || $message === '') {
            $_SESSION['error'] = 'Tous les champs sont requis.'; header('Location:?route=contact'); exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Email invalide.'; header('Location:?route=contact'); exit;
        }
        if (mb_strlen($message) > 5000) {
            $_SESSION['error'] = 'Message trop long.'; header('Location:?route=contact'); exit;
        }

        $_SESSION['contact_name']  = $name;
        $_SESSION['contact_email'] = $email;

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'];
            $mail->Password   = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);

            $mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME'] ?? 'Tracktoon');
            $mail->addAddress($_ENV['MAIL_TO']);

            $mail->addReplyTo($email, $name);

            $mail->Subject = 'Nouveau message de contact';
            $body  = "Nom: ".htmlspecialchars($name)."\n";
            $body .= "Email: ".htmlspecialchars($email)."\n\n";
            $body .= "Message:\n".htmlspecialchars($message)."\n";

            $mail->Body    = $body;
            $mail->AltBody = $body;

            $mail->send();

            $_SESSION['success']      = 'Message envoyé. Merci !';
            $_SESSION['last_contact'] = $now;
            header('Location:?route=contact'); exit;
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Impossible d'envoyer le message pour le moment.";
            header('Location:?route=contact'); exit;
        }
    }

    public function faq(): void
    {
        echo $this->twig->render('pages/faq.html.twig');
    }

    public function legal(): void
    {
        echo $this->twig->render('pages/legal.html.twig');
    }
}