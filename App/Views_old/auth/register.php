<?php require_once __DIR__ . '/../../Helpers/security.php'; ?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja - CarFuse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Załóż konto</h2>
                        <div id="alert-container"></div>

                        <form id="registerForm" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Imię i nazwisko</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">Podaj swoje imię i nazwisko.</div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Adres e-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Podaj poprawny adres e-mail.</div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Numer telefonu</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                                <div class="invalid-feedback">Podaj numer telefonu.</div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Adres</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                                <div class="invalid-feedback">Podaj swój adres.</div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Hasło</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <div class="invalid-feedback">Hasło musi mieć co najmniej 6 znaków.</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Zarejestruj się</button>
                        </form>

                        <div class="text-center mt-3">
                            Masz już konto? <a href="/login">Zaloguj się</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const formData = new FormData(form);
            
            fetch('/api/auth/register', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const alertContainer = document.getElementById('alert-container');
                alertContainer.innerHTML = '';
                
                if (data.status === 'success') {
                    alertContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    setTimeout(() => window.location.href = '/login', 2000);
                } else {
                    alertContainer.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(err => {
                console.error('Błąd:', err);
                alertContainer.innerHTML = `<div class="alert alert-danger">Wystąpił błąd podczas rejestracji. Spróbuj ponownie.</div>`;
            });
        });
    </script>
</body>
</html>
