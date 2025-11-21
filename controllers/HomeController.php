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
            $payload = [
                'sender' => [
                    'email' => $_ENV['BREVO_SENDER_EMAIL'],
                    'name'  => $_ENV['BREVO_SENDER_NAME'] ?? 'Tracktoon',
                ],
                'to' => [
                    ['email' => $_ENV['BREVO_TO_EMAIL']]
                ],
                'subject' => 'Nouveau message de contact',
                'textContent' => 
                    "Nom: {$name}\n" .
                    "Email: {$email}\n\n" .
                    "Message:\n{$message}\n",
                'htmlContent' =>
                    "<p><strong>Nom:</strong> ".htmlspecialchars($name)."</p>" .
                    "<p><strong>Email:</strong> ".htmlspecialchars($email)."</p>" .
                    "<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>",
                'replyTo' => [
                    'email' => $email,
                    'name'  => $name,
                ],
            ];

            $ch = curl_init('https://api.brevo.com/v3/smtp/email');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'accept: application/json',
                    'content-type: application/json',
                    'api-key: ' . $_ENV['BREVO_API_KEY'],
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);

            $response = curl_exec($ch);
            $errno    = curl_errno($ch);
            $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno !== 0) {
                throw new \RuntimeException('Erreur réseau cURL (Brevo).');
            }
            if ($status < 200 || $status >= 300) {
                throw new \RuntimeException('Brevo a renvoyé un statut HTTP non 2xx.');
            }

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