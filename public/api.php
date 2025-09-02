<?php
declare(strict_types=1);


require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\AuthController;

header('Content-Type: application/json; charset=utf-8');

$path   = '/' . trim($_GET['path'] ?? '', '/');          // ex: /auth/login
$method = $_SERVER['REQUEST_METHOD'];

$auth = new AuthController();

switch (true) {
  case $path === '/auth/register' && $method === 'POST':
    $auth->register(); break;

  case $path === '/auth/login' && $method === 'POST':
    $auth->login(); break;

  case $path === '/auth/logout' && $method === 'POST':
    $auth->logout(); break;

  case $path === '/auth/me' && $method === 'GET':
    $auth->me(); break;

  default:
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'Not Found']);
}
