<?php
/**
 * @var bool $needsSetup
 * @var array $flashes
 */
if (!function_exists('esc')) {
  function esc($value): string
  {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

// Put your logo image in public/assets and update this path if needed.
$logoWebPaths = [
  'public/assets/logo-1.png',
  'public/assets/logo-2.png',
];

$logoPaths = [];
foreach ($logoWebPaths as $path) {
  $fsPath = dirname(__DIR__, 2) . '/' . ltrim($path, '/');
  if (is_file($fsPath)) {
    $logoPaths[] = $path;
  }
}

$hasLogoImages = !empty($logoPaths);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>E-Legis Paperless Legislative Workflow and Document Management</title>
<link rel="stylesheet" href="public/assets/style.css" />
<script src="public/assets/app.js"></script>
</head>
<body class="login-page">
  <div class="login-shell">
    <div class="login-panel">
      <div class="login-brand">
        <div class="login-logo-row">
          <?php if ($hasLogoImages): ?>
            <?php foreach ($logoPaths as $index => $logoPath): ?>
            <div class="login-logo">
              <img src="<?= esc($logoPath) ?>" alt="E-Legis logo <?= $index + 1 ?>" class="login-logo-image" />
            </div>
            <?php endforeach; ?>
          <?php else: ?>
          <div class="login-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-image">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
          </div>
          <?php endif; ?>
        </div>
        <h1><?= $needsSetup ? 'Create Administrator Account' : 'E-Legis ' ?></h1>
        <p><?= $needsSetup ? 'You must create the first administrator account before signing in.' : 'Paperless Legislative Workflow and Document Management' ?></p>
      </div>

      <?php if (!empty($flashes)): ?>
        <div class="toast-wrap login-toast-wrap" style="margin-bottom:16px;display:flex;flex-direction:column;gap:8px;">
          <?php foreach ($flashes as $flash): ?>
            <div class="toast toast-<?= $flash['type'] === 'success' ? 'ok' : ($flash['type'] === 'error' ? 'err' : 'info') ?> show">
              <span><?= esc($flash['message']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($needsSetup): ?>
        <form class="login-form" method="post" action="index.php">
          <input type="hidden" name="action" value="create_first_admin" />
          <label for="name">Name</label>
          <input id="name" type="text" name="name" placeholder="Administrator name" autocomplete="name" required />
          <label for="email">Email</label>
          <input id="email" type="email" name="email" placeholder="admin@example.com" autocomplete="email" required autofocus />
          <label for="password">Password</label>
          <div class="password-field">
            <input id="password" type="password" name="password" placeholder="Create a secure password" autocomplete="new-password" required />
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
              <svg class="password-toggle-icon password-toggle-show" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              <svg class="password-toggle-icon password-toggle-hide" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 3l18 18"></path>
                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                <path d="M9.9 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a18.2 18.2 0 0 1-4.3 4.9"></path>
                <path d="M6.1 6.1C3.8 7.8 2 12 2 12s3.5 7 10 7c1 0 1.9-.1 2.7-.4"></path>
              </svg>
            </button>
          </div>
          <button type="submit" class="btn btn-primary">Create Admin Account</button>
        </form>
      <?php else: ?>
        <form class="login-form" method="post" action="index.php">
          <input type="hidden" name="action" value="login" />
          <label for="email">Email</label>
          <input id="email" type="email" name="email" placeholder="admin@example.com" autocomplete="email" required autofocus />
          <label for="password">Password</label>
          <div class="password-field">
            <input id="password" type="password" name="password" placeholder="Password" autocomplete="current-password" required />
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
              <svg class="password-toggle-icon password-toggle-show" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              <svg class="password-toggle-icon password-toggle-hide" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 3l18 18"></path>
                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                <path d="M9.9 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a18.2 18.2 0 0 1-4.3 4.9"></path>
                <path d="M6.1 6.1C3.8 7.8 2 12 2 12s3.5 7 10 7c1 0 1.9-.1 2.7-.4"></path>
              </svg>
            </button>
          </div>
          <label for="mode">Login As</label>
          <select id="mode" name="mode">
            <option value="admin">Admin</option>
            <option value="proceeding officer">Proceeding Officer</option>
            <option value="ctrfb">CTRFB</option>
            <option value="council members">Council Members</option>
            <option value="lce">Local Chief Executive / Mayor</option>
            <option value="mayor">Mayor</option>
            <option value="user">User</option>
          </select>
          <div class="field-note">Select your role to sign in.</div>
          <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <div id="pageLoader" class="page-loader" role="status" aria-live="polite" aria-label="Loading">
    <div class="loader-ring"></div>
    <div class="loader-text">Loading</div>
  </div>
</body>
</html>
