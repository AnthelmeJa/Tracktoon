<?php
class Library
{
    private const STATUTS = ['a_lire','en_cours','termine'];

    private int $userId;
    private int $bookId;
    private string $statut = 'a_lire';
    private bool $favori = false;
    private string $comment = '';

    public function __construct(
        int $userId,
        int $bookId,
        string $statut ="a_lire",
        bool $favori = false,
        string $comment = ''
    ) {
        $this->setUserId($userId);
        $this->setBookId($bookId);
        $this->setStatut($statut);
        $this->setFavori($favori);
        $this->setComment($comment);
    }

    public function getUserId(): int 
    { 
        return $this->userId; 
    }

    public function getBookId(): int 
    { 
        return $this->bookId; 
    }

    public function getStatut(): string 
    { 
        return $this->statut; 
    }

    public function isFavori(): bool 
    { 
        return $this->favori; 
    }

    public function getComment(): string 
    { 
        return $this->comment; 
    }

    public function setUserId(int $id): void {
        if ($id <= 0) throw new InvalidArgumentException("userId invalide.");
        $this->userId = $id;
    }
    public function setBookId(int $id): void {
        if ($id <= 0) throw new InvalidArgumentException("bookId invalide.");
        $this->bookId = $id;
    }
    public function setStatut(string $s): void {
        if (!in_array($s, self::STATUTS, true)) {
            throw new InvalidArgumentException("Statut invalide.");
        }
        $this->statut = $s;
    }
    public function setFavori(bool $f): void { $this->favori = $f; }
    public function setComment(string $c): void { $this->comment = trim($c); }
}