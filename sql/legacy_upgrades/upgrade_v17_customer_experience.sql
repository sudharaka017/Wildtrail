USE wildtrail_db;
ALTER TABLE bookings ADD COLUMN reserved_until DATETIME NULL AFTER cancellation_reason;
ALTER TABLE bookings ADD COLUMN refund_status ENUM('none','pending','refunded') NOT NULL DEFAULT 'none' AFTER reserved_until;
UPDATE bookings SET reserved_until=DATE_ADD(created_at, INTERVAL 15 MINUTE) WHERE status='pending' AND payment_status='unpaid' AND reserved_until IS NULL;
