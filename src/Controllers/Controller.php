<?php

namespace App\Controllers;

use App\Models\ArticleManager;
use App\Models\ReviewManager;
use App\Models\UserManager;
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
    protected ArticleManager $articleManager;
    protected ReviewManager $reviewManager;
    protected UserManager $userManager;

    public function __construct(Environment $twig) // vlozime twig pres konstruktor
    {
        $this->twig = $twig;
        $this->articleManager = new ArticleManager();
        $this->reviewManager = new ReviewManager($this->articleManager);
        $this->userManager = new UserManager();
    }

    abstract public function process(array $params): void;

    public function writeView(): void
    {
        // spojit ctrl data a headery
        $context = array_merge($this->data, ['header' => $this->header]);

        if (!empty($this->view)) {
            try {
                echo $this->twig->render($this->view . '.twig', $context);
            } catch (SyntaxError|RuntimeError|LoaderError) {
                $this->redirect('/notFound');
            }
        }
    }

    public function redirect(string $url): never
    {
        header("Location: $url");
        header("Connection: close");
        exit;
    }

    protected function jsonResponse(array $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}