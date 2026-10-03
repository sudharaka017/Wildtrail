<?php
namespace WildTrail\Domain;
final class Driver extends User {
    public function dashboardPath(): string {
        return 'driver/dashboard.php';
    }
    public function roleLabel(): string {
        return 'Driver / Jeep Owner';
    }
}
