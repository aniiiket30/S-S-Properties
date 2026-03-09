<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once 'database.php';

$database = new Database();
$db = $database->getConnection();

// Get POST data
$data = json_decode(file_get_contents("php://input"));

// Check if data exists
if(empty($data)) {
    // Try form data
    $data = (object) $_POST;
}

if(
    !empty($data->first_name) &&
    !empty($data->last_name) &&
    !empty($data->email) &&
    !empty($data->password) &&
    !empty($data->phone)
) {
    $first_name = htmlspecialchars(strip_tags($data->first_name));
    $last_name = htmlspecialchars(strip_tags($data->last_name));
    $email = htmlspecialchars(strip_tags($data->email));
    $password = password_hash($data->password, PASSWORD_DEFAULT);
    $phone = htmlspecialchars(strip_tags($data->phone));
    $user_type = isset($data->user_type) ? $data->user_type : 'buyer';

    // Check if email already exists
    $checkQuery = "SELECT id FROM users WHERE email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":email", $email);
    $checkStmt->execute();
    
    if($checkStmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(array("success" => false, "message" => "Email already exists."));
        exit;
    }

    $query = "INSERT INTO users 
              (first_name, last_name, email, password, phone, user_type) 
              VALUES 
              (:first_name, :last_name, :email, :password, :phone, :user_type)";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":first_name", $first_name);
    $stmt->bindParam(":last_name", $last_name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password", $password);
    $stmt->bindParam(":phone", $phone);
    $stmt->bindParam(":user_type", $user_type);
    
    if($stmt->execute()) {
        $last_id = $db->lastInsertId();
        
        // Get the created user
        $userQuery = "SELECT id, first_name, last_name, email, phone, user_type FROM users WHERE id = :id";
        $userStmt = $db->prepare($userQuery);
        $userStmt->bindParam(":id", $last_id);
        $userStmt->execute();
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(201);
        echo json_encode(array(
            "success" => true,
            "message" => "User registered successfully.",
            "user" => array(
                "id" => $user['id'],
                "first_name" => $user['first_name'],
                "last_name" => $user['last_name'],
                "email" => $user['email'],
                "phone" => $user['phone'],
                "user_type" => $user['user_type'],
                "full_name" => $user['first_name'] . " " . $user['last_name']
            )
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("success" => false, "message" => "Unable to register user."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Incomplete data. All fields are required."));
}
?>