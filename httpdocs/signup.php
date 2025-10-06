<?php
session_start();
require_once __DIR__ . '/config.php';

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
<title> Enderbit </title>
<link rel="icon" type="image/png" sizes="96x96" href="/icon.png">
<style>
  :root {
    --bg:#0d1117; --card:#161b22; --accent:#58a6ff; --primary:#1f6feb;
    --muted:#8b949e; --green:#238636; --red:#f85149;
    --text:#e6eef8; --input-bg:#0e1418; --input-border:#232629;
    --bg-gradient:#07101a;
  }
  [data-theme="light"] {
    --bg:#eff6ff; --card:#ffffff; --accent:#3b82f6; --primary:#2563eb;
    --muted:#64748b; --green:#16a34a; --red:#dc2626;
    --text:#1e3a8a; --input-bg:#ffffff; --input-border:#bfdbfe;
    --bg-gradient:#dbeafe;
  }

  html,body{height:100%;margin:0;font-family:Inter, Arial, sans-serif;
    background:linear-gradient(180deg,var(--bg),var(--bg-gradient));color:var(--text);}
  .page {
    min-height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:28px;
    box-sizing:border-box;
  }
  .card {
    width:100%;
    max-width:720px;
    background:var(--card);
    border-radius:14px;
    padding:36px;
    box-shadow:0 18px 50px rgba(0,0,0,.6);
    display:grid;
    grid-template-columns: 1fr;
    gap:18px;
    box-sizing:border-box;
    align-items:center;
  }
  @media (min-width:1000px){
    .card { grid-template-columns: 1fr 420px; padding:44px; gap:28px; }
  }
  .welcome {
    text-align:center;
    padding:8px 6px;
  }
  .welcome h1 { margin:0 0 10px; color:var(--accent); font-size:30px; }
  .welcome p { margin:0; color:var(--muted); font-size:16px; }

  .form-wrap {
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:stretch;
  }
  input[type=text], input[type=email], input[type=password] {
    width:100%;
    padding:14px;
    margin:10px 0;
    border-radius:10px;
    border:1px solid var(--input-border);
    background:var(--input-bg);
    color:var(--text);
    font-size:15px;
    box-sizing:border-box;
  }
  .btn {
    display:block;
    width:100%;
    padding:13px;
    border-radius:10px;
    font-weight:700;
    font-size:15px;
    text-align:center;
    text-decoration:none;
    cursor:pointer;
    box-sizing:border-box;
    border:0;
    margin-top:10px;
  }
  .btn-primary { background:var(--primary); color:#fff; }
  .btn-primary:hover { opacity:.9; }
  .btn-secondary { background:#202428; color:#fff; }
  [data-theme="light"] .btn-secondary { background:#e5e7eb; color:#111; }
  .btn-secondary:hover { opacity:.9; }

  .recaptcha { display:flex; justify-content:center; margin-top:10px; }
  .footer { text-align:center; margin-top:6px; color:var(--muted); font-size:13px; }
  .footer a { color:var(--accent); text-decoration:none; }

  .banner {
    position:fixed;
    left:-500px;
    top:20px;
    padding:12px 16px;
    border-radius:10px;
    min-width:260px;
    max-width:400px;
    box-shadow:0 8px 30px rgba(0,0,0,.5);
    display:flex;
    justify-content:space-between;
    align-items:center;
    transition:left .45s cubic-bezier(0.4, 0.0, 0.2, 1);
    z-index:2200;
  }
  .banner.show{ left:20px; }
  .banner.hide{ left:-500px !important; }
  .banner.success{ background:var(--green); color:#fff; }
  .banner.error{ background:var(--red); color:#fff; }
  .banner .close{ cursor:pointer; font-weight:700; color:#fff; padding-left:12px; opacity:0.8; }
  .banner .close:hover{ opacity:1; }

  @media (min-width: 768px) {
    .card { max-width:900px; padding:44px; }
    input, .btn { font-size:16px; padding:15px; }
    .welcome h1 { font-size:34px; }
  }
  @media (max-width: 520px) {
    .card { max-width:420px; padding:22px; border-radius:12px; }
    input, .btn { font-size:14px; padding:12px; }
    .welcome h1 { font-size:22px; }
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
  nav .nav-links a.active {
    color:var(--accent);
    border-bottom:2px solid var(--accent);
    padding-bottom:2px;
  }
  
  /* Theme toggle button */
  .theme-toggle {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:8px;
    padding:6px 10px;
    font-size:14px;
    cursor:pointer;
    color:var(--text);
  }

  /* Footer */
  footer {
    background:transparent;
    border-top:1px solid var(--input-border);
    padding:32px 24px;
    text-align:center;
    color:var(--muted);
  }
  footer a {
    color:var(--accent);
    text-decoration:none;
  }
</style>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
  <nav>
    <div class="container">
      <a href="/" class="logo">EnderBit</a>
            <div class="nav-links">
        <a href="/">Home</a>
        <a href="/services.php">Services</a>
        <a href="<?= htmlspecialchars($config['ptero_url'] ?? '#') ?>" target="_blank">Login</a>
        <a href="/support.php">Support</a>
        <a href="/faq.php">FAQ</a>
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

  <div class="page">
    <div class="card" role="main" aria-labelledby="welcome-title">
      <div class="welcome" id="welcome-col">
        <h1 id="welcome-title">Welcome to EnderBit</h1>
        <p class="lead">Fast, secure hosting with easy panel integration. Create your account here or sign in to get started.</p>
        <p style="margin-top:14px;color:var(--muted);font-size:14px">Need help? Contact the team.</p>
	    <p style="margin-top:14px;color:var(--muted);font-size:14px">support@enderbit.com</p>
      </div>

      <div class="form-wrap" aria-label="Signup form">
        <form method="post" action="register.php" novalidate>
          <input type="text" name="first" placeholder="First name" required>
          <input type="text" name="last" placeholder="Last name" required>
          <input type="text" name="username" placeholder="Username" required pattern="[a-zA-Z0-9_\-\.]+" title="Username can only contain letters, numbers, dots, hyphens and underscores">
          <input type="email" name="email" placeholder="Email address" required>
          <input type="password" name="password" placeholder="Password" required>
          <div class="recaptcha">
            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($config['recaptcha_site_key'] ?? '') ?>"></div>
          </div>
          <button type="submit" class="btn btn-primary">Sign Up</button>
        </form>
        <a class="btn btn-secondary" href="<?= htmlspecialchars($config['ptero_url'] ?? '#') ?>" target="_blank" rel="noopener">Login</a>
      </div>
    </div>
  </div>

  <footer>
    <p>&copy; 2025 EnderBit. All rights reserved. | <a href="mailto:support@enderbit.com">support@enderbit.com</a> | <a href="/admin.php">Admin</a></p>
  </footer>

<script>
function hideBanner(){
  const b = document.getElementById('banner');
  if (!b) return;
  b.classList.remove('show');
  setTimeout(()=>{ if(b) b.style.left='-500px'; }, 450);
}
window.addEventListener('load', ()=>{
  const b = document.getElementById('banner');
  if (!b) return;
  setTimeout(()=> b.classList.add('show'), 120);
  setTimeout(()=> hideBanner(), 5000);
});

// Theme toggle with localStorage
function toggleTheme(){
  const html = document.documentElement;
  const current = html.getAttribute("data-theme") || "dark";
  const next = current === "dark" ? "light" : "dark";
  html.setAttribute("data-theme", next);
  document.querySelector(".theme-toggle").textContent = next === "dark" ? "🌙" : "☀️";
  localStorage.setItem("theme", next);
}
(function(){
  const saved = localStorage.getItem("theme");
  if(saved){
    document.documentElement.setAttribute("data-theme", saved);
    document.querySelector(".theme-toggle").textContent = saved === "dark" ? "🌙" : "☀️";
  }
})();
</script>
</body>
</html>
