<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * Controller pro stranku spravy clanku
 */
class AdminCtrl extends Controller
{
    /**
     * @param $twig
     */
    public function __construct($twig)
    {
        parent::__construct($twig);
        // uzivatel musi byt prihlasen
        if (!isset($_SESSION['user'])) $this->redirect('/auth/login');
        // uzivatel musi byt admin
        if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) $this->redirect('/');
    }

    public function process(array $params): void
    {
        switch ($params[0] ?? '') {
            case 'change-status':
                $this->setArticleStatus();
                break;

            case 'assign':
                $this->addReviewer();
                break;

            case 'delete-review':
                $this->deleteReview();
                break;
            default:
                break;
        }

        $this->view = 'admin';                                  // sablona
        $this->header['title'] = "Správa článků | DigiArch";    // hlavicka stranky
        $this->data['user'] = $_SESSION['user'];                // data o uzivateli
        $this->data['articles'] = $this->articleManager->getAllArticles();               // vsechny clanky
        $this->data['reviews'] = $this->reviewManager->getAllReviews();                  // vsechny recenze
        $this->data['reviewers'] = $this->userManager->getUsersByRole('recenzent'); // vsichni recenzenti
    }

    // nastaveni statusu clanku
    private function setArticleStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $art_id = $_POST['article_id'] ?? '';
        $status = trim($_POST['status'] ?? '');

        // whitelist statusu
        $allowed_statuses = ['prijato', 'zamitnuto', 'cekajici'];

        // validace dat
        if (empty($art_id) || empty($status)) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatné data.'], 400);
        }

        // validace statusu
        if (!in_array($status, $allowed_statuses, true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný status článku.'], 400);
        }

        if ($this->articleManager->setArticleStatus($art_id, $status)) {
            $this->jsonResponse(['success' => true, 'message' => sprintf("Článek byl úspěšně nastaven na: %s.", $status)]);
        } else
            $this->jsonResponse(['success' => false, 'message' => sprintf("Článek se nepodařilo nastavit na: %s", $status)]);
    }

    // pridat recenzenta k clanku
    private function addReviewer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $art_id_raw = trim($_POST['article_id'] ?? '');
        $rec_id_raw = trim($_POST['reviewer_id'] ?? '');
        $art_id = filter_var($art_id_raw, FILTER_VALIDATE_INT);
        $rec_id = filter_var($rec_id_raw, FILTER_VALIDATE_INT);

        // validace dat
        if (empty($art_id) || empty($rec_id)) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatné data.'], 400);
        }

        // kontrola, ze recenzent jeste neni prirazen
        if ($this->reviewManager->getReviewByArticleAndReviewer((int)$art_id, (int)$rec_id)) {
            $this->jsonResponse(['success' => false, 'message' => 'Tento recenzent je již k tomuto článku přiřazen.'], 400);
        }

        if ($this->reviewManager->addReview($art_id, $rec_id)) {
            $this->jsonResponse(['success' => true, 'message' => "Recenzent byl úspěšně přidán."]);
        } else $this->jsonResponse(['success' => true, 'message' => "Recenzenta se nepovedlo přidat."]);
    }

    // smazat recenzi / prirazeni
    private function deleteReview(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $rec_id_raw = trim($_POST['review_id'] ?? '');
        $rec_id = filter_var($rec_id_raw, FILTER_VALIDATE_INT);

        // validace dat
        if (empty($rec_id)) $this->jsonResponse(['success' => false, 'message' => 'Neplatné data.'], 400);

        if ($this->reviewManager->deleteReview($rec_id)) {
            $this->jsonResponse(['success' => true, 'message' => "Recenze byla úspěšně odebrána."]);
        } else $this->jsonResponse(['success' => true, 'message' => "Recenzi se nepovedlo odebrat."]);

    }
}