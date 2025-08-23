-- MySQL schema for Booking Lapangan
-- Run this in phpMyAdmin or mysql client

CREATE DATABASE IF NOT EXISTS bookingdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookingdb;

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(30) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  balance DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Courts
CREATE TABLE IF NOT EXISTS courts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  type VARCHAR(50) NOT NULL,
  price_per_hour INT NOT NULL
) ENGINE=InnoDB;

-- Bookings
CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  court_id INT NOT NULL,
  start_datetime DATETIME NOT NULL,
  duration_hours INT NOT NULL,
  status ENUM('pending','confirmed','cancelled','rejected') NOT NULL DEFAULT 'pending',
  booking_code VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (court_id, start_datetime),
  CONSTRAINT fk_b_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_b_court FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Payments
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL, -- jumlah yang DIBAYAR user
  method ENUM('qris','transfer') NOT NULL,
  proof_url VARCHAR(255) NOT NULL,
  status ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_p_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed courts
INSERT INTO courts (name, type, price_per_hour) VALUES
('Lapangan Futsal A', 'futsal', 150000),
('Lapangan Futsal B', 'futsal', 150000),
('Lapangan Badminton A', 'badminton', 60000),
('Lapangan Badminton B', 'badminton', 60000);

-- Seed admin user (password: admin123)
INSERT INTO users (name, phone, password_hash, role) VALUES
('Administrator', '0800000000', '$2y$10$1aZ9n/0w5t3l4Jp9g3JxUeS6q9kq8S6s3qzvO8J7n4h1qVqPZg3xK', 'admin');


