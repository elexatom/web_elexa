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
            try {
                echo $this->twig->render($this->view . '.twig', $context);
            } catch (SyntaxError | RuntimeError | LoaderError) {
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


// bot added --------------------------
        protected function isAjaxRequest(): bool
        {
            return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        }


// ------------------------------
    protected function setFlashMessage(string $type, string $message): void
    {
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][$type] = $message;
    }

    protected function getFlashMessages(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
}