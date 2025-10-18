<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/background_tasks.php';
EnderBitBackgroundTasks::runScheduledTasks();

// ensure settings exist so reCAPTCHA key is available
$settingsFile = __DIR__ . '/settings.json';
if (!file_exists($settingsFile)) {
    file_put_contents($settingsFile, json_encode([
        'require_email_verify' => true,
        'require_admin_approve' => false
    ], JSON_PRETTY_PRINT));
}
$settings = json_decode(file_get_contents($settingsFile), true);
$msg = $_GET['msg'] ?? '';
$type = $_GET['type'] ?? ''; // 'success' or 'error'
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Sign Up - EnderBit</title>
<link rel="icon" type="image/png" sizes="96x96" href="/icon.png">
<style>
  :root {
    --bg:#0a0e14; --card:#151b26; --accent:#5f99ff; --primary:#4a7dff;
    --muted:#7d8fa9; --green:#3fb950; --red:#ff5252; --yellow:#ffa500;
    --text:#e6edf3; --input-bg:#1a2332; --input-border:#2d3544;
    --bg-gradient:#0d1117; --shadow:rgba(0,0,0,.4);
  }
  [data-theme="light"] {
    --bg:#f6f8fa; --card:#ffffff; --accent:#0969da; --primary:#0969da;
    --muted:#57606a; --green:#1a7f37; --red:#d1242f; --yellow:#bf8700;
    --text:#1f2328; --input-bg:#f6f8fa; --input-border:#d0d7de;
    --bg-gradient:#ffffff; --shadow:rgba(0,0,0,.1);
  }

  * { margin:0; padding:0; box-sizing:border-box; }
  
  html, body {
    height:100%;
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
    background:linear-gradient(135deg,var(--bg) 0%,var(--bg-gradient) 100%);
    color:var(--text);
  }

  /* Navigation */
  nav {
    background:var(--card);
    border-bottom:1px solid var(--input-border);
    padding:16px 0;
    position:sticky;
    top:0;
    z-index:1000;
    box-shadow:0 2px 8px rgba(0,0,0,.3);
  }
  nav .container {
    max-width:1200px;
    margin:0 auto;
    padding:0 24px;
    display:flex;
    justify-content:space-between;
    align-items:center;
  }
  nav .logo {
    font-size:24px;
    font-weight:700;
    color:var(--accent);
    text-decoration:none;
  }
  nav .nav-links {
    display:flex;
    gap:28px;
    align-items:center;
  }
  nav .nav-links a {
    color:var(--text);
    text-decoration:none;
    font-weight:500;
    transition:color .2s;
  }
  nav .nav-links a:hover { color:var(--accent); }
  nav .nav-links a.active { color:var(--accent); }
  
  /* Dropdown */
  nav .dropdown {
    position:relative;
    display:inline-block;
  }
  nav .dropdown-content {
    display:none;
    position:absolute;
    top:100%;
    left:0;
    background:var(--card);
    min-width:180px;
    box-shadow:0 8px 16px rgba(0,0,0,.4);
    border:1px solid var(--input-border);
    border-radius:8px;
    margin-top:0;
    padding-top:8px;
    z-index:1000;
  }
  nav .dropdown-content a {
    display:block;
    padding:12px 20px;
    text-decoration:none;
    color:var(--text);
    transition:background .2s;
  }
  nav .dropdown-content a:hover {
    background:var(--input-bg);
    color:var(--accent);
  }
  nav .dropdown:hover .dropdown-content {
    display:block;
  }
  
  /* Theme Toggle */
  .theme-toggle {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:8px;
    padding:6px 10px;
    font-size:14px;
    cursor:pointer;
    color:var(--text);
    vertical-align:middle;
    line-height:1;
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }

  /* Main Content */
  .main-wrapper {
    min-height:calc(100vh - 140px);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
  }

  .signup-container {
    width:100%;
    max-width:1100px;
    background:var(--card);
    border-radius:20px;
    box-shadow:0 20px 60px var(--shadow);
    overflow:hidden;
    border:1px solid var(--input-border);
    margin:auto;
  }

  .signup-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:0;
  }

  /* Left Side - Info */
  .signup-info {
    background:linear-gradient(135deg,var(--primary) 0%,var(--accent) 100%);
    padding:40px;
    color:#fff;
    display:flex;
    flex-direction:column;
    justify-content:center;
  }

  .signup-info h1 {
    font-size:36px;
    font-weight:800;
    margin-bottom:16px;
    line-height:1.2;
    letter-spacing:-1px;
  }

  .signup-info p {
    font-size:16px;
    line-height:1.6;
    opacity:0.95;
    margin-bottom:30px;
  }

  .feature-list {
    list-style:none;
  }

  .feature-list li {
    font-size:16px;
    margin-bottom:16px;
    padding-left:32px;
    position:relative;
    opacity:0.95;
  }

  .feature-list li:before {
    content:"✓";
    position:absolute;
    left:0;
    font-size:20px;
    font-weight:700;
    color:#fff;
  }

  /* Right Side - Form */
  .signup-form {
    padding:40px;
  }

  .signup-form h2 {
    font-size:26px;
    font-weight:700;
    margin-bottom:8px;
    color:var(--text);
  }

  .signup-form .subtitle {
    color:var(--muted);
    margin-bottom:24px;
    font-size:14px;
  }

  .form-group {
    margin-bottom:16px;
  }

  .form-group label {
    display:block;
    font-size:13px;
    font-weight:600;
    margin-bottom:6px;
    color:var(--text);
  }

  .form-group input {
    width:100%;
    padding:12px 14px;
    border-radius:10px;
    border:2px solid var(--input-border);
    background:var(--input-bg);
    color:var(--text);
    font-size:14px;
    transition:all .2s;
  }

  .form-group input:focus {
    outline:none;
    border-color:var(--accent);
    background:var(--card);
  }

  .form-row {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
  }

  .recaptcha-wrapper {
    display:flex;
    justify-content:center;
    margin:20px 0;
  }

  .btn {
    width:100%;
    padding:14px;
    border-radius:10px;
    font-weight:600;
    font-size:15px;
    text-align:center;
    cursor:pointer;
    border:none;
    transition:all .2s;
  }

  .btn-primary {
    background:linear-gradient(135deg,var(--primary) 0%,var(--accent) 100%);
    color:#fff;
    margin-bottom:10px;
  }

  .btn-primary:hover {
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(79,125,255,.3);
  }

  .btn-secondary {
    background:var(--input-bg);
    color:var(--text);
    border:2px solid var(--input-border);
    text-decoration:none;
    display:block;
  }

  .btn-secondary:hover {
    background:var(--input-border);
  }

  .help-text {
    text-align:center;
    margin-top:24px;
    color:var(--muted);
    font-size:14px;
  }

  .help-text a {
    color:var(--accent);
    text-decoration:none;
    font-weight:600;
  }

  /* Banner */
  .banner {
    position:fixed;
    left:-500px;
    top:80px;
    padding:16px 24px;
    border-radius:12px;
    min-width:320px;
    max-width:420px;
    box-shadow:0 10px 40px rgba(0,0,0,.3);
    display:flex;
    justify-content:space-between;
    align-items:center;
    transition:left .4s cubic-bezier(0.4,0,0.2,1);
    z-index:2000;
    font-weight:500;
  }
  .banner.show { left:20px; }
  .banner.success { background:var(--green); color:#fff; }
  .banner.error { background:var(--red); color:#fff; }
  .banner .close {
    cursor:pointer;
    font-size:20px;
    opacity:0.8;
    margin-left:16px;
  }
  .banner .close:hover { opacity:1; }

  /* Footer */
  footer {
    background:var(--card);
    border-top:1px solid var(--input-border);
    padding:24px;
    text-align:center;
    color:var(--muted);
    font-size:14px;
  }
  footer a {
    color:var(--accent);
    text-decoration:none;
  }

  /* Responsive */
  @media (max-width:968px) {
    .signup-grid {
      grid-template-columns:1fr;
    }
    .signup-info {
      padding:30px 24px;
    }
    .signup-info h1 {
      font-size:28px;
    }
    .signup-form {
      padding:30px 24px;
    }
    .form-row {
      grid-template-columns:1fr;
    }
    nav .nav-links {
      gap:20px;
      font-size:14px;
    }
    .main-wrapper {
      padding:10px;
    }
  }
</style>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
  <nav>
    <div class="container">
      <a href="/" class="logo">EnderBit</a>
      <div class="nav-links">
        <a href="/services.php">Services</a>
        <a href="/signup.php" class="active">Sign Up</a>
        <a href="<?= htmlspecialchars($config['ptero_url'] ?? '#') ?>" target="_blank">Login</a>
        <div class="dropdown">
          <a href="#">Support ▼</a>
          <div class="dropdown-content">
            <a href="/support.php">🎫 Submit Ticket</a>
            <a href="/faq.php">📚 Knowledge Base</a>
          </div>
        </div>
        <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
      </div>
    </div>
  </nav>

  <?php if ($msg): ?>
    <div id="banner" class="banner <?= htmlspecialchars($type ?: 'success') ?>">
      <span><?= htmlspecialchars($msg) ?></span>
      <span class="close" onclick="hideBanner()">×</span>
    </div>
  <?php endif; ?>

  <div class="main-wrapper">
    <div class="signup-container">
      <div class="signup-grid">
        <div class="signup-info">
          <h1>Start Your Hosting Journey</h1>
          <p>Join thousands of satisfied customers using EnderBit's powerful hosting platform.</p>
          <ul class="feature-list">
            <li>Lightning-fast server deployment</li>
            <li>24/7 expert support team</li>
            <li>Easy-to-use control panel</li>
            <li>99.9% uptime guarantee</li>
            <li>Free services based on your needs</li>
          </ul>
        </div>

        <div class="signup-form">
          <h2>Create Account</h2>
          <p class="subtitle">Fill in your details to get started</p>
          
          <form method="post" action="register.php">
            <div class="form-row">
              <div class="form-group">
                <label for="first">First Name</label>
                <input type="text" id="first" name="first" required>
              </div>
              <div class="form-group">
                <label for="last">Last Name</label>
                <input type="text" id="last" name="last" required>
              </div>
            </div>

            <div class="form-group">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" required pattern="[a-zA-Z0-9_\-\.]+" title="Only letters, numbers, dots, hyphens and underscores">
            </div>

            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" required minlength="8">
            </div>

            <div class="recaptcha-wrapper">
              <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($config['recaptcha_site_key'] ?? '') ?>"></div>
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>
            <a class="btn btn-secondary" href="<?= htmlspecialchars($config['ptero_url'] ?? '#') ?>" target="_blank">Already have an account? Login</a>

            <p class="help-text">Need help? Contact <a href="mailto:support@enderbit.com">support@enderbit.com</a></p>
          </form>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <p>&copy; 2025 EnderBit. All rights reserved. | <a href="mailto:support@enderbit.com">support@enderbit.com</a> | <a href="/admin.php">Admin</a></p>
  </footer>

  <script>
    function hideBanner() {
      const b = document.getElementById('banner');
      if (!b) return;
      b.classList.remove('show');
      setTimeout(() => { if(b) b.style.left = '-500px'; }, 400);
    }
    
    window.addEventListener('load', () => {
      const b = document.getElementById('banner');
      if (!b) return;
      setTimeout(() => b.classList.add('show'), 100);
      setTimeout(() => hideBanner(), 5000);
    });

    // Theme toggle
    function toggleTheme() {
      const html = document.documentElement;
      const current = html.getAttribute("data-theme") || "dark";
      const next = current === "dark" ? "light" : "dark";
      html.setAttribute("data-theme", next);
      document.querySelector(".theme-toggle").textContent = next === "dark" ? "🌙" : "☀️";
      localStorage.setItem("theme", next);
    }
    
    (function() {
      const saved = localStorage.getItem("theme");
      if (saved) {
        document.documentElement.setAttribute("data-theme", saved);
        const btn = document.querySelector(".theme-toggle");
        if (btn) btn.textContent = saved === "dark" ? "🌙" : "☀️";
      }
    })();
  </script>
</body>
</html>
