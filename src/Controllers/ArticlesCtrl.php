<?php
declare(strict_types=1);

namespace App\Controllers;

use Ramsey\Uuid\Uuid;

/**
 * Controller pro stranku s clanky
 */
class ArticlesCtrl extends Controller
{
    /**
     * @param $twig
     */
    public function __construct($twig)
    {
        parent::__construct($twig);

        // uzivatel musi byt prihlasen
        if (!isset($_SESSION['user'])) $this->redirect('/auth/login');
    }

    public function process(array $params): void
    {
        switch ($params[0] ?? '') {
            case 'create-article':
                $this->createArticle();
                break;

            case 'edit-article':
                $this->editArticle();
                break;

            case 'delete-article':
                $this->deleteArticle();
                break;

            default:
                break;
        }

        $this->view = 'articles';
        $this->data['user'] = $_SESSION['user'];
        // vsechny clanky uzivatele
        $this->data['articles'] = $this->articleManager->getAllArticlesByAuthor($_SESSION['user_id']);
        // vsechny recenze uzivatelovych clanku
        $this->data['reviews'] = $this->reviewManager->getAllReviewsForAuthor($_SESSION['user_id']);
        $this->header['title'] = "Moje články | DigiArch";
    }

    // vytvorit clanek
    private function createArticle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $title = trim($_POST['title'] ?? '');
        $abstr = trim($_POST['abstract'] ?? '');
        $pubdate = trim($_POST['publish_date'] ?? '');
        $article = $_FILES['pdf'] ?? null;

        // validace dat
        if (empty($title) || empty($abstr) || empty($pubdate) || $article === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatné data.'], 400);
        }

        // validace pdf
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $article['tmp_name']); // ziskame typ souboru
        finfo_close($finfo);

        // musi byt typu pdf
        if ($mime_type != 'application/pdf') {
            $this->jsonResponse(['success' => false, 'message' => 'Nepodporovaný formát obrázku. Použijte PDF.'], 400);
        }

        // validace velikosti (max 20MB)
        if ($article['size'] > 20 * 1024 * 1024) {
            $this->jsonResponse(['success' => false, 'message' => 'Obrázek je příliš velký. Maximum je 20MB.'], 400);
        }

        // vytvorit upload dir pokud neexistuje
        $upload_dir = __DIR__ . '/../../public/uploads/pdf/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        // generovat unikatni jmeno souboru
        $filename = Uuid::uuid4()->toString() . '.' . "pdf";
        $destination = $upload_dir . $filename;

        // presunout nahrany soubor
        if (move_uploaded_file($article['tmp_name'], $destination)) {
            $file_path = '/public/uploads/pdf/' . $filename; // cesta k souboru

            // ulozit clanek do db k aktualne prihlasenemu uzivateli
            $user_id = $_SESSION['user_id'];
            if ($this->articleManager->createArticle($title, $abstr, $pubdate, $user_id, $file_path)) {
                $this->jsonResponse(['success' => true, 'message' => 'Článek byl úspěšně vytvořen.']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Článek se nepodařilo uložit.'], 400);
            }
        } else $this->jsonResponse(['success' => false, 'message' => 'Fotku se nepodařilo uložit.'], 400);
    }

    // upravit clanek
    private function editArticle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $title = trim($_POST['title'] ?? '');
        $abstr = trim($_POST['abstract'] ?? '');
        $art_id_raw = trim($_POST['article_id'] ?? '');
        $art_id = filter_var($art_id_raw, FILTER_VALIDATE_INT);

        // validace dat
        if (empty($title) || empty($abstr) || empty($art_id)) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatné data.'], 400);
        }

        if ($this->articleManager->editArticle($title, $abstr, $art_id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Článek byl úspěšně upraven.']);
        } else $this->jsonResponse(['success' => false, 'message' => 'Článek se nepodařilo upravit.']);

    }

    // smazat clanek
    private function deleteArticle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $art_id_raw = trim($_POST['article_id'] ?? '');
        $art_id = filter_var($art_id_raw, FILTER_VALIDATE_INT);

        // validace dat
        if (empty($art_id)) $this->jsonResponse(['success' => false, 'message' => 'Neplatné data.'], 400);

        if ($this->articleManager->deleteArticle($art_id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Článek byl úspěšně smazán.']);
        } else $this->jsonResponse(['success' => false, 'message' => 'Článek se nepodařilo smazat.']);
    }
}
