<?php

namespace App\Models;

class UserManager
{
    public function getAllUsers(): array
    {
        return Db::query('
        SELECT * FROM `uzivatele`
        ');
    }

    public function getUserByEmail(string $email): array | bool
    {
        return Db::query('
        SELECT * FROM `uzivatele` WHERE email = ? LIMIT 1
        ', [$email], true);
    }
}