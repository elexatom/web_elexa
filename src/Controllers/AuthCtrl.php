<?php

namespace App\Controllers;

use App\Models\UserManager;

class AuthCtrl extends Controller
{
    private UserManager $userManager;

    public function __construct($twig)
    {
        parent::__construct($twig);
        $this->userManager = new UserManager();
    }

    public function process(array $params): void
    {
        $action = $params[0] ?? 'login'; // defaultne login

        if (!empty($_SESSION['user']) || (!empty($_SESSION['loggedin']) && $_SESSION['loggedin'] === true)) {
            if (in_array($action, ['login', 'register'])) {
                $this->redirect('/');
            }
        }

        if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                $this->redirect('/');
            }

            $email = $_POST["email"] ?? '';
            $password = $_POST["password"] ?? '';

            $user = $this->userManager->getUserByEmail($email);

            if ($user && password_verify($password, $user['heslo'])) {
                session_regenerate_id();
                $_SESSION['user'] = $user;
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_id'] = $user['userid'];
                $_SESSION['loggedin'] = true;
                $this->redirect('/');
            } else $this->data['error'] = "Invalid credentials";
        }

        if ($action === 'logout') {
            unset($_SESSION['user']);
            $_SESSION['loggedin'] = false;
            session_destroy();
            $this->redirect('/');
        }

        $this->header['title'] = "Auth";
        $this->view = $action;
    }
}