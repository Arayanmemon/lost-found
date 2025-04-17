-- Create Database
CREATE DATABASE IF NOT EXISTS lost_and_found;
USE lost_and_found;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Electronics', 'Phones, laptops, tablets, and other electronic devices'),
('Clothing', 'All types of clothing items'),
('Accessories', 'Watches, jewelry, bags, etc.'),
('Documents', 'ID cards, passports, certificates, etc.'),
('Books', 'Textbooks, notebooks, and other reading materials'),
('Keys', 'House keys, car keys, locker keys, etc.'),
('Other', 'Miscellaneous items');

-- Items Table
CREATE TABLE IF NOT EXISTS items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    date_item DATE NOT NULL,
    location VARCHAR(100) NOT NULL,
    image VARCHAR(255),
    status ENUM('open', 'closed', 'flagged') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Keywords Table for advanced matching
CREATE TABLE IF NOT EXISTS keywords (
    keyword_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    keyword VARCHAR(50) NOT NULL,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE
);

-- Messages Table for user communication
CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    item_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE
);

-- Matches Table for tracking potential matches
CREATE TABLE IF NOT EXISTS matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    lost_item_id INT NOT NULL,
    found_item_id INT NOT NULL,
    match_score FLOAT NOT NULL,
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lost_item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (found_item_id) REFERENCES items(item_id) ON DELETE CASCADE
);

-- Banned Keywords Table to prevent spam
CREATE TABLE IF NOT EXISTS banned_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(50) NOT NULL UNIQUE
);

-- Insert some default banned keywords
INSERT INTO banned_keywords (keyword) VALUES
('sex'),
('drugs'),
('gambling'),
('bitcoin'),
('viagra'),
('casino');

-- Insert admin user (password is 'admin123' hashed)
INSERT INTO users (username, email, password, full_name, user_type) VALUES
('admin', 'admin@example.com', '$2y$10$8TYrXmUvblIKtEI3LW7PmO7cUF1TTFdKFNo7yzJ9vQjcEgmkXKYlK', 'System Administrator', 'admin');