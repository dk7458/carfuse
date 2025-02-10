<?php
// ...existing code...
use App\Models\UserModel; // New dependency for database queries

class AuthService {

    // ...existing properties and methods...

    public function login($email, $password) {
        // Use UserModel to fetch the user instead of raw SQL
        $user = UserModel::findUserByEmail($email);
        if (!$user || !password_verify($password, $user->password_hash)) {
            logAuthFailure("Failed login attempt for email: " . $email);
            // ...existing error handling...
            throw new \Exception("Invalid credentials");
        }
        // ...existing login logic (e.g., token generation)...
        return [
            'user' => $user,
            'token' => $this->generateToken($user)
        ];
    }

    public function registerUser(array $data) {
        // Ensure the password is hashed with bcrypt
        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        unset($data['password']);
        
        // Use UserModel to create the new user
        $newUser = UserModel::createUser($data);
        if (!$newUser) {
            throw new \Exception("User registration failed");
        }
        return $newUser;
    }

    // ...existing code...
}
?>
