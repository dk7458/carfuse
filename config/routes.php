<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Middleware\AuthMiddleware;
use App\Middleware\TokenValidationMiddleware;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use Slim\App;

return function (App $app) {
    // Ensure the AuthController is instantiated
    $authController = $app->getContainer()->get(AuthController::class);

    // Ensure the UserController is instantiated
    $userController = $app->getContainer()->get(UserController::class);

    // Define routes
    $app->group('/auth', function () use ($authController) {
        $this->post('/login', [$authController, 'login']);
        $this->post('/register', [$authController, 'register']);
        $this->post('/refresh', [$authController, 'refresh']);
        $this->post('/logout', [$authController, 'logout']);
        $this->get('/user', [$authController, 'userDetails']);
    })->add($authController->middleware());

    $app->group('/users', function () use ($userController) {
        $this->get('/profile', [$userController, 'getUserProfile']);
        $this->post('/updateProfile', [$userController, 'updateProfile']);
    })->add($userController->middleware());

    // Define Public View Routes
    $app->get('/', fn() => require '../public/views/home.php');
    $app->get('/dashboard', fn() => require '../public/views/dashboard.php');
    $app->get('/profile', fn() => require '../public/views/user/profile.php');
    $app->get('/reports', fn() => require '../public/views/user/reports.php');
    $app->get('/auth/login', fn() => require '../public/views/auth/login.php');
    $app->get('/auth/register', fn() => require '../public/views/auth/register.php');
    $app->get('/auth/password_reset', fn() => require '../public/views/auth/password_reset.php');
    $app->get('/documents/signing_page', fn() => require '../public/views/documents/signing_page.php');

    // Define API Routes with Authentication and Middleware
    $app->post('/api/auth/login', [AuthController::class, 'login']);
    $app->post('/api/auth/register', [AuthController::class, 'register']);
    $app->post('/api/auth/refresh', [AuthController::class, 'refresh']);
    $app->post('/api/auth/logout', [AuthController::class, 'logout']);
    $app->get('/api/auth/userDetails', [AuthController::class, 'userDetails'])->add(AuthMiddleware::class);

    // Protected API Routes (Require Authentication)
    $app->get('/api/user/profile', [UserController::class, 'getUserProfile'])->add(AuthMiddleware::class);
    $app->post('/api/user/updateProfile', [UserController::class, 'updateProfile'])->add(AuthMiddleware::class);

    $app->get('/api/user/settings', function (Request $request, RequestHandler $handler) {
        return (new TokenValidationMiddleware())->__invoke($request, $handler);
    });

    $app->get('/api/user/notifications', function (Request $request, RequestHandler $handler) {
        return (new TokenValidationMiddleware())->__invoke($request, $handler);
    });

    $app->get('/api/dashboard/metrics', function (Request $request, RequestHandler $handler) {
        return (new TokenValidationMiddleware())->__invoke($request, $handler);
    });

    $app->get('/api/dashboard/reports', function (Request $request, RequestHandler $handler) {
        return (new TokenValidationMiddleware())->__invoke($request, $handler);
    });

    // Booking API Routes
    $app->post('/api/bookings/create', 'App\Controllers\BookingController@createBooking');
    $app->get('/api/bookings/view/{id:\d+}', 'App\Controllers\BookingController@viewBooking');
    $app->post('/api/bookings/cancel/{id:\d+}', 'App\Controllers\BookingController@cancelBooking');
    $app->post('/api/bookings/reschedule/{id:\d+}', 'App\Controllers\BookingController@rescheduleBooking');

    // Payment API Routes
    $app->post('/api/payments/process', 'App\Controllers\PaymentController@processPayment');
    $app->post('/api/payments/refund/{id:\d+}', 'App\Controllers\PaymentController@refundPayment');
    $app->get('/api/payments/history', 'App\Controllers\PaymentController@paymentHistory');

    // Report API Routes
    $app->post('/api/reports/generate', 'App\Controllers\ReportController@generateReport');
    $app->get('/api/reports/view/{id:\d+}', 'App\Controllers\ReportController@viewReport');

    // Admin API Routes
    $app->get('/api/admin/users', function (Request $request, RequestHandler $handler) {
        return (new AuthMiddleware())->__invoke($request, $handler, 'admin');
    });

    $app->get('/api/admin/dashboard', function (Request $request, RequestHandler $handler) {
        return (new AuthMiddleware())->__invoke($request, $handler, 'admin');
    });

    $app->get('/api/admin/logs', function (Request $request, RequestHandler $handler) {
        return (new AuthMiddleware())->__invoke($request, $handler, 'admin');
    });

    // Document API Routes
    $app->post('/api/documents/upload', 'App\Controllers\DocumentController@uploadDocument');
    $app->post('/api/documents/sign', 'App\Controllers\DocumentController@signDocument');
    $app->get('/api/documents/view/{id:\d+}', 'App\Controllers\DocumentController@viewDocument');

    // System API Routes
    $app->get('/api/system/logs', function (Request $request, RequestHandler $handler) {
        return (new AuthMiddleware())->__invoke($request, $handler, 'admin');
    });

    $app->get('/api/system/status', function (Request $request, RequestHandler $handler) {
        return (new TokenValidationMiddleware())->__invoke($request, $handler);
    });

    // Catch-All for Unmatched Requests
    $app->map(['GET', 'POST'], '/{any:.+}', function () {
        http_response_code(404);
        echo json_encode(["error" => "Not Found"]);
    });
};
