<?php

require __DIR__ . '/../config/autoload.php';
use PHPUnit\Framework\TestCase;

class UserControllerRegisterTest extends TestCase
{
    private $twig;
    private $usersManager;
    private $csrf;

    protected function setUp(): void
    {
      // reset session
      $_SESSION = [];

      $this->twig = $this->createMock(\Twig\Environment::class);
      $this->usersManager = $this->createMock(UsersManager::class);
      $this->csrf = $this->createMock(CSRFTokenManager::class);

      // par défaut le CSRF est bon
      $this->csrf->method('validate')->willReturn(true);
    }

    private function makeController(): UserController
    {
        return new class($this->twig, $this->usersManager, $this->csrf) extends UserController {
            public ?string $redirectedTo = null;

            // dans le vrai controller: protected function redirect(...)
            protected function redirect(string $url): void
            {
                $this->redirectedTo = $url;
            }
        };
    }

    public function test_champs_manquants()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token'       => 'ok',
            'pseudo'           => '', // vide
            'mail'             => 'user@test.com',
            'password'         => 'Azerty123',
            'confirm_password' => 'Azerty123',
        ];

        $controller = $this->makeController();
        $controller->checkRegister();

        $this->assertSame('Champs manquants', $_SESSION['error']);
        $this->assertSame('?route=register', $controller->redirectedTo);
    }

    public function test_email_non_conforme()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token'       => 'ok',
            'pseudo'           => 'toto',
            'mail'             => 'pas-un-mail',  // <- mauvais format
            'password'         => 'Azerty123',
            'confirm_password' => 'Azerty123',
        ];

        $controller = $this->makeController();
        $controller->checkRegister();

        $this->assertSame('Email non conforme', $_SESSION['error']);
        $this->assertSame('?route=register', $controller->redirectedTo);
    }

    public function test_confirmation_incorrecte()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token'       => 'ok',
            'pseudo'           => 'toto',
            'mail'             => 'user@test.com',
            'password'         => 'Azerty123',
            'confirm_password' => 'Azerty1234',  // différent
        ];

        $controller = $this->makeController();
        $controller->checkRegister();

        $this->assertSame('Les mots de passe ne correspondent pas', $_SESSION['error']);
        $this->assertSame('?route=register', $controller->redirectedTo);
    }

    public function test_mot_de_passe_trop_faible()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token'       => 'ok',
            'pseudo'           => 'toto',
            'mail'             => 'user@test.com',
            'password'         => 'azerty',      // ne matche pas le regex
            'confirm_password' => 'azerty',
        ];

        $controller = $this->makeController();
        $controller->checkRegister();

        $this->assertSame(
            'Mot de passe trop faible (8 caractères mini, 1 majuscule, 1 minuscule, 1 chiffre).',
            $_SESSION['error']
        );
        $this->assertSame('?route=register', $controller->redirectedTo);
    }

    public function test_email_deja_existant()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token'       => 'ok',
            'pseudo'           => 'toto',
            'mail'             => 'user@test.com',
            'password'         => 'Azerty123',
            'confirm_password' => 'Azerty123',
        ];

        // on dit qu'un user existe déjà
        $existingUser = $this->createMock(Users::class);
        $this->usersManager
            ->method('findByMail')
            ->with('user@test.com')
            ->willReturn($existingUser);

        $controller = $this->makeController();
        $controller->checkRegister();

        $this->assertSame('Utilisateur déjà existant', $_SESSION['error']);
        $this->assertSame('?route=register', $controller->redirectedTo);
    }

    public function test_tout_est_bon()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token'       => 'ok',
            'pseudo'           => 'toto',
            'mail'             => 'user@test.com',
            'password'         => 'Azerty123',
            'confirm_password' => 'Azerty123',
        ];

        // pas d'utilisateur existant
        $this->usersManager->method('findByMail')->willReturn(null);

        // ici on "simule" le fait que create() va setter l'ID sur l'objet
        $this->usersManager
            ->method('create')
            ->willReturnCallback(function (Users $u) {
                $u->setId(42);
                return $u;
            });

        $controller = $this->makeController();
        $controller->checkRegister();

        $this->assertSame('?route=home', $controller->redirectedTo);
        $this->assertSame(42, $_SESSION['user_id']);
        $this->assertSame('user', $_SESSION['role']);
    }
}