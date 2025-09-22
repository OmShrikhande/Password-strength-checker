<?php
// db/config.php - Local MySQL credentials (DO NOT COMMIT SECRETS IN REAL PROJECTS)
// Update these constants to match your local MySQL setup.

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'password_checker');
define('DB_USER', 'root');
// If your local root has a password, set it here. XAMPP default is often empty string.
define('DB_PASS', '');

// Optional: secret used for HMAC hashing of suggestions (fallback derived if empty)
// Change this to a random long value in production.
if (!defined('PSC_SECRET')) {
  define('PSC_SECRET', '');
}