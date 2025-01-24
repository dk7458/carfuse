<?php
require_once  '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'functions/email.php';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potwierdzenie Rezerwacji</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?= generateEmailHeader() ?>
        <p>Szanowny/a <?= htmlspecialchars($data['name']) ?>,</p>
        <p>Dziękujemy za dokonanie rezerwacji! Twoja rezerwacja została pomyślnie przetworzona.</p>
        <p><strong>Szczegóły rezerwacji:</strong></p>
        <ul>
            <li>Samochód: <?= htmlspecialchars($data['car']) ?></li>
            <li>Data odbioru: <?= htmlspecialchars($data['pickup_date']) ?></li>
            <li>Data zwrotu: <?= htmlspecialchars($data['dropoff_date']) ?></li>
            <li>Łączna cena: <?= htmlspecialchars($data['price']) ?> PLN</li>
        </ul>
        <p>Możesz zobaczyć lub pobrać swoją umowę, klikając poniższy link:</p>
        <a href="<?= htmlspecialchars($data['contract_link']) ?>" class="button">Zobacz umowę</a>
        <p>Cieszymy się, że możemy Cię obsłużyć. W razie pytań zapraszamy do kontaktu.</p>
        <p>Z poważaniem,<br>Zespół Carfuse</p>
    </div>
</body>
</html>
