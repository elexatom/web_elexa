<?php

namespace App\Controllers\Apis;

use App\Models\Db;
use PDO;

abstract class Api
{
    abstract public function handle(array $params, string $method): void;
}