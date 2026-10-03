<?php
namespace WildTrail\Repositories;
use PDO;
final class WildlifeRepository {
    public function __construct(private PDO $pdo) {
    }
    public function species(): array {
        return $this->pdo->query('SELECT * FROM species ORDER BY protected DESC, common_name')->fetchAll();
    }
    public function recent(int $limit=8, bool $verifiedOnly=false): array {
        $limit=max(1,min($limit,50));
        $where=$verifiedOnly ? " WHERE w.review_status='verified'" : '';
        return $this->pdo->query("SELECT w.*,s.common_name,s.protected,p.name park_name,z.name zone_name FROM wildlife_sightings w JOIN species s ON s.id=w.species_id JOIN parks p ON p.id=w.park_id LEFT JOIN park_zones z ON z.id=w.zone_id{$where} ORDER BY observed_at DESC LIMIT {$limit}")->fetchAll();
    }
    public function zoneActivity(int $parkId=0,int $speciesId=0,int $days=7,bool $verifiedOnly=false): array {
        $days=max(1,min($days,365));
        $where=['z.is_active=1'];
        $args=[];
        if($parkId) {
            $where[]='z.park_id=?';
            $args[]=$parkId;
        }
        $join="LEFT JOIN wildlife_sightings w ON w.zone_id=z.id AND w.observed_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        if($speciesId) {
            $join.=' AND w.species_id='.(int)$speciesId;
        }
        if($verifiedOnly) {
            $join.=" AND w.review_status='verified'";
        }
        $sql="SELECT z.id,z.park_id,z.name zone_name,z.code,z.center_lat,z.center_lng,z.radius_m,p.name park_name,COUNT(w.id) report_count,COALESCE(SUM(w.count_seen),0) animal_count,MAX(w.observed_at) last_seen FROM park_zones z JOIN parks p ON p.id=z.park_id {$join} WHERE ".implode(' AND ',$where)." GROUP BY z.id,z.park_id,z.name,z.code,z.center_lat,z.center_lng,z.radius_m,p.name ORDER BY p.name,z.sort_order,z.name";
        $st=$this->pdo->prepare($sql);
        $st->execute($args);
        return $st->fetchAll();
    }
    public function detailedSightings(int $parkId=0,int $speciesId=0,int $days=7): array {
        $days=max(1,min($days,365));
        $where=["w.observed_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)"];
        $args=[];
        if($parkId) {
            $where[]='w.park_id=?';
            $args[]=$parkId;
        }
        if($speciesId) {
            $where[]='w.species_id=?';
            $args[]=$speciesId;
        }
        $sql="SELECT w.*,s.common_name,s.protected,p.name park_name,z.name zone_name,u.full_name guide_name FROM wildlife_sightings w JOIN species s ON s.id=w.species_id JOIN parks p ON p.id=w.park_id JOIN users u ON u.id=w.guide_id LEFT JOIN park_zones z ON z.id=w.zone_id WHERE ".implode(' AND ',$where)." ORDER BY w.observed_at DESC LIMIT 500";
        $st=$this->pdo->prepare($sql);
        $st->execute($args);
        return $st->fetchAll();
    }
}
