<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * Controller pro hlavni stranku
 */
class HomeCtrl extends Controller
{
    public function process(array $params): void
    {
        $this->header['title'] = "Forum DigiArch";
        $this->view = 'home';
        $this->data['articles'] = $this->articleManager->getAllAcceptedArticles(); // vsechny schvalene clanky
        $this->data['user'] = $_SESSION['user'] ?? '';  // data o uzivateli
    }
}