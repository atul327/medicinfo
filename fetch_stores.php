<?php
header('Content-Type: application/json');
error_reporting(E_ALL); // Enable for debugging
ini_set('display_errors', 1);

// Database config
$host = 'localhost';
$dbname = 'medicalsystem';
$username = 'root';
$password = '';

// Create connection
$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    http_response_code(500);
    die(json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => mysqli_connect_error()
    ]));
}

// Accept either 'medicine' (name) or 'medicine_id' (ID)
$medicineName = isset($_GET['term']) ? trim(mysqli_real_escape_string($conn, $_GET['term'])) : '';
$medicineName = isset($_GET['medicine']) ? trim(mysqli_real_escape_string($conn, $_GET['medicine'])) : '';
$medicineId = isset($_GET['medicine_id']) ? intval($_GET['medicine_id']) : 0;
$pincode = isset($_GET['pincode']) ? preg_replace('/[^0-9]/', '', $_GET['pincode']) : '';

// Validate inputs
if (empty($medicineName) && $medicineId <= 0 && empty($pincode)) {
    http_response_code(400);
    die(json_encode([
        'status' => 'error',
        'message' => 'Specify medicine (name or ID) or pincode'
    ]));
}

try {
    // Query stores with medicine availability
    $sql = "SELECT 
                s.id,
                s.store_name,
                s.address,
                s.contact,
                s.pincode,
                m.medicine_name,
                m.brand_name,
                m.strength,
                m.price
            FROM medical_stores s
            JOIN medicine_availability ma ON s.id = ma.store_id
            JOIN medicines m ON ma.medicine_id = m.id";

    // Build WHERE conditions
    $where = [];
    $params = [];
    $types = '';

    if (!empty($medicineName)) {
        $where[] = "m.medicine_name LIKE ?";
        $params[] = "%$medicineName%";
        $types .= 's';
    }

    if ($medicineId > 0) {
        $where[] = "ma.medicine_id = ?";
        $params[] = $medicineId;
        $types .= 'i';
    }

    if (!empty($pincode)) {
        $where[] = "s.pincode = ?";
        $params[] = $pincode;
        $types .= 's';
    }

    // Combine WHERE clauses
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " LIMIT 50";

    // Execute query
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Format response
    $stores = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stores[] = [
            'id' => $row['id'],
            'store_name' => $row['store_name'],
            'address' => $row['address'],
            'contact' => $row['contact'],
            'pincode' => $row['pincode'],
            'medicine' => [
                'name' => $row['medicine_name'],
                'brand' => $row['brand_name'],
                'strength' => $row['strength'],
                'price' => $row['price']
            ]
        ];
    }

    // Always return 'data' as array (even if empty)
    echo json_encode([
        'status' => 'success',
        'data' => $stores
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) mysqli_stmt_close($stmt);
    mysqli_close($conn);
}