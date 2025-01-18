<table class="table table-bordered mt-4">
    <thead>
        <tr>
            <th>ID</th>
            <th>Marka</th>
            <th>Model</th>
            <th>Numer Rejestracyjny</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($fleet as $vehicle): ?>
            <tr>
                <td><?php echo $vehicle['id']; ?></td>
                <td><?php echo htmlspecialchars($vehicle['make']); ?></td>
                <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                <td><?php echo htmlspecialchars($vehicle['registration_number']); ?></td>
                <td><?php echo $vehicle['availability'] ? 'Dostępny' : 'Niedostępny'; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

