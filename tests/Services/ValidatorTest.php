<?php

use PHPUnit\Framework\TestCase;
use App\Services\Validator;

class ValidatorTest extends TestCase
{
    public function testValidationPasses(): void
    {
        $validator = new Validator();

        $data = [
            'email' => 'test@example.com',
            'password' => 'Password123'
        ];

        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/'
        ];

        $this->assertTrue($validator->validate($data, $rules));
        $this->assertEmpty($validator->errors());
    }

    public function testValidationFails(): void
    {
        $validator = new Validator();

        $data = ['email' => 'invalid-email'];
        $rules = ['email' => 'required|email'];

        $this->assertFalse($validator->validate($data, $rules));
        $this->assertNotEmpty($validator->errors());
        $this->assertArrayHasKey('email', $validator->errors());
    }
}
