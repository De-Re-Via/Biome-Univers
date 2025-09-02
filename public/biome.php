<?php
declare(strict_types=1);

// -------- CONFIG --------
$ALLOWED_ORIGIN = 'https://biomeexploreronline.netlify.app'; // domaine Netlify exact
$SECRET = 'change-me-long-secret';                           // DOIT être le même que dans index.php
$BOARD_MAX_BYTES = 200_000;

// -------- CORS --------
header('Content-Type: application/json; charset=utf-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === $ALLOWED_ORIGIN) { header("Access-Control-Allow-Origin: $origin"); header('Vary: Origin'); }
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') { http_response_code(200); echo '{"ok":true}'; exit; }

// -------- DB (PDO simple XAMPP) --------
try {
  $pdo = new PDO('mysql:host=127.0.0.1;dbname=biome_univers;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Throwable $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'db-conn']); exit; }

// -------- Auth HMAC légère --------
$uid = (int)($_GET['uid'] ?? 0);
$exp = (int)($_GET['exp'] ?? 0);
$sig = (string)($_GET['sig'] ?? '');
if ($uid <= 0 || $exp <= 0 || $sig === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'missing-params']); exit; }
$base = $uid . '|' . $exp;
$calc = rtrim(strtr(base64_encode(hash_hmac('sha256', $base, $SECRET, true)), '+/', '-_'), '=');
if (!hash_equals($calc, $sig) || time() > $exp) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'bad-signature']); exit; }

// -------- Routes --------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $st = $pdo->prepare('SELECT cycle,energy,biodiv,food,social,resilience,tech,board,version FROM biome_state WHERE user_id=?');
  $st->execute([$uid]);
  $row = $st->fetch();
  if (!$row) { echo json_encode(['ok'=>true,'state'=>null]); exit; }

  $axes = [
    'energy'=>(int)$row['energy'],'biodiv'=>(int)$row['biodiv'],'food'=>(int)$row['food'],
    'social'=>(int)$row['social'],'resilience'=>(int)$row['resilience'],'tech'=>(int)$row['tech']
  ];
  echo json_encode(['ok'=>true,'state'=>[
    'cycle'=>(int)$row['cycle'],
    'axes'=>$axes,
    'board'=> json_decode($row['board'] ?: '{}', true),
    'version'=>(int)$row['version']
  ]]); exit;
}

if ($method === 'POST') {
  $body = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
  $cycle   = max(1, (int)($body['cycle'] ?? 1));
  $axesIn  = $body['axes'] ?? [];
  $board   = $body['board'] ?? ['shape'=>'hex','radius'=>5,'size'=>44,'items'=>[]];
  $version = (int)($body['version'] ?? 1);

  $clamp = fn($v)=> max(0, min(100, (int)$v));
  $axes = [
    'energy'=>$clamp($axesIn['energy'] ?? 50),
    'biodiv'=>$clamp($axesIn['biodiv'] ?? 50),
    'food'=>$clamp($axesIn['food'] ?? 50),
    'social'=>$clamp($axesIn['social'] ?? 50),
    'resilience'=>$clamp($axesIn['resilience'] ?? 50),
    'tech'=>$clamp($axesIn['tech'] ?? 50),
  ];

  $boardJson = json_encode($board, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  if ($boardJson === false || strlen($boardJson) > $BOARD_MAX_BYTES) { http_response_code(413); echo json_encode(['ok'=>false,'error'=>'board-too-large']); exit; }

  $sql = "INSERT INTO biome_state (user_id,cycle,energy,biodiv,food,social,resilience,tech,board,version)
          VALUES (:u,:cy,:en,:bi,:fo,:so,:re,:te,:bd,:vr)
          ON DUPLICATE KEY UPDATE
            cycle=VALUES(cycle), energy=VALUES(energy), biodiv=VALUES(biodiv), food=VALUES(food),
            social=VALUES(social), resilience=VALUES(resilience), tech=VALUES(tech),
            board=VALUES(board), version=VALUES(version), updated_at=NOW()";
  $st = $pdo->prepare($sql);
  $st->execute([
    ':u'=>$uid, ':cy'=>$cycle,
    ':en'=>$axes['energy'], ':bi'=>$axes['biodiv'], ':fo'=>$axes['food'],
    ':so'=>$axes['social'], ':re'=>$axes['resilience'], ':te'=>$axes['tech'],
    ':bd'=>$boardJson, ':vr'=>$version
  ]);

  echo json_encode(['ok'=>true]); exit;
}

http_response_code(405);
echo json_encode(['ok'=>false,'error'=>'method-not-allowed']);
