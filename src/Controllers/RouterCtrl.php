<?php

namespace App\Controllers;

/**
 * Router, ktery na zaklade URL vola prislusny controller
 */
class RouterCtrl extends Controller
{
    // router zjisti dle url jaky ctrl volame, vytvori ho a ulozi do atributu $controller
    protected Controller $controller;

    public function process(array $params): void
    {
        $parsed_url = $this->parseUrl($params[0]);
        if (empty($parsed_url[0]))
            $this->redirect('/home');

        $class_ctrl = 'App\\Controllers\\' . $this->toCamelCase(array_shift($parsed_url)) . 'Ctrl'; // prvni element

        if (class_exists($class_ctrl)) // pokud existuje vytvorime instanci
            $this->controller = new $class_ctrl($this->twig);
        else
            $this->redirect('notFound'); // pokud neexistuje -> chybova stranka

        $this->controller->process($parsed_url); // zavolat logiku v ctrl

        // nastavi data, headery a sablonu, ktera se ukaze
        $this->data = $this->controller->data;
        $this->header = $this->controller->header;
        $this->view = $this->controller->view;
    }

    private function parseUrl(string $url): array
    {
        $parsed_url = parse_url($url);
        $parsed_url['path'] = ltrim($parsed_url['path'], '/');
        $parsed_url['path'] = trim($parsed_url['path']);
        return explode('/', $parsed_url['path']);
    }

    private function toCamelCase(string $string): string
    {
        // nazev-controlleru --> nazevControlleruController
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
}