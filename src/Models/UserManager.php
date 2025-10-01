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
            if ($e->getCode() === '23000') { // duplikat
                return false;
            }

            // necekany error
            error_log("DB error in createUser: " . $e->getMessage());
            return false;
        }
    }
}