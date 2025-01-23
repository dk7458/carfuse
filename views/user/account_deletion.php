<?php

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . '../../includes/functions.php';

?>

<div class="container">
    <h2 class="mt-5 text-danger">Usuń Konto</h2>
    <p class="text-center text-danger">Usunięcie konta jest nieodwracalne i spowoduje utratę wszystkich danych związanych z Twoim kontem, w tym rezerwacji, metod płatności, i dokumentów.</p>

    <form method="POST" action="/controllers/account_controller.php" class="mt-4 ajax-form">
        <input type="hidden" name="action" value="delete_account">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="mb-3">
            <label for="confirmation" class="form-label">Wpisz <strong>DELETE</strong>, aby potwierdzić:</label>
            <input type="text" id="confirmation" name="confirmation" class="form-control" placeholder="DELETE" required>
        </div>

        <button type="submit" class="btn btn-danger">Usuń Konto</button>
    </form>
</div>

