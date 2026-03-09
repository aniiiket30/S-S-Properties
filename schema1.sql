USE ssproperties;

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Contact information table (for company details)
CREATE TABLE IF NOT EXISTS contact_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    address TEXT NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    working_hours VARCHAR(100) NOT NULL,
    map_embed_code TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default contact information
INSERT INTO contact_info (address, email, phone, working_hours, map_embed_code) 
VALUES (
    '123 Business Street, Mumbai, India',
    'info@snsproperties.com',
    '+91 98765 43210',
    'Mon – Fri: 9:00 AM – 6:00 PM',
    '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3770.9031418206423!2d72.83252227447364!3d19.07656365218393!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7c9c90c2e6c3b%3A0x9f4c38b41e4b6b4c!2sMumbai%2C%20Maharashtra!5e0!3m2!1sen!2sin!4v1629896543210!5m2!1sen!2sin" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>'
)
ON DUPLICATE KEY UPDATE 
    address = VALUES(address),
    email = VALUES(email),
    phone = VALUES(phone),
    working_hours = VALUES(working_hours),
    map_embed_code = VALUES(map_embed_code);

-- Create indexes for better performance
CREATE INDEX idx_contact_status ON contact_messages(status);
CREATE INDEX idx_contact_created ON contact_messages(created_at);