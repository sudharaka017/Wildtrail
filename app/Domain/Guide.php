<?php
namespace WildTrail\Domain;
final class Guide extends User {
    public function dashboardPath(): string {
        return 'guide/dashboard.php';
    }
    public function roleLabel(): string {
        return 'Licensed Wildlife Guide';
    }
}
