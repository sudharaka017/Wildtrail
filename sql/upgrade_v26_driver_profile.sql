USE wildtrail_db;
CREATE TABLE IF NOT EXISTS driver_profiles(
 user_id INT PRIMARY KEY, license_no VARCHAR(80), license_doc VARCHAR(255), profile_photo VARCHAR(255), bio TEXT, languages VARCHAR(255), experience_years INT DEFAULT 0,
 CONSTRAINT fk_driver_profiles_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);
INSERT IGNORE INTO driver_profiles(user_id) SELECT id FROM users WHERE role='driver';
