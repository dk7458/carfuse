<?php
// File Path: /controllers/contract_ctrl.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

enforceRole(['admin', 'super_admin'], '/public/login.php');

/**
 * Fetch the admin signature file path.
 * @return string|null
 */
function getAdminSignature()
{
    $signaturePath = '/uploads/admin/signatures/signature.png';
    return file_exists($_SERVER['DOCUMENT_ROOT'] . $signaturePath) ? $signaturePath : null;
}

/**
 * Fetch contract templates.
 * @return array
 */
function getContractTemplates()
{
    $templateDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/admin/templates/';
    if (!is_dir($templateDir)) {
        mkdir($templateDir, 0777, true);
    }
    return glob("$templateDir*.{html,pdf}", GLOB_BRACE);
}

/**
 * Fetch contracts from the database with search and pagination.
 */
function getContracts($search, $dateFrom, $dateTo, $page)
{
    global $conn;
    $offset = ($page - 1) * 10;

    $query = "
        SELECT c.id, CONCAT(u.name, ' ', u.surname) AS user_name, CONCAT(f.make, ' ', f.model) AS vehicle, 
               c.created_at, c.file_path
        FROM contracts c
        JOIN users u ON c.user_id = u.id
        JOIN fleet f ON c.vehicle_id = f.id
        WHERE 1
    ";

    $params = [];
    $types = "";

    if (!empty($search)) {
        $query .= " AND (u.name LIKE ? OR u.surname LIKE ? OR f.make LIKE ? OR f.model LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= "ssss";
    }

    if (!empty($dateFrom)) {
        $query .= " AND c.created_at >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }

    if (!empty($dateTo)) {
        $query .= " AND c.created_at <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }

    $query .= " ORDER BY c.created_at DESC LIMIT ?, 10";
    $params[] = $offset;
    $types .= "i";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Failed to prepare query.");
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Count contracts for pagination.
 */
function countContracts($search, $dateFrom, $dateTo)
{
    global $conn;

    $query = "
        SELECT COUNT(*) 
        FROM contracts c
        JOIN users u ON c.user_id = u.id
        JOIN fleet f ON c.vehicle_id = f.id
        WHERE 1
    ";

    $params = [];
    $types = "";

    if (!empty($search)) {
        $query .= " AND (u.name LIKE ? OR u.surname LIKE ? OR f.make LIKE ? OR f.model LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= "ssss";
    }

    if (!empty($dateFrom)) {
        $query .= " AND c.created_at >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }

    if (!empty($dateTo)) {
        $query .= " AND c.created_at <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Failed to prepare query.");
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0];
}

// Handle file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'upload_signature') {
            if (!isset($_FILES['signature']) || $_FILES['signature']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("No valid file selected for upload.");
            }

            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/admin/signatures/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filePath = $uploadDir . 'signature.png';
            if (!move_uploaded_file($_FILES['signature']['tmp_name'], $filePath)) {
                throw new Exception("Failed to upload the file.");
            }
            redirect('/views/admin/contract_manager.php');
        }

        if ($action === 'upload_template') {
            if (!isset($_FILES['template']) || $_FILES['template']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("No valid file selected for upload.");
            }

            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/admin/templates/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = pathinfo($_FILES['template']['name'], PATHINFO_EXTENSION);
            if (!in_array(strtolower($fileExtension), ['html', 'pdf'])) {
                throw new Exception("Invalid file type. Only HTML and PDF files are allowed.");
            }

            $filePath = $uploadDir . basename($_FILES['template']['name']);
            if (!move_uploaded_file($_FILES['template']['tmp_name'], $filePath)) {
                throw new Exception("Failed to upload the file.");
            }
            redirect('/views/admin/contract_manager.php');
        }
    } catch (Exception $e) {
        logError($e->getMessage());
        redirect('/views/admin/contract_manager.php?error=' . urlencode($e->getMessage()));
    }
}
?>
