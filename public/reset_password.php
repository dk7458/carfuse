$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeEmail($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Nieprawidłowy adres e-mail.";
    } else {
        // Check if the email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $_SESSION['error_message'] = "E-mail nie istnieje w systemie.";
        } else {
            // Generate a reset token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $insertToken = $conn->prepare("
                INSERT INTO password_resets (email, token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $insertToken->bind_param("sss", $email, $token, $expiresAt);
            $insertToken->execute();

            // Send reset email
            $resetLink = "http://yourdomain.com/public/process_reset.php?token=$token";
            $message = "
                <h1>Resetowanie Hasła</h1>
                <p>Kliknij poniższy link, aby zresetować swoje hasło:</p>
                <a href='$resetLink'>$resetLink</a>
                <p>Link wygaśnie za 1 godzinę.</p>
            ";
            sendEmail($email, "Resetowanie Hasła", $message);

            $_SESSION['success_message'] = "Link do resetowania hasła został wysłany na Twój adres e-mail.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetowanie Hasła</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Resetowanie Hasła</h1>
        <?php include '../views/shared/messages.php'; ?>
        <form method="POST" action="" class="standard-form">
            <div class="mb-3">
                <label for="email" class="form-label">Adres e-mail</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Wyślij Link Resetowania</button>
        </form>
    </div>
</body>
</html>
