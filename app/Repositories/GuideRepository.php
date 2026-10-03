<?php
namespace WildTrail\Repositories;
use PDO;
final class GuideRepository {
    public function __construct(private PDO $pdo) {
    }
    public function availableFor(int $parkId, string $date, int $slotId, string $slotKey, string $language=''): array {
        $sql="SELECT u.id,u.full_name,u.avatar_url,g.*,COALESCE(AVG(r.guide_rating),0) guide_rating,COUNT(r.id) review_count FROM users u JOIN guide_profiles g ON g.user_id=u.id LEFT JOIN bookings rb ON rb.guide_id=u.id LEFT JOIN reviews r ON r.booking_id=rb.id WHERE u.role='guide' AND u.status='active' AND g.verified=1 AND g.available=1 AND EXISTS(SELECT 1 FROM guide_parks gp WHERE gp.guide_id=u.id AND gp.park_id=?) AND NOT EXISTS(SELECT 1 FROM bookings bx WHERE bx.guide_id=u.id AND bx.entry_date=? AND EXISTS(SELECT 1 FROM park_slots bps WHERE bps.id=bx.slot_id AND bps.slot_key=?) AND bx.status IN('pending','confirmed')) AND EXISTS(SELECT 1 FROM guide_availability ga WHERE ga.guide_id=u.id AND ga.available_date=? AND ga.slot=? AND ga.is_available=1)";
        $params=[$parkId,$date,$slotKey,$date,$slotKey];
        if($language!=='') {
            $sql.=' AND g.languages LIKE ?';
            $params[]='%'.$language.'%';
        }
        $sql.=' GROUP BY u.id ORDER BY guide_rating DESC,g.experience_years DESC,g.guide_fee ASC';
        $st=$this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
    public function lockAvailable(int $guideId, int $parkId): ?array {
        $st=$this->pdo->prepare("SELECT g.*,u.id FROM guide_profiles g JOIN users u ON u.id=g.user_id WHERE g.user_id=? AND g.verified=1 AND g.available=1 AND u.status='active' AND EXISTS(SELECT 1 FROM guide_parks gp WHERE gp.guide_id=g.user_id AND gp.park_id=?) FOR UPDATE");
        $st->execute([$guideId,$parkId]);
        return $st->fetch() ?: null;
    }
    public function isClosed(int $guideId, string $date, string $slotKey): bool {
        $st=$this->pdo->prepare('SELECT is_available FROM guide_availability WHERE guide_id=? AND available_date=? AND slot=?');
        $st->execute([$guideId,$date,$slotKey]);
        $row=$st->fetch();
        return !$row || !(int)$row['is_available'];
    }
    public function hasConflict(int $guideId, string $date, string $slotKey): bool {
        $st=$this->pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN park_slots ps ON ps.id=b.slot_id WHERE b.guide_id=? AND b.entry_date=? AND ps.slot_key=? AND b.status IN('pending','confirmed')");
        $st->execute([$guideId,$date,$slotKey]);
        return (bool)$st->fetchColumn();
    }
}
