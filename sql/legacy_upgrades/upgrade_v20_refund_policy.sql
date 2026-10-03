USE wildtrail_db;
ALTER TABLE bookings ADD COLUMN refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER refund_status;
ALTER TABLE bookings ADD COLUMN cancellation_fee DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER refund_amount;
-- Existing cancelled paid bookings are initialized to the same 50/50 policy for consistent display.
UPDATE bookings SET refund_amount=ROUND(amount*0.50,2), cancellation_fee=ROUND(amount*0.50,2) WHERE status='cancelled' AND payment_status IN('paid','refunded') AND refund_amount=0;
