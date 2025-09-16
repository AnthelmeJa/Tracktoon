<?php
class Gender
{
    private ?int $id = null;
    private string $gender;

    public function __construct(string $gender) { $this->setGender($gender); }

    public function getId(): ?int { return $this->id; }
    public function getGender(): string { return $this->gender; }

    public function setId(int $id): void { $this->id = $id; }
    public function setGender(string $g): void {
        $g = mb_strtolower(trim($g));
        if ($g === '') throw new InvalidArgumentException('Genre vide.');
        $this->gender = $g;
    }
}