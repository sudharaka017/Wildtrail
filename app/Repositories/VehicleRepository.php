<?php
namespace WildTrail\Repositories;
use PDO;
final class VehicleRepository {
    public function __construct(private PDO $pdo) {
    }
    public function availableFor(int $parkId, int $guests, string $date, int $slotId, string $slotKey): array {
        $sql="SELECT v.*,u.full_name driver_name,u.phone driver_phone,COALESCE(AVG(r.vehicle_rating),0) vehicle_rating,COUNT(r.id) review_count FROM vehicles v JOIN users u ON u.id=v.owner_id LEFT JOIN bookings rb ON rb.vehicle_id=v.id LEFT JOIN reviews r ON r.booking_id=rb.id WHERE v.approval_status='approved' AND v.status='available' AND u.status='active' AND v.capacity>=? AND EXISTS(SELECT 1 FROM vehicle_parks vp WHERE vp.vehicle_id=v.id AND vp.park_id=?) AND NOT EXISTS(SELECT 1 FROM bookings bx WHERE bx.vehicle_id=v.id AND bx.entry_date=? AND EXISTS(SELECT 1 FROM park_slots bps WHERE bps.id=bx.slot_id AND bps.slot_key=?) AND bx.status IN('pending','confirmed')) AND EXISTS(SELECT 1 FROM driver_availability da WHERE da.driver_id=v.owner_id AND da.available_date=? AND da.slot=? AND da.is_available=1) GROUP BY v.id ORDER BY vehicle_rating DESC,v.base_price ASC";
        $st=$this->pdo->prepare($sql);
        $st->execute([$guests,$parkId,$date,$slotKey,$date,$slotKey]);
        return $st->fetchAll();
    }
    public function lockAvailable(int $vehicleId, int $guests, int $parkId): ?array {
        $st=$this->pdo->prepare("SELECT v.*,u.id driver_id FROM vehicles v JOIN users u ON u.id=v.owner_id WHERE v.id=? AND v.approval_status='approved' AND v.status='available' AND v.capacity>=? AND u.status='active' AND EXISTS(SELECT 1 FROM vehicle_parks vp WHERE vp.vehicle_id=v.id AND vp.park_id=?) FOR UPDATE");
        $st->execute([$vehicleId,$guests,$parkId]);
        return $st->fetch() ?: null;
    }
    public function isDriverClosed(int $driverId, string $date, string $slotKey): bool {
        $st=$this->pdo->prepare('SELECT is_available FROM driver_availability WHERE driver_id=? AND available_date=? AND slot=?');
        $st->execute([$driverId,$date,$slotKey]);
        $row=$st->fetch();
        return !$row || !(int)$row['is_available'];
    }
    public function hasConflict(int $vehicleId, string $date, string $slotKey): bool {
        $st=$this->pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN park_slots ps ON ps.id=b.slot_id WHERE b.vehicle_id=? AND b.entry_date=? AND ps.slot_key=? AND b.status IN('pending','confirmed')");
        $st->execute([$vehicleId,$date,$slotKey]);
        return (bool)$st->fetchColumn();
    }
}
