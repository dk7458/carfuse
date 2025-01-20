<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Błąd</title>
    <link rel="stylesheet" href="/public/css/theme.css">
</head>
<body>
    <div class="container text-center" style="margin-top: 10%;">
        <h1 class="text-danger">Coś poszło nie tak!</h1>
        <p class="lead">Przepraszamy za niedogodności. Wystąpił błąd podczas przetwarzania Twojego żądania.</p>

        <?php if (isset($_GET['message']) && !empty($_GET['message'])): ?>
            <p class="text-muted">Szczegóły: <strong><?php echo htmlspecialchars($_GET['message']); ?></strong></p>
        <?php endif; ?>

        <a href="/public/index.php" class="btn btn-primary mt-4">Wróć do strony głównej</a>
    </div>
</body>
</html>

