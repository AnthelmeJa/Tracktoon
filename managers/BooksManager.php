<?php

class BooksManager extends AbstractManager
{
    public function __construct() {
        parent::__construct();
    }

    public function create(Book $book): ?Book
    {
        try {
            $this->db->beginTransaction();

            $query = $this->db->prepare('
                INSERT INTO books (title, type, description, image, chapter, author) 
                VALUES (:title, :type, :description, :image, :chapter, :author)
            ');
            
            $query->execute([
                'title'       => $book->getTitle(),
                'type'        => $book->getType(),
                'description' => $book->getDescription(),
                'image'       => $book->getImage(),
                'chapter'     => $book->getChapter(),
                'author'      => $book->getAuthor(),
            ]);

            $bookId = (int) $this->db->lastInsertId();
            $book->setId($bookId);

            // genres
            $gm = new GendersManager($this->db);
            foreach ($book->getGenders() as $name) {
                $gender   = $gm->findOrCreateByName($name);
                $genderId = (int)$gender->getId();

                $link = $this->db->prepare(
                    'INSERT INTO books_genders (id_book, id_gender) VALUES (:book, :gender)'
                );
                $link->execute(['book' => $bookId, 'gender' => $genderId]);
            }

            $this->db->commit();
            return $book;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findAllFiltered(
        ?string $title = null,
        ?string $type = null,
        ?string $genre = null,
        ?int $minChapter = null,
        int $limit = 30
    ): array {
        $sql = "SELECT DISTINCT b.* 
                FROM books b
                LEFT JOIN books_genders bg ON b.id = bg.id_book
                LEFT JOIN genders g ON bg.id_gender = g.id
                WHERE 1=1";

        $params = [];

        if ($title !== null && $title !== '') {
            $sql .= " AND b.title LIKE :title";
            $params['title'] = '%' . $title . '%';
        }

        if ($type !== null && $type !== '') {
            $sql .= " AND b.type = :type";
            $params['type'] = $type;
        }

        if ($genre !== null && $genre !== '') {
            $sql .= " AND g.gender = :genre";
            $params['genre'] = $genre;
        }

        if ($minChapter !== null) {
            $sql .= " AND (b.chapter IS NOT NULL AND b.chapter >= :minChapter)";
            $params['minChapter'] = $minChapter;
        }

        $sql .= " LIMIT :limit";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        $stmt->execute();

        $books = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $books[] = new Book(
                $row['title'],
                $row['type'],
                $row['description'],
                $row['image'],
                $row['chapter'] !== null ? (int)$row['chapter'] : null,
                [],
                $row['author']
            );
            $books[count($books)-1]->setId((int)$row['id']);
        }

        return $books;
    }

    public function getTrending(int $limit = 30): array
    {
        $queryTrending = $this->db->prepare(
            "SELECT b.*,
                    COUNT(s.id_user) AS votes,
                    ROUND(AVG(s.score), 2) AS avg_score
             FROM books b
             JOIN scores s ON s.id_book = b.id
             GROUP BY b.id
             HAVING votes > 0
             ORDER BY votes DESC, avg_score DESC
             LIMIT :limit"
        );
        $queryTrending->bindValue(':limit', $limit, PDO::PARAM_INT);
        $queryTrending->execute();

        $items = [];
        while ($rowTrending = $queryTrending->fetch(PDO::FETCH_ASSOC)) {
            $book = new Book(
                $rowTrending['title'],
                $rowTrending['type'],
                $rowTrending['description'],
                $rowTrending['image'],
                $rowTrending['chapter'] !== null ? (int)$rowTrending['chapter'] : null,
                [],
                $rowTrending['author']
            );
            $book->setId((int)$rowTrending['id']);
            $items[] = [
                'book'  => $book,
                'votes' => (int)$rowTrending['votes'],
                'avg'   => $rowTrending['avg_score'] !== null ? (float)$rowTrending['avg_score'] : null,
            ];
        }
        return $items;
    }

    public function findOne(int $id): ?Book
    {
        $query = $this->db->prepare('SELECT * FROM books WHERE id = :id');
        $query->execute(['id' => $id]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $qGenres = $this->db->prepare(
            'SELECT g.gender
             FROM genders g
             JOIN books_genders bg ON bg.id_gender = g.id
             WHERE bg.id_book = :id'
        );
        $qGenres->execute(['id' => $id]);
        $genders = [];
        while ($r = $qGenres->fetch(PDO::FETCH_ASSOC)) {
            $genders[] = $r['gender'];
        }

        $book = new Book(
            $row['title'],
            $row['type'],
            $row['description'],
            $row['image'],
            $row['chapter'] !== null ? (int)$row['chapter'] : null,
            $genders,
            $row['author']
        );
        $book->setId((int)$row['id']);
        return $book;
    }

public function findOneByTitleExact(string $title): ?Book
{
    $query = $this->db->prepare('SELECT * FROM books WHERE title = :t LIMIT 1');
    $query->execute(['t' => $title]);
    $row = $query->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;

    // Récupérer les genres
    $qGenres = $this->db->prepare(
        'SELECT g.gender
         FROM genders g
         JOIN books_genders bg ON bg.id_gender = g.id
         WHERE bg.id_book = :id'
    );
    $qGenres->execute(['id' => (int)$row['id']]);
    $genders = [];
    while ($r = $qGenres->fetch(PDO::FETCH_ASSOC)) {
        $genders[] = $r['gender'];
    }

    $book = new Book(
        $row['title'],
        $row['type'],
        $row['description'],
        $row['image'],
        $row['chapter'] !== null ? (int)$row['chapter'] : null,
        $genders,
        $row['author']
    );
    $book->setId((int)$row['id']);
    return $book;
}
    
    public function update(Book $book): bool
    {
        try {
            $this->db->beginTransaction();

            $q = $this->db->prepare(
                'UPDATE books
                    SET title = :title,
                        type = :type,
                        description = :description,
                        image = :image,
                        chapter = :chapter,
                        author = :author
                 WHERE id = :id'
            );
            $ok = $q->execute([
                'title'       => $book->getTitle(),
                'type'        => $book->getType(),
                'description' => $book->getDescription(),
                'image'       => $book->getImage(),
                'chapter'     => $book->getChapter(),
                'author'      => $book->getAuthor(),
                'id'          => $book->getId(),
            ]);
            if (!$ok) { $this->db->rollBack(); return false; }

            $qd = $this->db->prepare('DELETE FROM books_genders WHERE id_book = :id');
            $qd->execute(['id' => $book->getId()]);

            $gm = new GendersManager($this->db);
            foreach ($book->getGenders() as $name) {
                $gender = $gm->findOrCreateByName($name);
                $ql = $this->db->prepare(
                    'INSERT INTO books_genders (id_book, id_gender) VALUES (:b, :g)'
                );
                $ql->execute(['b' => $book->getId(), 'g' => $gender->getId()]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteById(int $id): bool
    {
        try {
            $this->db->beginTransaction();

            $this->db->prepare('DELETE FROM books_genders WHERE id_book = :id')->execute(['id' => $id]);
            $this->db->prepare('DELETE FROM scores        WHERE id_book = :id')->execute(['id' => $id]);
            $this->db->prepare('DELETE FROM library       WHERE id_book = :id')->execute(['id' => $id]);

            $ok = $this->db->prepare('DELETE FROM books WHERE id = :id')->execute(['id' => $id]);

            $this->db->commit();
            return (bool)$ok;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}