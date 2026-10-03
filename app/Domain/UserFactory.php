<?php
namespace WildTrail\Domain;

use InvalidArgumentException;

final class UserFactory
{
    public static function fromArray(array $row): User
    {
        $args = [(int)$row['id'], (string)$row['full_name'], (string)$row['email'], (string)$row['role']];
        return match ($row['role']) {
            'tourist' => new Tourist(...$args),
            'guide' => new Guide(...$args),
            'driver' => new Driver(...$args),
            'admin' => new Admin(...$args),
            'superadmin' => new SuperAdmin(...$args),
            default => throw new InvalidArgumentException('Unknown user role.'),
        };
    }

    public static function dashboardForRole(string $role): string
    {
        $dummy = ['id'=>0, 'full_name'=>'', 'email'=>'', 'role'=>$role];
        try { return self::fromArray($dummy)->dashboardPath(); }
        catch (InvalidArgumentException $e) { return 'index.php'; }
    }
}
