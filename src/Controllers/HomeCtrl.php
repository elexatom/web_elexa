<?php

namespace App\Controllers;

/**
 * Controller pro hlavni stranku
 */
class HomeCtrl extends Controller
{

    public function process(array $params): void
    {
        $this->header['title'] = "Forum DigiArch"; // hlavicka stranky
        $this->view = 'home'; // sablona
        $this->data['articles'] = $this->articleManager->getAllAcceptedArticles(); // vsechny schvalene clanky
        $this->data['user'] = $_SESSION['user'] ?? '';  // data o uzivateli
    }
}