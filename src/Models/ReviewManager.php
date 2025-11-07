<?php

namespace App\Models;

use PDOException;

/**
 * Manager dotazu na databazi - Recenze
 */
class ReviewManager
{
    private ArticleManager $articleManager;

    public function __construct(ArticleManager $articleManager)
    {
        $this->articleManager = $articleManager;
    }

    public function getAllReviewsForAuthor(int $author_id): array
    {
        $articles = $this->articleManager->getAllArticlesByAuthor($author_id);
        $reviews = [];

        foreach ($articles as &$article) {
            $article_reviews = Db::query('
                SELECT r.*, u.jmeno AS recenzent_jmeno, u.nick AS recenzent_nick
                FROM recenze r
                JOIN uzivatele u ON r.recenzent_id = u.userid
                WHERE r.clanek_id = ?
            ', [$article['clanek_id']]);
            foreach ($article_reviews as $review) {
                $reviews[] = array_merge($review);
            }
        }
        return $reviews;
    }
}