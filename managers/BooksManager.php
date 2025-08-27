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
                INSERT INTO books (title, type, description, image, chapter) 
                VALUES (:title, :type, :description, :image, :chapter)
            ');
            
            $query->execute([
                'title'       => $book->getTitle(),
                'type'        => $book->getType(),
                'description' => $book->getDescription(),
                'image'       => $book->getImage(),
                'chapter'     => $book->getChapter()
            ]);

            $bookId = (int) $this->db->lastInsertId();
            $book->setId($bookId);

            foreach ($book->getGenders() as $gender) {

                
                $check = $this->db->prepare('SELECT id FROM genders WHERE gender = :gender');
                $check->execute(['gender' => $gender]);
                $result = $check->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    $genderId = (int) $result['id'];
                } else {
                    
                    $insert = $this->db->prepare('INSERT INTO genders (gender) VALUES (:gender)');
                    $insert->execute(['gender' => $gender]);
                    $genderId = (int) $this->db->lastInsertId();
                }

                $link = $this->db->prepare('INSERT INTO books_genders (id_book, id_gender) VALUES (:book, :gender)');
                $link->execute([
                    'book'   => $bookId,
                    'gender' => $genderId
                ]);
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
            $sql .= " AND b.chapter >= :minChapter";
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
                (int)$row['chapter']
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
            $book = new Book($rowTrending['title'], $rowTrending['type'], $rowTrending['description'], $rowTrending['image'], (int)$rowTrending['chapter']);
            $book->setId((int)$rowTrending['id']);
            $items[] = [
                'book'  => $book,
                'votes' => (int)$rowTrending['votes'],
                'avg'   => $rowTrending['avg_score'] !== null ? (float)$rowTrending['avg_score'] : null,
            ];
        }
        return $items;
    }
}