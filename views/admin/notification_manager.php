<?php
// File Path: /views/admin/notification_manager.php
// Description: Allows admins to view, filter, resend, delete notifications, and manage the notification queue.
// Changelog:
// - Added notification queue management (processing, retrying, and deleting queued notifications).
// - Enhanced UI with tabs for better navigation.
// - Added support for scheduling push notifications.
// - Added options for configuring maintenance reminder templates.

require_once __DIR__ . '/../../includes/session_middleware.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/notification_helpers.php';
require_once __DIR__ . '/../../includes/functions.php';

enforceRole(['admin', 'super_admin']);

// Fetch filters for notifications
$type = $_GET['type'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Fetch notifications
$notifications = fetchNotifications($conn, $type, $startDate, $endDate, $search, $offset, $itemsPerPage);
$totalNotifications = countNotifications($conn, $type, $startDate, $endDate, $search);
$totalPages = ceil($totalNotifications / $itemsPerPage);

// Fetch queued notifications
$queueResult = $conn->query("SELECT * FROM notification_queue WHERE status = 'pending'");
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Menadżer Powiadomień</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include '../shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <h1>Menadżer Powiadomień</h1>

        <!-- Tabs for Notifications and Queue -->
        <ul class="nav nav-tabs mt-5">
            <li class="nav-item">
                <a class="nav-link active" id="notifications-tab" href="#notifications" data-bs-toggle="tab">Powiadomienia</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="queue-tab" href="#queue" data-bs-toggle="tab">Kolejka Powiadomień</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="templates-tab" href="#templates" data-bs-toggle="tab">Szablony Przypomnień</a>
            </li>
        </ul>

        <div class="tab-content mt-4">
            <!-- Notifications -->
            <div class="tab-pane fade show active" id="notifications">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">Wszystkie</option>
                            <option value="email" <?= $type === 'email' ? 'selected' : '' ?>>E-mail</option>
                            <option value="sms" <?= $type === 'sms' ? 'selected' : '' ?>>SMS</option>
                            <option value="push" <?= $type === 'push' ? 'selected' : '' ?>>Push</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Szukaj po odbiorcy" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Filtruj</button>
                    </div>
                </form>

                <table class="table table-bordered mt-4">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Typ</th>
                            <th>Odbiorca</th>
                            <th>Treść</th>
                            <th>Status</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($notifications->num_rows > 0): ?>
                            <?php while ($notification = $notifications->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($notification['sent_at']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($notification['type'])) ?></td>
                                    <td><?= htmlspecialchars($notification['recipient']) ?></td>
                                    <td><?= htmlspecialchars($notification['message']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($notification['status'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info resend-notification" data-id="<?= $notification['id'] ?>">Wyślij Ponownie</button>
                                        <button class="btn btn-sm btn-danger delete-notification" data-id="<?= $notification['id'] ?>">Usuń</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Brak powiadomień.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <nav>
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Poprzednia</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page === $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Następna</a>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Notification Queue -->
            <div class="tab-pane fade" id="queue">
                <h3 class="mt-4">Kolejka Powiadomień</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Typ</th>
                            <th>Treść</th>
                            <th>Status</th>
                            <th>Próby</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($queueResult && $queueResult->num_rows > 0): ?>
                            <?php while ($queuedNotification = $queueResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($queuedNotification['id']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($queuedNotification['type'])) ?></td>
                                    <td><?= htmlspecialchars($queuedNotification['message']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($queuedNotification['status'])) ?></td>
                                    <td><?= htmlspecialchars($queuedNotification['retry_count']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-success process-queue" data-id="<?= $queuedNotification['id'] ?>">Przetwórz</button>
                                        <button class="btn btn-sm btn-danger delete-queue" data-id="<?= $queuedNotification['id'] ?>">Usuń</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Brak powiadomień w kolejce.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Reminder Templates -->
            <div class="tab-pane fade" id="templates">
                <h3 class="mt-4">Szablony Przypomnień</h3>
                <form id="reminderTemplateForm" class="row g-3 mt-4">
                    <div class="col-md-6">
                        <label for="template_name" class="form-label">Nazwa Szablonu:</label>
                        <input type="text" name="template_name" id="template_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="template_content" class="form-label">Treść Szablonu:</label>
                        <textarea name="template_content" id="template_content" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="col-md-2 mt-4">
                        <button type="submit" class="btn btn-primary w-100">Zapisz</button>
                    </div>
                </form>

                <!-- Existing Templates -->
                <div class="mt-5">
                    <h3>Istniejące Szablony</h3>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nazwa Szablonu</th>
                                <th>Treść</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $templatesResult = $conn->query("SELECT * FROM reminder_templates");
                            if ($templatesResult->num_rows > 0): ?>
                                <?php while ($template = $templatesResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($template['id']) ?></td>
                                        <td><?= htmlspecialchars($template['name']) ?></td>
                                        <td><?= htmlspecialchars($template['content']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info edit-template" data-id="<?= $template['id'] ?>" data-name="<?= htmlspecialchars($template['name']) ?>" data-content="<?= htmlspecialchars($template['content']) ?>">Edytuj</button>
                                            <button class="btn btn-sm btn-danger delete-template" data-id="<?= $template['id'] ?>">Usuń</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Brak szablonów przypomnień.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/notification_manager.js"></script>
</body>
</html>
