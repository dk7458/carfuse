<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Vehicle;
use App\Models\Maintenance;
use App\Core\Auth;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;

class VehicleController extends Controller
{
    /**
     * Search for vehicles or get a paginated list of available vehicles
     * 
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        try {
            // Extract and validate query parameters
            $page = $request->getQueryParam('page', 1);
            $perPage = min($request->getQueryParam('per_page', 20), 50);
            
            $filters = [
                'type' => $request->getQueryParam('type'),
                'make' => $request->getQueryParam('make'),
                'model' => $request->getQueryParam('model'),
                'year' => $request->getQueryParam('year'),
                'min_price' => $request->getQueryParam('min_price'),
                'max_price' => $request->getQueryParam('max_price'),
                'features' => $request->getQueryParam('features'),
                'available_from' => $request->getQueryParam('available_from'),
                'available_to' => $request->getQueryParam('available_to'),
                'location' => $request->getQueryParam('location'),
                'sort' => $request->getQueryParam('sort')
            ];
            
            // Clean filters, removing nulls and validating inputs
            $filters = array_filter($filters);
            
            // Get vehicles based on filters
            $vehicleModel = new Vehicle();
            $result = $vehicleModel->search($filters, $page, $perPage);
            
            return $this->response([
                'status' => 'success',
                'message' => 'Vehicles retrieved successfully',
                'data' => [
                    'vehicles' => $result['vehicles']
                ],
                'meta' => [
                    'current_page' => $page,
                    'total_pages' => $result['total_pages'],
                    'total_vehicles' => $result['total'],
                    'per_page' => $perPage
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => 'Failed to retrieve vehicles'
            ], 500);
        }
    }
    
    /**
     * Get vehicle details by ID
     * 
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function show(Request $request, string $id): Response
    {
        try {
            if (!$this->isValidVehicleId($id)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'INVALID_VEHICLE_ID',
                    'message' => 'Invalid vehicle ID format'
                ], 400);
            }
            
            $vehicleModel = new Vehicle();
            $vehicle = $vehicleModel->findById($id);
            
            if (!$vehicle) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'VEHICLE_NOT_FOUND',
                    'message' => 'Vehicle not found'
                ], 404);
            }
            
            return $this->response([
                'status' => 'success',
                'message' => 'Vehicle details retrieved successfully',
                'data' => [
                    'vehicle' => $vehicle
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => 'Failed to retrieve vehicle details'
            ], 500);
        }
    }
    
    /**
     * Create a new vehicle
     * 
     * @param Request $request
     * @return Response
     */
    public function store(Request $request): Response
    {
        try {
            // Check admin authentication
            if (!Auth::isAdmin()) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'UNAUTHORIZED',
                    'message' => 'Admin privileges required'
                ], 403);
            }
            
            // Validate required fields
            $requiredFields = ['make', 'model', 'year', 'type', 'registration_number', 
                              'daily_rate', 'color', 'seats', 'transmission', 
                              'fuel_type', 'mileage', 'location'];
            
            $validationErrors = $this->validateRequiredFields($request->getBody(), $requiredFields);
            
            if (!empty($validationErrors)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'VALIDATION_ERROR',
                    'message' => 'Invalid or missing required fields',
                    'errors' => $validationErrors
                ], 400);
            }
            
            // Check if registration number already exists
            $vehicleModel = new Vehicle();
            if ($vehicleModel->registrationExists($request->getBody()['registration_number'])) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'REGISTRATION_EXISTS',
                    'message' => 'Registration number already exists'
                ], 400);
            }
            
            // Create vehicle
            $vehicleId = $vehicleModel->create($request->getBody());
            
            return $this->response([
                'status' => 'success',
                'message' => 'Vehicle created successfully',
                'data' => [
                    'vehicle_id' => $vehicleId,
                    'registration_number' => $request->getBody()['registration_number']
                ]
            ], 201);
        } catch (ValidationException $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'VALIDATION_ERROR',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 400);
        } catch (\Exception $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'VEHICLE_CREATION_FAILED',
                'message' => 'Failed to create vehicle'
            ], 500);
        }
    }
    
    /**
     * Update an existing vehicle
     * 
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function update(Request $request, string $id): Response
    {
        try {
            // Check admin authentication
            if (!Auth::isAdmin()) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'UNAUTHORIZED',
                    'message' => 'Admin privileges required'
                ], 403);
            }
            
            if (!$this->isValidVehicleId($id)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'INVALID_VEHICLE_ID',
                    'message' => 'Invalid vehicle ID format'
                ], 400);
            }
            
            $vehicleModel = new Vehicle();
            $vehicle = $vehicleModel->findById($id);
            
            if (!$vehicle) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'VEHICLE_NOT_FOUND',
                    'message' => 'Vehicle not found'
                ], 404);
            }
            
            // Check for registration number uniqueness if it's being updated
            $data = $request->getBody();
            if (isset($data['registration_number']) && 
                $data['registration_number'] !== $vehicle['registration_number'] &&
                $vehicleModel->registrationExists($data['registration_number'])) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'REGISTRATION_EXISTS',
                    'message' => 'Registration number already exists'
                ], 400);
            }
            
            // Update vehicle
            $updatedFields = $vehicleModel->update($id, $data);
            
            return $this->response([
                'status' => 'success',
                'message' => 'Vehicle updated successfully',
                'data' => [
                    'vehicle_id' => $id,
                    'updated_fields' => $updatedFields
                ]
            ]);
        } catch (ValidationException $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'VALIDATION_ERROR',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 400);
        } catch (\Exception $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'UPDATE_FAILED',
                'message' => 'Failed to update vehicle'
            ], 500);
        }
    }
    
    /**
     * Delete (soft delete) a vehicle
     * 
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function destroy(Request $request, string $id): Response
    {
        try {
            // Check admin authentication
            if (!Auth::isAdmin()) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'UNAUTHORIZED',
                    'message' => 'Admin privileges required'
                ], 403);
            }
            
            if (!$this->isValidVehicleId($id)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'INVALID_VEHICLE_ID',
                    'message' => 'Invalid vehicle ID format'
                ], 400);
            }
            
            $vehicleModel = new Vehicle();
            $vehicle = $vehicleModel->findById($id);
            
            if (!$vehicle) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'VEHICLE_NOT_FOUND',
                    'message' => 'Vehicle not found'
                ], 404);
            }
            
            // Check for active bookings
            if ($vehicleModel->hasActiveBookings($id)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'ACTIVE_BOOKINGS',
                    'message' => 'Vehicle has active bookings and cannot be deleted'
                ], 409);
            }
            
            // Soft delete vehicle
            $vehicleModel->softDelete($id);
            
            return $this->response([
                'status' => 'success',
                'message' => 'Vehicle deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'DELETE_FAILED',
                'message' => 'Failed to delete vehicle'
            ], 500);
        }
    }
    
    /**
     * Check vehicle availability for a specific date range
     * 
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function checkAvailability(Request $request, string $id): Response
    {
        try {
            if (!$this->isValidVehicleId($id)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'INVALID_VEHICLE_ID',
                    'message' => 'Invalid vehicle ID format'
                ], 400);
            }
            
            $startDate = $request->getQueryParam('start_date');
            $endDate = $request->getQueryParam('end_date');
            
            if (!$startDate || !$endDate || !$this->isValidDateRange($startDate, $endDate)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'INVALID_DATES',
                    'message' => 'Invalid date range or format'
                ], 400);
            }
            
            $vehicleModel = new Vehicle();
            $vehicle = $vehicleModel->findById($id);
            
            if (!$vehicle) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'VEHICLE_NOT_FOUND',
                    'message' => 'Vehicle not found'
                ], 404);
            }
            
            // Check availability for the date range
            $availability = $vehicleModel->checkAvailability($id, $startDate, $endDate);
            
            if ($availability['available']) {
                // Calculate pricing for the period
                $pricing = $vehicleModel->calculatePricing($id, $startDate, $endDate);
                
                return $this->response([
                    'status' => 'success',
                    'data' => [
                        'vehicle_id' => $id,
                        'available' => true,
                        'conflicting_bookings' => [],
                        'pricing' => $pricing
                    ]
                ]);
            } else {
                return $this->response([
                    'status' => 'success',
                    'data' => [
                        'vehicle_id' => $id,
                        'available' => false,
                        'conflicting_bookings' => $availability['conflicting_bookings'],
                        'next_available_date' => $availability['next_available_date']
                    ]
                ]);
            }
        } catch (\Exception $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => 'Failed to check availability'
            ], 500);
        }
    }
    
    /**
     * Get all vehicle types
     * 
     * @param Request $request
     * @return Response
     */
    public function getVehicleTypes(Request $request): Response
    {
        try {
            $vehicleModel = new Vehicle();
            $types = $vehicleModel->getAllVehicleTypes();
            
            return $this->response([
                'status' => 'success',
                'data' => [
                    'types' => $types
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => 'Failed to retrieve vehicle types'
            ], 500);
        }
    }
    
    /**
     * Update vehicle status
     * 
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function updateStatus(Request $request, string $id): Response
    {
        try {
            // Check admin authentication
            if (!Auth::isAdmin()) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'UNAUTHORIZED',
                    'message' => 'Admin privileges required'
                ], 403);
            }
            
            if (!$this->isValidVehicleId($id)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'INVALID_VEHICLE_ID',
                    'message' => 'Invalid vehicle ID format'
                ], 400);
            }
            
            $data = $request->getBody();
            
            if (!isset($data['status']) || !in_array($data['status'], ['available', 'unavailable', 'maintenance'])) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'INVALID_STATUS',
                    'message' => 'Invalid status value'
                ], 400);
            }
            
            // Require reason for maintenance or unavailable status
            if (($data['status'] === 'maintenance' || $data['status'] === 'unavailable') && empty($data['reason'])) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'REASON_REQUIRED',
                    'message' => 'Reason required for maintenance/unavailable status'
                ], 400);
            }
            
            $vehicleModel = new Vehicle();
            $vehicle = $vehicleModel->findById($id);
            
            if (!$vehicle) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'VEHICLE_NOT_FOUND',
                    'message' => 'Vehicle not found'
                ], 404);
            }
            
            $previousStatus = $vehicle['status'];
            
            // Check for active bookings if status is being changed to maintenance or unavailable
            if (($data['status'] === 'maintenance' || $data['status'] === 'unavailable') && $vehicleModel->hasActiveBookings($id)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'ACTIVE_BOOKINGS',
                    'message' => 'Vehicle has active bookings that conflict'
                ], 409);
            }
            
            // Update vehicle status
            $vehicleModel->updateStatus($id, $data['status'], $data['reason'] ?? null);
            
            return $this->response([
                'status' => 'success',
                'message' => 'Vehicle status updated successfully',
                'data' => [
                    'vehicle_id' => $id,
                    'previous_status' => $previousStatus,
                    'new_status' => $data['status']
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'UPDATE_FAILED',
                'message' => 'Failed to update vehicle status'
            ], 500);
        }
    }
    
    /**
     * Record maintenance activity for a vehicle
     * 
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function recordMaintenance(Request $request, string $id): Response
    {
        try {
            // Check admin authentication
            if (!Auth::isAdmin()) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'UNAUTHORIZED',
                    'message' => 'Admin privileges required'
                ], 403);
            }
            
            if (!$this->isValidVehicleId($id)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'INVALID_VEHICLE_ID',
                    'message' => 'Invalid vehicle ID format'
                ], 400);
            }
            
            $data = $request->getBody();
            
            // Validate required fields
            $requiredFields = ['type', 'description', 'start_date'];
            $validationErrors = $this->validateRequiredFields($data, $requiredFields);
            
            if (!empty($validationErrors)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'VALIDATION_ERROR',
                    'message' => 'Invalid or missing required fields',
                    'errors' => $validationErrors
                ], 400);
            }
            
            $vehicleModel = new Vehicle();
            $vehicle = $vehicleModel->findById($id);
            
            if (!$vehicle) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'VEHICLE_NOT_FOUND',
                    'message' => 'Vehicle not found'
                ], 404);
            }
            
            // Create maintenance record
            $maintenanceModel = new Maintenance();
            $maintenanceId = $maintenanceModel->create($id, $data);
            
            // Optionally update vehicle status if maintenance is ongoing
            $statusUpdated = false;
            if (isset($data['end_date']) && strtotime($data['end_date']) > time()) {
                $vehicleModel->updateStatus($id, 'maintenance', $data['description']);
                $statusUpdated = true;
            }
            
            // Update odometer if provided
            if (isset($data['odometer']) && $data['odometer'] > 0) {
                $vehicleModel->updateOdometer($id, $data['odometer']);
            }
            
            return $this->response([
                'status' => 'success',
                'message' => 'Maintenance record created successfully',
                'data' => [
                    'maintenance_id' => $maintenanceId,
                    'vehicle_id' => $id,
                    'status_updated' => $statusUpdated
                ]
            ], 201);
        } catch (ValidationException $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'VALIDATION_ERROR',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 400);
        } catch (\Exception $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'RECORD_CREATION_FAILED',
                'message' => 'Failed to create maintenance record'
            ], 500);
        }
    }
    
    /**
     * Get vehicle history
     * 
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function getHistory(Request $request, string $id): Response
    {
        try {
            // Check admin authentication
            if (!Auth::isAdmin()) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'UNAUTHORIZED',
                    'message' => 'Admin privileges required'
                ], 403);
            }
            
            if (!$this->isValidVehicleId($id)) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'INVALID_VEHICLE_ID',
                    'message' => 'Invalid vehicle ID format'
                ], 400);
            }
            
            $page = $request->getQueryParam('page', 1);
            $perPage = min($request->getQueryParam('per_page', 20), 100);
            $type = $request->getQueryParam('type');
            $fromDate = $request->getQueryParam('from_date');
            $toDate = $request->getQueryParam('to_date');
            
            $vehicleModel = new Vehicle();
            $vehicle = $vehicleModel->findById($id);
            
            if (!$vehicle) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'VEHICLE_NOT_FOUND',
                    'message' => 'Vehicle not found'
                ], 404);
            }
            
            // Get vehicle history with filters
            $result = $vehicleModel->getHistory($id, [
                'type' => $type,
                'from_date' => $fromDate,
                'to_date' => $toDate
            ], $page, $perPage);
            
            return $this->response([
                'status' => 'success',
                'data' => [
                    'vehicle' => [
                        'id' => $vehicle['id'],
                        'make' => $vehicle['make'],
                        'model' => $vehicle['model'],
                        'year' => $vehicle['year'],
                        'registration_number' => $vehicle['registration_number']
                    ],
                    'history' => $result['history']
                ],
                'meta' => [
                    'current_page' => $page,
                    'total_pages' => $result['total_pages'],
                    'total_items' => $result['total'],
                    'per_page' => $perPage
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => 'Failed to retrieve vehicle history'
            ], 500);
        }
    }
    
    /**
     * Admin view of vehicles
     * 
     * @param Request $request
     * @return Response
     */
    public function adminIndex(Request $request): Response
    {
        try {
            // Check admin authentication
            if (!Auth::isAdmin()) {
                return $this->response([
                    'status' => 'error',
                    'error' => 'UNAUTHORIZED',
                    'message' => 'Admin privileges required'
                ], 403);
            }
            
            $page = $request->getQueryParam('page', 1);
            $perPage = min($request->getQueryParam('per_page', 20), 50);
            
            $filters = [
                'type' => $request->getQueryParam('type'),
                'make' => $request->getQueryParam('make'),
                'model' => $request->getQueryParam('model'),
                'year' => $request->getQueryParam('year'),
                'status' => $request->getQueryParam('status'),
                'registration_number' => $request->getQueryParam('registration_number'),
                'location' => $request->getQueryParam('location'),
                'sort' => $request->getQueryParam('sort'),
                'include_deleted' => $request->getQueryParam('include_deleted', false)
            ];
            
            // Clean filters, removing nulls and validating inputs
            $filters = array_filter($filters, function($value) {
                return $value !== null;
            });
            
            $vehicleModel = new Vehicle();
            $result = $vehicleModel->adminSearch($filters, $page, $perPage);
            
            return $this->response([
                'status' => 'success',
                'message' => 'Vehicles retrieved successfully',
                'data' => [
                    'vehicles' => $result['vehicles']
                ],
                'meta' => [
                    'current_page' => $page,
                    'total_pages' => $result['total_pages'],
                    'total_vehicles' => $result['total'],
                    'per_page' => $perPage
                ]
            ]);
        } catch (AuthorizationException $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'FORBIDDEN',
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return $this->response([
                'status' => 'error',
                'error' => 'SERVER_ERROR',
                'message' => 'Failed to retrieve vehicles'
            ], 500);
        }
    }
    
    /**
     * Validate if vehicle ID format is valid
     * 
     * @param string $id
     * @return bool
     */
    private function isValidVehicleId(string $id): bool
    {
        // Assuming vehicle IDs follow the pattern v-{numeric} or similar pattern
        return preg_match('/^v-\d+$/', $id) === 1;
    }
    
    /**
     * Validate required fields in request data
     * 
     * @param array $data
     * @param array $requiredFields
     * @return array
     */
    private function validateRequiredFields(array $data, array $requiredFields): array
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "The {$field} field is required";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate date range
     * 
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    private function isValidDateRange(string $startDate, string $endDate): bool
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        
        return $start && $end && $start <= $end;
    }
}
