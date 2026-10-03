<?php
namespace WildTrail\Repositories;

use PDO;
use WildTrail\Domain\User;
use WildTrail\Domain\UserFactory;

final class UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
        $st->execute([strtolower(trim($email))]);
        return $st->fetch() ?: null;
    }

    public function findDomainUserById(int $id): ?User
    {
        $st = $this->pdo->prepare('SELECT id,full_name,email,role FROM users WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ? UserFactory::fromArray($row) : null;
    }

    public function touchLastLogin(int $id): void
    {
        $this->pdo->prepare('UPDATE users SET last_login=NOW() WHERE id=?')->execute([$id]);
    }

    public function create(string $name, string $email, string $phone, string $passwordHash, string $role, string $status, string $nic): int
    {
        $st = $this->pdo->prepare('INSERT INTO users(full_name,email,phone,password_hash,role,status,nic_passport) VALUES(?,?,?,?,?,?,?)');
        $st->execute([$name,$email,$phone,$passwordHash,$role,$status,$nic]);
        return (int)$this->pdo->lastInsertId();
    }
}
