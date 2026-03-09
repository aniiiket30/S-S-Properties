<?php
// post_property.php - Complete Property Posting API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to establish database connection
function getDatabaseConnection() {
    $host = "localhost";
    $db_name = "ssproperties";
    $username = "root";
    $password = "";
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Database connection failed: " . $e->getMessage()));
        exit;
    }
}

// Function to validate required fields
function validateRequiredFields($data, $requiredFields) {
    $errors = array();
    foreach ($requiredFields as $field) {
        if (empty($data->$field)) {
            $errors[] = "$field is required";
        }
    }
    return $errors;
}

// Function to generate property title
function generatePropertyTitle($propertyType, $bedrooms, $locality, $city) {
    $typeMap = array(
        'apartment' => 'Apartment',
        'villa' => 'Villa',
        'builder-floor' => 'Builder Floor',
        'plot' => 'Plot',
        'studio' => 'Studio Apartment',
        'penthouse' => 'Penthouse',
        'farmhouse' => 'Farmhouse',
        'commercial' => 'Commercial Space'
    );
    
    $type = isset($typeMap[$propertyType]) ? $typeMap[$propertyType] : 'Property';
    
    $bedroomText = '';
    if ($bedrooms && $bedrooms !== 'studio') {
        $bedroomText = $bedrooms . ' BHK ';
    } elseif ($bedrooms === 'studio') {
        $bedroomText = 'Studio ';
    }
    
    return $bedroomText . $type . ' in ' . $locality . ', ' . $city;
}

// Main processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid JSON input"));
        exit;
    }
    
    // Validate required fields
    $requiredFields = array('userId', 'purpose', 'propertyType', 'city', 'locality', 
                           'area', 'description', 'price', 'name', 'email', 'phone');
    $validationErrors = validateRequiredFields($data, $requiredFields);
    
    if (!empty($validationErrors)) {
        http_response_code(400);
        echo json_encode(array("message" => "Validation failed", "errors" => $validationErrors));
        exit;
    }
    
    // Validate phone number
    $phone = preg_replace('/[^0-9]/', '', $data->phone);
    if (strlen($phone) < 10) {
        http_response_code(400);
        echo json_encode(array("message" => "Please enter a valid 10-digit phone number"));
        exit;
    }
    
    // Validate email
    if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(array("message" => "Please enter a valid email address"));
        exit;
    }
    
    try {
        // Get database connection
        $db = getDatabaseConnection();
        
        // Start transaction
        $db->beginTransaction();
        
        // Generate property title
        $title = generatePropertyTitle(
            $data->propertyType,
            $data->bedrooms ?? null,
            $data->locality,
            $data->city
        );
        
        // Insert property into database
        $propertySql = "INSERT INTO properties (
            user_id, title, description, property_type, transaction_type,
            price, price_unit, price_negotiable, monthly_maintenance,
            city, locality, address, bedrooms, bathrooms, super_area,
            carpet_area, floor, total_floors, furnishing, property_age,
            facilities, video_link, status, created_at
        ) VALUES (
            :user_id, :title, :description, :property_type, :transaction_type,
            :price, :price_unit, :price_negotiable, :monthly_maintenance,
            :city, :locality, :address, :bedrooms, :bathrooms, :super_area,
            :carpet_area, :floor, :total_floors, :furnishing, :property_age,
            :facilities, :video_link, :status, NOW()
        )";
        
        $propertyStmt = $db->prepare($propertySql);
        
        // Prepare facilities as JSON string
        $facilitiesJson = isset($data->facilities) ? json_encode($data->facilities) : null;
        
        $propertyStmt->execute(array(
            ':user_id' => $data->userId,
            ':title' => $title,
            ':description' => $data->description,
            ':property_type' => $data->propertyType,
            ':transaction_type' => $data->purpose,
            ':price' => $data->price,
            ':price_unit' => $data->priceUnit ?? 'lakh',
            ':price_negotiable' => $data->priceNegotiable ?? 'yes',
            ':monthly_maintenance' => $data->maintenance ?? null,
            ':city' => $data->city,
            ':locality' => $data->locality,
            ':address' => $data->locality . ', ' . $data->city,
            ':bedrooms' => $data->bedrooms ?? null,
            ':bathrooms' => $data->bathrooms ?? 1,
            ':super_area' => $data->area,
            ':carpet_area' => $data->carpetArea ?? null,
            ':floor' => $data->floor ?? null,
            ':total_floors' => $data->totalFloors ?? null,
            ':furnishing' => $data->furnishing ?? 'unfurnished',
            ':property_age' => $data->propertyAge ?? '1-5',
            ':facilities' => $facilitiesJson,
            ':video_link' => $data->videoLink ?? null,
            ':status' => 'active'
        ));
        
        $propertyId = $db->lastInsertId();
        
        // Insert contact details
        $contactSql = "INSERT INTO property_contacts (
            property_id, contact_name, contact_email, contact_phone,
            whatsapp_number, created_at
        ) VALUES (
            :property_id, :contact_name, :contact_email, :contact_phone,
            :whatsapp_number, NOW()
        )";
        
        $contactStmt = $db->prepare($contactSql);
        $contactStmt->execute(array(
            ':property_id' => $propertyId,
            ':contact_name' => $data->name,
            ':contact_email' => $data->email,
            ':contact_phone' => $phone,
            ':whatsapp_number' => isset($data->whatsapp) ? preg_replace('/[^0-9]/', '', $data->whatsapp) : null
        ));
        
        // Handle images if provided (storing as base64 in database or file system)
        if (isset($data->images) && is_array($data->images) && !empty($data->images)) {
            $imageSql = "INSERT INTO property_images (
                property_id, image_url, is_primary, upload_order, created_at
            ) VALUES (:property_id, :image_url, :is_primary, :upload_order, NOW())";
            
            $imageStmt = $db->prepare($imageSql);
            
            $order = 0;
            foreach ($data->images as $base64Image) {
                // For production, you should save images as files and store the file path
                // For this demo, we'll store base64 directly (not recommended for production)
                $isPrimary = ($order === 0) ? 1 : 0;
                
                $imageStmt->execute(array(
                    ':property_id' => $propertyId,
                    ':image_url' => $base64Image, // Store base64 or file path
                    ':is_primary' => $isPrimary,
                    ':upload_order' => $order
                ));
                
                $order++;
            }
        }
        
        // Commit transaction
        $db->commit();
        
        // Send success response
        http_response_code(201);
        echo json_encode(array(
            "message" => "Property posted successfully.",
            "property_id" => $propertyId,
            "title" => $title,
            "status" => "active"
        ));
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        if ($db) {
            $db->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(array(
            "message" => "Failed to post property. Database error: " . $e->getMessage(),
            "error_code" => $e->getCode()
        ));
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            "message" => "Failed to post property. Error: " . $e->getMessage()
        ));
    }
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed. Use POST."));
}
?>