<?php

use PHPUnit\Framework\TestCase;

require_once './includes/db_connect.php';
require_once './includes/functions.php';

class BookingTest extends TestCase
{
    private $conn;

    protected function setUp(): void
    {
        global $conn; // Access the global database connection
        $this->conn = $conn;

        // Create a test user and vehicle
        $this->conn->query("INSERT INTO users (name, email, password_hash, role) VALUES ('Test User', 'test@example.com', 'password', 'user')");
        $this->conn->query("INSERT INTO fleet (make, model, registration_number, availability) VALUES ('Test Make', 'Test Model', 'ABC123', 1)");
    }

    protected function tearDown(): void
    {
        // Clean up the database
        $this->conn->query("DELETE FROM bookings WHERE user_id = (SELECT id FROM users WHERE email = 'test@example.com')");
        $this->conn->query("DELETE FROM users WHERE email = 'test@example.com'");
        $this->conn->query("DELETE FROM fleet WHERE registration_number = 'ABC123'");
    }

    public function testCreateBooking()
    {
        // Fetch test user and vehicle
        $userId = $this->conn->query("SELECT id FROM users WHERE email = 'test@example.com'")->fetch_assoc()['id'];
        $vehicleId = $this->conn->query("SELECT id FROM fleet WHERE registration_number = 'ABC123'")->fetch_assoc()['id'];

        // Create a booking
        $pickupDate = date('Y-m-d', strtotime('+3 days'));
        $dropoffDate = date('Y-m-d', strtotime('+7 days'));
        $totalPrice = 500.00;

        $stmt = $this->conn->prepare("
            INSERT INTO bookings (user_id, vehicle_id, pickup_date, dropoff_date, total_price, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iissd", $userId, $vehicleId, $pickupDate, $dropoffDate, $totalPrice);
        $stmt->execute();

        // Verify booking exists
        $booking = $this->conn->query("SELECT * FROM bookings WHERE user_id = $userId AND vehicle_id = $vehicleId")->fetch_assoc();
        $this->assertNotNull($booking, "Booking was not created.");

        // Additional assertions
        $this->assertEquals($pickupDate, $booking['pickup_date'], "Pickup date does not match.");
        $this->assertEquals($dropoffDate, $booking['dropoff_date'], "Dropoff date does not match.");
        $this->assertEquals($totalPrice, $booking['total_price'], "Total price does not match.");
    }

    public function testCreateBookingWithUnavailableVehicle()
    {
        // Fetch test user and vehicle
        $userId = $this->conn->query("SELECT id FROM users WHERE email = 'test@example.com'")->fetch_assoc()['id'];
        $vehicleId = $this->conn->query("SELECT id FROM fleet WHERE registration_number = 'ABC123'")->fetch_assoc()['id'];

        // Set vehicle to unavailable
        $this->conn->query("UPDATE fleet SET availability = 0 WHERE id = $vehicleId");

        // Attempt to create a booking
        $pickupDate = date('Y-m-d', strtotime('+3 days'));
        $dropoffDate = date('Y-m-d', strtotime('+7 days'));
        $totalPrice = 500.00;

        $stmt = $this->conn->prepare("
            INSERT INTO bookings (user_id, vehicle_id, pickup_date, dropoff_date, total_price, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iissd", $userId, $vehicleId, $pickupDate, $dropoffDate, $totalPrice);

        $this->expectException(Exception::class);
        $stmt->execute();

        // Verify booking does not exist
        $booking = $this->conn->query("SELECT * FROM bookings WHERE user_id = $userId AND vehicle_id = $vehicleId")->fetch_assoc();
        $this->assertNull($booking, "Booking should not have been created for an unavailable vehicle.");
    }

    public function testBookingDateValidation()
    {
        // Fetch test user and vehicle
        $userId = $this->conn->query("SELECT id FROM users WHERE email = 'test@example.com'")->fetch_assoc()['id'];
        $vehicleId = $this->conn->query("SELECT id FROM fleet WHERE registration_number = 'ABC123'")->fetch_assoc()['id'];

        // Invalid booking dates
        $pickupDate = date('Y-m-d', strtotime('+7 days'));
        $dropoffDate = date('Y-m-d', strtotime('+3 days')); // Dropoff before pickup
        $totalPrice = 500.00;

        $stmt = $this->conn->prepare("
            INSERT INTO bookings (user_id, vehicle_id, pickup_date, dropoff_date, total_price, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iissd", $userId, $vehicleId, $pickupDate, $dropoffDate, $totalPrice);

        $this->expectException(Exception::class);
        $stmt->execute();

        // Verify booking does not exist
        $booking = $this->conn->query("SELECT * FROM bookings WHERE user_id = $userId AND vehicle_id = $vehicleId")->fetch_assoc();
        $this->assertNull($booking, "Booking should not have been created with invalid dates.");
    }
}
