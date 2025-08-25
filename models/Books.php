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

    public function __construct(
        string $title,
        string $type,
        string $description,
        ?string $image = null,
        ?int $chapter = null,
        array $genders = []
    ) {
        $this->setTitle($title);
        $this->setType($type);
        $this->setDescription($description);
        $this->setImage($image);
        $this->setChapter($chapter);

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
        $allowedTypes = ['manwha', 'manhua', 'manga']; 
        if (!in_array($type, $allowedTypes, true)) {
            throw new Exception("Type invalide.");
        }
        $this->type = $type;
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
}