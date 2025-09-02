<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\DB;

class AuthController extends Controller {
  public function register(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $d = $this->input();
    $name  = trim($d['name'] ?? '');
    $email = trim($d['email'] ?? '');
    $pass  = (string)($d['password'] ?? '');

    if ($name==='' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass)<6) {
      $this->json(['ok'=>false,'error'=>'Champs invalides (nom, email, mdp≥6)'], 422);
    }

    $pdo = DB::conn();
    $st = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $st->execute([$email]);
    if ($st->fetch()) $this->json(['ok'=>false,'error'=>'Email déjà utilisé'], 409);

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,"explorer")')
        ->execute([$name,$email,$hash]);
    $id = (int)$pdo->lastInsertId();

    $_SESSION['user'] = ['id'=>$id,'name'=>$name,'email'=>$email,'role'=>'explorer'];
    $this->json(['ok'=>true,'user'=>$_SESSION['user']]);
  }

  public function login(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $d = $this->input();
    $email = trim($d['email'] ?? '');
    $pass  = (string)($d['password'] ?? '');

    $pdo = DB::conn();
    $st = $pdo->prepare('SELECT id,name,email,password_hash,role,is_active FROM users WHERE email=? LIMIT 1');
    $st->execute([$email]);
    $u = $st->fetch();

    if (!$u || !$u['is_active'] || !password_verify($pass, $u['password_hash'])) {
      $this->json(['ok'=>false,'error'=>'Email ou mot de passe invalide'], 401);
    }

    $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id=?')->execute([$u['id']]);
    $_SESSION['user'] = ['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']];
    $this->json(['ok'=>true,'user'=>$_SESSION['user']]);
  }

  public function logout(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $_SESSION = []; session_destroy();
    $this->json(['ok'=>true]);
  }

  public function me(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $this->json(['ok'=>true,'user'=>$_SESSION['user'] ?? null]);
  }
}
