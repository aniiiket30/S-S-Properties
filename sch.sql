USE ssproperties;

-- Properties table
CREATE TABLE IF NOT EXISTS properties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    property_type VARCHAR(50) NOT NULL,
    transaction_type ENUM('sell', 'rent') NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    price_unit ENUM('lakh', 'crore') NOT NULL DEFAULT 'lakh',
    price_negotiable ENUM('yes', 'no', 'slightly') DEFAULT 'yes',
    monthly_maintenance DECIMAL(10,2),
    city VARCHAR(100) NOT NULL,
    locality VARCHAR(150) NOT NULL,
    address TEXT,
    bedrooms VARCHAR(10),
    bathrooms INT DEFAULT 1,
    super_area DECIMAL(8,2) NOT NULL,
    carpet_area DECIMAL(8,2),
    floor VARCHAR(50),
    total_floors INT,
    furnishing ENUM('furnished', 'semi-furnished', 'unfurnished') DEFAULT 'unfurnished',
    property_age VARCHAR(50) DEFAULT '1-5',
    facilities TEXT,
    video_link VARCHAR(500),
    status ENUM('active', 'inactive', 'sold', 'rented') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Property images table
CREATE TABLE IF NOT EXISTS property_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    image_url TEXT NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    upload_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Contact details table
CREATE TABLE IF NOT EXISTS property_contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    contact_name VARCHAR(100) NOT NULL,
    contact_email VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(15) NOT NULL,
    whatsapp_number VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_properties_city ON properties(city);
CREATE INDEX idx_properties_locality ON properties(locality);
CREATE INDEX idx_properties_status ON properties(status);
CREATE INDEX idx_properties_user ON properties(user_id);
CREATE INDEX idx_images_property ON property_images(property_id);
CREATE INDEX idx_contacts_property ON property_contacts(property_id);
