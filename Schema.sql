-- Create the database
CREATE DATABASE IF NOT EXISTS ssproperties;
USE ssproperties;

-- Table for user registration
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,  -- Changed from optional to required
    user_type ENUM('buyer', 'seller', 'agent') DEFAULT 'buyer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for property enquiries
CREATE TABLE IF NOT EXISTS property_enquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    property_title VARCHAR(255) NOT NULL,
    dealer_name VARCHAR(100) NOT NULL,
    enquirer_name VARCHAR(100) NOT NULL,
    phone_code VARCHAR(10) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    user_type ENUM('individual', 'agent', 'builder') DEFAULT 'individual',
    enquiry_reason ENUM('investment', 'self_use', 'rental') DEFAULT 'self_use',
    message TEXT,
    status ENUM('pending', 'contacted', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for saved properties (favorites)
CREATE TABLE IF NOT EXISTS saved_properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_property (user_id, property_id)
);

-- Insert some sample dealers for testing
-- Note: In production, use password_hash() to generate hashed passwords
-- Password for all test users is "password"
INSERT INTO users (first_name, last_name, email, password, phone, user_type) VALUES
('John', 'Dealer', 'john@elite.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 9876543210', 'agent'),
('Prime', 'Properties', 'info@primeproperties.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 9876543211', 'agent'),
('Luxury', 'Homes', 'contact@luxuryhomes.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 9876543212', 'agent');

-- Insert a sample user for testing
INSERT INTO users (first_name, last_name, email, password, phone, user_type) VALUES
('Test', 'User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 9876543213', 'buyer');