<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Ustawienia systemowe</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Konfiguracja systemu</h3>
    </div>

    <!-- Formularz ustawień systemowych -->
    <form id="settingsForm" class="mt-4">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="site_name" class="form-label">Nazwa strony</label>
            <input type="text" id="site_name" name="site_name" class="form-control" placeholder="Podaj nazwę strony" value="<?= esc($settings['site_name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="admin_email" class="form-label">E-mail administratora</label>
            <input type="email" id="admin_email" name="admin_email" class="form-control" placeholder="Podaj e-mail administratora" value="<?= esc($settings['admin_email'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="timezone" class="form-label">Strefa czasowa</label>
            <select id="timezone" name="timezone" class="form-select">
                <?php foreach (timezone_identifiers_list() as $tz): ?>
                    <option value="<?= esc($tz) ?>" <?= ($settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= esc($tz) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" <?= isset($settings['maintenance_mode']) && $settings['maintenance_mode'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="maintenance_mode">Tryb konserwacji</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Zapisz zmiany</button>
    </form>

    <div id="responseMessage" class="mt-3"></div>
</div>

<script src="/js/admin.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
