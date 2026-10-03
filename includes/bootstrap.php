<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/security.php';
require_once __DIR__.'/auth.php';
verify_csrf();

// Release abandoned unpaid reservations so guides/jeeps are not blocked indefinitely.
try {
    $expired=$pdo->query("SELECT id,visitor_id,guide_id,driver_id,booking_code FROM bookings WHERE status='pending' AND payment_status='unpaid' AND reserved_until IS NOT NULL AND reserved_until < NOW()")->fetchAll();
    foreach($expired as $x){
        $pdo->prepare("UPDATE bookings SET status='cancelled',cancellation_reason='Payment window expired' WHERE id=? AND status='pending' AND payment_status='unpaid'")->execute([$x['id']]);
        $pdo->prepare("INSERT INTO notifications(user_id,message) VALUES(?,?)")->execute([$x['visitor_id'],"Reservation {$x['booking_code']} expired because payment was not completed within 15 minutes. The guide and jeep were released."]);
    }
} catch (Throwable $e) { /* older database: run v17 upgrade */ }

// Keep the safari lifecycle current: a paid, confirmed safari becomes completed after its visit date.
try { $pdo->exec("UPDATE bookings SET status='completed' WHERE status='confirmed' AND payment_status='paid' AND entry_date < CURDATE()"); } catch (Throwable $e) { /* Allows older databases to load during upgrades. */ }
