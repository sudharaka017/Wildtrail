<?php
use WildTrail\Domain\UserFactory;
use WildTrail\Repositories\UserRepository;
use WildTrail\Services\AuthService;

function auth_service(): AuthService {
    global $pdo;
    static $service=null;
    if($service===null) $service=new AuthService(new UserRepository($pdo));
    return $service;
}

function login_user($u){ auth_service()->loginFromArray($u); }
function logout_user(){ auth_service()->logout(); }
function require_login(){ auth_service()->requireLogin('../login.php'); }
function require_role($roles){ auth_service()->requireRole($roles,'../login.php'); }
function home_for_role($r){ return UserFactory::dashboardForRole((string)$r); }
