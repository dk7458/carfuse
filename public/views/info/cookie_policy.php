<?php
require_once __DIR__ . '/../vendor/autoload.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie & Data Policy</title>
</head>
<body>
    <h2>Cookie & Data Storage Policy</h2>
    <p>We use cookies to improve user experience and store minimal user data for security.</p>
    <p>By clicking "Accept", you consent to our use of cookies and data storage as outlined in our privacy policy.</p>
    
    <button id="accept-cookies">Accept</button>
    <button id="revoke-consent">Revoke Consent</button>

    <script type="module">
        import CookieHandler from '/js/cookies.js';

        const cookieHandler = new CookieHandler();
        cookieHandler.setLanguage('pl'); // Example: Set language to Polish

        document.getElementById("accept-cookies").addEventListener("click", function () {
            cookieHandler.acceptConsent();
        });

        document.getElementById("revoke-consent").addEventListener("click", function () {
            cookieHandler.revokeConsent();
        });
    </script>
</body>
</html>
