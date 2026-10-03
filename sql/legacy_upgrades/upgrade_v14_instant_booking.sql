-- WildTrail Lanka v14: instant availability booking
-- Run once on an existing v12/v13 database. No user/booking data is deleted.
-- Booking-level admin approval is retired. Staff verification of guides/vehicles remains.
UPDATE bookings
SET approval_status='approved',
    approved_at=COALESCE(approved_at,created_at),
    rejection_reason=NULL
WHERE status <> 'cancelled' AND approval_status='pending';

-- Previously rejected requests stay rejected for audit/history. New v14 bookings are inserted as approved automatically.
