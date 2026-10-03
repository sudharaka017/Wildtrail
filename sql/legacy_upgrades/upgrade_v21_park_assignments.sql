USE wildtrail_db;
CREATE TABLE IF NOT EXISTS vehicle_parks(vehicle_id INT NOT NULL,park_id INT NOT NULL,PRIMARY KEY(vehicle_id,park_id),FOREIGN KEY(vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,FOREIGN KEY(park_id) REFERENCES parks(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS guide_parks(guide_id INT NOT NULL,park_id INT NOT NULL,PRIMARY KEY(guide_id,park_id),FOREIGN KEY(guide_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(park_id) REFERENCES parks(id) ON DELETE CASCADE);
-- Preserve existing verified records during upgrade by assigning them to all active parks. Owners/guides can narrow these selections in their profile screens; doing so triggers re-verification.
INSERT IGNORE INTO guide_parks(guide_id,park_id) SELECT g.user_id,p.id FROM guide_profiles g CROSS JOIN parks p WHERE g.verified=1 AND p.status='active';
INSERT IGNORE INTO vehicle_parks(vehicle_id,park_id) SELECT v.id,p.id FROM vehicles v CROSS JOIN parks p WHERE v.approval_status='approved' AND p.status='active';
