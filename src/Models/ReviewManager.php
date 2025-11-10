<?php

namespace App\Models;

use PDOException;

/**
 * Model - Recenze
 */
class ReviewManager
{
    private ArticleManager $articleManager;

    public function __construct(ArticleManager $articleManager)
    {
        $this->articleManager = $articleManager;
    }

    public function getAllReviews(): array
    {
        return Db::query('
        SELECT * FROM `recenze`
        ');
    }

    public function getAllReviewsForAuthor(int $author_id): array
    {
        $articles = $this->articleManager->getAllArticlesByAuthor($author_id);
        $reviews = [];

        foreach ($articles as &$article) {
            $article_reviews = Db::query('
                SELECT r.*, u.jmeno AS recenzent_jmeno, u.nick AS recenzent_nick
                FROM `recenze` r
                JOIN `uzivatele` u ON r.recenzent_id = u.userid
                WHERE r.clanek_id = ?
            ', [$article['clanek_id']]);
            foreach ($article_reviews as $review) {
                $reviews[] = array_merge($review);
            }
        }
        return $reviews;
    }

    public function addReview(int $article_id, int $reviwer_id): bool
    {
        try {
            Db::query('
                INSERT INTO `recenze` (clanek_id, recenzent_id)
                VALUES (?, ?)
            ', [$article_id, $reviwer_id]);
            return true;
        } catch (PDOException $e) {
            // necekany error
            print $e->getMessage();
            error_log("DB error in addReview: " . $e->getMessage());
            return false;
        }
    }

    public function getReviewByArticleAndReviewer(int $article_id, int $reviewer_id): array|bool
    {
        try {
            return Db::query('
            SELECT * FROM `recenze` WHERE clanek_id = ? AND recenzent_id = ?
            ', [$article_id, $reviewer_id], true);
        } catch (PDOException $e) {
            // necekany error
            print $e->getMessage();
            error_log("DB error in getReviewByArticleAndReviewer: " . $e->getMessage());
            return false;
        }
    }

    public function deleteReview(int $review_id): bool
    {
        try {
            Db::query('
            DELETE FROM `recenze` WHERE recenze_id = ?
            ', [$review_id]);
            return true;
        } catch (PDOException $e) {
            // necekany error
            print $e->getMessage();
            error_log("DB error in deleteReview: " . $e->getMessage());
            return false;
        }
    }

    public function getAllReviewsByUser(int $user_id): array|bool
    {
        try {
            return Db::query('
            SELECT * FROM `recenze` WHERE recenzent_id = ?
            ', [$user_id]);
        } catch (PDOException $e) {
            // necekany error
            print $e->getMessage();
            error_log("DB error in getAllReviewsByUser: " . $e->getMessage());
            return false;
        }
    }

    public function getAllArticlesForReviewer(int $rec_id): array
    {
        $reviews = $this->getAllReviewsByUser($rec_id);
        $articles = [];

        foreach ($reviews as &$review) {
            $article = Db::query('
            SELECT c.clanek_id, c.abstrakt, c.nazev_clanku, c.pdf_odkaz, c.datum,
                   r.recenze_id, r.cat1, r.cat2, r.cat3, r.cat4, r.komentar, c.status
            FROM `clanky` c
            LEFT JOIN `recenze` r ON c.clanek_id = r.clanek_id AND r.recenzent_id = ?
            WHERE c.clanek_id = ?
        ', [$rec_id, $review['clanek_id']], true);

            if ($article) {
                $articles[] = $article;
            }
        }

        return array_unique($articles, SORT_REGULAR);
    }

    public function updateReview(int $review_id, int $cat1, int $cat2, int $cat3, int $cat4, string $komentar): bool
    {
        try {
            Db::query('
            UPDATE `recenze` SET cat1 = ?, cat2 = ?, cat3 = ?, cat4 = ?, komentar = ?
            WHERE recenze_id = ?
            ', [$cat1, $cat2, $cat3, $cat4, $komentar, $review_id]);
            return true;
        } catch (PDOException $e) {
            // necekany error
            print $e->getMessage();
            error_log("DB error in updateReview: " . $e->getMessage());
            return false;
        }
    }
}