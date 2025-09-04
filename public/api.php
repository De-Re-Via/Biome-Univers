<?php
declare(strict_types=1);

// --- DEBUG API UNIQUEMENT ---
// Laisse display_errors à 1 ici, le temps de vérifier.
// (Tu pourras repasser à 0 une fois OK.)
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

header('Content-Type: application/json; charset=utf-8');

/* CORS minimal: même si la page appelle en same-origin,
   on autorise Netlify au cas où le jeu appelle direct. */
$config = require __DIR__ . '/config.prod.php';
$allow = $config['CORS_ALLOW_ORIGINS'] ?? [];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allow, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Helper JSON input
function json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/* -------- ROUTAGE ROBUSTE --------
   - /api/auth/login         (réécriture Apache)
   - api.php?route=auth/login (secours)
*/
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$route = $_GET['route'] ?? ltrim($path, '/');
if (str_starts_with($route, 'public/')) $route = substr($route, 7); // au cas où...
if (str_starts_with($route, 'api/'))    $route = substr($route, 4);

// Health check (debug rapide)
if ($route === 'health') {
    echo json_encode(['ok' => true, 'php' => PHP_VERSION]); exit;
}

/* -------- DB -------- */
try {
    $pdo = new PDO(
        'mysql:host='.$config['DB_HOST'].';dbname='.$config['DB_NAME'].';charset=utf8mb4',
        $config['DB_USER'],
        $config['DB_PASS'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'db-conn','info'=>$e->getMessage()]);
    exit;
}

/* -------- ENDPOINTS -------- */

// POST auth/login  { email, password }
if ($route === 'auth/login' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $in = json_input();
    $email = trim((string)($in['email'] ?? ''));
    $password = (string)($in['password'] ?? '');

    if ($email === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'missing_credentials']); exit;
    }

    try {
        $st = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $u = $st->fetch();
        if (!$u || !password_verify($password, $u['password_hash'])) {
            http_response_code(401);
            echo json_encode(['ok'=>false,'error'=>'invalid_credentials']); exit;
        }

        $_SESSION['user'] = [
            'id'   => (int)$u['id'],
            'name' => $u['name'],
            'email'=> $u['email'],
            'role' => $u['role'],
        ];
        echo json_encode(['ok'=>true,'user'=>$_SESSION['user']]); exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>'login-failed','info'=>$e->getMessage()]); exit;
    }
}

// GET auth/me
if ($route === 'auth/me' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'error'=>'not_authenticated']); exit;
    }
    echo json_encode(['ok'=>true,'user'=>$_SESSION['user']]); exit;
}

// POST auth/register { name, email, password }
if ($route === 'auth/register' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $in = json_input();
    $name  = trim((string)($in['name']  ?? ''));
    $email = trim((string)($in['email'] ?? ''));
    $pass  = (string)($in['password']   ?? '');

    if ($name === '' || $email === '' || $pass === '') {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'missing_fields']); exit;
    }

    try {
        $st = $pdo->prepare('SELECT 1 FROM users WHERE email=? LIMIT 1');
        $st->execute([$email]);
        if ($st->fetch()) {
            http_response_code(409);
            echo json_encode(['ok'=>false,'error'=>'email_taken']); exit;
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO users(name,email,password_hash,role,is_active,created_at,updated_at) VALUES(?,?,?,?,1,NOW(),NOW())');
        $st->execute([$name,$email,$hash,'explorer']);
        $uid = (int)$pdo->lastInsertId();

        $_SESSION['user'] = ['id'=>$uid,'name'=>$name,'email'=>$email,'role'=>'explorer'];
        echo json_encode(['ok'=>true,'user'=>$_SESSION['user']]); exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>'register-failed','info'=>$e->getMessage()]); exit;
    }
}

// POST auth/logout
if ($route === 'auth/logout' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    echo json_encode(['ok'=>true]); exit;
}

http_response_code(404);
echo json_encode(['ok'=>false,'error'=>'not_found','route'=>$route]);
