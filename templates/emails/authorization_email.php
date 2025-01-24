<?php
require_once  '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'functions/email.php';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Autoryzacyjny</title>
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
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?= generateEmailHeader() ?>
        <p>Szanowny/a <?= htmlspecialchars($data['name']) ?>,</p>
        <p>Poprosiłeś/aś o wykonanie akcji, która wymaga dodatkowej autoryzacji. Prosimy potwierdzić swoje żądanie, klikając poniższy przycisk:</p>
        <a href="<?= htmlspecialchars($data['authorization_link']) ?>" class="button">Autoryzuj Żądanie</a>
        <p>Jeśli nie prosiłeś/aś o tę operację, zignoruj tę wiadomość lub skontaktuj się z naszym zespołem wsparcia.</p>
        <p>Z poważaniem,<br>Zespół Carfuse</p>
        <p style="font-size: 0.9em; color: #777;">Uwaga: Ten e-mail został wygenerowany automatycznie. Prosimy nie odpowiadać na tę wiadomość.</p>
    </div>
</body>
</html>
