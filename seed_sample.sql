-- seed_sample.sql
-- NOTE: Replace <PASSWORD_HASH> with a bcrypt/hash produced by PHP's password_hash
-- Example to generate hash on command line (Windows PowerShell):
-- php -r "echo password_hash('admin123', PASSWORD_DEFAULT).PHP_EOL;"

USE MedicarePlus;

-- Create an admin user (replace password hash)
INSERT INTO users (first_name, last_name, username, email, password, role) VALUES
('Site','Admin','admin','admin@example.com','<PASSWORD_HASH>','admin');

-- After inserting the user, create a patient record and a doctor for testing
-- Replace <ADMIN_USER_ID> after you find the inserted ID from your DB
-- INSERT INTO patients (user_id, dob, gender, medical_id) VALUES (<PATIENT_USER_ID>, '1990-01-01', 'female', 'MID1001');
-- INSERT INTO doctors (user_id, specialization) VALUES (<DOCTOR_USER_ID>, 'General Practice');

-- Example appointment (replace patient_id and doctor_id with actual IDs):
-- INSERT INTO appointments (patient_id, doctor_id, datetime, status) VALUES (1, 1, '2025-12-01 09:30:00', 'pending');
