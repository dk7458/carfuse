<?php
require '../../includes/db_connect.php';
require '../../includes/functions.php';

session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/login.php');
}

// Set session timeout
$timeout = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    redirect('/login.php');
}
$_SESSION['last_activity'] = time();

$userId = $_SESSION['user_id'];

// Fetch user bookings
$bookings = $conn->query("
    SELECT b.id, f.make, f.model, f.registration_number, b.pickup_date, b.dropoff_date, b.total_price, b.status, b.rental_contract_pdf 
    FROM bookings b 
    JOIN fleet f ON b.vehicle_id = f.id 
    WHERE b.user_id = $userId
    ORDER BY b.created_at DESC
");

// Fetch user details
$userDetails = $conn->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();

// Fetch user documents
$userDocuments = glob("../../uploads/users/$userId/*.{pdf}", GLOB_BRACE);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Użytkownika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/theme.css" rel="stylesheet">

    <style>
        body {
            overflow-y: scroll;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.9.359/pdf.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="#bookings" class="list-group-item list-group-item-action active" data-bs-toggle="collapse" aria-expanded="true">Rezerwacje</a>
                    <a href="#profile" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Profil</a>
                    <a href="#personal-data" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Zmień Dane Osobowe</a>
                    <a href="#reset-password" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Zresetuj Hasło</a>
                    <a href="#documents" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Twoje Dokumenty</a>
                </div>
            </div>
            <div class="col-md-9">
                <div id="bookings" class="collapse show">
                    <h1 class="text-center">Twoje Rezerwacje</h1>
                    <p class="text-center">Zarządzaj swoimi rezerwacjami poniżej.</p>

                    <?php if ($bookings->num_rows > 0): ?>
                        <table class="table table-bordered mt-4">
                            <thead>
                                <tr>
                                    <th>ID Rezerwacji</th>
                                    <th>Pojazd</th>
                                    <th>Numer Rejestracyjny</th>
                                    <th>Data Odbioru</th>
                                    <th>Data Zwrotu</th>
                                    <th>Cena Całkowita</th>
                                    <th>Status</th>
                                    <th>Umowa</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $booking['id']; ?></td>
                                        <td><?php echo "{$booking['make']} {$booking['model']}"; ?></td>
                                        <td><?php echo $booking['registration_number']; ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($booking['pickup_date'])); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($booking['dropoff_date'])); ?></td>
                                        <td><?php echo number_format($booking['total_price'], 2, ',', ' '); ?> PLN</td>
                                        <td><?php echo ucfirst($booking['status']); ?></td>
                                        <td>
                                            <?php if ($booking['rental_contract_pdf']): ?>
                                                <a href="<?php echo $booking['rental_contract_pdf']; ?>" target="_blank">Pobierz</a>
                                            <?php else: ?>
                                                Niedostępna
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($booking['status'] === 'active'): ?>
                                                <a href="/controllers/booking_controller.php?action=cancel&id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Czy na pewno chcesz anulować tę rezerwację?');">Anuluj</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info text-center mt-4">
                            Nie masz żadnych aktywnych rezerwacji.
                        </div>
                    <?php endif; ?>
                </div>

                <div id="profile" class="collapse">
                    <h2 class="mt-5">Twój Profil</h2>
                    <div class="card p-4">
                        <h2>Dane Osobowe</h2>
                        <p><strong>Imię:</strong> <span><?php echo $userDetails['name']; ?></span></p>
                        <p><strong>Nazwisko:</strong> <span><?php echo $userDetails['surname']; ?></span></p>
                        <p><strong>E-mail:</strong> <span><?php echo $userDetails['email']; ?></span></p>
                        <p><strong>Adres:</strong> <span><?php echo $userDetails['address']; ?></span></p>
                        <p><strong>PESEL lub Numer Dowodu:</strong> <span><?php echo $userDetails['pesel_or_id']; ?></span></p>
                        <a href="#personal-data" class="btn btn-primary mt-3" data-bs-toggle="collapse" aria-expanded="false">Zmień Dane Osobowe</a>
                    </div>
                </div>

                <div id="personal-data" class="collapse">
                    <h2 class="mt-5">Zmień Dane Osobowe</h2>
                    <form action="/user/user_controller_proxy.php" method="POST" class="standard-form">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-3">
                            <label for="name" class="form-label">Imię</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo $userDetails['name']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="surname" class="form-label">Nazwisko</label>
                            <input type="text" id="surname" name="surname" class="form-control" value="<?php echo $userDetails['surname']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo $userDetails['email']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="address_part1" class="form-label">Adres - Część 1</label>
                            <input type="text" id="address_part1" name="address_part1" class="form-control" value="<?php echo explode(' ', $userDetails['address'])[0]; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="address_part2" class="form-label">Adres - Część 2</label>
                            <input type="text" id="address_part2" name="address_part2" class="form-control" value="<?php echo implode(' ', array_slice(explode(' ', $userDetails['address']), 1)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="pesel_or_id" class="form-label">PESEL lub Numer Dowodu</label>
                            <input type="text" id="pesel_or_id" name="pesel_or_id" class="form-control" value="<?php echo $userDetails['pesel_or_id']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefon</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo $userDetails['phone']; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Zapisz Zmiany</button>
                    </form>
                </div>

                <div id="reset-password" class="collapse">
                    <h2 class="mt-5">Zresetuj Hasło</h2>
                    <form action="/user/user_controller_proxy.php" method="POST" class="standard-form">
                        <input type="hidden" name="action" value="reset_password">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Obecne Hasło</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nowe Hasło</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Potwierdź Nowe Hasło</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Zresetuj Hasło</button>
                    </form>
                </div>

                <div id="documents" class="collapse">
                    <h2 class="mt-5">Twoje Dokumenty</h2>
                    <?php if (!empty($userDocuments)): ?>
                        <ul class="list-group">
                            <?php foreach ($userDocuments as $document): ?>
                                <li class="list-group-item">
                                    <a href="<?php echo $document; ?>" target="_blank"><?php echo basename($document); ?></a>
                                    <div id="pdf-viewer-<?php echo basename($document, ".pdf"); ?>" class="pdf-viewer"></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-info">Brak dokumentów do wyświetlenia.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for displaying responses -->
    <div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="responseModalLabel">Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="responseMessage">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.standard-form').on('submit', function(event) {
                event.preventDefault();
                var form = $(this);
                $.ajax({
                    type: form.attr('method'),
                    url: form.attr('action'),
                    data: form.serialize(),
                    success: function(response) {
                        console.log(response); // Log the response for debugging
                        try {
                            var jsonResponse = JSON.parse(response);
                            var message = jsonResponse.success || jsonResponse.error;
                            $('#responseMessage').text(message);
                            $('#responseModal').modal('show');

                            if (jsonResponse.success) {
                                // Update profile section with the latest data
                                $.ajax({
                                    url: '/user/get_user_details.php',
                                    method: 'GET',
                                    success: function(data) {
                                        var userDetails = JSON.parse(data);
                                        $('#profile .card p').each(function() {
                                            var field = $(this).find('strong').text().toLowerCase().replace(':', '');
                                            $(this).find('span').text(userDetails[field]);
                                        });
                                    }
                                });
                            }
                        } catch (e) {
                            console.error('Invalid JSON response:', response);
                            $('#responseMessage').text('An error occurred. Please try again.');
                            $('#responseModal').modal('show');
                        }
                    },
                    error: function() {
                        $('#responseMessage').text('An error occurred. Please try again.');
                        $('#responseModal').modal('show');
                    }
                });
            });

            document.querySelectorAll('.pdf-viewer').forEach(viewer => {
                const url = viewer.previousElementSibling.href;
                const loadingTask = pdfjsLib.getDocument(url);
                loadingTask.promise.then(pdf => {
                    pdf.getPage(1).then(page => {
                        const scale = 1.5;
                        const viewport = page.getViewport({ scale: scale });
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        viewer.appendChild(canvas);
                        const renderContext = {
                            canvasContext: context,
                            viewport: viewport
                        };
                        page.render(renderContext);
                    });
                });
            });

            document.querySelectorAll('.list-group-item-action').forEach(item => {
                item.addEventListener('click', function () {
                    document.querySelectorAll('.collapse').forEach(collapse => {
                        if (collapse.id !== this.getAttribute('href').substring(1)) {
                            collapse.classList.remove('show');
                        }
                    });
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
