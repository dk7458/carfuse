# API Communication

*Last updated: 2023-11-15*

This document explains the communication patterns between frontend and backend systems in the CarFuse application.

## Table of Contents
- [Communication Overview](#communication-overview)
- [Request/Response Cycle](#requestresponse-cycle)
- [Authentication Flow](#authentication-flow)
- [Data Fetching Patterns](#data-fetching-patterns)
- [Error Handling](#error-handling)
- [Optimization Techniques](#optimization-techniques)
- [Related Documentation](#related-documentation)

## Communication Overview

CarFuse uses a RESTful API architecture for frontend-backend communication:

- **Protocol**: HTTPS
- **Format**: JSON for requests and responses
- **Authentication**: Bearer tokens + CSRF protection
- **Versioning**: URL-based versioning (/api/v1/...)

## Request/Response Cycle

### Basic Request Flow

1. Frontend prepares request with appropriate headers
2. Backend validates request (authentication, CSRF, input)
3. Backend processes request and prepares response
4. Frontend receives and handles response

```javascript
// Frontend request example
async function fetchVehicles(filters) {
  const response = await CarFuseAPI.get('/vehicles', {
    params: filters
  });
  
  return response.data;
}
```

### API Service

The frontend uses a centralized API service:

```javascript
// API service implementation
const CarFuseAPI = {
  // Base configuration
  baseURL: '/api/v1',
  
  // Request methods
  async get(endpoint, options = {}) {
    return this.request('GET', endpoint, null, options);
  },
  
  async post(endpoint, data, options = {}) {
    return this.request('POST', endpoint, data, options);
  },
  
  // Core request method with error handling
  async request(method, endpoint, data = null, options = {}) {
    const url = this.baseURL + endpoint;
    const headers = this.getHeaders(options);
    
    try {
      const response = await fetch(url, {
        method,
        headers,
        body: data ? JSON.stringify(data) : null
      });
      
      const responseData = await response.json();
      
      if (!response.ok) {
        throw new ApiError(responseData.error, response.status);
      }
      
      return responseData;
    } catch (error) {
      this.handleError(error);
      throw error;
    }
  }
};
```

## Authentication Flow

### Login Process

1. Frontend collects credentials
2. POST request to `/api/auth/login`
3. Backend validates credentials and generates token
4. Token returned to frontend and stored
5. Subsequent requests include token

```javascript
// Login sequence
async function login(email, password) {
  try {
    const response = await CarFuseAPI.post('/auth/login', {
      email,
      password
    });
    
    // Store token
    localStorage.setItem('auth_token', response.data.token);
    
    // Update application state
    CarFuseSecurity.setAuthenticated(true);
    
    return response.data.user;
  } catch (error) {
    throw new Error('Authentication failed: ' + error.message);
  }
}
```

### Request Authentication

All authenticated requests include the token:

```javascript
// Request headers
function getHeaders(options = {}) {
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  };
  
  // Add authentication if available
  const token = localStorage.getItem('auth_token');
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  
  // Add CSRF token for non-GET requests
  if (options.method !== 'GET') {
    headers['X-CSRF-TOKEN'] = getCsrfToken();
  }
  
  return headers;
}
```

## Data Fetching Patterns

### Resource Fetching

```javascript
// Fetch a specific resource
async function getVehicle(id) {
  return CarFuseAPI.get(`/vehicles/${id}`);
}

// Fetch a collection with filters
async function searchVehicles(params) {
  return CarFuseAPI.get('/vehicles', { params });
}
```

### Data Mutations

```javascript
// Create a resource
async function createBooking(bookingData) {
  return CarFuseAPI.post('/bookings', bookingData);
}

// Update a resource
async function updateBooking(id, bookingData) {
  return CarFuseAPI.put(`/bookings/${id}`, bookingData);
}

// Delete a resource
async function cancelBooking(id) {
  return CarFuseAPI.delete(`/bookings/${id}`);
}
```

## Error Handling

### Frontend Error Handling

```javascript
// API Error class
class ApiError extends Error {
  constructor(errorData, status) {
    super(errorData.message || 'API Error');
    this.name = 'ApiError';
    this.status = status;
    this.code = errorData.code;
    this.details = errorData.details || {};
  }
}

// Error handling in components
async function handleSubmit() {
  try {
    const result = await createBooking(formData);
    showSuccess('Booking created successfully');
  } catch (error) {
    if (error instanceof ApiError) {
      // Handle specific API errors
      if (error.status === 422) {
        handleValidationErrors(error.details);
      } else if (error.status === 403) {
        redirectToLogin();
      } else {
        showError(error.message);
      }
    } else {
      // Handle network or unknown errors
      showError('An unexpected error occurred');
      console.error(error);
    }
  }
}
```

## Optimization Techniques

### Request Caching

```javascript
// Simple cache implementation
const cache = new Map();

async function getCachedData(url, ttl = 60000) {
  const cacheKey = url;
  const cached = cache.get(cacheKey);
  
  if (cached && Date.now() - cached.timestamp < ttl) {
    return cached.data;
  }
  
  const data = await CarFuseAPI.get(url);
  cache.set(cacheKey, {
    data,
    timestamp: Date.now()
  });
  
  return data;
}
```

### Request Batching

```javascript
// For related resources
async function getVehicleWithDetails(id) {
  return CarFuseAPI.get(`/vehicles/${id}?include=features,location,reviews`);
}
```

## Related Documentation

- [API Overview](../../api/overview.md)
- [Authentication Security](../../security/authentication.md)
- [CSRF Protection](../../security/csrf-protection.md)
- [Frontend Architecture](../frontend/overview.md)
- [Backend Architecture](../backend/overview.md)
