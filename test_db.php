<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/App/Helpers/DatabaseHelper.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\DatabaseHelper;

DatabaseHelper::getInstance();

try {
    $user = Capsule::table('users')->first();
    var_dump($user);
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage();
}
