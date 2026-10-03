-- WildTrail Lanka v5: zone-based wildlife activity map upgrade
-- Run AFTER upgrade_v2.sql and upgrade_v3.sql when keeping an older database.
-- The seeded circles are operational demo zones for this student system, NOT official DWC boundary polygons.

CREATE TABLE IF NOT EXISTS park_zones(
  id INT AUTO_INCREMENT PRIMARY KEY,
  park_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(40) NOT NULL,
  center_lat DECIMAL(10,7) NOT NULL,
  center_lng DECIMAL(10,7) NOT NULL,
  radius_m INT DEFAULT 2500,
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  UNIQUE KEY uq_park_zone_code(park_id,code),
  CONSTRAINT fk_zone_park FOREIGN KEY(park_id) REFERENCES parks(id) ON DELETE CASCADE
);

ALTER TABLE wildlife_sightings
  ADD COLUMN IF NOT EXISTS zone_id INT NULL AFTER booking_id;

INSERT IGNORE INTO park_zones(park_id,name,code,center_lat,center_lng,radius_m,sort_order) VALUES
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

-- Link older text-zone demo records where possible.
UPDATE wildlife_sightings w
JOIN park_zones z ON z.park_id=w.park_id AND (LOWER(TRIM(w.zone))=LOWER(TRIM(z.name)) OR LOWER(TRIM(w.zone))=LOWER(CONCAT('Block ',z.code)))
SET w.zone_id=z.id
WHERE w.zone_id IS NULL;

-- Fresh installs created from schema.sql include a foreign key from wildlife_sightings.zone_id to park_zones.id.
-- This upgrade intentionally avoids adding a named FK so it can be safely re-run on varied XAMPP/MariaDB versions.
