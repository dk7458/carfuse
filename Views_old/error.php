/*
|--------------------------------------------------------------------------
| Strona Błędów - Obsługa 404, 500 i innych problemów
|--------------------------------------------------------------------------
| Strona wyświetla kreatywny komunikat błędu z tłem przedstawiającym
| autobus komunikacji miejskiej. Obsługuje różne kody błędów.
|
| Ścieżka: App/Views/error.php
*/

<?php


// Pobranie kodu błędu, jeśli nie przekazano, ustaw na 404
$errorCode = isset($_GET['code']) ? intval($_GET['code']) : 404;

// Lista domyślnych komunikatów dla kodów błędów
$errorMessages = [
    403 => "Nie masz uprawnień do tej strony. Może jednak lepiej zostać pasażerem?",
    404 => "Nie udało nam się odnaleźć tej strony. Może została zabrana na pętlę?",
    500 => "Coś poszło nie tak po naszej stronie. Pracujemy nad tym jak kierowca przy zmianie trasy.",
    503 => "System chwilowo niedostępny. Autobus wróci do trasy niebawem!",
];

// Pobranie komunikatu lub ustawienie domyślnego
$errorMessage = $errorMessages[$errorCode] ?? "Wystąpił nieznany błąd. Może to znak, by zrobić sobie przerwę? ☕";
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zamiast jazdy mamy zjazd</title>
    <link rel="stylesheet" href="/css/main.min.css">
    <style>
        body {
            background: url('/images/bus-error.jpg') no-repeat center center fixed;
            background-size: cover;
            text-align: center;
            color: white;
            font-family: Arial, sans-serif;
        }
        .error-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.7);
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        .error-code {
            font-size: 1rem;
            color: #ffcc00;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #ffcc00;
            color: black;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .btn:hover {
            background: #e6b800;
        }
    </style>
</head>
<body>

<div class="error-container">
    <h1>Zamiast jazdy mamy zjazd.</h1>
    <p><?= $errorMessage ?></p>
    <p class="error-code">Kod błędu: <?= $errorCode ?></p>
    <a href="/" class="btn">Powrót na stronę główną</a>
</div>

</body>
</html>
