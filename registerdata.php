<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$dbname = 'medicalsystem';
$username = 'root'; 
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $conn->beginTransaction();

    // 1. Insert into Users table
    $stmt = $conn->prepare("INSERT INTO Users (name, email, password, phone, address, gender) 
                          VALUES (:name, :email, :password, :phone, :address, :gender)");
    
    $stmt->execute([
        ':name' => $_POST['name'],
        ':email' => $_POST['email'],
        ':password' => $_POST['password'],
        ':phone' => $_POST['phone'],
        ':address' => $_POST['address'],
        ':gender' => $_POST['gender']
    ]);

    $user_id = $conn->lastInsertId();

    // 2. Insert into Medical_Stores table with user_id
    $stmt = $conn->prepare("INSERT INTO Medical_Stores 
                          (store_name, address, contact, pincode)
                          VALUES (:store_name, :address, :contact, :pincode)");
    
    $stmt->execute([
        ':store_name' => $_POST['medical_name'],
        ':address' => $_POST['address'],
        ':contact' => $_POST['phone'],
        ':pincode' => $_POST['pin']
    ]);

    // Commit transaction
    $conn->commit();

    header("Location: MedicineData.php?email=".urlencode($_POST['email']));
    exit();

} catch (PDOException $e) {
    if (isset($conn)) $conn->rollBack();
    die("Registration failed: " . $e->getMessage());
}
?>