<?php
/*
|--------------------------------------------------------------------------
| Header - Dynamiczny Nagłówek dla Wszystkich Widoków
|--------------------------------------------------------------------------
| Plik przełącza się dynamicznie pomiędzy stroną główną, pulpitem użytkownika
| oraz pulpitem administratora. Wyświetla odpowiednie skróty i powitanie.
|
| Ścieżka: public/layouts/header.php
*/

if (!isset($_SESSION)) {
    session_start();
}

// Ensure the header is included only once
if (!empty($_SESSION['layout_loaded'])) {
    return;
}
$_SESSION['layout_loaded'] = true;

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$username = $isLoggedIn ? $_SESSION['username'] : "Gość";

// Pobranie losowego modelu auta z bazy, jeśli są dostępne
$availableCars = ["Toyota Corolla", "Ford Mustang", "BMW X5", "Audi A6", "Mercedes C-Class"];
$randomCar = $availableCars[array_rand($availableCars)];

// Lista powitań dynamicznych
$greetings = [
    "Hej, $username! $randomCar już czeka na przygodę! 🚗💨",
    "Witaj, $username! Może dziś przejażdżka $randomCar? 🌍",
    "$username, świat stoi przed Tobą otworem! $randomCar już grzeje silnik! 🏎️",
    "Gotowy na podróż, $username? $randomCar nie chce stać w miejscu! 📅",
    "$username, może czas na spontaniczny wypad? $randomCar jest gotowy! 🎒",
    "Nie czekaj, $username – $randomCar znika szybciej niż hot dogi na stacji! ⏳",
    "Witaj, $username! Może dziś coś sportowego? $randomCar czeka na rozgrzanie! 🏁",
    "Nie musisz mieć własnego auta, $username! $randomCar już czeka, by Ci służyć! 🚗",
    "Hej, $username! $randomCar to klucz do niezapomnianej podróży! 💰",
    "$username, weekend bez planu? $randomCar to zawsze dobry pomysł! 🏕️",
    "Dłuższy wyjazd? Krótki city-break? $randomCar nie pyta – jedzie! 🛣️",
    "Nie czekaj do ostatniej chwili, $username! $randomCar chce ruszać! 🔥",
    "Dziś dobry dzień na podróż, $username! $randomCar jest na to gotowy! 🏖️",
    "Niech nic Cię nie zatrzyma, $username! $randomCar tylko czeka na Twój ruch! 🚙",
    "$username, wiesz co robi różnicę? Wybór auta. Może $randomCar? 🏜️",
    "Każda podróż zaczyna się od decyzji – a $randomCar to świetny wybór! 🛤️",
    "Twój plan na dziś: rezerwacja, kluczyki, $randomCar i w drogę! 🚦",
    "$username, czas na nową trasę! $randomCar już gotowy do jazdy! 🚘",
    "Nie odkładaj marzeń na później, $username – wynajmij $randomCar i jedź! 🎯",
    "Najlepsze podróże zaczynają się od rezerwacji! Może $randomCar? 📌",
    "$randomCar mówi, że masz jeszcze czas na rezerwację… ale nie za długo! 🏁",
    "Masz misję, $username! Wsiadaj do $randomCar i ruszaj na wyprawę! 🎯",
    "Nie masz planów na weekend? $randomCar ma je za Ciebie! 🚀",
    "Czyżbyś szukał przygody, $username? $randomCar już pali się do jazdy! 🔥",
    "Twój dzień zapowiada się ciekawie, jeśli wsiądziesz do $randomCar! 🎉",
    "$username, za godzinę w mieście jest koncert. $randomCar to Twoja wejściówka! 🎶",
    "Niespodzianka! Twój $randomCar ma bagażnik pełen optymizmu! 📦😁",
    "$username, w $randomCar radio puszcza tylko najlepsze kawałki do jazdy! 🎧",
    "$randomCar mówi, że potrzebuje wakacji. Zawieź go gdzieś! 🌴",
];

$greeting = $isLoggedIn ? $greetings[array_rand($greetings)] : "Witaj w CarFuse! Wynajmij auto i ruszaj w drogę!";
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfuse - Wynajem Aut</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <script src="/public/js/main.js" defer></script>
</head>
<body>

<header>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="logo">🚗 CarFuse</a>
            <ul class="nav-links">
                <?php if (!$isLoggedIn): ?>
                    <li><a href="/auth/login">🔑 Zaloguj się</a></li>
                    <li><a href="/auth/register">📝 Zarejestruj się</a></li>
                <?php else: ?>
                    <li class="greeting"><?= $greeting ?></li>
                    <?php if ($isAdmin): ?>
                        <li><a href="/dashboard">⚙️ Panel Admina</a></li>
                    <?php else: ?>
                        <li><a href="/dashboard">📊 Panel Użytkownika</a></li>
                    <?php endif; ?>
                    <li><a href="/logout">🚪 Wyloguj się</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>
