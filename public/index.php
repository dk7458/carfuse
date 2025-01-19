<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wynajem Samochodów</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: var(--font-family);
            font-size: var(--font-size);
        }

        /* Header */
        .navbar {
            background: #e8e8e8;
        }

        .navbar .nav-link {
            color: white !important;
        }

        .navbar .nav-link:hover {
            color: var(--accent-color) !important;
        }

        .navbar-brand img {
            max-height: 50px;
        }

        /* Hero Section */
        .hero {
            background: url('https://source.unsplash.com/1600x900/?car,road') no-repeat center center;
            background-size: cover;
            height: 100vh;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
        }

        .hero .btn-primary {
            background-color: var(--accent-color);
            border: none;
            padding: 12px 24px;
            font-size: 1.2rem;
            border-radius: 50px;
            margin-top: 20px;
        }

        .hero .btn-primary:hover {
            background-color: #e64a19;
        }

        /* Features Section */
        .features {
            padding: 50px 0;
        }

        .features .feature-box {
            text-align: center;
            padding: 20px;
        }

        .features .feature-box i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--accent-color);
        }

        /* Booking Section */
        #book-now {
            margin: 50px auto;
            max-width: 800px;
        }

        .form-control {
            width: 100%;
            max-width: 400px;
            margin: 10px auto;
            padding: 10px;
            font-size: 1rem;
        }

        /* Footer */
        footer {
            background: #e8e8e8;
            color: black;
            padding: 40px 0;
            text-align: center;
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <?php include '../views/shared/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <h1 class="display-3 fw-bold">Odkryj Nowe Horyzonty</h1>
        <p class="lead">Najlepsze samochody na każdą podróż</p>
        <a href="#book-now" class="btn btn-primary">Zarezerwuj teraz</a>
    </section>

    <!-- Features Section -->
    <section id="features" class="features container text-center">
        <h2 class="fw-bold mb-5">Dlaczego My?</h2>
        <div class="row">
            <div class="col-md-4 feature-box">
                <i class="bi bi-car-front-fill"></i>
                <h4 class="mt-3">Szeroki Wybór</h4>
                <p>Wybierz z różnorodnych pojazdów dostosowanych do Twoich potrzeb.</p>
            </div>
            <div class="col-md-4 feature-box">
                <i class="bi bi-currency-dollar"></i>
                <h4 class="mt-3">Przystępne Ceny</h4>
                <p>Najwyższa jakość w rozsądnej cenie.</p>
            </div>
            <div class="col-md-4 feature-box">
                <i class="bi bi-shield-check"></i>
                <h4 class="mt-3">Zaufana Obsługa</h4>
                <p>Bezpieczny wynajem z najlepszą obsługą klienta.</p>
            </div>
        </div>
    </section>

    <!-- Booking Section -->
    <section id="book-now" class="container py-5">
        <h2 class="fw-bold text-center mb-5">Zarezerwuj Swój Pojazd</h2>
        <form method="POST" action="/public/booking_process.php" class="standard-form row g-3">
            <div class="col-md-6">
                <label for="pickup-location" class="form-label">Miejsce odbioru</label>
                <input type="text" class="form-control" id="pickup-location" name="pickupLocation" placeholder="Wprowadź lokalizację" required>
            </div>
            <div class="col-md-6">
                <label for="dropoff-location" class="form-label">Miejsce zwrotu</label>
                <input type="text" class="form-control" id="dropoff-location" name="dropoffLocation" placeholder="Wprowadź lokalizację" required>
            </div>
            <div class="col-md-6">
                <label for="pickup-date" class="form-label">Data odbioru</label>
                <input type="date" class="form-control" id="pickup-date" name="pickupDate" required>
            </div>
            <div class="col-md-6">
                <label for="dropoff-date" class="form-label">Data zwrotu</label>
                <input type="date" class="form-control" id="dropoff-date" name="dropoffDate" required>
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary btn-lg">Wyszukaj</button>
            </div>
        </form>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 Wynajem Samochodów. Wszystkie prawa zastrzeżone.</p>
    </footer>
</body>

</html>
