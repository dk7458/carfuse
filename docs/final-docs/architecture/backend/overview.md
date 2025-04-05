# Backend Architecture Overview

*Last updated: 2023-11-15*

This document provides a comprehensive overview of the CarFuse backend architecture, explaining its structure, key components, and design patterns.

## Table of Contents
- [Architecture Principles](#architecture-principles)
- [System Architecture](#system-architecture)
- [Core Components](#core-components)
- [Data Flow](#data-flow)
- [Directory Structure](#directory-structure)
- [Key Technologies](#key-technologies)
- [Related Documentation](#related-documentation)

## Architecture Principles

The CarFuse backend is built on the following core principles:

1. **Modular Design**: Separation of concerns with independent modules
2. **API-First**: Well-defined API contracts for all services
3. **Security by Default**: Security built into all layers
4. **Testability**: Components designed for automated testing
5. **Scalability**: Horizontal scaling of independent services
6. **Maintainability**: Clean code patterns and comprehensive documentation

## System Architecture

The backend follows a layered architecture:

1. **Presentation Layer**: API endpoints and controllers
2. **Business Logic Layer**: Services and domain logic
3. **Data Access Layer**: Repositories and data mappers
4. **Infrastructure Layer**: Database, caching, external services

## Core Components

### Controllers
Handle HTTP requests and delegate to appropriate services.

```php
class BookingController
{
    public function store(BookingRequest $request)
    {
        $booking = $this->bookingService->createBooking(
            $request->validated()
        );
        
        return new BookingResource($booking);
    }
}
```

### Services
Contain business logic and orchestrate operations.

```php
class BookingService
{
    public function createBooking(array $data)
    {
        // Validate availability
        $this->vehicleService->checkAvailability($data['vehicle_id'], $data['start_date'], $data['end_date']);
        
        // Create booking
        $booking = $this->bookingRepository->create($data);
        
        // Process payment
        $this->paymentService->processPayment($booking, $data['payment_method_id']);
        
        // Send notifications
        $this->notificationService->sendBookingConfirmation($booking);
        
        return $booking;
    }
}
```

### Repositories
Handle data persistence and retrieval.

```php
class BookingRepository
{
    public function getActiveBookings($userId)
    {
        return Booking::where('user_id', $userId)
                     ->where('status', 'active')
                     ->get();
    }
    
    public function create(array $data)
    {
        return Booking::create($data);
    }
}
```

### Models
Represent database entities and relationships.

```php
class Booking extends Model
{
    protected $fillable = [
        'user_id', 'vehicle_id', 'start_date', 'end_date', 'status', 'total_price'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
```

## Data Flow

Typical request flow:

1. Request arrives at API endpoint
2. Controller validates input and authorizes request
3. Controller delegates to appropriate service(s)
4. Service implements business logic
5. Service uses repositories for data operations
6. Repository interacts with database/models
7. Data flows back through layers
8. Controller returns appropriate response

## Directory Structure

```
/app
  /Http
    /Controllers     # Request handlers
    /Middleware      # Request filters
    /Requests        # Request validation
    /Resources       # API resource transformers
  /Services          # Business logic
  /Repositories      # Data access
  /Models            # Database models
  /Events            # Event definitions
  /Listeners         # Event handlers
  /Jobs              # Background jobs
  /Exceptions        # Custom exceptions
  /Providers         # Service providers
/config              # Configuration files
/database
  /migrations        # Database schema
  /factories         # Test factories
/routes              # API routes
/tests               # Automated tests
```

## Key Technologies

- **PHP 8.1+**: Core programming language
- **Laravel**: Web application framework
- **MySQL**: Primary database
- **Redis**: Caching and queues
- **JSON API**: API response format
- **JWT**: Stateless authentication

## Related Documentation

- [API Overview](../../api/overview.md)
- [Database Schema](database-schema.md)
- [Authentication](../../security/authentication.md)
- [Integration Patterns](../integration/api-communication.md)
- [Deployment Guide](../../development/guides/deployment.md)
