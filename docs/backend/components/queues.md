# Queues

## Overview
The CarFuse queueing system provides persistent storage for operations that may fail and require retries. Each queue is implemented as a JSON file storage system that maintains state across application restarts. The system supports operation retries with configurable attempt limits and comprehensive logging.

## Queue Configuration

Each queue type has its own configuration:

- **NotificationQueue**: Uses a fixed retry limit (3 attempts)
- **DocumentQueue**: More configurable with:
  - Custom queue file location 
  - Configurable retry attempts
  - Default fallback values if configuration is missing

## Available Queues

### Notification Queue

**Purpose:** Manages outgoing notifications to users with guaranteed delivery through retry mechanisms.

**Message Structure:**
```php
[
    'user_id'  => int,    // Target user identifier
    'type'     => string, // Notification type
    'message'  => string, // Notification content
    'options'  => array,  // Additional parameters
    'attempts' => int     // Retry counter (managed internally)
]
```

**Producer Implementation:**
- Add notifications to queue using `push()` method
- Automatically initializes retry counter to 0

```php
$notificationQueue->push([
    'user_id' => 123,
    'type' => 'appointment_reminder',
    'message' => 'Your appointment is tomorrow',
    'options' => ['priority' => 'high']
]);
```

**Consumer Implementation:**
- Process queue with `process()` method
- Internally uses NotificationService to send notifications
- Removes successful messages from queue
- Increments attempt counter for failed messages

**Error Handling:**
- Failed notifications are retried up to 3 times
- Comprehensive logging of queue operations and failures
- Notifications exceeding retry limits are removed and logged as errors

### Document Queue

**Purpose:** Manages document storage operations with configurable retry capability for handling storage service outages.

**Message Structure:**
```php
[
    'destination_path' => string, // Storage location path
    'file_name'        => string, // Name of the file
    'content'          => mixed,  // Content to be stored
    'attempts'         => int     // Retry counter (managed internally)
]
```

**Producer Implementation:**
- Add documents to queue using `push()` method
- Automatically initializes retry counter to 0

```php
$documentQueue->push([
    'destination_path' => '/user/123/documents/',
    'file_name' => 'contract.pdf',
    'content' => $pdfContent
]);
```

**Consumer Implementation:**
- Process queue with `process()` method
- Uses FileStorage service to store documents
- Removes successful operations from queue
- Increments attempt counter for failed operations

**Error Handling:**
- Failed storage operations are retried based on configured max attempts
- Comprehensive logging of queue operations and failures
- Document operations exceeding retry limits are removed and logged as errors

## Integration with Other Components

```
┌───────────────┐     ┌───────────────┐     ┌───────────────┐
│   Services    │     │    Queues     │     │  Consumers    │
│  (Producers)  │     │               │     │               │
│               │     │               │     │               │
│ User Service  │────►│ Notification  │────►│ Notification  │
│ Admin Panel   │     │    Queue      │     │   Service     │
│               │     │               │     │               │
│ Upload Service│────►│   Document    │────►│ File Storage  │
│ Import Module │     │    Queue      │     │   Service     │
└───────────────┘     └───────────────┘     └───────────────┘
```

### Integration Points

- **NotificationQueue**:
  - Integrates with `NotificationService` for sending notifications
  - Can be used by any component that needs to notify users
  - Provides asynchronous notification capabilities to synchronous operations

- **DocumentQueue**:
  - Integrates with `FileStorage` for storing documents
  - Used by file upload features and document generators
  - Handles temporary outages in storage services

## Processing Jobs

Queue processing should be triggered regularly through a scheduled task:

```php
// Example cron job or scheduled task
function processQueuedJobs() {
    $notificationQueue->process();
    $documentQueue->process();
}
```

Best practices:
- Process queues at regular intervals (e.g., every minute)
- Implement monitoring to detect growing queue sizes
- Handle processing exceptions to prevent job processor failures
