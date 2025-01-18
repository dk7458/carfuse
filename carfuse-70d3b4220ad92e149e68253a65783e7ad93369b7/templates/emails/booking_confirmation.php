<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
</head>
<body>
    <h1>Thank You for Your Booking, <?= htmlspecialchars($name) ?>!</h1>
    <p>Your booking details are as follows:</p>
    <ul>
        <li><strong>Vehicle:</strong> <?= htmlspecialchars($vehicle) ?></li>
        <li><strong>Pickup Date:</strong> <?= htmlspecialchars($pickup_date) ?></li>
        <li><strong>Dropoff Date:</strong> <?= htmlspecialchars($dropoff_date) ?></li>
    </ul>
    <p>We look forward to serving you. If you have any questions, feel free to contact us.</p>
</body>
</html>
