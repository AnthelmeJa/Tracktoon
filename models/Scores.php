<?php

class Scores
{
    private int $userId;  
    private int $bookId;  
    private int $score; 

    public function __construct(int $userId, int $bookId, int $score)
    {
        $this->setUserId($userId);
        $this->setBookId($bookId);
        $this->setScore($score);
    }

    public function getUserId(): int
    { 
        return $this->userId;
    }

    public function getBookId(): int 
    { 
        return $this->bookId; 
    }

    public function getScore(): int   
    { 
        return $this->score; 
    }

    
    public function setUserId(int $userId): void
    {
        if ($userId <= 0) throw new Exception("userId invalide.");
        $this->userId = $userId;
    }

    public function setBookId(int $bookId): void
    {
        if ($bookId <= 0) throw new Exception("bookId invalide.");
        $this->bookId = $bookId;
    }

    public function setScore(int $score): void
    {
        if ($score < 1 || $score > 5) {
            throw new Exception("La note doit être entre 1 et 5.");
        }
        $this->score = $score;
    }
}