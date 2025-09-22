<?php
// index.php - Main UI for Password Strength Checker
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Password Strength Checker</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/password strength checker/assets/css/styles.css" />
</head>
<body>
  <div class="container">
    <header class="header">
      <h1>Password Strength Checker</h1>
      <p class="subtitle">Fast, private, and local. Your password is never stored.</p>
    </header>

    <section class="card">
      <form id="checkerForm" autocomplete="off" novalidate>
        <div class="field">
          <label for="password">Password</label>
          <div class="password-input">
            <input type="password" id="password" name="password" placeholder="Enter a password" aria-describedby="passwordHelp" />
            <button type="button" id="togglePassword" class="icon-button" aria-label="Toggle visibility" title="Show/Hide password">
              <span class="icon-eye" aria-hidden="true"></span>
            </button>
          </div>
          <small id="passwordHelp" class="hint">We never store your password. Checks happen in your browser.</small>
        </div>

        <div class="strength-meter" aria-hidden="true">
          <div class="bar" id="bar-1"></div>
          <div class="bar" id="bar-2"></div>
          <div class="bar" id="bar-3"></div>
          <div class="bar" id="bar-4"></div>
        </div>

        <div class="strength-status">
          <div class="status-left">
            <span id="strengthIcon" class="status-icon neutral" aria-hidden="true"></span>
            <span id="strengthLabel" class="status-label">Start typing to check strength</span>
          </div>
          <div class="status-right">
            <span class="badge" id="lengthBadge" title="At least 12 characters">Len</span>
            <span class="badge" id="lowerBadge" title="Lowercase letter">a</span>
            <span class="badge" id="upperBadge" title="Uppercase letter">A</span>
            <span class="badge" id="numberBadge" title="Number">1</span>
            <span class="badge" id="symbolBadge" title="Special character">@</span>
          </div>
        </div>

        <div class="field">
          <label for="targetUrl">Form Action URL (optional)</label>
          <input type="url" id="targetUrl" name="targetUrl" placeholder="Paste a form action URL to validate password instantly" />
          <small class="hint">We will not send your password. The URL is used only for validation context and logging.</small>
        </div>

        <div class="actions">
          <button type="submit" class="btn-primary">Check Now</button>
          <button type="button" id="suggestBtn" class="btn-ghost" title="Suggest a unique, strong password">Suggest Strong Password</button>
          <button type="reset" class="btn-ghost">Reset</button>
        </div>
      </form>
    </section>

    <footer class="footer">
      <p>Local, secure, and privacy-first. © <?php echo date('Y'); ?></p>
    </footer>
  </div>

  <script>
    window.PSC_CONFIG = {
      apiBase: '/password strength checker/api',
      minLength: 12,
      rules: {
        minLength: 12,
        lowercase: true,
        uppercase: true,
        number: true,
        symbol: true
      }
    };
  </script>
  <script src="/password strength checker/assets/js/app.js"></script>
</body>
</html>