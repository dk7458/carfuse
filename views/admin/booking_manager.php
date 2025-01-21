<td>
    <?php if ($booking['refund_status'] === 'none'): ?>
        <button class="btn btn-sm btn-warning refund-button" data-id="<?= $booking['id'] ?>" data-amount="<?= $booking['total_price'] ?>">Zwrot</button>
    <?php else: ?>
        <span class="badge bg-success">Zwrot: <?= ucfirst($booking['refund_status']) ?></span>
    <?php endif; ?>
</td>
