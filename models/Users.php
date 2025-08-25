<?php
class Users
{
    private ?int $id = null;      
    private string $pseudo;
    private string $mail;
    private string $password;
    private string $role;

    public function __construct(string $pseudo, string $mail, string $password, string $role = 'user', bool $hashed = false)
    {
        $this->setPseudo($pseudo);
        $this->setMail($mail);
        $this->setPassword($password, $hashed);
        $this->setRole($role);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function getMail(): string
    {
        return $this->mail;
    }
    
    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setPseudo(string $pseudo): void
    {
        if (empty($pseudo)) {
            throw new Exception("Le pseudo ne peut pas être vide.");
        }
        $this->pseudo = $pseudo;
    }

    public function setMail(string $mail): void
    {
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email invalide.");
        }
        $this->mail = $mail;
    }

    public function setPassword(string $password, bool $hashed = false): void
    {
        if ($hashed) {
            $this->password = $password;
        } else {
            $this->password = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    public function setRole(string $role): void
    {
        $allowedRoles = ['user', 'admin', 'super_admin'];
        if (!in_array($role, $allowedRoles, true)) {
            throw new Exception("Role invalide.");
        }
        $this->role = $role;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}