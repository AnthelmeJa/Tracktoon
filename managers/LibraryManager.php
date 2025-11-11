<?php

class LibraryManager extends AbstractManager
{
    public function __construct()
    {
        parent::__construct();
    }

    
    public function createOrUpdate(Library $library): bool
    {
        $query = $this->db->prepare(
            'INSERT INTO library (id_user, id_book, statut, favori, comment)
             VALUES (:id_user, :id_book, :statut, :favori, :comment)
             ON DUPLICATE KEY UPDATE
               statut = VALUES(statut),
               favori = VALUES(favori),
               comment = VALUES(comment)'
        );

        $result = $query->execute([
            'id_user' => $library->getUserId(),
            'id_book' => $library->getBookId(),
            'statut'  => $library->getStatut(),
            'favori'  => $library->isFavori() ? 1 : 0,
            'comment' => $library->getComment(),
        ]);

        return $result;
    }

    /** Récupère l'entrée d'un user pour un book (ou null si absente) */
    public function findOne(int $userId, int $bookId): ?Library
    {
        $query = $this->db->prepare(
            'SELECT id_user, id_book, statut, favori, comment
             FROM library
             WHERE id_user = :id_user AND id_book = :id_book'
        );

        $result = $query->execute(['id_user' => $userId, 'id_book' => $bookId]);
        if (!$result) return null;

        $rowLibrary = $query->fetch(PDO::FETCH_ASSOC);
        if (!$rowLibrary) return null;

        return new Library(
            (int)$rowLibrary['id_user'],
            (int)$rowLibrary['id_book'],
            $rowLibrary['statut'],
            (bool)$rowLibrary['favori'],
            $rowLibrary['comment']
        );
    }

    public function findAll(int $userId, ?string $statut = null, ?bool $favori = null, int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT id_user, id_book, statut, favori, comment
                FROM library
                WHERE id_user = :id_user';

        $params = ['id_user' => $userId];

        if ($statut !== null) {
            $sql .= ' AND statut = :statut';
            $params['statut'] = $statut;
        }
        if ($favori !== null) {
            $sql .= ' AND favori = :favori';
            $params['favori'] = $favori ? 1 : 0;
        }

        $sql .= ' ORDER BY id_book DESC LIMIT :limit OFFSET :offset';

        $query = $this->db->prepare($sql);

        foreach ($params as $k => $v) {
            $query->bindValue(':'.$k, $v);
        }

        $query->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);

        $result = $query->execute();
        if (!$result) return [];

        $items = [];
        while ($rowLibrary = $query->fetch(PDO::FETCH_ASSOC)) {
            $items[] = new Library(
                (int)$rowLibrary['id_user'],
                (int)$rowLibrary['id_book'],
                $rowLibrary['statut'],
                (bool)$rowLibrary['favori'],
                $rowLibrary['comment']
            );
        }
        return $items;
    }

    public function deleteByIds(int $userId, int $bookId): bool
    {
        $query = $this->db->prepare(
            'DELETE FROM library WHERE id_user = :id_user AND id_book = :id_book'
        );
        return $query->execute(['id_user' => $userId, 'id_book' => $bookId]);
    }

    /** IDs des favoris d’un utilisateur (pratique pour badges/filtre) */
    public function getFavoritesBookIds(int $userId): array
    {
        $query = $this->db->prepare(
            'SELECT id_book FROM library WHERE id_user = :id_user AND favori = 1'
        );
        $query->execute(['id_user' => $userId]);

        $ids = [];
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = (int)$row['id_book'];
        }
        return $ids;
    }

    public function getCounts(int $userId): array
    {
        $base = ['a_lire' => 0, 'en_cours' => 0, 'termine' => 0, 'favoris' => 0];

        $q1 = $this->db->prepare(
            'SELECT statut, COUNT(*) AS c
             FROM library
             WHERE id_user = :u
             GROUP BY statut'
        );
        $q1->execute(['u' => $userId]);
        while ($row = $q1->fetch(PDO::FETCH_ASSOC)) {
            $s = $row['statut'];
            $c = (int)$row['c'];
            if (isset($base[$s])) {
                $base[$s] = $c;
            }
        }

        $q2 = $this->db->prepare(
            'SELECT COUNT(*) AS cfav
             FROM library
             WHERE id_user = :u AND favori = 1'
        );
        $q2->execute(['u' => $userId]);
        $r2 = $q2->fetch(PDO::FETCH_ASSOC);
        $base['favoris'] = $r2 ? (int)$r2['cfav'] : 0;

        return $base;
    }
}