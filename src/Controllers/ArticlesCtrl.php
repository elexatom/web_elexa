<?php

namespace App\Controllers;

use App\Models\ArticleManager;
use App\Models\ReviewManager;

class ArticlesCtrl extends Controller
{
    private ArticleManager $articleManager;
    private ReviewManager $reviewManager;

    public function __construct($twig)
    {
        parent::__construct($twig);
        $this->articleManager = new ArticleManager();
        $this->reviewManager = new ReviewManager($this->articleManager);

        // uzivatel musi byt prihlasen
        if (!isset($_SESSION['user'])) $this->redirect('/auth/login');
    }

    public function process(array $params): void
    {


        $this->view = 'articles';
        $this->data['user'] = $_SESSION['user'];
        $this->data['articles'] = $this->articleManager->getAllArticlesByAuthor($_SESSION['user_id']);
        $this->data['reviews'] = $this->reviewManager->getAllReviewsForAuthor($_SESSION['user_id']);
        $this->header['title'] = "Moje články | DigiArch";
    }
}