<?php
namespace WildTrail\Repositories;
use PDO;

final class ParkZoneRepository
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        return $this->pdo->query("SELECT z.*,p.name park_name FROM park_zones z JOIN parks p ON p.id=z.park_id WHERE z.is_active=1 ORDER BY p.name,z.sort_order,z.name")->fetchAll();
    }

    public function byPark(int $parkId): array
    {
        $st=$this->pdo->prepare("SELECT * FROM park_zones WHERE park_id=? AND is_active=1 ORDER BY sort_order,name");
        $st->execute([$parkId]);
        return $st->fetchAll();
    }
}
