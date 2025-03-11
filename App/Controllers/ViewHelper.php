<?php

function view($viewName, $data = [])
{
    $viewPath = BASE_PATH . "/App/Views/{$viewName}.php";

    if (!file_exists($viewPath)) {
        die("❌ View not found: {$viewName}");
    }

    extract($data); // Extract data for use inside views
    require $viewPath;
}
