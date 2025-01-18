<?php
require '../includes/db_connect.php';
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfuse - Wynajem Samochodów</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../views/shared/navbar.php'; ?>

    <div class="hero" style="background: url('https://source.unsplash.com/1600x900/?car,travel') no-repeat center center; background-size: cover; height: 100vh;">
        <div class="d-flex align-items-center justify-content-center flex-column text-center text-white" style="height: 100%;">
            <h1 class="display-3">Odkryj Nowe Horyzonty</h1>
            <p class="lead">Najlepsze samochody na każdą podróż</p>
            <a href="/public/register.php" class="btn btn-primary btn-lg">Zarejestruj się teraz</a>
        </div>
    </div>

    <div class="container text-center py-5">
        <h2>Dlaczego warto nas wybrać?</h2>
        <div class="row mt-4">
            <div class="col-md-4">
                <i class="bi bi-car-front-fill" style="font-size: 3rem; color: #007bff;"></i>
                <h4 class="mt-3">Szeroki Wybór</h4>
                <p>Wybierz z różnorodnych pojazdów dostosowanych do Twoich potrzeb.</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-currency-dollar" style="font-size: 3rem; color: #007bff;"></i>
                <h4 class="mt-3">Przystępne Ceny</h4>
                <p>Najwyższa jakość w rozsądnej cenie.</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-shield-check" style="font-size: 3rem; color: #007bff;"></i>
                <h4 class="mt-3">Zaufana Obsługa</h4>
                <p>Bezpieczny wynajem z najlepszą obsługą klienta.</p>
            </div>
        </div>
    </div>

    <?php include '../views/shared/footer.php'; ?>
</body>
</html>
