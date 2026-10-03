<?php
namespace WildTrail\Domain;
final class Tourist extends User {
    public function dashboardPath(): string {
        return 'tourist/dashboard.php';
    }
    public function roleLabel(): string {
        return 'Tourist';
    }
}
