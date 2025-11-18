<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 *  Stranka pro spravu uzivatelu adminem a superadminem
 */
class UsersCtrl extends Controller
{
    /**
     * @param $twig
     */
    public function __construct($twig)
    {
        parent::__construct($twig);
        // uzivatel musi byt prihlasen
        if (!isset($_SESSION['user'])) $this->redirect('/auth/login');
        // uzivatel musi byt admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin') $this->redirect('/');
    }

    public function process(array $params): void
    {
        switch ($params[0] ?? '') {
            case 'change-role':
                $this->changeRole();
                break;

            case 'toggle-status':
                $this->toggleStatus();
                break;

            default:
                break;
        }

        $this->view = 'users';
        $this->header['title'] = "Správa uživatelů | DigiArch";
        $this->data['user'] = $_SESSION['user'];
        $this->data['user_incontrol'] = $_SESSION['user'];
        $this->data['users'] = $this->userManager->getAllUsers();
    }

    // zmenit roli uzivatele
    private function changeRole(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $user_id = trim($_POST['user_id'] ?? '');

        $user_role = trim($_POST['role'] ?? '');
        $user_roles = ['admin', 'superadmin', 'autor', 'recenzent'];

        if (empty($user_id) || empty($user_role)) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatné data.'], 400);
        }

        // validace role
        if (!in_array($user_role, $user_roles)) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatná role.'], 400);
        }

        // zmenit roli
        if ($this->userManager->changeUserRole((int)$user_id, $user_role)) {
            $this->jsonResponse(['success' => true, 'message' => 'Role uživatele byla úspěšně změněna.']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Nepodařilo se změnit roli uživatele.'], 500);
        }


    }

    // zmenit status uzivatele
    private function toggleStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $user_id = trim($_POST['user_id'] ?? '');
        $user_status = trim($_POST['status'] ?? '');

        // validace dat
        if (empty($user_id) || !in_array($user_status, [0, 1])) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatné data.'], 400);
        }

        // zmenit status
        if ($this->userManager->toggleStatus((int)$user_id, $user_status)) {
            $this->jsonResponse(['success' => true, 'message' => 'Uživatel byl úspěšně za/odblokován.']);
        } else {
            $this->jsonResponse(['success' => true, 'message' => 'Operace se nezdařila.']);
        }
    }
}