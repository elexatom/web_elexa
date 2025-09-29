<?php

namespace App\Controllers;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Abstraktni trida pro Controller
 */
abstract class Controller
{
    protected array $data = [];
    protected string $view = "";
    protected array $header = ['title' => '', 'key_words' => '', 'description' => ''];
    protected Environment $twig;

    public function __construct(Environment $twig) // Vlozime twig pres konstruktor
    {
        $this->twig = $twig;
    }

    abstract public function process(array $params): void;

    public function writeView(): void
    {
        // spojit ctrl data a headery
        $context = array_merge($this->data, ['header' => $this->header]);

        if (!empty($this->view)) {
            echo $this->twig->render($this->view . '.twig', $context);
        }
    }

    public function redirect(string $url): never
    {
        header("Location: $url");
        header("Connection: close");
        exit;
    }
}