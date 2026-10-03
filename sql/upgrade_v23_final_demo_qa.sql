USE wildtrail_db;
-- Optional performance indexes for an existing v22 database.
-- If an index already exists, skip that individual statement in phpMyAdmin.
CREATE INDEX idx_booking_date_slot_status ON bookings(entry_date,slot_id,status);
CREATE INDEX idx_booking_guide ON bookings(guide_id);
CREATE INDEX idx_booking_vehicle ON bookings(vehicle_id);
CREATE INDEX idx_notifications_user_read ON notifications(user_id,is_read);
CREATE INDEX idx_sightings_observed ON wildlife_sightings(observed_at);
