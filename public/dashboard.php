<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: /login");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/css/shared.css">
</head>
<body>
    <h1>Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION["username"] ?? "User") ?>!</p>

    <p><a href="/profile">View Profile</a></p>
    <p><a href="/logout.php">Logout</a></p>
</body>
</html>
