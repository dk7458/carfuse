<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Powiadomienia</h1>

<div class="notifications-container">
    <div class="card shadow p-4">
        <h3 class="text-center">Moje powiadomienia</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Typ</th>
                    <th>Wiadomość</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody id="notificationTable">
                <!-- Dane powiadomień ładowane dynamicznie -->
            </tbody>
        </table>
    </div>

    <div class="card shadow mt-4">
        <div class="card-body">
            <h4 class="text-center">Ustawienia powiadomień</h4>
            <form id="notificationSettingsForm">
                <?= csrf_field() ?>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="emailNotifications" name="emailNotifications">
                    <label class="form-check-label" for="emailNotifications">Włącz powiadomienia e-mail</label>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="smsNotifications" name="smsNotifications">
                    <label class="form-check-label" for="smsNotifications">Włącz powiadomienia SMS</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">Zapisz ustawienia</button>
            </form>
        </div>
    </div>
</div>

<script src="/js/user_notifications.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
