<?php
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!isUserLoggedIn()) {
    header("Location: /login");
    exit();
}
?>

<div class="charts-container">
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="text-center">Trendy Rezerwacji</h5>
                    <canvas id="bookingTrends"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="text-center">Trendy Przychodów</h5>
                    <canvas id="revenueTrends"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/charts.js"></script>
