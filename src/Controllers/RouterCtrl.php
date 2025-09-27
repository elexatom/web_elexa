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
        $class_ctrl = $this->toCamelCase(array_shift($parsed_url)) . 'Ctrl'; // prvni element
        echo $class_ctrl;
        echo('<br />');
        print_r($parsed_url);
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