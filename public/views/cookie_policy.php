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

    <script>
        document.getElementById("accept-cookies").addEventListener("click", function () {
            fetch("/api/consent/accept.php", { method: "POST" })
                .then(() => {
                    document.cookie = "cookie_consent=true; path=/; max-age=31536000";
                    alert("Consent accepted.");
                });
        });

        document.getElementById("revoke-consent").addEventListener("click", function () {
            fetch("/api/consent/revoke.php", { method: "POST" })
                .then(() => {
                    document.cookie = "cookie_consent=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    alert("Consent revoked. Some site features may be disabled.");
                });
        });
    </script>
</body>
</html>
