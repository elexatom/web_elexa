<?php

namespace App\Controllers\Apis;

use App\Models\Db;

class UsersApi extends Api
{

    public function handle(array $params, string $method): void
    {
        header('Content-Type: application/json');

        $conn = Db::database();

        $conn->prepare("SELECT * FROM `uzivatele`");

    }
}