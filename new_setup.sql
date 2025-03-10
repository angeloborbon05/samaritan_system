-- Drop existing database if it exists
DROP DATABASE IF EXISTS samaritan_db;

-- Create new database
CREATE DATABASE samaritan_db;
USE samaritan_db;

-- Create users table with basic structure
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_type ENUM('admin', 'donor', 'beneficiary') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create donations table
CREATE TABLE donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create beneficiary_requests table
CREATE TABLE beneficiary_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    amount_needed DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'fulfilled', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create donation_matches table
CREATE TABLE donation_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donation_id INT NOT NULL,
    request_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donation_id) REFERENCES donations(id),
    FOREIGN KEY (request_id) REFERENCES beneficiary_requests(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
INSERT INTO users (email, password, name, user_type) VALUES 
('admin@samaritan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'admin');

-- Insert sample donor
INSERT INTO users (email, password, name, user_type) VALUES 
('donor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sample Donor', 'donor');

-- Insert sample beneficiary
INSERT INTO users (email, password, name, user_type) VALUES 
('beneficiary@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sample Beneficiary', 'beneficiary'); 