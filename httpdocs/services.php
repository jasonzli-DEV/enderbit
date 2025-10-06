<?php
session_start();
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Our Services - EnderBit Game Server Hosting</title>
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

  * { margin:0; padding:0; box-sizing:border-box; }
  html,body { min-height:100%; font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    background:linear-gradient(180deg,var(--bg),#07101a); color:var(--text); }

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
  nav .nav-links .dropdown {
    position:relative;
    display:inline-block;
  }
  nav .nav-links .dropdown-content {
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
  nav .nav-links .dropdown-content a {
    display:block;
    padding:12px 20px;
    text-decoration:none;
    color:var(--text);
    transition:background .2s;
  }
  nav .nav-links .dropdown-content a:hover {
    background:var(--input-bg);
    color:var(--accent);
  }
  nav .nav-links .dropdown:hover .dropdown-content {
    display:block;
  }
  nav .nav-links .dropdown > a {
    display:inline-flex;
    align-items:center;
    gap:4px;
  }
  nav .nav-links a.active { color:var(--accent); border-bottom:2px solid var(--accent); }

  /* Page Header */
  .page-header {
    padding:60px 24px 40px;
    text-align:center;
    max-width:900px;
    margin:0 auto;
  }
  .page-header h1 {
    font-size:48px;
    margin-bottom:16px;
    background:linear-gradient(135deg, var(--accent), #a78bfa);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
  }
  .page-header p {
    font-size:18px;
    color:var(--muted);
    line-height:1.6;
  }

  /* Services Grid */
  .services-container {
    padding:40px 24px 80px;
    max-width:1200px;
    margin:0 auto;
  }
  .services-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));
    gap:32px;
  }
  .service-card {
    background:var(--card);
    border-radius:16px;
    padding:40px;
    border:2px solid var(--input-border);
    transition:transform .3s, border-color .3s, box-shadow .3s;
    text-align:center;
  }
  .service-card:hover {
    transform:translateY(-8px);
    border-color:var(--accent);
    box-shadow:0 12px 32px rgba(88,166,255,.2);
  }
  .service-icon-large {
    width:100px;
    height:100px;
    border-radius:20px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:56px;
    margin:0 auto 24px;
  }
  .service-card h2 {
    font-size:28px;
    margin-bottom:12px;
    color:var(--accent);
  }
  .service-card .tagline {
    font-size:14px;
    color:var(--muted);
    margin-bottom:20px;
    font-style:italic;
  }
  .service-card p {
    color:var(--muted);
    line-height:1.7;
    margin-bottom:24px;
  }
  .service-features {
    list-style:none;
    text-align:left;
    margin:24px 0;
    padding:0;
  }
  .service-features li {
    padding:10px 0;
    color:var(--text);
    display:flex;
    align-items:center;
    gap:10px;
  }
  .service-features li::before {
    content:"✓";
    color:var(--green);
    font-weight:700;
    font-size:18px;
  }
  .btn-service {
    display:inline-block;
    padding:14px 36px;
    background:var(--primary);
    color:#fff;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
    font-size:16px;
    transition:all .2s;
  }
  .btn-service:hover {
    opacity:.9;
    transform:translateY(-2px);
  }

  /* Footer */
  footer {
    background:var(--card);
    border-top:1px solid var(--input-border);
    padding:32px 24px;
    margin-top:80px;
    text-align:center;
    color:var(--muted);
  }
  footer a {
    color:var(--accent);
    text-decoration:none;
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

  @media (max-width: 768px) {
    .page-header h1 { font-size:36px; }
    nav .nav-links { gap:16px; font-size:14px; }
    .services-grid { grid-template-columns:1fr; }
  }
</style>
</head>
<body>
  <nav>
    <div class="container">
      <a href="/" class="logo">EnderBit</a>
      <div class="nav-links">
        <a href="/services.php">Services</a>
        <a href="/signup.php">Sign Up</a>
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

  <div class="page-header">
    <h1>Our Game Hosting Services</h1>
    <p>Choose from our premium game server hosting solutions. All plans include DDoS protection, free hosting with instant setup, and 24/7 support.</p>
  </div>

  <div class="services-container">
    <div class="services-grid">
      
      <!-- Minecraft Hosting -->
      <div class="service-card">
        <div class="service-icon-large" style="background:linear-gradient(135deg,#62a462,#8bc34a);">🎮</div>
        <h2>Minecraft Hosting</h2>
        <p class="tagline">The world's most popular game server</p>
        <p>Host your own Minecraft server with full control, mod support, and lightning-fast performance. Perfect for Java Edition and Bedrock Edition.</p>
        <ul class="service-features">
          <li>Java & Bedrock Edition support</li>
          <li>Full SFTP & file access</li>
          <li>Automatic backups</li>
          <li>Plugin support (Spigot, Paper, Forge)</li>
          <li>Custom JAR uploads</li>
        </ul>
        <a href="/signup.php" class="btn-service">Get Started</a>
      </div>

      <!-- Discord Bot Hosting -->
      <div class="service-card">
        <div class="service-icon-large" style="background:linear-gradient(135deg,#5865f2,#7289da);">💬</div>
        <h2>Discord Bot Hosting</h2>
        <p class="tagline">Keep your bots online 24/7</p>
        <p>Host your Discord bots with guaranteed uptime. Supports Python, JavaScript, and more with easy deployment.</p>
        <ul class="service-features">
          <li>24/7 uptime guarantee</li>
          <li>Support for Discord.js, Discord.py</li>
          <li>Easy GitHub integration</li>
          <li>Auto-restart on crashes</li>
          <li>Real-time logs & monitoring</li>
          <li>Multiple bot support</li>
        </ul>
        <a href="/signup.php" class="btn-service">Get Started</a>
      </div>

      <!-- Rust Hosting -->
      <div class="service-card">
        <div class="service-icon-large" style="background:linear-gradient(135deg,#ce422b,#e74c3c);">⚔️</div>
        <h2>Rust Server Hosting</h2>
        <p class="tagline">High-performance survival gaming</p>
        <p>Run your own Rust server with optimized performance and low latency. Perfect for competitive and casual gameplay.</p>
        <ul class="service-features">
          <li>Optimized for performance</li>
          <li>Oxide & uMod support</li>
          <li>Custom map support</li>
          <li>Scheduled wipes</li>
          <li>RCON access</li>
          <li>Low latency worldwide</li>
        </ul>
        <a href="/signup.php" class="btn-service">Get Started</a>
      </div>

      <!-- ARK Hosting -->
      <div class="service-card">
        <div class="service-icon-large" style="background:linear-gradient(135deg,#d4a76a,#f39c12);">🦖</div>
        <h2>ARK: Survival Evolved</h2>
        <p class="tagline">Prehistoric survival adventure</p>
        <p>Host your ARK server with full customization options. Support for all maps including custom ones.</p>
        <ul class="service-features">
          <li>All official maps supported</li>
          <li>Custom map uploads</li>
          <li>Mod support</li>
          <li>Cluster support</li>
          <li>Tek tier optimized</li>
          <li>RCON management</li>
        </ul>
        <a href="/signup.php" class="btn-service">Get Started</a>
      </div>

      <!-- CS:GO Hosting -->
      <div class="service-card">
        <div class="service-icon-large" style="background:linear-gradient(135deg,#f39c12,#e67e22);">🔫</div>
        <h2>CS:GO Server Hosting</h2>
        <p class="tagline">Competitive FPS gaming</p>
        <p>Host Counter-Strike: Global Offensive servers with 128-tick performance and competitive settings.</p>
        <ul class="service-features">
          <li>128-tick servers</li>
          <li>SourceMod & MetaMod support</li>
          <li>Workshop maps</li>
          <li>Custom game modes</li>
          <li>Anti-cheat integration</li>
          <li>Fast-DL support</li>
        </ul>
        <a href="/signup.php" class="btn-service">Get Started</a>
      </div>

      <!-- Multi-Game Support -->
      <div class="service-card">
        <div class="service-icon-large" style="background:linear-gradient(135deg,#ff6b6b,#ee5a6f);">🎯</div>
        <h2>More Games</h2>
        <p class="tagline">100+ games supported</p>
        <p>We support over 10 different games including Valheim, Terraria, 7 Days to Die, Satisfactory, and many more!</p>
        <ul class="service-features">
          <li>Valheim servers</li>
          <li>Terraria hosting</li>
          <li>7 Days to Die</li>
          <li>Satisfactory servers</li>
          <li>Project Zomboid</li>
          <li>Custom game requests</li>
        </ul>
        <a href="/signup.php" class="btn-service">Get Started</a>
      </div>

    </div>
  </div>

  <footer>
    <p>&copy; 2025 EnderBit. All rights reserved. | <a href="mailto:support@enderbit.com">support@enderbit.com</a> | <a href="/admin.php">Admin</a></p>
  </footer>

<script>
// Theme toggle with localStorage
function toggleTheme(){
  const html = document.documentElement;
  const current = html.getAttribute("data-theme") || "dark";
  const next = current === "dark" ? "light" : "dark";
  html.setAttribute("data-theme", next);
  document.querySelectorAll(".theme-toggle").forEach(btn => {
    btn.textContent = next === "dark" ? "🌙" : "☀️";
  });
  localStorage.setItem("theme", next);
}
(function(){
  const saved = localStorage.getItem("theme");
  if(saved){
    document.documentElement.setAttribute("data-theme", saved);
    document.querySelectorAll(".theme-toggle").forEach(btn => {
      btn.textContent = saved === "dark" ? "🌙" : "☀️";
    });
  }
})();
</script>
</body>
</html>
