<?php

use PHPUnit\Framework\TestCase;

require_once './includes/functions.php';

class NotificationTest extends TestCase
{
    public function testSendEmail()
    {
        $result = sendEmail("test@example.com", "Test Subject", "Test Message");
        $this->assertTrue($result, "Failed to send email.");
    }

    public function testSendSMS()
    {
        $result = sendSMS("+48123456789", "Test SMS Message");
        $this->assertTrue($result, "Failed to send SMS.");
    }
}
