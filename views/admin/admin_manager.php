// ...existing code...

// Fetch data using the centralized proxy
$filters = [
    'search' => $_GET['search'] ?? '',
    'startDate' => $_GET['start_date'] ?? '',
    'endDate' => $_GET['end_date'] ?? ''
];
$queryString = http_build_query($filters);
$response = file_get_contents(BASE_URL . "/public/api.php?endpoint=admin&action=fetch_admins&" . $queryString);
$data = json_decode($response, true);

if ($data['success']) {
    $admins = $data['admins'];
    foreach ($admins as $admin) {
        echo "<tr>
            <td>{$admin['name']}</td>
            <td>{$admin['email']}</td>
            <td>{$admin['role']}</td>
            <td>{$admin['created_at']}</td>
        </tr>";
    }
}

// ...existing code...
