<?php

namespace App\Controllers;

/**
 * Controller pro stranku recenzentu
 */
class ReviewsCtrl extends Controller
{
    public function __construct($twig)
    {
        parent::__construct($twig);

        // uzivatel musi byt prihlasen
        if (!isset($_SESSION['user'])) $this->redirect('/auth/login');
        // uzivatel musi byt recenzent
        if ($_SESSION['role'] !== 'recenzent' && $_SESSION['role'] !== 'admin') $this->redirect('/');
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
        $this->data['reviews'] = $this->reviewManager->getAllReviewsByUser($_SESSION['user_id']);
        $this->data['articles'] = $this->reviewManager->getAllArticlesForReviewer($_SESSION['user_id']);
    }

    private function saveReview(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $article_id = trim($_POST['article_id'] ?? '');
        $review_id = trim($_POST['review_id'] ?? '');

        $cat1 = (int)($_POST['cat1'] ?? 0);
        $cat2 = (int)($_POST['cat2'] ?? 0);
        $cat3 = (int)($_POST['cat3'] ?? 0);
        $cat4 = (int)($_POST['cat4'] ?? 0);

        $komentar = trim($_POST['komentar'] ?? '');

        if (empty($article_id) || empty($review_id) || empty($komentar) || $cat1 < 1 || $cat2 < 1 || $cat3 < 1 || $cat4 < 1) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatné data.'], 400);
        }

        if ($this->reviewManager->updateReview($review_id, $cat1, $cat2, $cat3, $cat4, $komentar)) {
            $this->jsonResponse(['success' => true, 'message' => 'Recenze byla aktualizována.']);
        } else $this->jsonResponse(['success' => false, 'message' => 'Nepodařilo se uložit recenzi.'], 400);
    }
}