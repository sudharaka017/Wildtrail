<?php
namespace WildTrail\Contracts;
interface DashboardUserInterface {
    public function dashboardPath(): string;
    public function roleLabel(): string;
}
