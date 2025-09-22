<?php
// api/log.php - Logs operations metadata to MySQL (never the password)
// Expected JSON:
// {
//   action_url: string,
//   score: number (0-4),
//   rules: { minLength: number, lowercase: bool, uppercase: bool, number: bool, symbol: bool },
//   checks: { lengthOk: bool, hasLower: bool, hasUpper: bool, hasNumber: bool, hasSymbol: bool }
// }

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Resource-Policy: same-origin');

// Load DB config
$configFile = __DIR__ . '/../db/config.php';
if (!file_exists($configFile)) {
  http_response_code(500);
  echo json_encode(['error' => 'Server not configured']);
  exit;
}
require_once $configFile;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON']);
  exit;
}

// Validate basic fields (no password present by design)
$actionUrl = isset($payload['action_url']) ? substr((string)$payload['action_url'], 0, 1024) : '';
$score = isset($payload['score']) ? (int)$payload['score'] : null;
$rules = isset($payload['rules']) && is_array($payload['rules']) ? $payload['rules'] : [];
$checks = isset($payload['checks']) && is_array($payload['checks']) ? $payload['checks'] : [];

if ($score === null || $score < 0 || $score > 4) {
  http_response_code(422);
  echo json_encode(['error' => 'Invalid score']);
  exit;
}

try {
  $pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]
  );

  $stmt = $pdo->prepare('INSERT INTO psc_logs (action_url, score, rules_json, checks_json, created_at, user_agent, ip_hash) VALUES (?, ?, ?, ?, NOW(), ?, ?)');
  $rulesJson = json_encode($rules, JSON_UNESCAPED_SLASHES);
  $checksJson = json_encode($checks, JSON_UNESCAPED_SLASHES);

  // Privacy: store only a salted hash of the IP, not the IP itself
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  $salt = hash('sha256', __FILE__ . php_uname());
  $ipHash = $ip ? hash('sha256', $salt . '|' . $ip) : null;

  $stmt->execute([$actionUrl, $score, $rulesJson, $checksJson, $ua, $ipHash]);

  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DB error']);
}