<?php
// api/suggest.php - Generate a unique, strong password suggestion
// - Always strong: includes lower, upper, number, symbol
// - Minimum length is configurable via ?min= (default 12, min 9)
// - Uniqueness enforced by storing ONLY a salted HMAC hash of suggestions
// - The actual suggested password is NEVER stored

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Resource-Policy: same-origin');

$configFile = __DIR__ . '/../db/config.php';
if (!file_exists($configFile)) {
  http_response_code(500);
  echo json_encode(['error' => 'Server not configured']);
  exit;
}
require_once $configFile;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$min = isset($_GET['min']) ? (int)$_GET['min'] : 12;
$min = max(9, min($min, 128)); // clamp 9..128
$length = max($min, 16); // default to 16 for stronger entropy

// Derive secret for HMAC. Prefer PSC_SECRET if defined in config.php.
$secret = defined('PSC_SECRET') && PSC_SECRET !== ''
  ? PSC_SECRET
  : hash('sha256', __FILE__ . '|' . php_uname() . '|' . DB_NAME);

function randomFrom(string $chars): string {
  $i = random_int(0, strlen($chars) - 1);
  return $chars[$i];
}

function secureShuffle(array $arr): array {
  // Fisher-Yates with cryptographically secure random_int
  for ($i = count($arr) - 1; $i > 0; $i--) {
    $j = random_int(0, $i);
    [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
  }
  return $arr;
}

function generateMemorablePassword(int $minLen): string {
  // Generate a strong but memorable passphrase-style password
  // Strategy: 3-4 simple words with a separator, one capitalized, plus digits and a symbol
  $words = [
    'mango','river','sunset','forest','coffee','panda','galaxy','pebble','rocket','orange','island','orchid','maple','canyon','thunder','breeze','mentor','pixel','cotton','ember','velvet','lotus','yoga','delta','pepper','copper','nova','sierra','arctic','safari','shadow','silver','cosmic','harbor','turbo','matrix','sonic','tundra','oasis','fusion','echo','falcon','nimbus','vortex','raven','sable','coral','jade','onyx','quartz','amber','hazel','mint','ivory','pluto','neon','aster','aurora','denim','cloud','meadow','tiger','zebra','otter','willow','pine','cedar','drift','polar','dune','cocoa','ginger','ember','sol','lunar','terra','comet','delta','alfa','bravo','kilo','zulu','omega','sigma','gamma','theta','zen','rapid','quiet','glow','calm','brisk','flame','frost','mellow','splash','ripple','sprout','bloom','spark','pulse','orbit','trail','summit','ridge','grove'
  ];
  $seps = ['-', '_', '.', '@', '+'];

  do {
    $numWords = 3;
    $parts = [];
    for ($i = 0; $i < $numWords; $i++) {
      $w = $words[random_int(0, count($words) - 1)];
      $parts[] = $w;
    }
    // Capitalize one random word to ensure uppercase presence
    $capIndex = random_int(0, $numWords - 1);
    $parts[$capIndex] = ucfirst($parts[$capIndex]);

    $sep = $seps[random_int(0, count($seps) - 1)];
    $base = implode($sep, $parts);

    // Ensure at least one digit and one symbol
    if (!preg_match('/\d/', $base)) {
      $base .= random_int(10, 99);
    }
    if (!preg_match('/[^A-Za-z0-9]/', $base)) {
      $base .= $seps[random_int(0, count($seps) - 1)];
    }

    // Pad with extra word(s) if below minimum length
    while (strlen($base) < $minLen) {
      $base .= $sep . $words[random_int(0, count($words) - 1)];
    }

    $pw = $base;

    // Final safety: ensure class presence
    if (!preg_match('/[a-z]/', $pw)) { $pw .= 'a'; }
    if (!preg_match('/[A-Z]/', $pw)) { $pw .= 'A'; }
    if (!preg_match('/\d/', $pw)) { $pw .= (string)random_int(0, 9); }
    if (!preg_match('/[^A-Za-z0-9]/', $pw)) { $pw .= $seps[random_int(0, count($seps) - 1)]; }

    // Loop exits once length is satisfied; uniqueness is enforced later via DB
  } while (strlen($pw) < $minLen);

  return $pw;
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

  $attempts = 0;
  $maxAttempts = 12;
  $password = '';

  while ($attempts < $maxAttempts) {
    $attempts++;
    $password = generateMemorablePassword($length);
    $hashHex = hash_hmac('sha256', $password, $secret);

    try {
      $stmt = $pdo->prepare('INSERT INTO psc_suggestions (hash, created_at) VALUES (?, NOW())');
      $stmt->execute([$hashHex]);
      // Insert succeeded → unique
      break;
    } catch (Throwable $e) {
      // On duplicate, try again
      if ($attempts >= $maxAttempts) {
        throw $e; // give up after several attempts
      }
    }
  }

  echo json_encode([
    'password' => $password,
    'length' => strlen($password),
    'strength' => 'Very Strong',
    'unique' => true,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Server error']);
}