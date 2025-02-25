<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarFuse - Strona Główna</title>
    <link rel="stylesheet" href="/css/style.css">
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            fetch('/api/views/home.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('content').innerHTML = data.content;
                })
                .catch(error => {
                    document.getElementById('content').innerHTML = '<p>Error loading content. Please try again later.</p>';
                    console.error('There was a problem with the fetch operation:', error);
                });
        });
    </script>
</head>
<body>
<?php include __DIR__ . '/layouts/navbar.php'; ?>

<div id="content">
    <!-- Content will be loaded here dynamically -->
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>
<script src="/js/shared.js"></script>
</body>
</html>
