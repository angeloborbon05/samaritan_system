-- Create database
CREATE DATABASE IF NOT EXISTS samaritan_system;
USE samaritan_system;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    user_type ENUM('donor', 'beneficiary', 'admin') NOT NULL,
    profile_image VARCHAR(255),
    email_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT TRUE,
    feedback_notifications BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE
);

-- Create beneficiaries table
CREATE TABLE IF NOT EXISTS beneficiaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    birth_date DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    contact_number VARCHAR(20),
    profile_image VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id)
);

-- Create posts table
CREATE TABLE IF NOT EXISTS posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    beneficiary_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    urgency_level ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id),
    INDEX idx_beneficiary_id (beneficiary_id),
    INDEX idx_status (status),
    INDEX idx_category (category)
);

-- Create post_images table
CREATE TABLE IF NOT EXISTS post_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id)
);

-- Create verification_documents table
CREATE TABLE IF NOT EXISTS verification_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    document_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id)
);

-- Create help_received table
CREATE TABLE IF NOT EXISTS help_received (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    donor_id INT NOT NULL,
    amount DECIMAL(10,2),
    description TEXT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (donor_id) REFERENCES users(id),
    INDEX idx_post_id (post_id),
    INDEX idx_donor_id (donor_id),
    INDEX idx_status (status)
);

-- Create help_images table
CREATE TABLE IF NOT EXISTS help_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    help_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (help_id) REFERENCES help_received(id) ON DELETE CASCADE,
    INDEX idx_help_id (help_id)
);

-- Create ratings table
CREATE TABLE IF NOT EXISTS ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    help_id INT NOT NULL,
    beneficiary_id INT NOT NULL,
    donor_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (help_id) REFERENCES help_received(id),
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id),
    FOREIGN KEY (donor_id) REFERENCES users(id),
    INDEX idx_help_id (help_id),
    INDEX idx_beneficiary_id (beneficiary_id),
    INDEX idx_donor_id (donor_id)
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_type (type)
);

-- Create thank_you_messages table
CREATE TABLE IF NOT EXISTS thank_you_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    beneficiary_id INT NOT NULL,
    donor_id INT NOT NULL,
    help_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id),
    FOREIGN KEY (donor_id) REFERENCES users(id),
    FOREIGN KEY (help_id) REFERENCES help_received(id),
    INDEX idx_beneficiary_id (beneficiary_id),
    INDEX idx_donor_id (donor_id),
    INDEX idx_help_id (help_id)
); 