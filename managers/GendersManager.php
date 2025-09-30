<?php
class GendersManager extends AbstractManager
{
    public function __construct(?PDO $db = null)
    {
        if ($db) { 
            $this->db = $db; 
        } else { 
            parent::__construct(); 
        }
    }

    public function findOneByName(string $name): ?Gender
    {
        $normalized = mb_strtolower(trim($name));

        $query  = $this->db->prepare('SELECT id, gender FROM genders WHERE gender = :gender');
        $result = $query->execute(['gender' => $normalized]);
        if (!$result) return null;

        $rowGender = $query->fetch(PDO::FETCH_ASSOC);
        if (!$rowGender) return null;

        $gender = new Gender($rowGender['gender']);
        $gender->setId((int)$rowGender['id']);

        return $gender;
    }

    public function create(Gender $gender): ?Gender
    {
        $query  = $this->db->prepare('INSERT INTO genders (gender) VALUES (:gender)');
        $result = $query->execute(['gender' => $gender->getGender()]);
        if (!$result) return null;

        $gender->setId((int)$this->db->lastInsertId());
        return $gender;
    }

    public function findOrCreateByName(string $name): Gender
    {
        $found = $this->findOneByName($name);
        if ($found) return $found;

        $new     = new Gender($name);
        $created = $this->create($new);

        if (!$created) {
            throw new RuntimeException('Création de genre échouée.');
        }

        return $created;
    }
}