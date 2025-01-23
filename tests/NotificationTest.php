<?php

use PHPUnit\Framework\TestCase;

require_once './includes/functions.php';

class NotificationTest extends TestCase
{
    public function testSendEmail()
    {
        // Simulate sending an email
        $recipient = "test@example.com";
        $subject = "Test Subject";
        $message = "Test Message";

        // Mock the email sending function if applicable
        $result = sendEmail($recipient, $subject, $message);

        $this->assertTrue($result, "Failed to send email to $recipient with subject: $subject.");
    }

    public function testSendSMS()
    {
        // Simulate sending an SMS
        $phoneNumber = "+48123456789";
        $message = "Test SMS Message";

        // Mock the SMS sending function if applicable
        $result = sendSMS($phoneNumber, $message);

        $this->assertTrue($result, "Failed to send SMS to $phoneNumber with message: $message.");
    }

    public function testInvalidEmail()
    {
        // Attempt to send an email to an invalid address
        $recipient = "invalid-email";
        $subject = "Test Subject";
        $message = "Test Message";

        $result = sendEmail($recipient, $subject, $message);

        $this->assertFalse($result, "Email should not be sent to an invalid address: $recipient.");
    }

    public function testInvalidPhoneNumber()
    {
        // Attempt to send an SMS to an invalid number
        $phoneNumber = "12345"; // Invalid phone number
        $message = "Test SMS Message";

        $result = sendSMS($phoneNumber, $message);

        $this->assertFalse($result, "SMS should not be sent to an invalid number: $phoneNumber.");
    }

    public function testEmptyMessageEmail()
    {
        // Attempt to send an email with an empty message
        $recipient = "test@example.com";
        $subject = "Test Subject";
        $message = "";

        $result = sendEmail($recipient, $subject, $message);

        $this->assertFalse($result, "Email should not be sent with an empty message.");
    }

    public function testEmptyMessageSMS()
    {
        // Attempt to send an SMS with an empty message
        $phoneNumber = "+48123456789";
        $message = "";

        $result = sendSMS($phoneNumber, $message);

        $this->assertFalse($result, "SMS should not be sent with an empty message.");
    }
}
