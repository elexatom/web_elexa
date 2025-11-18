<?php

use App\Controllers\RouterCtrl;
use App\Models\Db;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require __DIR__ . '/vendor/autoload.php';
mb_internal_encoding("UTF-8");
ini_set("session.cookie_httponly", 1); // XSS protection - zabrani cteni cookies JS

session_start();

// Twig setup
$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader, ['cache' => false, 'debug' => true, 'autoescape' => 'html']);

Db::connect("127.0.0.1", "dbadmin", "Zcu2025@", 'webdata');

$uri = $_SERVER['REQUEST_URI'];

// normalize url - /auth/ -> /auth
if ($uri !== '/' && str_ends_with($uri, '/')) {
    $newUri = rtrim($uri, '/');
    header("Location: $newUri");
    exit;
}


$router = new RouterCtrl($twig);
$router->process([$_SERVER['REQUEST_URI']]);
$router->writeView();
