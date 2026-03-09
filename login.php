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

if(!empty($data->email) && !empty($data->password)) {
    $email = htmlspecialchars(strip_tags($data->email));
    $password = $data->password;
    
    $query = "SELECT id, first_name, last_name, email, password, phone, user_type 
              FROM users 
              WHERE email = :email 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password
        if(password_verify($password, $row['password'])) {
            // Remove password from response
            unset($row['password']);
            
            $user = array(
                "id" => $row['id'],
                "first_name" => $row['first_name'],
                "last_name" => $row['last_name'],
                "email" => $row['email'],
                "phone" => $row['phone'],
                "user_type" => $row['user_type'],
                "full_name" => $row['first_name'] . " " . $row['last_name']
            );
            
            // Start session
            session_start();
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_name'] = $row['first_name'] . " " . $row['last_name'];
            $_SESSION['user_type'] = $row['user_type'];
            
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Login successful.",
                "user" => $user,
                "session_id" => session_id()
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("success" => false, "message" => "Invalid email or password."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "User not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Email and password are required."));
}
?>