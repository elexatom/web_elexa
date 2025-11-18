<?php
declare(strict_types=1);

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

    public function __construct(Environment $twig)
    {
        $this->twig = $twig; // vlozeni twigu
        $this->articleManager = new ArticleManager();                    // vlozeni modelu Article
        $this->reviewManager = new ReviewManager($this->articleManager); // vlozeni modelu Review
        $this->userManager = new UserManager();                          // vlozeni modelu User
    }

    // metoda pro zpracovani url adresy
    abstract public function process(array $params): void;

    // metoda pro vytvoreni pohledu
    public function writeView(): void
    {
        $this->setSecurityHeaders(); // security headery
        // spojit ctrl data a headery
        $context = array_merge($this->data, ['header' => $this->header]);

        if (!empty($this->view)) { // pokud byl nastaven twig template
            try { // najdeme ho
                echo $this->twig->render($this->view . '.twig', $context);
            } catch (SyntaxError|RuntimeError|LoaderError) { // nebyl nalezen
                $this->redirect('/notFound');
            }
        }
    }

    // metoda pro presmerovani
    public function redirect(string $url): never
    {
        header("Location: $url");
        header("Connection: close");
        exit;
    }

    // metoda pro vypis json odpovedi
    protected function jsonResponse(array $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // metoda pro nastaveni security headeru
    protected function setSecurityHeaders(): void
    {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://unpkg.com https://code.jquery.com https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
    }
}