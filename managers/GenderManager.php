<?php
class GendersManager extends AbstractManager
{
    public function __construct(?PDO $db = null)
    {
        if ($db) { $this->db = $db; } else { parent::__construct(); }
    }

    public function findOneByName(string $name): ?Gender
    {
        $name = mb_strtolower(trim($name));
        $q = $this->db->prepare('SELECT id, gender FROM genders WHERE gender = :g');
        $q->execute(['g' => $name]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $g = new Gender($row['gender']);
        $g->setId((int)$row['id']);
        return $g;
    }

    public function create(Gender $g): ?Gender
    {
        $q = $this->db->prepare('INSERT INTO genders (gender) VALUES (:g)');
        $ok = $q->execute(['g' => $g->getGender()]);
        if (!$ok) return null;

        $g->setId((int)$this->db->lastInsertId());
        return $g;
    }

    public function findOrCreateByName(string $name): Gender
    {
        $found = $this->findOneByName($name);
        if ($found) return $found;

        $new = new Gender($name);
        $created = $this->create($new);
        if (!$created) throw new RuntimeException('Création de genre échouée.');
        return $created;
    }
}