<?php

namespace App\Controllers;

/**
 * Chybova stranka
 */
class NotFoundCtrl extends Controller
{
    public function process(array $params): void
    {
        header("HTTP/1.0 404 Not Found"); // hlavicka pozadavku
        $this->header['title'] = "Oops! 404 Not Found"; // hlavicka stranky
        $this->view = 'notFound'; // sablona
    }
}