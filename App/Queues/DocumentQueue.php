<?php

namespace App\Queues;

use DocumentManager\Services\FileStorage;
use Psr\Log\LoggerInterface;

class DocumentQueue
{
    private FileStorage $fileStorage;
    private string $queueFile;
    private LoggerInterface $logger;
    private const MAX_RETRY_ATTEMPTS = 3;

    public function __construct(FileStorage $fileStorage, string $queueFile, LoggerInterface $logger)
    {
        $this->fileStorage = $fileStorage;
        $this->queueFile = $queueFile;
        $this->logger = $logger;
    }

    public function push(array $document): void
    {
        $queue = $this->getQueue();
        $document['attempts'] = 0;
        $queue[] = $document;
        $this->saveQueue($queue);
        $this->logger->info('Document added to queue', $document);
    }

    public function process(): void
    {
        $queue = $this->getQueue();
        foreach ($queue as $index => $document) {
            try {
                $success = $this->fileStorage->storeFile($document['file_path'], $document['destination']);

                if ($success) {
                    unset($queue[$index]);
                    $this->logger->info('Document processed successfully', $document);
                } else {
                    $queue[$index]['attempts']++;
                    $this->logger->warning('Document processing failed, retrying...', [
                        'document' => $document,
                        'attempts' => $queue[$index]['attempts'],
                    ]);
                }

                if ($queue[$index]['attempts'] >= self::MAX_RETRY_ATTEMPTS) {
                    $this->logger->error('Max retry attempts reached for document', $document);
                    unset($queue[$index]);
                }
            } catch (\Exception $e) {
                $this->logger->error('Error processing document', [
                    'document' => $document,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->saveQueue(array_values($queue));
    }

    private function getQueue(): array
    {
        if (!file_exists($this->queueFile)) {
            return [];
        }
        return json_decode(file_get_contents($this->queueFile), true) ?? [];
    }

    private function saveQueue(array $queue): void
    {
        file_put_contents($this->queueFile, json_encode($queue, JSON_PRETTY_PRINT));
    }
}
