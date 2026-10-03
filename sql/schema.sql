CREATE DATABASE IF NOT EXISTS wildtrail_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; USE wildtrail_db;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS notifications,reviews,wildlife_sightings,species,guide_availability,guide_parks,guide_profiles,driver_availability,vehicle_parks,vehicles,driver_profiles,bookings,park_slots,park_zones,park_images,parks,users;
SET FOREIGN_KEY_CHECKS=1;
CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY,full_name VARCHAR(120) NOT NULL,email VARCHAR(150) NOT NULL UNIQUE,phone VARCHAR(20),password_hash VARCHAR(255),google_id VARCHAR(150) UNIQUE,role ENUM('tourist','driver','guide','admin','superadmin') NOT NULL DEFAULT 'tourist',status ENUM('pending','active','rejected','suspended') NOT NULL DEFAULT 'active',nic_passport VARCHAR(40),avatar_url VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,last_login TIMESTAMP NULL);
CREATE TABLE parks(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(150) NOT NULL,slug VARCHAR(150) UNIQUE,province VARCHAR(100),description TEXT,image_url VARCHAR(500),entry_fee DECIMAL(10,2) DEFAULT 0,daily_vehicle_cap INT DEFAULT 60,status ENUM('active','inactive') DEFAULT 'active');
CREATE TABLE park_images(id INT AUTO_INCREMENT PRIMARY KEY,park_id INT NOT NULL,image_url VARCHAR(700) NOT NULL,caption VARCHAR(180),credit VARCHAR(180),source_url VARCHAR(700),sort_order INT DEFAULT 0,FOREIGN KEY(park_id) REFERENCES parks(id) ON DELETE CASCADE);
CREATE TABLE park_slots(id INT AUTO_INCREMENT PRIMARY KEY,park_id INT NOT NULL,label VARCHAR(80),slot_key ENUM('dawn','morning','afternoon') NOT NULL,start_time TIME,end_time TIME,vehicle_cap INT DEFAULT 20,price_modifier DECIMAL(10,2) DEFAULT 0,is_active TINYINT(1) DEFAULT 1,FOREIGN KEY(park_id) REFERENCES parks(id) ON DELETE CASCADE);
CREATE TABLE park_zones(id INT AUTO_INCREMENT PRIMARY KEY,park_id INT NOT NULL,name VARCHAR(120) NOT NULL,code VARCHAR(40) NOT NULL,center_lat DECIMAL(10,7) NOT NULL,center_lng DECIMAL(10,7) NOT NULL,radius_m INT DEFAULT 2500,sort_order INT DEFAULT 0,is_active TINYINT(1) DEFAULT 1,UNIQUE(park_id,code),FOREIGN KEY(park_id) REFERENCES parks(id) ON DELETE CASCADE);
CREATE TABLE driver_profiles(user_id INT PRIMARY KEY,license_no VARCHAR(80),license_doc VARCHAR(255),profile_photo VARCHAR(255),bio TEXT,languages VARCHAR(255),experience_years INT DEFAULT 0,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE TABLE vehicles(id INT AUTO_INCREMENT PRIMARY KEY,owner_id INT NOT NULL,plate_number VARCHAR(30) UNIQUE,make_model VARCHAR(120),capacity INT DEFAULT 6,photo VARCHAR(255),safari_type ENUM('open','covered','premium') DEFAULT 'open',amenities VARCHAR(500),base_price DECIMAL(10,2) DEFAULT 18000,registration_no VARCHAR(80),insurance_no VARCHAR(80),fitness_no VARCHAR(80),registration_doc VARCHAR(255),insurance_doc VARCHAR(255),fitness_doc VARCHAR(255),approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',status ENUM('available','maintenance','inactive') DEFAULT 'available',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE TABLE vehicle_parks(vehicle_id INT NOT NULL,park_id INT NOT NULL,PRIMARY KEY(vehicle_id,park_id),FOREIGN KEY(vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,FOREIGN KEY(park_id) REFERENCES parks(id) ON DELETE CASCADE);
CREATE TABLE driver_availability(id INT AUTO_INCREMENT PRIMARY KEY,driver_id INT NOT NULL,available_date DATE NOT NULL,slot ENUM('dawn','morning','afternoon') NOT NULL,is_available TINYINT(1) DEFAULT 1,UNIQUE(driver_id,available_date,slot),FOREIGN KEY(driver_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE TABLE guide_profiles(user_id INT PRIMARY KEY,license_no VARCHAR(80),license_doc VARCHAR(255),profile_photo VARCHAR(255),bio TEXT,languages VARCHAR(255),specialties VARCHAR(255),experience_years INT DEFAULT 0,guide_fee DECIMAL(10,2) DEFAULT 6000,available TINYINT(1) DEFAULT 1,verified TINYINT(1) DEFAULT 0,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE TABLE guide_parks(guide_id INT NOT NULL,park_id INT NOT NULL,PRIMARY KEY(guide_id,park_id),FOREIGN KEY(guide_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(park_id) REFERENCES parks(id) ON DELETE CASCADE);
CREATE TABLE guide_availability(id INT AUTO_INCREMENT PRIMARY KEY,guide_id INT NOT NULL,available_date DATE NOT NULL,slot ENUM('dawn','morning','afternoon') NOT NULL,is_available TINYINT(1) DEFAULT 1,UNIQUE(guide_id,available_date,slot),FOREIGN KEY(guide_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE TABLE bookings(id INT AUTO_INCREMENT PRIMARY KEY,booking_code VARCHAR(24) UNIQUE,visitor_id INT NOT NULL,park_id INT NOT NULL,slot_id INT NOT NULL,entry_date DATE NOT NULL,guests INT NOT NULL,driver_id INT NULL,vehicle_id INT NULL,guide_id INT NULL,status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',driver_response ENUM('pending','accepted','rejected') DEFAULT 'pending',payment_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',approved_by INT NULL,approved_at DATETIME NULL,rejection_reason VARCHAR(255),amount DECIMAL(10,2) DEFAULT 0,payment_ref VARCHAR(100),entry_fee_component DECIMAL(10,2) DEFAULT 0,vehicle_fee_component DECIMAL(10,2) DEFAULT 0,guide_fee_component DECIMAL(10,2) DEFAULT 0,preferred_language VARCHAR(60),pickup_location VARCHAR(180),contact_phone VARCHAR(30),special_notes TEXT,cancellation_reason VARCHAR(255),refund_status ENUM('none','pending','refunded') DEFAULT 'none',refund_amount DECIMAL(10,2) DEFAULT 0,cancellation_fee DECIMAL(10,2) DEFAULT 0,reserved_until DATETIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(visitor_id) REFERENCES users(id),FOREIGN KEY(park_id) REFERENCES parks(id),FOREIGN KEY(slot_id) REFERENCES park_slots(id),FOREIGN KEY(driver_id) REFERENCES users(id) ON DELETE SET NULL,FOREIGN KEY(vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,FOREIGN KEY(guide_id) REFERENCES users(id) ON DELETE SET NULL,FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL);
CREATE TABLE species(id INT AUTO_INCREMENT PRIMARY KEY,common_name VARCHAR(120),scientific_name VARCHAR(160),category VARCHAR(50),protected TINYINT(1) DEFAULT 0,image_url VARCHAR(500));
CREATE TABLE wildlife_sightings(id INT AUTO_INCREMENT PRIMARY KEY,guide_id INT NOT NULL,park_id INT NOT NULL,species_id INT NOT NULL,booking_id INT NULL,zone_id INT NULL,zone VARCHAR(120),count_seen INT DEFAULT 1,notes TEXT,photo VARCHAR(255),latitude DECIMAL(10,7) NULL,longitude DECIMAL(10,7) NULL,observed_at DATETIME DEFAULT CURRENT_TIMESTAMP,review_status ENUM('normal','flagged','verified') DEFAULT 'normal',FOREIGN KEY(guide_id) REFERENCES users(id),FOREIGN KEY(park_id) REFERENCES parks(id),FOREIGN KEY(species_id) REFERENCES species(id),FOREIGN KEY(booking_id) REFERENCES bookings(id) ON DELETE SET NULL,FOREIGN KEY(zone_id) REFERENCES park_zones(id) ON DELETE SET NULL);
CREATE TABLE reviews(id INT AUTO_INCREMENT PRIMARY KEY,booking_id INT UNIQUE,visitor_id INT,guide_rating TINYINT NULL,vehicle_rating TINYINT NULL,driver_rating TINYINT NULL,overall_rating TINYINT NOT NULL,comment TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(booking_id) REFERENCES bookings(id),FOREIGN KEY(visitor_id) REFERENCES users(id));
CREATE TABLE notifications(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,message VARCHAR(500),is_read TINYINT(1) DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE INDEX idx_booking_date_slot_status ON bookings(entry_date,slot_id,status);
CREATE INDEX idx_booking_guide ON bookings(guide_id);
CREATE INDEX idx_booking_vehicle ON bookings(vehicle_id);
CREATE INDEX idx_notifications_user_read ON notifications(user_id,is_read);
CREATE INDEX idx_sightings_observed ON wildlife_sightings(observed_at);
INSERT INTO users(full_name,email,password_hash,role,status) VALUES ('System Administrator','admin@wildtrail.lk','$2y$12$my3iWKKFdhQ2TUmsmaj2KOSkVG7HEQpN.ArwoyxSfP1y2VOxE3q6K','superadmin','active'),('Nimali Perera','tourist@wildtrail.lk','$2y$12$my3iWKKFdhQ2TUmsmaj2KOSkVG7HEQpN.ArwoyxSfP1y2VOxE3q6K','tourist','active'),('Kasun Silva','driver@wildtrail.lk','$2y$12$my3iWKKFdhQ2TUmsmaj2KOSkVG7HEQpN.ArwoyxSfP1y2VOxE3q6K','driver','active'),('Kavindu Senanayake','guide@wildtrail.lk','$2y$12$my3iWKKFdhQ2TUmsmaj2KOSkVG7HEQpN.ArwoyxSfP1y2VOxE3q6K','guide','active');
INSERT INTO users(full_name,email,password_hash,role,status,phone) VALUES
('Nadeesha Fernando','guide2@wildtrail.lk','$2y$12$my3iWKKFdhQ2TUmsmaj2KOSkVG7HEQpN.ArwoyxSfP1y2VOxE3q6K','guide','active','0772345678'),
('Tharindu Jayasinghe','guide3@wildtrail.lk','$2y$12$my3iWKKFdhQ2TUmsmaj2KOSkVG7HEQpN.ArwoyxSfP1y2VOxE3q6K','guide','active','0713456789'),
('Sahan Perera','driver2@wildtrail.lk','$2y$12$my3iWKKFdhQ2TUmsmaj2KOSkVG7HEQpN.ArwoyxSfP1y2VOxE3q6K','driver','active','0764567890'),
('Ruwan Bandara','driver3@wildtrail.lk','$2y$12$my3iWKKFdhQ2TUmsmaj2KOSkVG7HEQpN.ArwoyxSfP1y2VOxE3q6K','driver','active','0755678901');
INSERT INTO parks(name,slug,province,description,image_url,entry_fee,daily_vehicle_cap) VALUES
('Yala National Park','yala','Southern Province','Dry forest, lagoons and open plains with one of Sri Lanka’s best-known leopard landscapes.','https://commons.wikimedia.org/wiki/Special:Redirect/file/Yala%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600',4500,80),
('Udawalawe National Park','udawalawe','Sabaragamuwa Province','Open grasslands and reservoir edges renowned for close elephant encounters.','https://commons.wikimedia.org/wiki/Special:Redirect/file/Landscape%20in%20Udawalawe%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600',3500,65),
('Minneriya National Park','minneriya','North Central Province','Ancient reservoir, grasslands and seasonal elephant gatherings.','https://commons.wikimedia.org/wiki/Special:Redirect/file/Minneriya%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600',3200,60),
('Wilpattu National Park','wilpattu','North Western Province','Sri Lanka’s largest national park, known for natural villus, sloth bears and leopards.','https://commons.wikimedia.org/wiki/Special:Redirect/file/Amazing%20Natural%20Landscapes.jpg?width=1600',4200,50);
INSERT INTO park_images(park_id,image_url,caption,credit,source_url,sort_order) VALUES
((SELECT id FROM parks WHERE slug='yala'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Yala%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600','Wetland landscape in Yala','Charles J. Sharp','https://commons.wikimedia.org/wiki/File:Yala_National_Park,_Sri_Lanka.jpg',1),
((SELECT id FROM parks WHERE slug='yala'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Leopard%20in%20the%20Yala%20National%20Park.jpg?width=1600','Sri Lankan leopard in Yala','Wikimedia Commons contributor','https://commons.wikimedia.org/wiki/File:Leopard_in_the_Yala_National_Park.jpg',2),
((SELECT id FROM parks WHERE slug='yala'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/An%20Elephant%20in%20Yala%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600','Elephant in Yala National Park','Richard Mortel','https://commons.wikimedia.org/wiki/File:An_Elephant_in_Yala_National_Park,_Sri_Lanka.jpg',3),
((SELECT id FROM parks WHERE slug='yala'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Elephant%20at%20Yala%20National%20Park.jpg?width=1600','Elephant at Yala','Satheesinthu','https://commons.wikimedia.org/wiki/File:Elephant_at_Yala_National_Park.jpg',4),
((SELECT id FROM parks WHERE slug='udawalawe'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Landscape%20in%20Udawalawe%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600','Reservoir landscape in Udawalawe','M.S Dulan De Silva','https://commons.wikimedia.org/wiki/File:Landscape_in_Udawalawe_National_Park,_Sri_Lanka.jpg',1),
((SELECT id FROM parks WHERE slug='udawalawe'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Udawalawe%20national%20park.jpg?width=1600','Elephants in Udawalawe','Ganiarachchi','https://commons.wikimedia.org/wiki/File:Udawalawe_national_park.jpg',2),
((SELECT id FROM parks WHERE slug='udawalawe'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Udawalawa%20National%20Park%20Wild%20Elephant.jpg?width=1600','Wild elephant in Udawalawe','Hasala Abhilasha','https://commons.wikimedia.org/wiki/File:Udawalawa_National_Park_Wild_Elephant.jpg',3),
((SELECT id FROM parks WHERE slug='udawalawe'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Udawalawe%20National%20Park.jpg?width=1600','Udawalawe National Park','AngelHuangLanka','https://commons.wikimedia.org/wiki/File:Udawalawe_National_Park.jpg',4),
((SELECT id FROM parks WHERE slug='minneriya'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Minneriya%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600','Minneriya landscape','Madhawa Dunuwila','https://commons.wikimedia.org/wiki/File:Minneriya_National_Park,_Sri_Lanka.jpg',1),
((SELECT id FROM parks WHERE slug='minneriya'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Elephants%20at%20Minneriya%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600','Elephant and calf at Minneriya','Eli Solidum','https://commons.wikimedia.org/wiki/File:Elephants_at_Minneriya_National_Park,_Sri_Lanka.jpg',2),
((SELECT id FROM parks WHERE slug='minneriya'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Minneriya%20National%20Park%2C%20elephants%20gathering.jpg?width=1600','Elephant gathering at Minneriya','Walter Gehr','https://commons.wikimedia.org/wiki/File:Minneriya_National_Park,_elephants_gathering.jpg',3),
((SELECT id FROM parks WHERE slug='minneriya'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Minneriya%20National%20Park%2C%20baby%20elephant.jpg?width=1600','Baby elephant in Minneriya','Walter Gehr','https://commons.wikimedia.org/wiki/File:Minneriya_National_Park,_baby_elephant.jpg',4),
((SELECT id FROM parks WHERE slug='wilpattu'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Amazing%20Natural%20Landscapes.jpg?width=1600','Natural landscape of Wilpattu','Jayani Jayasinghe','https://commons.wikimedia.org/wiki/File:Amazing_Natural_Landscapes.jpg',1),
((SELECT id FROM parks WHERE slug='wilpattu'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Sri%20Lankan%20leopard.jpg?width=1600','Sri Lankan leopard in Wilpattu','Hasith-lk','https://commons.wikimedia.org/wiki/File:Sri_Lankan_leopard.jpg',2),
((SELECT id FROM parks WHERE slug='wilpattu'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Deer%2CWilpattu%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600','Deer in Wilpattu','Chamrith','https://commons.wikimedia.org/wiki/File:Deer,Wilpattu_National_Park,_Sri_Lanka.jpg',3),
((SELECT id FROM parks WHERE slug='wilpattu'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Wilpattu%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600','Wildlife in Wilpattu National Park','Richard Mortel','https://commons.wikimedia.org/wiki/File:Wilpattu_National_Park,_Sri_Lanka.jpg',4),
((SELECT id FROM parks WHERE slug='yala'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Slothbearatyala.jpg?width=1600','Sloth bear in Yala National Park','Faslan','https://commons.wikimedia.org/wiki/File:Slothbearatyala.jpg',5),
((SELECT id FROM parks WHERE slug='udawalawe'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Sri%20Lankan%20Elephant.jpg?width=1600','Sri Lankan elephant in Udawalawe','Wikimedia Commons contributor','https://commons.wikimedia.org/wiki/File:Sri_Lankan_Elephant.jpg',5),
((SELECT id FROM parks WHERE slug='minneriya'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Herd%20of%20Elephant%20in%20Minneriya%20National%20Park.jpg?width=1600','Elephant herd in Minneriya','Chamara','https://commons.wikimedia.org/wiki/File:Herd_of_Elephant_in_Minneriya_National_Park.jpg',5),
((SELECT id FROM parks WHERE slug='wilpattu'),'https://commons.wikimedia.org/wiki/Special:Redirect/file/Sloth%20Bear%20-%20Wilpattu%20National%20Park.jpg?width=1600','Sloth bear in Wilpattu National Park','Nishan Silva','https://commons.wikimedia.org/wiki/File:Sloth_Bear_-_Wilpattu_National_Park.jpg',5);
-- Operational demo zones for the student system. These circles are not official DWC boundaries and can be edited/replaced with authoritative zone data later.
INSERT INTO park_zones(park_id,name,code,center_lat,center_lng,radius_m,sort_order) VALUES
((SELECT id FROM parks WHERE slug='yala'),'Zone A','A',6.3725,81.5142,3500,1),
((SELECT id FROM parks WHERE slug='yala'),'Zone B','B',6.4015,81.4880,3500,2),
((SELECT id FROM parks WHERE slug='yala'),'Zone C','C',6.3360,81.4720,3500,3),
((SELECT id FROM parks WHERE slug='yala'),'Zone D','D',6.4200,81.5350,3500,4),
((SELECT id FROM parks WHERE slug='udawalawe'),'Zone A','A',6.4746,80.8881,3500,1),
((SELECT id FROM parks WHERE slug='udawalawe'),'Zone B','B',6.4460,80.9160,3500,2),
((SELECT id FROM parks WHERE slug='minneriya'),'Zone A','A',8.0390,80.8890,3500,1),
((SELECT id FROM parks WHERE slug='minneriya'),'Zone B','B',8.0670,80.9140,3500,2),
((SELECT id FROM parks WHERE slug='wilpattu'),'Zone A','A',8.4560,80.0160,4000,1),
((SELECT id FROM parks WHERE slug='wilpattu'),'Zone B','B',8.5000,80.0600,4000,2);
INSERT INTO park_slots(park_id,label,slot_key,start_time,end_time,vehicle_cap) SELECT id,'Dawn','dawn','05:45','10:00',25 FROM parks;
INSERT INTO park_slots(park_id,label,slot_key,start_time,end_time,vehicle_cap) SELECT id,'Morning','morning','10:30','14:30',20 FROM parks;
INSERT INTO park_slots(park_id,label,slot_key,start_time,end_time,vehicle_cap) SELECT id,'Afternoon','afternoon','15:00','18:30',25 FROM parks;
INSERT INTO species(common_name,scientific_name,category,protected,image_url) VALUES ('Sri Lankan leopard','Panthera pardus kotiya','Mammal',1,'https://commons.wikimedia.org/wiki/Special:Redirect/file/Leopard%20in%20the%20Yala%20National%20Park.jpg?width=1000'),('Sri Lankan elephant','Elephas maximus maximus','Mammal',1,'https://commons.wikimedia.org/wiki/Special:Redirect/file/Udawalawa%20National%20Park%20Wild%20Elephant.jpg?width=1000'),('Painted stork','Mycteria leucocephala','Bird',0,'https://images.unsplash.com/photo-1552728089-57bdde30beb3?auto=format&fit=crop&w=900&q=84');

INSERT IGNORE INTO guide_profiles(user_id,license_no,bio,languages,specialties,experience_years,guide_fee,available,verified) VALUES
(4,'G-2048','Licensed wildlife guide focused on responsible sightings, visitor safety and low-impact field practice.','Sinhala, English, German','Leopard tracking, Birding, Photography',6,6500,1,1),
(5,'G-3112','Patient naturalist with a strong focus on elephants, families and first-time safari visitors.','Sinhala, English, French','Elephants, Family safaris, Botany',5,5500,1,1),
(6,'G-4277','Birding-focused field guide experienced in wetlands and dry-zone national parks.','Sinhala, English, Tamil','Birding, Wetlands, Conservation',8,7000,1,1);
INSERT IGNORE INTO driver_profiles(user_id,license_no,bio,languages,experience_years) VALUES
(3,'B1234567','Experienced safari driver focused on safe, respectful wildlife viewing.','Sinhala, English',7),
(7,'B2345678','Safari operator experienced with family groups and dry-zone routes.','Sinhala, English',5),
(8,'B3456789','Photography-friendly safari driver with extensive national park experience.','Sinhala, English, Tamil',9);
INSERT INTO vehicles(owner_id,plate_number,make_model,capacity,safari_type,amenities,base_price,registration_no,insurance_no,fitness_no,approval_status,status) VALUES
(3,'WP CAB 4821','Toyota Hilux Safari',6,'open','Raised seats, canopy, first-aid kit, binocular holder',18500,'REG-4821','INS-4821','FIT-4821','approved','available'),
(7,'SP CAG 7714','Mahindra Scorpio Safari',7,'covered','Covered roof, rain curtains, cooler box, USB charging',17000,'REG-7714','INS-7714','FIT-7714','approved','available'),
(8,'WP CAD 9042','Toyota Land Cruiser Safari',6,'premium','Premium seats, photography rail, cooler box, USB charging',24000,'REG-9042','INS-9042','FIT-9042','approved','available');

-- Demo park assignments. Real guides/drivers choose the parks they are authorised to serve in their profile/vehicle screen.
INSERT IGNORE INTO guide_parks(guide_id,park_id) SELECT u.id,p.id FROM users u CROSS JOIN parks p WHERE u.role='guide' AND u.status='active';
INSERT IGNORE INTO vehicle_parks(vehicle_id,park_id) SELECT v.id,p.id FROM vehicles v CROSS JOIN parks p WHERE v.approval_status='approved';

-- Demo availability for the next 90 days. This makes a fresh university/demo install immediately bookable.
-- Real guides and drivers can replace these values from their monthly availability screens.
INSERT INTO guide_availability(guide_id,available_date,slot,is_available)
SELECT u.id, DATE_ADD(CURDATE(), INTERVAL nums.n DAY), slots.slot, 1
FROM users u
CROSS JOIN (
  SELECT ones.n + tens.n*10 AS n
  FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) ones
  CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8) tens
) nums
CROSS JOIN (SELECT 'dawn' slot UNION ALL SELECT 'morning' UNION ALL SELECT 'afternoon') slots
WHERE u.role='guide' AND u.status='active';

INSERT INTO driver_availability(driver_id,available_date,slot,is_available)
SELECT u.id, DATE_ADD(CURDATE(), INTERVAL nums.n DAY), slots.slot, 1
FROM users u
CROSS JOIN (
  SELECT ones.n + tens.n*10 AS n
  FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) ones
  CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8) tens
) nums
CROSS JOIN (SELECT 'dawn' slot UNION ALL SELECT 'morning' UNION ALL SELECT 'afternoon') slots
WHERE u.role='driver' AND u.status='active';

INSERT INTO wildlife_sightings(guide_id,park_id,species_id,zone_id,zone,count_seen,notes,latitude,longitude,review_status,observed_at) VALUES
(4,(SELECT id FROM parks WHERE slug='yala'),(SELECT id FROM species WHERE common_name='Sri Lankan leopard'),(SELECT id FROM park_zones WHERE park_id=(SELECT id FROM parks WHERE slug='yala') AND code='A'),'Zone A',2,'Observed at a safe distance near scrub and water edge.',6.3725000,81.5142000,'verified',NOW()),
(4,(SELECT id FROM parks WHERE slug='udawalawe'),(SELECT id FROM species WHERE common_name='Sri Lankan elephant'),(SELECT id FROM park_zones WHERE park_id=(SELECT id FROM parks WHERE slug='udawalawe') AND code='A'),'Zone A',11,'Family group moving toward open grassland.',6.4746000,80.8881000,'verified',NOW()),
(4,(SELECT id FROM parks WHERE slug='minneriya'),(SELECT id FROM species WHERE common_name='Painted stork'),(SELECT id FROM park_zones WHERE park_id=(SELECT id FROM parks WHERE slug='minneriya') AND code='A'),'Zone A',5,'Feeding in shallow water.',8.0390000,80.8890000,'verified',NOW());
