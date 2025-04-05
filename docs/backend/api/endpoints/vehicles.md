# Vehicles API Endpoints

## Overview

The Vehicles API provides endpoints for managing the vehicle fleet, including vehicle registration, updates, deletion, search, and availability checking. These endpoints enable administrators to manage the fleet inventory and users to search and view vehicle details.

## Authentication and Permissions

| Endpoint Pattern                      | Required Role | Notes                                      |
|--------------------------------------|---------------|-------------------------------------------|
| `GET /vehicles`                       | None          | Search/list vehicles (public)              |
| `GET /vehicles/{id}`                  | None          | Get vehicle details (public)               |
| `POST /vehicles`                      | Admin         | Create a new vehicle                       |
| `PUT /vehicles/{id}`                  | Admin         | Update vehicle details                     |
| `DELETE /vehicles/{id}`               | Admin         | Delete a vehicle (soft delete)             |
| `GET /vehicles/availability/{id}`     | None          | Check vehicle availability                 |
| `GET /vehicles/types`                 | None          | Get all vehicle types (public)             |
| `PUT /vehicles/{id}/status`           | Admin         | Update vehicle status                      |
| `POST /vehicles/{id}/maintenance`     | Admin         | Record vehicle maintenance                 |
| `GET /vehicles/{id}/history`          | Admin         | Get vehicle history                        |
| `GET /vehicles/admin`                 | Admin         | Admin view of vehicles                     |

## Rate Limiting

Vehicle endpoints have the following rate limits:
- Standard tier: 100 requests per minute
- Premium tier: 500 requests per minute
- Public search: 60 requests per minute per IP

---

## Search/List Vehicles

Search for vehicles or get a paginated list of available vehicles.

### HTTP Request

`GET /vehicles`

### Query Parameters

| Parameter       | Type    | Required | Description                                | Constraints                        |
|----------------|---------|----------|--------------------------------------------|-----------------------------------|
| `page`         | Integer | No       | Page number                                | Default: 1, Min: 1                 |
| `per_page`     | Integer | No       | Items per page                             | Default: 20, Max: 50               |
| `type`         | String  | No       | Vehicle type                               | e.g., sedan, suv, truck            |
| `make`         | String  | No       | Vehicle manufacturer                       | e.g., Toyota, Honda                |
| `model`        | String  | No       | Vehicle model                              | e.g., Camry, Civic                 |
| `year`         | Integer | No       | Manufacturing year                         | Format: YYYY                       |
| `min_price`    | Float   | No       | Minimum daily rate                         | Must be >= 0                       |
| `max_price`    | Float   | No       | Maximum daily rate                         | Must be > min_price                |
| `features`     | String  | No       | Comma-separated list of features           | e.g., gps,bluetooth,backup_camera  |
| `available_from`| String  | No       | Start of availability period               | ISO 8601 format                    |
| `available_to` | String  | No       | End of availability period                 | ISO 8601 format                    |
| `location`     | String  | No       | Pick-up location                           | City name or location code         |
| `sort`         | String  | No       | Sort order                                 | Format: field:direction (e.g., price:asc) |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Vehicles retrieved successfully",
  "data": {
    "vehicles": [
      {
        "id": "v-123",
        "make": "Toyota",
        "model": "Camry",
        "year": 2022,
        "type": "sedan",
        "daily_rate": 49.99,
        "registration_number": "ABC123",
        "color": "Silver",
        "seats": 5,
        "features": ["bluetooth", "backup_camera", "cruise_control"],
        "status": "available",
        "thumbnail": "https://example.com/images/vehicles/v-123_thumb.jpg",
        "location": "New York Downtown"
      },
      {
        "id": "v-124",
        "make": "Honda",
        "model": "CR-V",
        "year": 2023,
        "type": "suv",
        "daily_rate": 69.99,
        "registration_number": "XYZ456",
        "color": "Blue",
        "seats": 5,
        "features": ["bluetooth", "navigation", "backup_camera", "cruise_control"],
        "status": "available",
        "thumbnail": "https://example.com/images/vehicles/v-124_thumb.jpg",
        "location": "New York Downtown"
      }
    ]
  },
  "meta": {
    "current_page": 1,
    "total_pages": 10,
    "total_vehicles": 187,
    "per_page": 20
  }
}
```

### Error Codes

| Status Code | Error Code              | Description                                      |
|-------------|------------------------|--------------------------------------------------|
| 400         | `INVALID_PARAMETERS`    | Invalid search parameters                        |
| 500         | `SERVER_ERROR`          | Failed to retrieve vehicles                      |

### Notes

- Public endpoint - no authentication required
- Results are filtered to show only available vehicles by default
- Multiple search criteria can be combined for narrower results
- Only basic vehicle information is included in list results
- Full details are available via the vehicle details endpoint
- Date range filtering checks for vehicles available during the entire specified period

---

## Get Vehicle Details

Retrieve detailed information for a specific vehicle.

### HTTP Request

`GET /vehicles/{id}`

### Path Parameters

| Parameter | Type   | Required | Description         | Constraints                     |
|-----------|--------|----------|---------------------|--------------------------------|
| `id`      | String | Yes      | Vehicle identifier  | Must be a valid vehicle ID      |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Vehicle details retrieved successfully",
  "data": {
    "vehicle": {
      "id": "v-123",
      "make": "Toyota",
      "model": "Camry",
      "year": 2022,
      "type": "sedan",
      "daily_rate": 49.99,
      "weekly_rate": 299.94,
      "monthly_rate": 1199.76,
      "registration_number": "ABC123",
      "color": "Silver",
      "seats": 5,
      "engine": "2.5L 4-cylinder",
      "transmission": "automatic",
      "fuel_type": "gasoline",
      "fuel_efficiency": "28 mpg city / 39 mpg highway",
      "features": [
        "bluetooth",
        "backup_camera",
        "cruise_control",
        "lane_departure_warning",
        "automatic_emergency_braking"
      ],
      "description": "A comfortable mid-size sedan with excellent fuel efficiency and modern safety features. Ideal for business trips and family vacations.",
      "status": "available",
      "mileage": 15250,
      "images": [
        "https://example.com/images/vehicles/v-123_1.jpg",
        "https://example.com/images/vehicles/v-123_2.jpg",
        "https://example.com/images/vehicles/v-123_3.jpg",
        "https://example.com/images/vehicles/v-123_4.jpg"
      ],
      "location": "New York Downtown",
      "location_coordinates": {
        "latitude": 40.7127281,
        "longitude": -74.0060152
      },
      "average_rating": 4.7,
      "reviews_count": 24,
      "insurance_options": [
        {
          "id": "ins_basic",
          "name": "Basic Coverage",
          "daily_rate": 9.99,
          "description": "Includes liability and collision coverage with $1000 deductible"
        },
        {
          "id": "ins_premium",
          "name": "Premium Coverage",
          "daily_rate": 19.99,
          "description": "Includes liability, collision, and comprehensive coverage with $500 deductible"
        }
      ]
    }
  }
}
```

### Error Codes

| Status Code | Error Code              | Description                                      |
|-------------|------------------------|--------------------------------------------------|
| 400         | `INVALID_VEHICLE_ID`    | Invalid vehicle ID format                        |
| 404         | `VEHICLE_NOT_FOUND`     | Vehicle not found                                |
| 500         | `SERVER_ERROR`          | Failed to retrieve vehicle details               |

### Notes

- Public endpoint - no authentication required
- Includes comprehensive vehicle details including photos and specifications
- Insurance options are based on vehicle type and value
- Average rating is calculated from customer reviews
- Vehicle location may be generalized for public access
- Detailed view counts are logged for analytics purposes

---

## Create Vehicle

Create a new vehicle in the system.

### HTTP Request

`POST /vehicles`

### Authentication

Requires a valid admin authentication token.

### Request Body Parameters

| Parameter              | Type    | Required | Description                      | Constraints                       |
|-----------------------|---------|----------|----------------------------------|----------------------------------|
| `make`                | String  | Yes      | Vehicle manufacturer              | Non-empty string                  |
| `model`               | String  | Yes      | Vehicle model                     | Non-empty string                  |
| `year`                | Integer | Yes      | Manufacturing year                | Between 1900 and current year + 1 |
| `type`                | String  | Yes      | Vehicle type                      | Must be a valid vehicle type      |
| `registration_number` | String  | Yes      | Registration plate               | Unique in the system              |
| `daily_rate`          | Float   | Yes      | Daily rental rate                 | Greater than 0                    |
| `weekly_rate`         | Float   | No       | Weekly rental rate               | Greater than 0                    |
| `monthly_rate`        | Float   | No       | Monthly rental rate              | Greater than 0                    |
| `color`               | String  | Yes      | Vehicle color                     | Non-empty string                  |
| `seats`               | Integer | Yes      | Number of seats                   | Between 1 and 20                  |
| `engine`              | String  | No       | Engine details                    | String                            |
| `transmission`        | String  | Yes      | Transmission type                 | Values: automatic, manual         |
| `fuel_type`           | String  | Yes      | Fuel type                         | e.g., gasoline, diesel, electric  |
| `fuel_efficiency`     | String  | No       | Fuel efficiency details           | String                            |
| `features`            | Array   | No       | Array of vehicle features         | Array of strings                  |
| `description`         | String  | No       | Vehicle description               | Max: 2000 characters              |
| `mileage`             | Integer | Yes      | Current odometer reading          | Non-negative integer              |
| `location`            | String  | Yes      | Current vehicle location          | Non-empty string                  |
| `location_coordinates`| Object  | No       | GPS coordinates                   | Contains latitude and longitude   |
| `status`              | String  | No       | Initial vehicle status            | Default: available                |
| `images`              | Array   | No       | Array of image URLs               | Array of strings                  |

### Example Request

```json
{
  "make": "Honda",
  "model": "Civic",
  "year": 2023,
  "type": "sedan",
  "registration_number": "XYZ789",
  "daily_rate": 45.99,
  "weekly_rate": 275.94,
  "monthly_rate": 1103.76,
  "color": "Red",
  "seats": 5,
  "engine": "1.5L 4-cylinder Turbo",
  "transmission": "automatic",
  "fuel_type": "gasoline",
  "fuel_efficiency": "31 mpg city / 40 mpg highway",
  "features": ["bluetooth", "navigation", "backup_camera", "lane_assist"],
  "description": "A sporty and efficient compact sedan with excellent fuel economy and modern technology features.",
  "mileage": 3500,
  "location": "Boston Downtown",
  "location_coordinates": {
    "latitude": 42.3600825,
    "longitude": -71.0588801
  },
  "status": "available"
}
```

### Response

Status code: `201 Created`

```json
{
  "status": "success",
  "message": "Vehicle created successfully",
  "data": {
    "vehicle_id": "v-125",
    "registration_number": "XYZ789"
  }
}
```

### Error Codes

| Status Code | Error Code                  | Description                                      |
|-------------|---------------------------|--------------------------------------------------|
| 400         | `VALIDATION_ERROR`         | Invalid or missing required fields                |
| 400         | `REGISTRATION_EXISTS`      | Registration number already exists                |
| 401         | `UNAUTHORIZED`             | User not authenticated                            |
| 403         | `FORBIDDEN`                | User does not have admin privileges               |
| 500         | `VEHICLE_CREATION_FAILED`  | Failed to create vehicle                          |

### Notes

- Vehicle creation is logged for audit purposes
- Registration number is validated for uniqueness
- System automatically generates a unique vehicle ID
- Weekly and monthly rates are calculated from daily rate if not provided
- Image upload is handled separately via a dedicated upload endpoint
- Vehicle status defaults to available but can be set initially to maintenance or unavailable

---

## Update Vehicle

Update an existing vehicle's details.

### HTTP Request

`PUT /vehicles/{id}`

### Authentication

Requires a valid admin authentication token.

### Path Parameters

| Parameter | Type   | Required | Description         | Constraints                     |
|-----------|--------|----------|---------------------|--------------------------------|
| `id`      | String | Yes      | Vehicle identifier  | Must be a valid vehicle ID      |

### Request Body Parameters

Parameters are the same as for vehicle creation, but all are optional. Only provided fields will be updated.

### Example Request

```json
{
  "daily_rate": 47.99,
  "weekly_rate": 287.94,
  "monthly_rate": 1151.76,
  "features": ["bluetooth", "navigation", "backup_camera", "lane_assist", "sunroof"],
  "mileage": 4500,
  "status": "maintenance"
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Vehicle updated successfully",
  "data": {
    "vehicle_id": "v-125",
    "updated_fields": ["daily_rate", "weekly_rate", "monthly_rate", "features", "mileage", "status"]
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `VALIDATION_ERROR`       | Invalid field values                              |
| 400         | `REGISTRATION_EXISTS`    | Registration number already exists                |
| 401         | `UNAUTHORIZED`           | User not authenticated                            |
| 403         | `FORBIDDEN`              | User does not have admin privileges               |
| 404         | `VEHICLE_NOT_FOUND`      | Vehicle not found                                 |
| 500         | `UPDATE_FAILED`          | Failed to update vehicle                          |

### Notes

- Only provided fields are updated; others remain unchanged
- Vehicle update is logged for audit purposes
- Status changes may affect existing bookings and require additional handling
- Registration number changes are checked for uniqueness
- Booking conflicts are checked when changing vehicle status
- Rate changes only affect future bookings, not existing ones

---

## Delete Vehicle

Remove a vehicle from the system (soft delete).

### HTTP Request

`DELETE /vehicles/{id}`

### Authentication

Requires a valid admin authentication token.

### Path Parameters

| Parameter | Type   | Required | Description         | Constraints                     |
|-----------|--------|----------|---------------------|--------------------------------|
| `id`      | String | Yes      | Vehicle identifier  | Must be a valid vehicle ID      |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Vehicle deleted successfully"
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_VEHICLE_ID`     | Invalid vehicle ID format                        |
| 401         | `UNAUTHORIZED`           | User not authenticated                            |
| 403         | `FORBIDDEN`              | User does not have admin privileges               |
| 404         | `VEHICLE_NOT_FOUND`      | Vehicle not found                                 |
| 409         | `ACTIVE_BOOKINGS`        | Vehicle has active bookings and cannot be deleted |
| 500         | `DELETE_FAILED`          | Failed to delete vehicle                          |

### Notes

- This performs a soft delete - vehicle records remain in the database but are marked as deleted
- Vehicles with active bookings cannot be deleted
- A vehicle can be restored later by an admin if needed
- Deletion is logged for audit purposes
- Historical bookings and revenue data are maintained

---

## Check Vehicle Availability

Check if a vehicle is available for a specific date range.

### HTTP Request

`GET /vehicles/availability/{id}`

### Path Parameters

| Parameter | Type   | Required | Description         | Constraints                     |
|-----------|--------|----------|---------------------|--------------------------------|
| `id`      | String | Yes      | Vehicle identifier  | Must be a valid vehicle ID      |

### Query Parameters

| Parameter     | Type   | Required | Description                | Constraints                     |
|--------------|--------|----------|----------------------------|--------------------------------|
| `start_date` | String | Yes      | Start date of rental period| ISO 8601 format                |
| `end_date`   | String | Yes      | End date of rental period  | ISO 8601 format                |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "data": {
    "vehicle_id": "v-123",
    "available": true,
    "conflicting_bookings": [],
    "pricing": {
      "daily_rate": 49.99,
      "days": 5,
      "base_amount": 249.95,
      "taxes": 20.00,
      "total": 269.95
    }
  }
}
```

If not available:
```json
{
  "status": "success",
  "data": {
    "vehicle_id": "v-123",
    "available": false,
    "conflicting_bookings": [
      {
        "start_date": "2023-07-18T10:00:00Z",
        "end_date": "2023-07-20T10:00:00Z"
      }
    ],
    "next_available_date": "2023-07-21T10:00:00Z"
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_VEHICLE_ID`     | Invalid vehicle ID format                        |
| 400         | `INVALID_DATES`          | Invalid date range or format                      |
| 404         | `VEHICLE_NOT_FOUND`      | Vehicle not found                                 |
| 500         | `SERVER_ERROR`           | Failed to check availability                      |

### Notes

- Public endpoint - no authentication required
- Checks both bookings and maintenance schedules
- Returns pricing information for the specified period when available
- Provides conflicting booking dates when unavailable
- Date range validation ensures end date is after start date
- Minimum rental period may apply based on vehicle type

---

## Get Vehicle Types

Retrieve a list of all vehicle types with descriptions.

### HTTP Request

`GET /vehicles/types`

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "data": {
    "types": [
      {
        "id": "sedan",
        "name": "Sedan",
        "description": "A standard 4-door car suitable for up to 5 passengers with trunk storage",
        "image": "https://example.com/images/types/sedan.jpg",
        "capacity": {
          "passengers": 5,
          "luggage": "2 large suitcases"
        }
      },
      {
        "id": "suv",
        "name": "SUV",
        "description": "Sport Utility Vehicle with increased ground clearance and spacious interior",
        "image": "https://example.com/images/types/suv.jpg",
        "capacity": {
          "passengers": 7,
          "luggage": "3 large suitcases"
        }
      },
      {
        "id": "compact",
        "name": "Compact",
        "description": "Small, fuel-efficient car ideal for city driving",
        "image": "https://example.com/images/types/compact.jpg",
        "capacity": {
          "passengers": 4,
          "luggage": "1 large suitcase"
        }
      },
      {
        "id": "luxury",
        "name": "Luxury",
        "description": "Premium vehicles offering superior comfort and features",
        "image": "https://example.com/images/types/luxury.jpg",
        "capacity": {
          "passengers": 5,
          "luggage": "3 large suitcases"
        }
      }
    ]
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 500         | `SERVER_ERROR`       | Failed to retrieve vehicle types                 |

### Notes

- Public endpoint - no authentication required
- Vehicle types are defined by the system and rarely change
- Vehicle types may affect pricing, insurance options, and rental policies
- Used primarily for search filtering and vehicle categorization

---

## Update Vehicle Status

Update the status of a specific vehicle.

### HTTP Request

`PUT /vehicles/{id}/status`

### Authentication

Requires a valid admin authentication token.

### Path Parameters

| Parameter | Type   | Required | Description         | Constraints                     |
|-----------|--------|----------|---------------------|--------------------------------|
| `id`      | String | Yes      | Vehicle identifier  | Must be a valid vehicle ID      |

### Request Body Parameters

| Parameter | Type   | Required | Description       | Constraints                                |
|-----------|--------|----------|-------------------|-------------------------------------------|
| `status`  | String | Yes      | New vehicle status| Values: available, unavailable, maintenance|
| `reason`  | String | No       | Reason for status change | Required for maintenance and unavailable|

### Example Request

```json
{
  "status": "maintenance",
  "reason": "Scheduled oil change and tire rotation"
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Vehicle status updated successfully",
  "data": {
    "vehicle_id": "v-123",
    "previous_status": "available",
    "new_status": "maintenance"
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_VEHICLE_ID`     | Invalid vehicle ID format                        |
| 400         | `INVALID_STATUS`         | Invalid status value                             |
| 400         | `REASON_REQUIRED`        | Reason required for maintenance/unavailable status|
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 403         | `FORBIDDEN`              | User does not have admin privileges              |
| 404         | `VEHICLE_NOT_FOUND`      | Vehicle not found                                |
| 409         | `ACTIVE_BOOKINGS`        | Vehicle has active bookings that conflict        |
| 500         | `UPDATE_FAILED`          | Failed to update vehicle status                   |

### Notes

- Status changes are logged for audit purposes
- Setting a vehicle to maintenance or unavailable checks for booking conflicts
- Status changes triggering booking conflicts require admin confirmation
- Status history is maintained for reporting purposes
- Automated notifications may be sent to affected bookings

---

## Record Vehicle Maintenance

Record maintenance activity for a vehicle.

### HTTP Request

`POST /vehicles/{id}/maintenance`

### Authentication

Requires a valid admin authentication token.

### Path Parameters

| Parameter | Type   | Required | Description         | Constraints                     |
|-----------|--------|----------|---------------------|--------------------------------|
| `id`      | String | Yes      | Vehicle identifier  | Must be a valid vehicle ID      |

### Request Body Parameters

| Parameter          | Type    | Required | Description                       | Constraints                    |
|-------------------|---------|----------|-----------------------------------|-------------------------------|
| `type`            | String  | Yes      | Maintenance type                   | e.g., repair, service, inspection|
| `description`     | String  | Yes      | Maintenance description           | Non-empty string              |
| `start_date`      | String  | Yes      | Maintenance start date            | ISO 8601 format               |
| `end_date`        | String  | No       | Expected end date                 | ISO 8601 format               |
| `cost`            | Float   | No       | Maintenance cost                  | Non-negative number           |
| `odometer`        | Integer | No       | Current odometer reading          | Non-negative integer          |
| `performed_by`    | String  | No       | Person/company performing work    | String                        |
| `parts_replaced`  | Array   | No       | List of parts replaced            | Array of strings              |
| `notes`           | String  | No       | Additional notes                  | String                        |

### Example Request

```json
{
  "type": "service",
  "description": "Regular 10,000 mile service",
  "start_date": "2023-07-15T09:00:00Z",
  "end_date": "2023-07-15T16:00:00Z",
  "cost": 249.99,
  "odometer": 10250,
  "performed_by": "AutoService Center",
  "parts_replaced": [
    "Oil filter",
    "Air filter", 
    "Wiper blades"
  ],
  "notes": "All fluids topped up, brakes inspected - 80% remaining"
}
```

### Response

Status code: `201 Created`

```json
{
  "status": "success",
  "message": "Maintenance record created successfully",
  "data": {
    "maintenance_id": 456,
    "vehicle_id": "v-123",
    "status_updated": true
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_VEHICLE_ID`     | Invalid vehicle ID format                        |
| 400         | `VALIDATION_ERROR`       | Invalid or missing required fields               |
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 403         | `FORBIDDEN`              | User does not have admin privileges              |
| 404         | `VEHICLE_NOT_FOUND`      | Vehicle not found                                |
| 500         | `RECORD_CREATION_FAILED` | Failed to create maintenance record              |

### Notes

- Maintenance recording can automatically update vehicle status
- Vehicle odometer reading is updated if provided
- Maintenance history is used for service scheduling and vehicle valuation
- Costs are tracked for financial reporting
- Maintenance records can be viewed in the vehicle history

---

## Get Vehicle History

Retrieve the history of a vehicle including bookings, maintenance, and status changes.

### HTTP Request

`GET /vehicles/{id}/history`

### Authentication

Requires a valid admin authentication token.

### Path Parameters

| Parameter | Type   | Required | Description         | Constraints                     |
|-----------|--------|----------|---------------------|--------------------------------|
| `id`      | String | Yes      | Vehicle identifier  | Must be a valid vehicle ID      |

### Query Parameters

| Parameter  | Type    | Required | Description               | Constraints                     |
|------------|---------|----------|---------------------------|--------------------------------|
| `page`     | Integer | No       | Page number               | Default: 1, Min: 1              |
| `per_page` | Integer | No       | Items per page            | Default: 20, Max: 100           |
| `type`     | String  | No       | Filter by history type    | Values: booking, maintenance, status |
| `from_date`| String  | No       | Start date for filtering  | ISO 8601 format                |
| `to_date`  | String  | No       | End date for filtering    | ISO 8601 format                |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "data": {
    "vehicle": {
      "id": "v-123",
      "make": "Toyota",
      "model": "Camry",
      "year": 2022,
      "registration_number": "ABC123"
    },
    "history": [
      {
        "type": "status_change",
        "timestamp": "2023-07-14T16:30:00Z",
        "details": {
          "from": "available",
          "to": "maintenance",
          "reason": "Scheduled maintenance",
          "changed_by": "admin@example.com"
        }
      },
      {
        "type": "maintenance",
        "timestamp": "2023-07-15T09:00:00Z",
        "details": {
          "maintenance_id": 456,
          "type": "service",
          "description": "Regular 10,000 mile service",
          "start_date": "2023-07-15T09:00:00Z",
          "end_date": "2023-07-15T16:00:00Z",
          "performed_by": "AutoService Center",
          "cost": 249.99
        }
      },
      {
        "type": "status_change",
        "timestamp": "2023-07-15T16:35:00Z",
        "details": {
          "from": "maintenance",
          "to": "available",
          "reason": "Maintenance completed",
          "changed_by": "admin@example.com"
        }
      },
      {
        "type": "booking",
        "timestamp": "2023-07-01T10:30:00Z",
        "details": {
          "booking_id": 789,
          "user_id": 456,
          "pickup_date": "2023-07-10T14:00:00Z",
          "dropoff_date": "2023-07-13T12:00:00Z",
          "status": "completed",
          "amount": 149.97,
          "user_name": "John Doe"
        }
      }
    ]
  },
  "meta": {
    "current_page": 1,
    "total_pages": 3,
    "total_items": 42,
    "per_page": 20
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_VEHICLE_ID`     | Invalid vehicle ID format                        |
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 403         | `FORBIDDEN`              | User does not have admin privileges              |
| 404         | `VEHICLE_NOT_FOUND`      | Vehicle not found                                |
| 500         | `SERVER_ERROR`           | Failed to retrieve vehicle history               |

### Notes

- Comprehensive history includes all vehicle-related events
- Events are sorted chronologically (newest first)
- History can be filtered by type for focused analysis
- Includes maintenance records, status changes, and booking history
- Personally identifiable information is limited for data protection compliance
- Detailed maintenance costs are only visible to admins
