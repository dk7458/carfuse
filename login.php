<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ./views/user/profile.php");
    exit;
}

$pageTitle = "Logowanie - Carfuse";
include './includes/header.php';
?>

<div class="container mt-5">
    <h1>Zaloguj się</h1>
    <form action="./controllers/user_controller.php" method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Hasło</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <button type="submit" name="action" value="login" class="btn btn-primary">Zaloguj się</button>
    </form>
    <p class="mt-3">Nie masz konta? <a href="register.php">Zarejestruj się</a></p>
</div>

<?php include './includes/footer.php'; ?>
