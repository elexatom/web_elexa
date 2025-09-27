<?php

namespace App\Controllers;

/**
 * Abstraktni trida pro Controller
 */
abstract class
Controller
{
    protected array $data = [];
    protected string $view = "";
    protected array $header = ['titulek' => '', 'klicova_slova' => '', 'popis' => ''];

    abstract function process(array $params): void;

    public function writeView(): void
    {
        if ($this->view != null) {
            extract($this->data);
            require("Views/$this->view.php");
        }
    }

    public function redirect(string $url): never
    {
        header("Location: $url");
        header("Connection: close");
        exit;
    }
}