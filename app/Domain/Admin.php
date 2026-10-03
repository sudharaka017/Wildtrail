<?php
namespace WildTrail\Domain;
class Admin extends User {
    public function dashboardPath(): string {
        return 'admin/dashboard.php';
    }
    public function roleLabel(): string {
        return 'Administrator';
    }
}
