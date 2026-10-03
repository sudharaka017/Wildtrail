-- WildTrail Lanka v12: realistic administrator approval before PayHere payment.
-- Safe for an existing v11 database. Run once in phpMyAdmin.
ALTER TABLE bookings
  ADD COLUMN approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER payment_status,
  ADD COLUMN approved_by INT NULL AFTER approval_status,
  ADD COLUMN approved_at DATETIME NULL AFTER approved_by,
  ADD COLUMN rejection_reason VARCHAR(255) NULL AFTER approved_at,
  ADD CONSTRAINT fk_bookings_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

-- Preserve already-paid historical bookings as approved.
UPDATE bookings SET approval_status='approved', approved_at=COALESCE(approved_at,created_at)
WHERE payment_status='paid';
