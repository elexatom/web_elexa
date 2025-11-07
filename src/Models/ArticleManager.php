<?php

namespace App\Models;

use PDOException;

/**
 * Manager dotazu na databazi - Clanky
 */
class ArticleManager
{
    public function getAllArticles(): array
    {
        return Db::query('
        SELECT * FROM `clanky`
        ');
    }

    public function getAllArticlesByAuthor(int $author_id): array
    {
        return Db::query('
        SELECT * FROM `clanky` WHERE autor_id = ?
        ', [$author_id]);
    }
}