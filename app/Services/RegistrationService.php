<?php
namespace WildTrail\Services;

use PDO;
use InvalidArgumentException;
use WildTrail\Repositories\UserRepository;
use WildTrail\Support\Validator;

final class RegistrationService
{
    public function __construct(private PDO $pdo, private UserRepository $users) {}

    public function register(
        string $name,
        string $email,
        string $phone,
        string $password,
        string $role,
        string $nic
    ): array {
        if (!Validator::name($name)) {
            throw new InvalidArgumentException('Invalid full name.');
        }
        if (!Validator::email($email)) {
            throw new InvalidArgumentException('Invalid email address.');
        }
        if (!Validator::sriLankanPhone($phone, true)) {
            throw new InvalidArgumentException('Invalid phone number.');
        }
        if (!Validator::password($password)) {
            throw new InvalidArgumentException('Password does not meet the security requirements.');
        }
        if (!Validator::nicOrPassport($nic, false)) {
            throw new InvalidArgumentException('Invalid NIC or passport number.');
        }
        if (!in_array($role, ['tourist', 'driver', 'guide'], true)) {
            throw new InvalidArgumentException('Invalid account type.');
        }

        $status = $role === 'tourist' ? 'active' : 'pending';
        $this->pdo->beginTransaction();

        try {
            $id = $this->users->create(
                trim($name),
                strtolower(trim($email)),
                trim($phone),
                password_hash($password, PASSWORD_DEFAULT),
                $role,
                $status,
                trim($nic)
            );

            if ($role === 'guide') {
                $this->pdo->prepare('INSERT INTO guide_profiles(user_id) VALUES(?)')->execute([$id]);
            }

            $this->pdo->commit();
            return ['id' => $id, 'status' => $status];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
