<?php
// Path: App/Views/landing.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfuse - Rent a Car Effortlessly</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        /* Dark Minimal Theme */
        body {
            background-color: #121212;
            color: #fff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #181818;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        nav {
            display: flex;
            justify-content: center;
            padding: 15px 0;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 15px;
            font-size: 18px;
        }
        nav a:hover {
            color: #FFD700;
        }
        .hero {
            text-align: center;
            padding: 100px 20px;
            background: url('/assets/images/hero-bg.jpg') no-repeat center center/cover;
        }
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.8);
        }
        .hero p {
            font-size: 20px;
            opacity: 0.8;
            margin-bottom: 30px;
        }
        .search-form {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .search-form input, .search-form button {
            padding: 12px;
            font-size: 16px;
            border-radius: 5px;
            border: none;
        }
        .search-form button {
            background: #FFD700;
            color: #121212;
            font-weight: bold;
            cursor: pointer;
        }
        .search-form button:hover {
            background: #FFC107;
        }
        .features {
            text-align: center;
            padding: 60px 20px;
        }
        .features h2 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        .feature-list {
            display: flex;
            justify-content: center;
            gap: 50px;
        }
        .feature {
            font-size: 18px;
            background: #181818;
            padding: 20px;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background: #181818;
            opacity: 0.8;
        }
    </style>
</head>
<body>

<header>
    Carfuse
</header>

<nav>
    <a href="#home">Home</a>
    <a href="#features">Why Choose Us</a>
    <a href="login">Login</a>
    <a href="register">Sign Up</a>
</nav>

<section class="hero">
    <h1>Find the Perfect Car for Your Journey</h1>
    <p>Flexible rentals, best prices, and 24/7 support.</p>
    <form class="search-form" action="/search" method="GET">
        <input type="text" name="location" placeholder="Enter Pickup Location">
        <input type="date" name="pickup_date">
        <input type="date" name="return_date">
        <button type="submit">Search Cars</button>
    </form>
</section>

<section class="features">
    <h2>Why Choose Carfuse?</h2>
    <div class="feature-list">
        <div class="feature">✔ Best Prices Guaranteed</div>
        <div class="feature">✔ 24/7 Customer Support</div>
        <div class="feature">✔ Flexible Rental Periods</div>
    </div>
</section>

<footer class="footer">
    &copy; 2024 Carfuse. All Rights Reserved.
</footer>

</body>
</html>
