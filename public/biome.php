<?php
declare(strict_types=1);

// -------- CONFIG --------
$config = require __DIR__ . '/config.prod.php';

// Autoriser le front (Netlify) à appeler cette API
$ALLOWED_ORIGIN = 'https://biomeexploreronline.netlify.app';
$BOARD_MAX_BYTES = 200_000;

// -------- CORS --------
header('Content-Type: application/json; charset=utf-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === $ALLOWED_ORIGIN) {
  header("Access-Control-Allow-Origin: $origin");
  header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
  http_response_code(200);
  echo '{"ok":true}';
  exit;
}

// -------- Auth HMAC légère --------
// Le jeu envoie uid, exp, sig dans la query string
$uid = (int)($_GET['uid'] ?? 0);
$exp = (int)($_GET['exp'] ?? 0);
$sig = (string)($_GET['sig'] ?? '');

if ($uid <= 0 || $exp <= 0 || $sig === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'missing-params']);
  exit;
}

$base = $uid . '|' . $exp;
$calc = rtrim(strtr(base64_encode(hash_hmac('sha256', $base, $config['SECRET'], true)), '+/', '-_'), '=');
if (!hash_equals($calc, $sig) || time() > $exp) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'bad-signature']);
  exit;
}

// -------- DB (PDO) --------
try {
  $pdo = new PDO(
    'mysql:host='.$config['DB_HOST'].';dbname='.$config['DB_NAME'].';charset=utf8mb4',
    $config['DB_USER'],
    $config['DB_PASS'],
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'db-conn']);
  exit;
}

// -------- Routes --------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  // Récupère la dernière sauvegarde
  $st = $pdo->prepare('
    SELECT cycle, energy, biodiv, food, social, resilience, tech, board, version
    FROM biome_state
    WHERE user_id = ?
  ');
  $st->execute([$uid]);
  $row = $st->fetch();

  if (!$row) {
    echo json_encode(['ok'=>true,'state'=>null]);
    exit;
  }

  $axes = [
    'energy'     => (int)$row['energy'],
    'biodiv'     => (int)$row['biodiv'],
    'food'       => (int)$row['food'],
    'social'     => (int)$row['social'],
    'resilience' => (int)$row['resilience'],
    'tech'       => (int)$row['tech'],
  ];

  echo json_encode([
    'ok'    => true,
    'state' => [
      'cycle'   => (int)$row['cycle'],
      'axes'    => $axes,
      'board'   => json_decode($row['board'] ?: '{}', true),
      'version' => (int)$row['version'],
    ]
  ]);
  exit;
}

if ($method === 'POST') {
  // Enregistre une sauvegarde
  $body = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];

  $cycle   = max(1, (int)($body['cycle'] ?? 1));
  $axesIn  = is_array($body['axes'] ?? null) ? $body['axes'] : [];
  $board   = $body['board'] ?? ['shape'=>'hex','radius'=>5,'size'=>44,'items'=>[]];
  $version = (int)($body['version'] ?? 1);

  $clamp = static fn($v) => max(0, min(100, (int)$v));
  $axes = [
    'energy'     => $clamp($axesIn['energy']     ?? 50),
    'biodiv'     => $clamp($axesIn['biodiv']     ?? 50),
    'food'       => $clamp($axesIn['food']       ?? 50),
    'social'     => $clamp($axesIn['social']     ?? 50),
    'resilience' => $clamp($axesIn['resilience'] ?? 50),
    'tech'       => $clamp($axesIn['tech']       ?? 50),
  ];

  $boardJson = json_encode($board, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  if ($boardJson === false || strlen($boardJson) > $BOARD_MAX_BYTES) {
    http_response_code(413);
    echo json_encode(['ok'=>false,'error'=>'board-too-large']);
    exit;
  }

  $sql = "
    INSERT INTO biome_state
      (user_id, cycle, energy, biodiv, food, social, resilience, tech, board, version)
    VALUES
      (:u, :cy, :en, :bi, :fo, :so, :re, :te, :bd, :vr)
    ON DUPLICATE KEY UPDATE
      cycle      = VALUES(cycle),
      energy     = VALUES(energy),
      biodiv     = VALUES(biodiv),
      food       = VALUES(food),
      social     = VALUES(social),
      resilience = VALUES(resilience),
      tech       = VALUES(tech),
      board      = VALUES(board),
      version    = VALUES(version),
      updated_at = NOW()
  ";

  $st = $pdo->prepare($sql);
  $st->execute([
    ':u'  => $uid,
    ':cy' => $cycle,
    ':en' => $axes['energy'],
    ':bi' => $axes['biodiv'],
    ':fo' => $axes['food'],
    ':so' => $axes['social'],
    ':re' => $axes['resilience'],
    ':te' => $axes['tech'],
    ':bd' => $boardJson,
    ':vr' => $version,
  ]);

  echo json_encode(['ok'=>true]);
  exit;
}

http_response_code(405);
echo json_encode(['ok'=>false,'error'=>'method-not-allowed']);
