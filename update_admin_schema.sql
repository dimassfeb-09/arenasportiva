-- Update database schema for admin functionality
USE bookingdb;

ALTER TABLE users 
ADD COLUMN username VARCHAR(50) UNIQUE AFTER phone,
ADD COLUMN email VARCHAR(100) UNIQUE AFTER username,
ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER email;
-- Tambah field coupon_discount
ALTER TABLE users ADD COLUMN coupon_discount INT DEFAULT 0 AFTER role;

-- Update existing users to have username and email
UPDATE users SET 
username = CONCAT('user', id),
email = CONCAT('user', id, '@example.com')
WHERE username IS NULL OR email IS NULL;

-- Create admin user
INSERT INTO users (name, phone, username, email, password, role, created_at) VALUES 
('Admin Arena Sportiva', '085894781559', 'admin', 'admin@arenasportiva.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW());

-- Add status column to courts table if not exists
ALTER TABLE courts 
ADD COLUMN status ENUM('available', 'maintenance', 'unavailable') DEFAULT 'available' AFTER price;

-- Update existing courts to be available
UPDATE courts SET status = 'available' WHERE status IS NULL;

-- Show updated schema
DESCRIBE users;
DESCRIBE courts;

-- Show admin user
SELECT id, name, username, email, role FROM users WHERE role = 'admin';
