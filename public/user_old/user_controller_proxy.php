<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/controllers/user_controller.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'update_profile':
        // Call the function to update the profile
        updateProfile();
        break;
    case 'reset_password':
        // Call the function to reset the password
        resetPassword();
        break;
    default:
        // Handle unknown actions
        header('Location: /public/user/dashboard.php');
        exit;
}
?>