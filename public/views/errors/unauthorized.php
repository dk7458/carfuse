<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarFuse - Brak dostępu</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8 text-center">
        <div class="text-red-500 mb-6">
            <i class="fas fa-lock text-6xl"></i>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-800 mb-3">Odmowa dostępu</h1>
        
        <p class="text-gray-600 mb-6">
            Nie masz uprawnień do wyświetlenia tej strony. Skontaktuj się z administratorem lub wróć do poprzedniej strony.
        </p>
        
        <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4">
            <a href="/" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                <i class="fas fa-home mr-2"></i> Strona główna
            </a>
            <button onclick="history.back()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Wróć
            </button>
        </div>
    </div>
</body>
</html>
