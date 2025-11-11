<?php

class Book
{
    private ?int $id = null;
    private string $title;
    private string $type;
    private string $description;
    private ?string $image = null;
    private ?int $chapter = null;
    private array $genders = [];
    private string $author;

    public function __construct(
        string $title,
        string $type,
        string $description,
        ?string $image = null,
        ?int $chapter = null,
        array $genders = [],
        string $author = 'Inconnu'
    ) {
        $this->setTitle($title);
        $this->setType($type);
        $this->setDescription($description);
        $this->setImage($image);
        $this->setChapter($chapter);
        $this->setAuthor($author);

        foreach ($genders as $gender) {
            $this->addGender($gender);
        }
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getChapter(): ?int
    {
        return $this->chapter;
    }

    public function getGenders(): array
    {
        return $this->genders;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setTitle(string $title): void
    {
        if (empty($title)) {
            throw new Exception("Le titre ne peut pas être vide.");
        }
        $this->title = $title;
    }

    public function setType(string $type): void
    {
        $allowed = ['manhwa', 'manhua', 'manga'];
        $t = strtolower(trim($type));
        if (!in_array($t, $allowed, true)) {
            throw new Exception("Type invalide.");
        }
        $this->type = $t;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function setChapter(?int $chapter): void
    {
        if ($chapter !== null && $chapter < 1) {
            throw new Exception("Le numéro de chapitre doit être positif.");
        }
        $this->chapter = $chapter;
    }

     public function setGenders(array $genders): void
    {
        $this->genders = $genders;
    }

    public function addGender(string $gender): void
    {
        if (!in_array($gender, $this->genders, true)) {
            $this->genders[] = $gender;
        }
    }

    public function setAuthor(string $author): void {
        $a = trim($author);
        if ($a === '') throw new InvalidArgumentException("L’auteur est obligatoire.");
        $this->author = $a;
    }
}