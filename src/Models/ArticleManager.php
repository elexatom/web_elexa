<?php
declare(strict_types=1);

namespace App\Models;

use PDOException;

/**
 * Model - Clanky
 */
class ArticleManager
{
    // vrati vsechny clanky
    public function getAllArticles(): array
    {
        return Db::query('
        SELECT * FROM `clanky`
        ');
    }

    // vrati vsechny prijate clanky
    public function getAllAcceptedArticles(): array
    {
        return Db::query('
        SELECT * FROM `clanky` WHERE status = ?     
        ', ['prijato']);
    }

    // vrati clanky uzivatele dle ID
    public function getAllArticlesByAuthor(int $author_id): array
    {
        return Db::query('
        SELECT * FROM `clanky` WHERE autor_id = ?
        ', [$author_id]);
    }

    // vlozi novy clanek
    public function createArticle(string $title, string $abstract, string $publish_date, int $author_id, string $pdf_route): bool
    {
        try {
            Db::query('
            INSERT INTO `clanky` (nazev_clanku, abstrakt, datum, autor_id, pdf_odkaz) VALUES (?, ?, ?, ?, ?)
            ', [$title, $abstract, $publish_date, $author_id, $pdf_route]);
            return true;
        } catch (PDOException $e) {
            // necekany error
            print $e->getMessage();
            error_log("DB error in createArticle: " . $e->getMessage());
            return false;
        }
    }

    // upravi clanek dle ID
    public function editArticle(string $title, string $abstract, int $article_id): bool
    {
        try {
            Db::query('
            UPDATE `clanky` SET nazev_clanku = ?, abstrakt = ? WHERE clanek_id = ?
            ', [$title, $abstract, $article_id]);
            return true;
        } catch (PDOException $e) {
            // necekany error
            print $e->getMessage();
            error_log("DB error in editArticle: " . $e->getMessage());
            return false;
        }
    }

    // smaze clanek dle ID
    public function deleteArticle(string $article_id): bool
    {
        try {
            Db::query('
            DELETE FROM `clanky` WHERE clanek_id = ?
            ', [$article_id]);
            return true;
        } catch (PDOException $e) {
            // necekany error
            print $e->getMessage();
            error_log("DB error in deleteArticle: " . $e->getMessage());
            return false;
        }
    }

    // nastavi status clanku dle ID
    public function setArticleStatus(string $article_id, string $status): bool
    {
        try {
            Db::query('
            UPDATE `clanky` SET status = ? WHERE clanek_id = ?
            ', [$status, $article_id]);
            return true;
        } catch (PDOException $e) {
            // necekany error
            print $e->getMessage();
            error_log("DB error in setArticleStatus: " . $e->getMessage());
            return false;
        }
    }
}