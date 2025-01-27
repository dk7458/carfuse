<?php

namespace App\Queues;

use App\Services\NotificationService;
use Psr\Log\LoggerInterface;

class NotificationQueue
{
    private string $queueFile;
    private NotificationService $notificationService;
    private LoggerInterface $logger;
    private const MAX_RETRY_ATTEMPTS = 3;

    public function __construct(
        NotificationService $notificationService,
        string $queueFile,
        LoggerInterface $logger
    ) {
        $this->notificationService = $notificationService;
        $this->queueFile = $queueFile;
        $this->logger = $logger;
    }

    /**
     * Push a notification onto the queue
     */
    public function push(array $notification): void
    {
        $queue = $this->getQueue();
        $notification['attempts'] = 0; // Initialize retry attempts
        $queue[] = $notification;
        $this->saveQueue($queue);
        $this->logger->info('Notification added to queue', $notification);
    }

    /**
     * Process the notification queue
     */
    public function process(): void
    {
        $queue = $this->getQueue();
        foreach ($queue as $index => $notification) {
            try {
                $success = $this->notificationService->sendNotification(
                    $notification['user_id'],
                    $notification['type'],
                    $notification['message'],
                    $notification['options']
                );

                if ($success) {
                    unset($queue[$index]); // Remove notification on success
                    $this->logger->info('Notification processed successfully', $notification);
                } else {
                    $queue[$index]['attempts']++;
                    $this->logger->warning('Notification failed, retrying...', [
                        'notification' => $notification,
                        'attempts' => $queue[$index]['attempts'],
                    ]);
                }

                // Remove notifications that exceed retry attempts
                if ($queue[$index]['attempts'] >= self::MAX_RETRY_ATTEMPTS) {
                    $this->logger->error('Max retry attempts reached for notification', $notification);
                    unset($queue[$index]);
                }
            } catch (\Exception $e) {
                $this->logger->error('Error processing notification', [
                    'notification' => $notification,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->saveQueue(array_values($queue)); // Reindex and save the queue
    }

    /**
     * Retrieve the current queue
     */
    private function getQueue(): array
    {
        if (!file_exists($this->queueFile)) {
            return [];
        }
        return json_decode(file_get_contents($this->queueFile), true) ?? [];
    }

    /**
     * Save the current queue to the file
     */
    private function saveQueue(array $queue): void
    {
        file_put_contents($this->queueFile, json_encode($queue, JSON_PRETTY_PRINT));
    }
}
