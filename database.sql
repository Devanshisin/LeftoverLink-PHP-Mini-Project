-- LeftoverLink Database Schema
-- Run this script in MySQL/MariaDB to create the database and tables.

DROP DATABASE IF EXISTS leftoverlink;
CREATE DATABASE leftoverlink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE leftoverlink;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('donor','recipient','admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Food listings table
CREATE TABLE food_listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    food_name VARCHAR(150) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    quantity DECIMAL(10,2) NOT NULL CHECK (quantity > 0),
    unit VARCHAR(50) NOT NULL,
    location VARCHAR(200) NOT NULL,
    preparation_details TEXT,
    pickup_deadline DATETIME NOT NULL,
    status ENUM('pending','available','partially_reserved','fully_reserved','collected','expired','rejected') NOT NULL DEFAULT 'pending',
    available_quantity DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Food requests table

-- Food requests table
CREATE TABLE food_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    recipient_id INT NOT NULL,
    quantity_requested DECIMAL(10,2) NOT NULL,
    status ENUM(
        'pending',
        'approved',
        'rejected',
        'cancelled',
        'collected'
    ) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_quantity_requested
        CHECK (quantity_requested > 0),

    FOREIGN KEY (listing_id)
        REFERENCES food_listings(id)
        ON DELETE CASCADE,

    FOREIGN KEY (recipient_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_listing_recipient_status
        (listing_id, recipient_id, status)
) ENGINE=InnoDB;


-- Optional demonstration data (after creating tables)
INSERT INTO users (name, email, password, role) VALUES
    ('Admin User', 'admin@leftoverlink.edu', SHA2('admin123', 256), 'admin'),
    ('Donor One', 'donor1@example.com', SHA2('donorpass', 256), 'donor'),
    ('Recipient One', 'recipient1@example.com', SHA2('recipientpass', 256), 'recipient');

INSERT INTO food_listings (donor_id, food_name, description, category, quantity, unit, location, preparation_details, pickup_deadline, status, available_quantity)
VALUES
    (2, 'Veg Sandwiches', 'Fresh vegetable sandwiches, 2 hrs after preparation', 'Food', 30, 'pieces', 'Canteen A', 'Prepared at 10:00 AM', DATE_ADD(NOW(), INTERVAL 4 HOUR), 'available', 30);

INSERT INTO food_requests (listing_id, recipient_id, quantity_requested, status)
VALUES
    (1, 3, 5, 'pending');
