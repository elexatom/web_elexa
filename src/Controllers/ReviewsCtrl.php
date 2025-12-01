<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * Controller pro stranku recenzentu
 */
class ReviewsCtrl extends Controller
{
    /**
     * @param $twig
     */
    public function __construct($twig)
    {
        parent::__construct($twig);

        // uzivatel musi byt prihlasen
        if (!isset($_SESSION['user'])) $this->redirect('/auth/login');
        // uzivatel musi byt recenzent
        if ($_SESSION['role'] !== 'recenzent') $this->redirect('/');
    }

    public function process(array $params): void
    {
        switch ($params[0] ?? '') {
            case 'save-review':
                $this->saveReview();
                break;

            default:
                break;
        }

        $this->view = 'reviews';
        $this->header['title'] = "Recenze | DigiArch";
        $this->data['user'] = $_SESSION['user'];
        // vsechny recenze uzivatele
        $this->data['reviews'] = $this->reviewManager->getAllReviewsByUser($_SESSION['user_id']);
        // vsechny recenzovane clanky uzivatele
        $this->data['articles'] = $this->reviewManager->getAllArticlesForReviewer($_SESSION['user_id']);
    }

    // ulozit (upravit) recenzi
    private function saveReview(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $article_id = filter_var(trim($_POST['article_id'] ?? ''), FILTER_VALIDATE_INT);
        $review_id = filter_var(trim($_POST['review_id'] ?? ''), FILTER_VALIDATE_INT);

        $catg1 = (int)($_POST['cat1'] ?? 0);
        $catg2 = (int)($_POST['cat2'] ?? 0);
        $catg3 = (int)($_POST['cat3'] ?? 0);
        $catg4 = (int)($_POST['cat4'] ?? 0);

        $komentar = trim($_POST['komentar'] ?? '');

        $allowed = '<p><b><strong><i><em><u><ul><ol><li><br>';
        $komentar = strip_tags($komentar, $allowed);

        // validace dat
        if (empty($article_id) || empty($review_id) || empty($komentar) || $catg1 < 1 || $catg2 < 1 || $catg3 < 1 || $catg4 < 1) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatné data.'], 400);
        }

        // ulozit data
        if ($this->reviewManager->updateReview($review_id, $catg1, $catg2, $catg3, $catg4, $komentar)) {
            $this->jsonResponse(['success' => true, 'message' => 'Recenze byla aktualizována.']);
        } else $this->jsonResponse(['success' => false, 'message' => 'Nepodařilo se uložit recenzi.'], 400);
    }
}