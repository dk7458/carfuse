<?php
/*
|--------------------------------------------------------------------------
| CarFuse Homepage
|--------------------------------------------------------------------------
| This is now the landing page and entry point for the application.
|
| Path: public/index.php
*/

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

$view = __DIR__ . '/views/home.php'; // Home page content
include __DIR__ . '/layouts/main.php';
?>
