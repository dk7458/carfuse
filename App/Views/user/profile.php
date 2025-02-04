<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Mój profil</h1>

<div class="profile-container">
    <div class="card shadow p-4">
        <h3 class="text-center">Dane użytkownika</h3>
        <form id="profileForm">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="name" class="form-label">Imię i nazwisko</label>
                <input type="text" id="name" name="name" class="form-control" required value="<?= esc($user->name) ?>">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Adres e-mail</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?= esc($user->email) ?>">
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Numer telefonu</label>
                <input type="tel" id="phone" name="phone" class="form-control" required value="<?= esc($user->phone) ?>" pattern="[0-9]{9,}">
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Adres</label>
                <textarea id="address" name="address" class="form-control" rows="2" required><?= esc($user->address) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">Zapisz zmiany</button>
        </form>
    </div>
</div>

<script src="/js/user_profile.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
