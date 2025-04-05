<?php if (empty($notifications)): ?>
<div class="text-center py-6">
    <div class="text-gray-400 text-4xl mb-3">
        <i class="fas fa-bell-slash"></i>
    </div>
    <p class="text-gray-500">Nie masz żadnych powiadomień</p>
</div>
<?php else: ?>
<div class="space-y-4" x-data="{
    markAsRead(id) {
        htmx.ajax('POST', '/notifications/mark-read', {
            values: { notification_id: id },
            target: '#notification-' + id,
            swap: 'outerHTML'
        });
    }
}">
    <?php foreach ($notifications as $notification): 
        $isUnread = !$notification->read_at;
        $notificationClass = $isUnread ? 'bg-blue-50 unread-notification' : '';
        $iconClass = 'fas fa-bell';
        $iconColorClass = 'text-blue-600';
        
        // Set appropriate icon and color based on notification type
        switch ($notification->type) {
            case 'booking_confirmation':
                $iconClass = 'fas fa-calendar-check';
                $iconColorClass = 'text-green-600';
                break;
            case 'booking_canceled':
                $iconClass = 'fas fa-calendar-times';
                $iconColorClass = 'text-red-600';
                break;
            case 'payment_received':
                $iconClass = 'fas fa-money-bill-wave';
                $iconColorClass = 'text-green-600';
                break;
            case 'system':
                $iconClass = 'fas fa-cog';
                $iconColorClass = 'text-gray-600';
                break;
        }
        
        // Format the notification date
        $createdAt = new DateTime($notification->created_at);
        $now = new DateTime();
        $interval = $createdAt->diff($now);
        
        if ($interval->d < 1) {
            if ($interval->h < 1) {
                $timeAgo = $interval->i . ' min temu';
            } else {
                $timeAgo = $interval->h . ' godz. temu';
            }
        } else {
            $timeAgo = $createdAt->format('d.m.Y H:i');
        }
    ?>
    <div id="notification-<?= $notification->id ?>" class="flex items-start p-3 rounded-lg <?= $notificationClass ?>">
        <div class="flex-shrink-0">
            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="<?= $iconClass ?> <?= $iconColorClass ?>"></i>
            </div>
        </div>
        <div class="ml-3 flex-1">
            <div class="flex justify-between items-start">
                <p class="text-sm text-gray-800 font-medium"><?= htmlspecialchars($notification->title) ?></p>
                <?php if ($isUnread): ?>
                <button @click="markAsRead(<?= $notification->id ?>)" class="text-xs text-blue-600 hover:text-blue-800">
                    <i class="fas fa-check"></i> Oznacz jako przeczytane
                </button>
                <?php endif; ?>
            </div>
            <p class="text-xs text-gray-500"><?= htmlspecialchars($notification->message) ?></p>
            <p class="text-xs text-gray-400 mt-1"><?= $timeAgo ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
