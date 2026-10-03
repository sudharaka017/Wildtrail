<?php
namespace WildTrail\Domain;
final class SuperAdmin extends Admin {
    public function dashboardPath(): string {
        return 'admin/dashboard.php';
    }
    public function roleLabel(): string {
        return 'Super Administrator';
    }
}
