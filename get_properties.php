<?php
// get_properties.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT p.*, pc.contact_name, pc.contact_email, pc.contact_phone 
              FROM properties p 
              LEFT JOIN property_contacts pc ON p.id = pc.property_id
              WHERE p.status = 'active'
              ORDER BY p.created_at DESC 
              LIMIT 9";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $properties = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Get property images
        $imageQuery = "SELECT image_url FROM property_images WHERE property_id = :property_id ORDER BY upload_order";
        $imageStmt = $db->prepare($imageQuery);
        $imageStmt->bindParam(":property_id", $row['id']);
        $imageStmt->execute();
        
        $images = array();
        while ($imageRow = $imageStmt->fetch(PDO::FETCH_ASSOC)) {
            $images[] = $imageRow['image_url'];
        }
        
        // If no images, add default image
        if (empty($images)) {
            $images[] = "https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80";
        }
        
        // Parse facilities from JSON
        $facilities = array();
        if (!empty($row['facilities'])) {
            $facilities = json_decode($row['facilities'], true);
        }
        
        // Create property object similar to your JavaScript structure
        $property = array(
            "id" => $row['id'],
            "title" => $row['title'],
            "location" => $row['locality'] . ', ' . $row['city'],
            "price" => formatPrice($row['price'], $row['price_unit']),
            "pricePerSqft" => "₹" . number_format($row['price'] / $row['super_area'], 0) . " per sq.ft.",
            "images" => $images,
            "bedrooms" => $row['bedrooms'] ? $row['bedrooms'] . " BHK" : "Plot",
            "bathrooms" => $row['bathrooms'] ? $row['bathrooms'] : "N/A",
            "area" => $row['super_area'] . " sq.ft.",
            "carpetArea" => $row['carpet_area'] ? $row['carpet_area'] . " sq.ft." : $row['super_area'] . " sq.ft.",
            "floor" => $row['floor'] ?: "Various",
            "facing" => "Various",
            "propertyAge" => formatPropertyAge($row['property_age']),
            "transactionType" => $row['transaction_type'] === 'sell' ? 'New' : 'Rent',
            "furnishing" => ucfirst($row['furnishing']),
            "parking" => in_array('parking', $facilities) ? "1 Covered" : "No",
            "powerBackup" => in_array('power-backup', $facilities) ? "Full" : "Partial",
            "propertyOwnership" => "Freehold",
            "roadWidth" => "40 Feet",
            "wheelchairFriendly" => "Yes",
            "gatedCommunity" => in_array('security', $facilities) ? "Yes" : "No",
            "petFriendly" => "Yes",
            "flooring" => "Vitrified",
            "cornerProperty" => "No",
            "waterSource" => "Municipal + Borewell",
            "overlooking" => "Garden View",
            "configuration" => $row['bedrooms'] ? $row['bedrooms'] . " BHK" : "Plot/Land",
            "address" => $row['address'],
            "features" => $facilities,
            "whyConsider" => array(
                "Prime location in " . $row['locality'],
                "Modern amenities and facilities",
                "Excellent connectivity and infrastructure",
                "Good investment potential"
            ),
            "dealerName" => $row['contact_name'],
            "dealerProperties" => rand(20, 100),
            "dealerVerified" => rand(5, 30),
            "dealerAddress" => $row['locality'] . ', ' . $row['city'],
            "dealerLocalities" => $row['locality'] . ', ' . $row['city'],
            "category" => getCategoryFromType($row['property_type'], $row['transaction_type']),
            "builder" => "godrej",
            "city" => $row['city'],
            "propertyCode" => "P" . str_pad($row['id'], 5, '0', STR_PAD_LEFT)
        );
        
        $properties[] = $property;
    }
    
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "properties" => $properties
    ));
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Failed to fetch properties. Error: " . $e->getMessage()
    ));
}

function formatPrice($price, $unit) {
    if ($unit === 'crore') {
        return "₹" . number_format($price, 1) . " Cr";
    } else {
        return "₹" . number_format($price, 1) . " Lakhs";
    }
}

function formatPropertyAge($age) {
    $ageMap = array(
        'new' => 'New Launch',
        '0-1' => '0-1 Years',
        '1-5' => '1-5 Years',
        '5+' => '5+ Years'
    );
    return isset($ageMap[$age]) ? $ageMap[$age] : '1-5 Years';
}

function getCategoryFromType($propertyType, $transactionType) {
    if ($transactionType === 'rent') {
        return 'rent';
    } elseif (in_array($propertyType, array('apartment', 'villa', 'penthouse'))) {
        return 'all';
    } elseif ($propertyType === 'plot') {
        return 'plot';
    } elseif ($propertyType === 'commercial') {
        return 'commercial';
    } else {
        return 'all';
    }
}
?>