<?php
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// MQTT broker configuration
$broker = 'mqtt.yourbroker.com';
$port = 1883;
$clientId = 'car_rental_mqtt_subscriber';
$username = 'mqtt_user';
$password = 'mqtt_password';

try {
    // Create an MQTT client
    $mqtt = new MqttClient($broker, $port, $clientId);
    $connectionSettings = (new ConnectionSettings())
        ->setUsername($username)
        ->setPassword($password);

    $mqtt->connect($connectionSettings, true);

    echo "Connected to MQTT broker.\n";

    // Subscribe to topics
    $mqtt->subscribe('notifications/admin', function ($topic, $message) {
        echo "[ADMIN] $message\n";
        // Handle admin notifications (e.g., save to database or log)
    }, 0);

    $mqtt->subscribe('notifications/user', function ($topic, $message) {
        echo "[USER] $message\n";
        // Handle user notifications (e.g., trigger an email or SMS)
    }, 0);

    // Keep the script running to listen for messages
    $mqtt->loop(true);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo "An error occurred while connecting to the MQTT broker.\n";
} finally {
    $mqtt->disconnect();
    echo "Disconnected from MQTT broker.\n";
}
?>
