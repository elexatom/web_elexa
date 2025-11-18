<?php
declare(strict_types=1);

namespace App\Controllers;

use Ramsey\Uuid\Uuid;

/**
 * Controller pro spravu profilu uzivatele
 */
class ProfileCtrl extends Controller
{
    /**
     * @param $twig
     */
    public function __construct($twig)
    {
        parent::__construct($twig);

        // uzivatel musi byt prihlasen
        if (!isset($_SESSION['user'])) $this->redirect('/auth/login');
    }

    public function process(array $params): void
    {
        switch ($params[0] ?? '') {
            case 'update-name':
                $this->updateName();
                break;

            case 'update-nick':
                $this->updateNick();
                break;

            case 'update-password':
                $this->updatePassword();
                break;

            case 'update-picture':
                $this->uploadPicture();
                break;

            default:
                break;
        }

        $this->view = 'profile';
        $this->data['user'] = $_SESSION['user'];
        $this->header['title'] = "Váš Profil | DigiArch"; // hlavicka stranky
    }

    // zmenit jmeno
    private function updateName(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $jmeno = htmlspecialchars(trim($_POST['jmeno'] ?? ''), ENT_QUOTES, 'UTF-8');

        // validace
        if (empty($jmeno) || !preg_match('/^[A-Za-z]*\s[A-Za-z]*$/', $jmeno) || strlen($jmeno) < 3) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatné jméno.'], 400);
        }

        // aktualizace v databazi
        if ($this->userManager->updateJmeno($_SESSION['user_id'], $jmeno)) {
            $_SESSION['user']['jmeno'] = $jmeno; // update session flagu
            $this->jsonResponse(['success' => true, 'message' => 'Jméno bylo úspěšně změněno.', 'jmeno' => $jmeno]);
        } else $this->jsonResponse(['success' => false, 'message' => 'Nepodařilo se změnit jméno.'], 500);
    }

    // update nicku
    private function updateNick(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        $nick = htmlspecialchars(trim($_POST['nick'] ?? ''), ENT_QUOTES, 'UTF-8');

        // validace
        if (empty($nick) ||
            !preg_match('/^[A-Za-z][A-Za-z0-9\-]*$/', $nick)
            || strlen($nick) < 5 || strlen($nick) > 15
        ) $this->jsonResponse(['success' => false, 'message' => 'Neplatný nick.'], 400);

        // kontrola duplicity - nick je unique
        if ($this->userManager->nickExists($nick, $_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Tento nick již existuje.'], 400);
        }

        // update nicku
        if ($this->userManager->updateNick($_SESSION['user_id'], $nick)) {
            $_SESSION['user']['nick'] = $nick; // update session flagu
            $this->jsonResponse(['success' => true, 'message' => 'Nick byl úspěšně změněn.', 'nick' => $nick]);
        } else $this->jsonResponse(['success' => false, 'message' => 'Nepodařilo se změnit nick.'], 500);
    }

    // update hesla
    private function updatePassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz'], 405);
        }

        $cur_pwd = $_POST['current_pwd'] ?? '';
        $new_pwd = $_POST['new_pwd'] ?? '';
        $conf_pwd = $_POST['conf_pwd'] ?? '';
        $user_id = $_SESSION['user_id'];

        // validace
        if (empty($cur_pwd) || empty($new_pwd) || empty($conf_pwd)) {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatné data.'], 400);
        }

        // validace aktualniho hesla
        if (!$this->userManager->verifyCurrentPwd($user_id, $cur_pwd)) {
            $this->jsonResponse(['success' => false, 'message' => 'Současné heslo je nesprávné.'],
                400);
        }

        // validace noveho
        if (strlen($new_pwd) < 8) {
            $this->jsonResponse(['success' => false, 'message' => 'Nové heslo musí mít alespoň 8 znaků.'],
                400);
        }

        // validace velkych/malych pismen
        if (!preg_match('/[a-z]/', $new_pwd) || !preg_match('/[A-Z]/', $new_pwd)) {
            $this->jsonResponse(['success' => false, 'message' => 'Nové heslo musí obsahovat malé a velké písmeno.'],
                400);
        }

        // validace cisla a spec. znaku
        if (!preg_match('/[0-9]/', $new_pwd) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_pwd)) {
            $this->jsonResponse(['success' => false, 'message' => 'Nové heslo musí obsahovat číslo a spec. znak.'],
                400);
        }

        // kontrola confirmu
        if ($new_pwd !== $conf_pwd) {
            $this->jsonResponse(['success' => false, 'message' => 'Nová hesla se neshodují.'], 400);
        }

        // update hesla
        if ($this->userManager->updatePassword($user_id, $new_pwd)) {
            $this->jsonResponse(['success' => true, 'message' => 'Heslo bylo úspěšně změněno.']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Nepodařilo se změnit heslo.'], 500);
        }
    }

    // nahrani profilove fotky
    private function uploadPicture(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Neplatný dotaz.'], 405);
        }

        // validace souboru
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['success' => false, 'message' => 'Nepodařilo se nahrát obrázek.']);
        }

        $file_pic = $_FILES['profile_picture'];

        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']; // povolene formaty
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // ziskame typ souboru
        $mime_type = finfo_file($finfo, $file_pic['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) { // validace formatu
            $this->jsonResponse(['success' => false, 'message' => 'Nepodporovaný formát obrázku. Použijte PNG, JPG, JPEG nebo WEBP.'], 400);
        }

        // validace velikosti (max 10MB)
        if ($file_pic['size'] > 10 * 1024 * 1024) {
            $this->jsonResponse(['success' => false, 'message' => 'Obrázek je příliš velký. Maximum je 10MB.'], 400);
        }

        // vytvorit upload dir pokud neexistuje
        $upload_dir = __DIR__ . '/../../public/uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // generovat unikatni jmeno souboru
        $extension = pathinfo($file_pic['name'], PATHINFO_EXTENSION);
        $filename = Uuid::uuid4()->toString() . '.' . $extension;
        $destination = $upload_dir . $filename;

        // presunout nahrany soubor
        if (move_uploaded_file($file_pic['tmp_name'], $destination)) {
            $profile_pic_path = '/public/uploads/profiles/' . $filename;

            // smazat starou pokud existuje
            if (!empty($_SESSION['user']['profile_picture'])) {
                $old_file = __DIR__ . '/../../public' . $_SESSION['user']['profile_picture'];
                if (file_exists($old_file)) unlink($old_file);
            }

            // update v databazi
            $user_id = $_SESSION['user_id'];
            if ($this->userManager->updateProfilePic($user_id, $profile_pic_path)) {
                $_SESSION['user']['profile_picture'] = $profile_pic_path; // update session flagu

                $this->jsonResponse(['success' => true, 'message' => 'Fotka byla úspěšně změněna.']);
            } else $this->jsonResponse(['success' => false, 'message' => 'Fotku se nepodařilo uložit.'], 400);
        } else $this->jsonResponse(['success' => false, 'message' => 'Fotku se nepodařilo uložit.'], 400);
    }
}