<?php
/*
|--------------------------------------------------------------------------
| Global Layout Wrapper - main.php
|--------------------------------------------------------------------------
| This wrapper ensures that every view is displayed inside a structured
| layout with a header and footer.
|
| Path: public/layouts/main.php
*/

require_once __DIR__ . '/../layouts/header.php';
?>

<main class="content">
    <?php include $view; ?>
</main>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
