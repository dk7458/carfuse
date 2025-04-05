// Existing routes...

// Profile routes
$app->get('/profile', [\App\Controllers\UserController::class, 'showProfilePage']);
$app->post('/profile/update', [\App\Controllers\UserController::class, 'updateProfile']);
$app->post('/user/change-password', [\App\Controllers\UserController::class, 'changePassword']);
