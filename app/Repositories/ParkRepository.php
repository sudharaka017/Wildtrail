<?php
namespace WildTrail\Repositories;
use PDO;
final class ParkRepository {
    public function __construct(private PDO $pdo) {
    }
    public function active(): array {
        return $this->pdo->query("SELECT * FROM parks WHERE status='active' ORDER BY name")->fetchAll();
    }
    public function activeSlots(int $parkId): array {
        $st=$this->pdo->prepare('SELECT * FROM park_slots WHERE park_id=? AND is_active=1 ORDER BY start_time');
        $st->execute([$parkId]);
        return $st->fetchAll();
    }
}
