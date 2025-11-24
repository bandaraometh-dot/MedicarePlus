-- SQL schema for MedicarePlus
CREATE DATABASE IF NOT EXISTS MedicarePlus DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE MedicarePlus;

-- Users table (patients, doctors, admin)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  dob DATE,
  address VARCHAR(500),
  role ENUM('patient','doctor','admin') DEFAULT 'patient',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Appointments
CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  full_name VARCHAR(200) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  dob DATE,
  department VARCHAR(100),
  doctor VARCHAR(100),
  appointment_date DATE,
  appointment_time VARCHAR(20),
  reason TEXT,
  status VARCHAR(50) DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Contacts / messages
CREATE TABLE IF NOT EXISTS contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200),
  email VARCHAR(255),
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optional: seed an admin user (change password after import)
-- password: admin123 (will be hashed in PHP if created manually)
-- INSERT INTO users (first_name,last_name,username,email,password,role) VALUES ('Admin','User','admin','admin@example.com', '$2y$10$XXXXXXXXXXXXXXXXXXXXXXXXXXXXX', 'admin');
