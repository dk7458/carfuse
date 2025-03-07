<?php

namespace App\Queues;

use App\Services\FileStorage;
use Psr\Log\LoggerInterface;

class DocumentQueue
{
    private FileStorage $fileStorage;
    private string $queueFile;
    private LoggerInterface $logger;
    private int $maxRetryAttempts;

    public function __construct(
        LoggerInterface $logger, 
        FileStorage $fileStorage, 
        array $config
    ) {
        $this->logger = $logger;
        $this->fileStorage = $fileStorage;
        
        // Get configuration from injected config
        $this->queueFile = $config['documents']['queue']['file'] ?? __DIR__ . '/../../storage/queues/document_queue.json';
        $this->maxRetryAttempts = $config['documents']['queue']['max_retry_attempts'] ?? 3;
    }

    /**
     * Add document to processing queue
     */
    public function push(array $document): void
    {
        $queue = $this->getQueue();
        $document['attempts'] = 0;
        $queue[] = $document;
        $this->saveQueue($queue);
        $this->logger->info('Document added to queue', $document);
    }

    /**
     * Process queued documents
     */
    public function process(): void
    {
        $queue = $this->getQueue();
        foreach ($queue as $index => $document) {
            try {
                $success = $this->fileStorage->storeFile(
                    $document['destination_path'],
                    $document['file_name'],
                    $document['content']
                );

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

                if ($queue[$index]['attempts'] >= $this->maxRetryAttempts) {
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

    /**
     * Get the current document queue
     */
    private function getQueue(): array
    {
        if (!file_exists($this->queueFile)) {
            return [];
        }
        return json_decode(file_get_contents($this->queueFile), true) ?? [];
    }

    /**
     * Save the document queue
     */
    private function saveQueue(array $queue): void
    {
        file_put_contents($this->queueFile, json_encode($queue, JSON_PRETTY_PRINT));
    }
}
