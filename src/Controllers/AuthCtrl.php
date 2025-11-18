<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * Controller pro autorizaci uživatelů (přihlášení, registrace, odhlášení)
 */
class AuthCtrl extends Controller
{
    public function process(array $params): void
    {
        $action = $params[0] ?? 'login'; // defaultne login (/auth === /auth/login)

        // pokud je uzivatel jiz prihlasen, redir. na home
        if (!empty($_SESSION['user']) || (!empty($_SESSION['loggedin']) && $_SESSION['loggedin'] === true)) {
            if (in_array($action, ['login', 'register'])) {
                $this->redirect('/');
            }
        }

        // prihlaseni uzivatele
        if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                $this->redirect('/');
            }

            $email_raw = $_POST["email"] ?? '';
            $email = filter_var($email_raw, FILTER_VALIDATE_EMAIL);
            $password = $_POST["password"] ?? '';

            $user = $this->userManager->getUserByEmail($email); // ziskat data o uzivateli

            if ($user['schvaleno'] === 0) { // ucet ceka na schvaleni, redir. informacni stranka
                $this->redirect('/auth/welcome');
            } elseif ($user && password_verify($password, $user['heslo'])) { // overit udaje
                session_regenerate_id(); // obnovit session id - session hijacking
                $_SESSION['user'] = $user; // nastavit session flags
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_id'] = $user['userid'];
                $_SESSION['loggedin'] = true;
                $this->redirect('/');
            } else { // neplatne udaje
                $_SESSION['error'] = "Neplatný email nebo heslo.";
                $this->redirect('/auth');
            }
        }

        // odhlaseni uzivatele
        if ($action === 'logout') {
            session_unset();
            session_regenerate_id(true);
            session_destroy(); // znicit session a presmerovat na home
            $this->redirect('/');
        }

        // registrace uzivatele
        if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') $this->handleRegister();

        // preserve zpravy - aby nedoslo k form resubmitu
        if (isset($_SESSION['error'])) $this->data['error'] = $_SESSION['error'];

        $this->header['title'] = "Auth | DigiArch";
        $this->view = $action;
    }

    // registrace uzivatele
    public function handleRegister(): void
    {
        $nickname = trim($_POST['nick'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $errors = [];

        // Validace nicku
        if (!preg_match('/^[A-Za-z][A-Za-z0-9\-]{4,14}$/', $nickname)) {
            $errors[] = "Nick musí mít 5–15 znaků, začínat písmenem a obsahovat jen písmena, čísla nebo '-'.";
        }
        // Validace jmena
        if (!preg_match('#^[A-Za-z]*\s[A-Za-z]*$#', $username)) {
            $errors[] = "Neplatné jméno";
        }
        // Validace emailu
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Zadejte platný email.";
        }

        // Validace hesla
        if (strlen($password) < 8) {
            $errors[] = "Heslo musí mít alespoň 8 znaků.";
        }
        if (!preg_match('/[a-z]/u', $password)) {
            $errors[] = "Heslo musí obsahovat malé písmeno.";
        }
        if (!preg_match('/[A-Z]/u', $password)) {
            $errors[] = "Heslo musí obsahovat velké písmeno.";
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = "Heslo musí obsahovat číslo.";
        }
        if (!preg_match('/[^A-Za-z0-9]/u', $password)) {
            $errors[] = "Heslo musí obsahovat speciální znak.";
        }
        if ($password !== $confirm) {
            $errors[] = "Hesla se neshodují.";
        }

        if (!empty($errors)) {
            $this->data = [
                'errors' => $errors,
                'form' => [
                    'nick' => $nickname,
                    'username' => $username,
                    'email' => $email
                ]
            ];
            return;
        }

        // zapis v db
        $user_account = $this->userManager->createUser($username, $nickname, $email, $password);

        if (empty($user_account)) { // kontrola duplicity uctu
            $this->data = [
                'errors' => ["Tento účet již existuje."],
            ];
        } else $this->redirect('/auth/welcome');
    }
}