<?php
class ScoresManager extends AbstractManager
{
    public function __construct() {
        parent::__construct();
    }

    public function createOrUpdateScore(Scores $score): ?Scores
    {
        $query = $this->db->prepare(
            'INSERT INTO scores (id_user, id_book, score)
            VALUES (:id_user, :id_book, :score)
            ON DUPLICATE KEY UPDATE score = VALUES(score)'
        );

        $result = $query->execute([
            'id_user' => $score->getUserId(),
            'id_book' => $score->getBookId(),
            'score'   => $score->getScore(),
        ]);

        if ($result) {
            return $score;
        }

        return null;
    }

    public function getBookScores(int $bookId): ?array
    {
        $query = $this->db->prepare(
            'SELECT
                COUNT(*) AS votes,
                SUM(score) AS total_score,
                ROUND(AVG(score), 2) AS avg_score
            FROM scores
            WHERE id_book = :id_book'
        );

        $result = $query->execute(['id_book' => $bookId]);

        if ($result) {
            $rowScores = $query->fetch(PDO::FETCH_ASSOC);
            return [
                'votes' => $rowScores ? (int)$rowScores['votes'] : 0,
                'total' => $rowScores && $rowScores['total_score'] !== null ? (int)$rowScores['total_score'] : 0,
                'avg'   => $rowScores && $rowScores['avg_score'] !== null ? (float)$rowScores['avg_score'] : null,
            ];
        }

        return null;
    }
}