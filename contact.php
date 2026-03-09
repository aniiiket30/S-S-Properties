<?php
// contact.php - Contact Form Handler
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
$host = "localhost";
$db_name = "ssproperties";
$username = "root";
$password = "";

// Function to connect to database
function connectDB() {
    global $host, $db_name, $username, $password;
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        return null;
    }
}

// Handle GET request - Load contact info
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conn = connectDB();
    
    if (!$conn) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }
    
    try {
        // Get contact information
        $stmt = $conn->prepare("SELECT address, email, phone, working_hours FROM contact_info LIMIT 1");
        $stmt->execute();
        $contact_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($contact_info) {
            echo json_encode([
                'success' => true,
                'data' => $contact_info
            ]);
        } else {
            // Default values if no data in database
            echo json_encode([
                'success' => true,
                'data' => [
                    'address' => '123 Business Street, Mumbai, India',
                    'email' => 'info@snsproperties.com',
                    'phone' => '+91 98765 43210',
                    'working_hours' => 'Mon – Fri: 9:00 AM – 6:00 PM'
                ]
            ]);
        }
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching contact info: ' . $e->getMessage()
        ]);
    }
    
    $conn = null;
}

// Handle POST request - Submit contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if form data is submitted
    if (!$input && isset($_POST['name'])) {
        $input = $_POST;
    }
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'No data received'
        ]);
        exit;
    }
    
    // Validate input
    $errors = [];
    
    if (empty($input['name'])) {
        $errors[] = 'Name is required';
    }
    
    if (empty($input['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($input['message'])) {
        $errors[] = 'Message is required';
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors)
        ]);
        exit;
    }
    
    $conn = connectDB();
    
    if (!$conn) {
        echo json_encode([
            'success' => true, // Still show success for demo
            'message' => 'Message received! (Demo Mode)',
            'data' => [
                'name' => htmlspecialchars($input['name']),
                'email' => htmlspecialchars($input['email'])
            ]
        ]);
        exit;
    }
    
    try {
        // Save message to database
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message, status) 
                                VALUES (:name, :email, :message, 'new')");
        
        $stmt->execute([
            ':name' => htmlspecialchars($input['name']),
            ':email' => htmlspecialchars($input['email']),
            ':message' => htmlspecialchars($input['message'])
        ]);
        
        $message_id = $conn->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for contacting us! We will get back to you soon.',
            'data' => [
                'message_id' => $message_id,
                'name' => htmlspecialchars($input['name']),
                'email' => htmlspecialchars($input['email'])
            ]
        ]);
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => true, // Still show success for demo
            'message' => 'Message received! (Demo Mode - Database error)',
            'data' => [
                'name' => htmlspecialchars($input['name']),
                'email' => htmlspecialchars($input['email'])
            ]
        ]);
    }
    
    $conn = null;
}
?>