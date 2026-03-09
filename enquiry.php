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
    !empty($data->property_id) &&
    !empty($data->property_title) &&
    !empty($data->dealer_name) &&
    !empty($data->enquirer_name) &&
    !empty($data->phone_code) &&
    !empty($data->phone_number) &&
    !empty($data->message)
) {
    $property_id = htmlspecialchars(strip_tags($data->property_id));
    $property_title = htmlspecialchars(strip_tags($data->property_title));
    $dealer_name = htmlspecialchars(strip_tags($data->dealer_name));
    $enquirer_name = htmlspecialchars(strip_tags($data->enquirer_name));
    $phone_code = htmlspecialchars(strip_tags($data->phone_code));
    $phone_number = htmlspecialchars(strip_tags($data->phone_number));
    $email = isset($data->email) ? htmlspecialchars(strip_tags($data->email)) : NULL;
    $user_type = isset($data->user_type) ? $data->user_type : 'individual';
    $enquiry_reason = isset($data->enquiry_reason) ? $data->enquiry_reason : 'self_use';
    $message = htmlspecialchars(strip_tags($data->message));
    
    $query = "INSERT INTO property_enquiries 
              (property_id, property_title, dealer_name, enquirer_name, 
               phone_code, phone_number, email, user_type, enquiry_reason, message) 
              VALUES 
              (:property_id, :property_title, :dealer_name, :enquirer_name, 
               :phone_code, :phone_number, :email, :user_type, :enquiry_reason, :message)";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":property_id", $property_id);
    $stmt->bindParam(":property_title", $property_title);
    $stmt->bindParam(":dealer_name", $dealer_name);
    $stmt->bindParam(":enquirer_name", $enquirer_name);
    $stmt->bindParam(":phone_code", $phone_code);
    $stmt->bindParam(":phone_number", $phone_number);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":user_type", $user_type);
    $stmt->bindParam(":enquiry_reason", $enquiry_reason);
    $stmt->bindParam(":message", $message);
    
    if($stmt->execute()) {
        $last_id = $db->lastInsertId();
        
        http_response_code(201);
        echo json_encode(array(
            "success" => true,
            "message" => "Enquiry submitted successfully.",
            "enquiry_id" => $last_id,
            "notification" => "Your enquiry has been sent to the dealer. They will contact you shortly."
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("success" => false, "message" => "Unable to submit enquiry. Please try again."));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false, 
        "message" => "Incomplete data. Required fields: property_id, property_title, dealer_name, enquirer_name, phone_code, phone_number, message."
    ));
}
?>