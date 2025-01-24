<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/db_connect.php';
require_once BASE_PATH . 'includes/session_middleware.php';
require_once BASE_PATH . 'functions/global.php';
require_once BASE_PATH . 'functions/email.php';
require_once BASE_PATH . 'functions/user.php';


header('Content-Type: application/json');

enforceRole(['admin', 'super_admin'], '/public/login.php');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            throw new Exception("Invalid CSRF token.");
        }

        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'delete_user':
                $userId = intval($_POST['user_id']);
                if ($userId === 0 || $userId === $_SESSION['user_id']) {
                    throw new Exception("Invalid user ID.");
                }

                $targetUser = getUserDetails($conn, $userId);
                if ($targetUser['role'] === 'admin' && $_SESSION['user_role'] !== 'super_admin') {
                    throw new Exception("Only Super Admins can delete Admins.");
                }

                if (deleteUser($userId)) {
                    logAction($_SESSION['user_id'], 'delete_user', "Deleted user ID: $userId");
                    echo json_encode(['success' => 'User deleted successfully.']);
                } else {
                    throw new Exception("Failed to delete user.");
                }
                break;

            case 'update_role':
                $userId = intval($_POST['user_id']);
                $role   = $_POST['role'] ?? '';

                if (!in_array($role, ['user', 'admin'])) {
                    throw new Exception("Invalid role.");
                }

                if ($role === 'admin' && $_SESSION['user_role'] !== 'super_admin') {
                    throw new Exception("Only Super Admins can assign Admin roles.");
                }

                if (updateUserRole($userId, $role)) {
                    logAction($_SESSION['user_id'], 'update_role', "Updated user ID: $userId to role: $role");
                    echo json_encode(['success' => 'User role updated successfully.']);
                } else {
                    throw new Exception("Failed to update user role.");
                }
                break;

            default:
                throw new Exception("Unknown action.");
        }
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    logError($e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

/**
 * Fetches users from the database.
 *
 * @param mysqli $conn Database connection.
 * @return array Associative array of users.
 * @throws Exception If a database error occurs.
 */
function fetchUsers($conn) {
    $query = "SELECT id, name, email, role, status FROM users";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    return $users;
}

/**
 * Edits user details in the database.
 *
 * @global mysqli $db Database connection.
 * @param int $id User ID.
 * @param string $name User name.
 * @param string $email User email.
 * @param string $role User role.
 * @param string $status User status.
 * @return bool True if the update is successful.
 * @throws Exception If any validation or database error occurs.
 */
function editUser($id, $name, $email, $role, $status) {
    global $db;

    // Validate required parameters
    if (empty($id) || empty($name) || empty($email) || empty($role) || empty($status)) {
        throw new Exception("All parameters are required.");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    // Validate role
    $validRoles = ['user', 'admin', 'super_admin'];
    if (!in_array($role, $validRoles)) {
        throw new Exception("Invalid role.");
    }

    // Validate status
    $validStatuses = ['Active', 'Inactive'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception("Invalid status.");
    }

    // Check if user exists
    $query = "SELECT id FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        throw new Exception("User does not exist.");
    }

    // Update user details
    $updateQuery = "UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bind_param('ssssi', $name, $email, $role, $status, $id);

    if ($updateStmt->execute()) {
        return true;
    } else {
        throw new Exception("Failed to update user: " . $db->error);
    }
}

/**
 * Adds a new user to the database.
 *
 * @global mysqli $db Database connection.
 * @param string $name User name.
 * @param string $email User email.
 * @param string $role User role.
 * @param string $status User status.
 * @return int ID of the newly created user.
 * @throws Exception If any validation or database error occurs.
 */
function addUser($name, $email, $role, $status) {
    global $db;

    // Validate required parameters
    if (empty($name) || empty($email) || empty($role) || empty($status)) {
        throw new Exception("All parameters are required.");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    // Validate role
    $validRoles = ['user', 'admin', 'super_admin'];
    if (!in_array($role, $validRoles)) {
        throw new Exception("Invalid role.");
    }

    // Validate status
    $validStatuses = ['Active', 'Inactive'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception("Invalid status.");
    }

    // Check for duplicate email
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        throw new Exception("A user with this email already exists.");
    }

    // Insert new user
    $insertQuery = "INSERT INTO users (name, email, role, status) VALUES (?, ?, ?, ?)";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bind_param('ssss', $name, $email, $role, $status);

    if ($insertStmt->execute()) {
        return $insertStmt->insert_id;
    } else {
        throw new Exception("Failed to add user: " . $db->error);
    }
}

/**
 * Deletes a user from the database.
 *
 * @global mysqli $db Database connection.
 * @param int $id User ID.
 * @return bool True if the user is deleted successfully.
 * @throws Exception If any validation or database error occurs.
 */
function deleteUser($id) {
    global $db;

    // Validate user ID
    if (empty($id) || !is_int($id) || $id <= 0) {
        throw new Exception("Invalid user ID.");
    }

    // Check if user exists
    $query = "SELECT id FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        throw new Exception("User not found.");
    }

    // Delete user
    $deleteQuery = "DELETE FROM users WHERE id = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bind_param('i', $id);

    if ($deleteStmt->execute()) {
        return true;
    } else {
        throw new Exception("Failed to delete user: " . $db->error);
    }
}

/**
 * Fetches the details of a single user from the database.
 *
 * @global mysqli $db Database connection.
 * @param int $id User ID.
 * @return array Associative array of user details.
 * @throws Exception If any validation or database error occurs.
 */
function fetchUserById($id) {
    global $db;

    // Validate user ID
    if (empty($id) || !is_int($id) || $id <= 0) {
        throw new Exception("Invalid user ID.");
    }

    // Prepare SQL query to fetch user details
    $query = "SELECT id, name, email, role, status FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows === 0) {
        throw new Exception("User not found.");
    }

    // Fetch user data
    $user = $result->fetch_assoc();

    return $user;
}

/**
 * Exports all users to a CSV file.
 *
 * @global mysqli $db Database connection.
 * @throws Exception If a database error occurs.
 */
function exportUsersToCSV() {
    global $db;

    // Fetch user data
    $query = "SELECT id, name, email, role, status FROM users";
    $result = $db->query($query);

    if (!$result) {
        throw new Exception("Database error: " . $db->error);
    }

    // Create a temporary file in memory
    $output = fopen('php://output', 'w');

    // Set headers to trigger download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users.csv"');

    // Add CSV column headers
    fputcsv($output, ['ID', 'Name', 'Email', 'Role', 'Status']);

    // Add user data to CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    // Close the output stream
    fclose($output);
    exit;
}

/**
 * Validates user input for adding or editing a user.
 *
 * @param array $input Associative array of user input.
 * @return array Array of validation errors, or an empty array if all inputs are valid.
 */
function validateUserInput($input) {
    $errors = [];

    // Check required fields
    if (empty($input['name'])) {
        $errors[] = "Name is required.";
    }
    if (empty($input['email'])) {
        $errors[] = "Email is required.";
    }
    if (empty($input['role'])) {
        $errors[] = "Role is required.";
    }
    if (empty($input['status'])) {
        $errors[] = "Status is required.";
    }

    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Validate role
    $validRoles = ['user', 'admin', 'super_admin'];
    if (!in_array($input['role'], $validRoles)) {
        $errors[] = "Invalid role.";
    }

    // Validate status
    $validStatuses = ['Active', 'Inactive'];
    if (!in_array($input['status'], $validStatuses)) {
        $errors[] = "Invalid status.";
    }

    return $errors;
}

/**
 * Updates the role of a specific user in the database.
 *
 * @global mysqli $db Database connection.
 * @param int    $userId User ID.
 * @param string $role   New role for the user.
 * @return bool  True on success, or throws an exception on failure.
 * @throws Exception If validation fails or a database error occurs.
 */
function updateUserRole($userId, $role) {
    global $db;

    // Validate user ID
    if (empty($userId) || !is_int($userId) || $userId <= 0) {
        throw new Exception("Invalid user ID.");
    }

    // Validate role
    $validRoles = ['user', 'admin', 'super_admin'];
    if (!in_array($role, $validRoles)) {
        throw new Exception("Invalid role.");
    }

    // Check if user exists
    $query = "SELECT id FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        throw new Exception("User does not exist.");
    }

    // Update role
    $updateQuery = "UPDATE users SET role = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bind_param('si', $role, $userId);

    if ($updateStmt->execute()) {
        return true;
    } else {
        throw new Exception("Failed to update user role: " . $db->error);
    }
}
?>
