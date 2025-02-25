<?php
/*
|--------------------------------------------------------------------------
| Global Layout Wrapper - main.php
|--------------------------------------------------------------------------
| This file ensures all views have a consistent layout with a header and footer.
|
| Path: public/layouts/main.php
*/

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

// Ensure $view is set before including it
if (!isset($view) || !file_exists($view)) {
    die("Error: View file not found.");
}

// Include header
require_once __DIR__ . '/header.php';
?>

<main class="content">
    <?php include $view; ?>
</main>

<?php
// Include footer
require_once __DIR__ . '/footer.php';
?>
