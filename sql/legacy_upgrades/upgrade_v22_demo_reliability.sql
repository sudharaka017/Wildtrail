-- WildTrail Lanka v22: explicit slot keys for cross-park conflict protection
ALTER TABLE park_slots ADD COLUMN slot_key ENUM('dawn','morning','afternoon') NULL AFTER label;
UPDATE park_slots SET slot_key=CASE WHEN LOWER(label) LIKE '%dawn%' THEN 'dawn' WHEN LOWER(label) LIKE '%afternoon%' THEN 'afternoon' ELSE 'morning' END WHERE slot_key IS NULL;
ALTER TABLE park_slots MODIFY slot_key ENUM('dawn','morning','afternoon') NOT NULL;
CREATE INDEX idx_bookings_date_slot_status ON bookings(entry_date,slot_id,status);
CREATE INDEX idx_bookings_guide ON bookings(guide_id);
CREATE INDEX idx_bookings_vehicle ON bookings(vehicle_id);
CREATE INDEX idx_notifications_user_read ON notifications(user_id,is_read);
CREATE INDEX idx_sightings_observed ON wildlife_sightings(observed_at);

-- Final demo QA indexes (safe to skip if already present in a manually upgraded DB)
-- Fresh installs already receive these from schema.sql in v23.
