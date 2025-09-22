// app.js - Client-side password strength evaluation and logging (no password leaves browser)
(function () {
  const config = window.PSC_CONFIG || { apiBase: '/api', minLength: 12 };
  const form = document.getElementById('checkerForm');
  const input = document.getElementById('password');
  const targetUrl = document.getElementById('targetUrl');
  const togglePassword = document.getElementById('togglePassword');
  const suggestBtn = document.getElementById('suggestBtn');

  const bars = [
    document.getElementById('bar-1'),
    document.getElementById('bar-2'),
    document.getElementById('bar-3'),
    document.getElementById('bar-4')
  ];

  const badges = {
    length: document.getElementById('lengthBadge'),
    lower: document.getElementById('lowerBadge'),
    upper: document.getElementById('upperBadge'),
    number: document.getElementById('numberBadge'),
    symbol: document.getElementById('symbolBadge'),
  };

  const strengthIcon = document.getElementById('strengthIcon');
  const strengthLabel = document.getElementById('strengthLabel');

  const REQUIREMENTS = {
    minLength: config.rules?.minLength ?? config.minLength ?? 12,
    lowercase: true,
    uppercase: true,
    number: true,
    symbol: true,
  };

  function evaluatePassword(pw) {
    // Never send the password anywhere. Only compute locally.
    const lengthOk = pw.length >= REQUIREMENTS.minLength;
    const hasLower = /[a-z]/.test(pw);
    const hasUpper = /[A-Z]/.test(pw);
    const hasNumber = /\d/.test(pw);
    const hasSymbol = /[^A-Za-z0-9]/.test(pw);

    // Score from 0-4
    let score = 0;
    if (lengthOk) score++;
    if (hasLower && hasUpper) score++;
    if (hasNumber) score++;
    if (hasSymbol) score++;

    // Label
    let label = 'Weak';
    if (score === 1) label = 'Weak';
    if (score === 2) label = 'Medium';
    if (score === 3) label = 'Strong';
    if (score === 4) label = 'Very Strong';

    return {
      score,
      label,
      checks: { lengthOk, hasLower, hasUpper, hasNumber, hasSymbol }
    };
  }

  function setBars(score) {
    bars.forEach((bar, idx) => {
      bar.classList.remove('fill', 'level-1', 'level-2', 'level-3', 'level-4');
      if (idx < score) {
        bar.classList.add('fill', `level-${score}`);
      }
    });
  }

  function setBadges(checks) {
    badges.length.classList.toggle('ok', checks.lengthOk);
    badges.lower.classList.toggle('ok', checks.hasLower);
    badges.upper.classList.toggle('ok', checks.hasUpper);
    badges.number.classList.toggle('ok', checks.hasNumber);
    badges.symbol.classList.toggle('ok', checks.hasSymbol);
  }

  function setStatus(score, label) {
    strengthIcon.classList.remove('weak', 'medium', 'strong', 'very-strong', 'neutral');
    const map = { 0: 'neutral', 1: 'weak', 2: 'medium', 3: 'strong', 4: 'very-strong' };
    strengthIcon.classList.add(map[score] || 'neutral');
    strengthLabel.textContent = label;
  }

  function logOperation(url, score, checks) {
    // Only send metadata, NEVER the password
    try {
      const payload = {
        action_url: (url || '').toString().slice(0, 1024),
        score,
        rules: {
          minLength: REQUIREMENTS.minLength,
          lowercase: REQUIREMENTS.lowercase,
          uppercase: REQUIREMENTS.uppercase,
          number: REQUIREMENTS.number,
          symbol: REQUIREMENTS.symbol,
        },
        checks: {
          lengthOk: checks.lengthOk,
          hasLower: checks.hasLower,
          hasUpper: checks.hasUpper,
          hasNumber: checks.hasNumber,
          hasSymbol: checks.hasSymbol,
        }
      };
      fetch(`${config.apiBase}/log.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }).catch(() => {});
    } catch (e) {
      // No-op
    }
  }

  // Live updates
  input.addEventListener('input', () => {
    const { score, label, checks } = evaluatePassword(input.value);
    setBars(score);
    setBadges(checks);
    setStatus(score, label);
  });

  // Submit logs the operation (metadata only)
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const { score, label, checks } = evaluatePassword(input.value);
    setBars(score);
    setBadges(checks);
    setStatus(score, label);
    logOperation(targetUrl.value, score, checks);
  });

  // Reset
  form.addEventListener('reset', () => {
    setBars(0);
    setBadges({ lengthOk: false, hasLower: false, hasUpper: false, hasNumber: false, hasSymbol: false });
    setStatus(0, 'Start typing to check strength');
  });

  // Toggle visibility
  togglePassword.addEventListener('click', () => {
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
  });

  // Suggest button -> fetch unique strong and memorable password; fallback locally if API fails
  suggestBtn?.addEventListener('click', async () => {
    const applyPassword = (pw) => {
      input.value = pw;
      const { score, label, checks } = evaluatePassword(input.value);
      setBars(score);
      setBadges(checks);
      setStatus(score, label);
    };

    const fallbackLocal = () => {
      // Simple, memorable local generator: three words + separator + 2 digits
      const words = ['mango','river','sunset','forest','coffee','panda','galaxy','pebble','rocket','orange','island','orchid','maple','canyon','breeze','pixel','cotton','ember','velvet','lotus','pepper','copper','nova','willow','pine','cedar','drift','dune','ginger','lunar','terra','comet','zen','glow','calm','flame','frost','mellow','spark','orbit','trail','grove'];
      const seps = ['-', '_', '.', '@', '+'];
      const pick = (arr) => arr[Math.floor(Math.random() * arr.length)];
      const cap = (w) => w.charAt(0).toUpperCase() + w.slice(1);
      const w1 = pick(words);
      const w2 = cap(pick(words));
      const w3 = pick(words);
      const sep = pick(seps);
      let pw = `${w1}${sep}${w2}${sep}${w3}`;
      if (!/[0-9]/.test(pw)) pw += String(Math.floor(10 + Math.random() * 90));
      if (!/[^A-Za-z0-9]/.test(pw)) pw += pick(seps);
      while (pw.length < (config.rules?.minLength ?? config.minLength ?? 12)) {
        pw += sep + pick(words);
      }
      applyPassword(pw);
    };

    try {
      const res = await fetch(`${config.apiBase}/suggest.php?min=${encodeURIComponent(config.rules?.minLength ?? config.minLength ?? 12)}`, { method: 'GET' });
      if (!res.ok) throw new Error('Request failed');
      const data = await res.json();
      if (data && data.password) return applyPassword(data.password);
      fallbackLocal();
    } catch (e) {
      fallbackLocal();
    }
  });

  // Initialize
  setBars(0);
  setStatus(0, 'Start typing to check strength');
})();