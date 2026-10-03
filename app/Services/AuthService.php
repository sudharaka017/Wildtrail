<?php
namespace WildTrail\Services;

use WildTrail\Domain\User;
use WildTrail\Domain\UserFactory;
use WildTrail\Repositories\UserRepository;

final class AuthService
{
    public function __construct(private UserRepository $users) {}

    public function attempt(string $email, string $password): ?User
    {
        $row = $this->users->findByEmail($email);
        if (!$row || $row['status'] !== 'active' || empty($row['password_hash']) || !password_verify($password, $row['password_hash'])) {
            return null;
        }
        $user = UserFactory::fromArray($row);
        $this->login($user);
        $this->users->touchLastLogin($user->id());
        return $user;
    }

    public function login(User $user): void
    {
        session_regenerate_id(true);
        foreach ($user->toSessionArray() as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    public function loginFromArray(array $row): void
    {
        $this->login(UserFactory::fromArray($row));
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public function isLoggedIn(): bool { return !empty($_SESSION['user_id']); }

    public function requireLogin(string $loginPath='../login.php'): void
    {
        if (!$this->isLoggedIn()) { header('Location: '.$loginPath); exit; }
    }

    public function requireRole(array|string $roles, string $loginPath='../login.php'): void
    {
        $this->requireLogin($loginPath);
        if (!in_array((string)($_SESSION['user_role'] ?? ''), (array)$roles, true)) {
            http_response_code(403);
            exit('Access denied for this role.');
        }
    }
}
