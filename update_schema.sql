-- Update database schema to include 'rejected' status
USE bookingdb;

-- Update bookings table to include 'rejected' status
ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','cancelled','rejected') NOT NULL DEFAULT 'pending';

-- Check if the update was successful
DESCRIBE bookings;
