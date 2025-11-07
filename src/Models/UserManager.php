<?php

namespace App\Models;

use PDOException;

/**
 * Manager dotazu na databazi - Uzivatele
 */
class UserManager
{
    public function getAllUsers(): array
    {
        return Db::query('
        SELECT * FROM `uzivatele`
        ');
    }

    public function updateJmeno(int $user_id, string $jmeno): bool
    {
        return (is_array(Db::query(
            'UPDATE uzivatele SET jmeno = ?  WHERE userid = ?',
            [$jmeno, $user_id]
        )));
    }

    public function nickExists(string $nick, int $vynechat_uid): bool
    {
        $result = Db::query(
            'SELECT COUNT(*) FROM uzivatele WHERE nick = ? AND userid != ?',
            [$nick, $vynechat_uid]
        );
        return isset($result[0]['COUNT(*)']) && $result[0]['COUNT(*)'] > 0;
    }

    public function updateNick(int $user_id, string $nick): bool
    {
        return (is_array(Db::query(
            "UPDATE uzivatele SET nick = ? WHERE userid = ?",
            [$nick, $user_id]
        )));
    }

    public function verifyCurrentPwd(int $user_id, string $password): bool
    {
        $result = Db::query(
            "SELECT heslo FROM uzivatele WHERE userid = ?",
            [$user_id], true
        );
        return $result['heslo'] && password_verify($password, $result['heslo']);
    }

    public function updatePassword(int $user_id, string $newPassword): bool
    {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        try {
            Db::query("UPDATE uzivatele SET heslo = ? WHERE userid = ?", [$hashed, $user_id]);
            return true;
        } catch (PDOException $e) {
            error_log("DB error in updatePassword: " . $e->getMessage());
            return false;
        }
    }

    public function updateProfilePic(int $user_id, string $profile_pic_path): bool
    {
        try {
            Db::query("UPDATE uzivatele SET profile_picture = ? WHERE userid = ?",
                [$profile_pic_path, $user_id]
            );
            return true;
        } catch (PDOException $e) {
            error_log("DB error in updateProfilePic: " . $e->getMessage());
            return false;
        }
    }

    public function getUserByEmail(string $email): array|bool
    {
        return Db::query('
        SELECT * FROM `uzivatele` WHERE email = ? LIMIT 1
        ', [$email], true);
    }

    public function createUser(string $username, string $nickname, string $email, string $password): bool|array
    {
        try {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            Db::query('
            INSERT INTO `uzivatele` (jmeno, nick, heslo, email) VALUES (?, ?, ?, ?)
            ', [$username, $nickname, $hashed, $email]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') return false; // duplikat

            // necekany error
            error_log("DB error in createUser: " . $e->getMessage());
            return false;
        }
    }

    public function changeUserRole(int $user_id, string $user_role): bool
    {
        try {
            Db::query("UPDATE uzivatele SET role = ? WHERE userid = ?", [$user_role, $user_id]);
            return true;
        } catch (PDOException $e) {
            error_log("DB error in changeUserRole: " . $e->getMessage());
            return false;
        }
    }

    public function toggleStatus(int $user_id, string $user_status): bool
    {
        try {
            Db::query("UPDATE uzivatele SET schvaleno = ? WHERE userid = ?", [$user_status, $user_id]);
            return true;
        } catch (PDOException $e) {
            error_log("DB error in toggleStatus: " . $e->getMessage());
            return false;
        }
    }
}