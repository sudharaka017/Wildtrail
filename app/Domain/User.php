<?php
namespace WildTrail\Domain;

use WildTrail\Contracts\DashboardUserInterface;

abstract class User implements DashboardUserInterface
{
    public function __construct(
        protected int $id,
        protected string $fullName,
        protected string $email,
        protected string $role
    ) {}

    public function id(): int { return $this->id; }
    public function fullName(): string { return $this->fullName; }
    public function email(): string { return $this->email; }
    public function role(): string { return $this->role; }

    public function toSessionArray(): array
    {
        return [
            'user_id' => $this->id,
            'user_name' => $this->fullName,
            'user_email' => $this->email,
            'user_role' => $this->role,
        ];
    }
}
