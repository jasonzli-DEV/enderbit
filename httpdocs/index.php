<?php
session_start();
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>EnderBit - Premium Game Server Hosting</title>
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
  html,body { height:100%; font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
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
  nav .nav-links a.active { 
    color:var(--accent); 
    border-bottom:2px solid var(--accent); 
    padding-bottom:2px;
  }
  
  nav .btn-nav {
    padding:10px 20px;
    background:var(--primary);
    color:#fff;
    border-radius:8px;
    text-decoration:none;
    font-weight:600;
    transition:opacity .2s;
  }
  nav .btn-nav:hover { opacity:.9; }

  /* Hero Section */
  .hero {
    padding:80px 24px;
    text-align:center;
    max-width:900px;
    margin:0 auto;
  }
  .hero h1 {
    font-size:52px;
    margin-bottom:20px;
    background:linear-gradient(135deg, var(--accent), #a78bfa);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
  }
  .hero p {
    font-size:20px;
    color:var(--muted);
    margin-bottom:36px;
    line-height:1.6;
  }
  .hero-buttons {
    display:flex;
    gap:16px;
    justify-content:center;
    flex-wrap:wrap;
  }
  .btn {
    padding:14px 32px;
    border-radius:10px;
    font-weight:600;
    font-size:16px;
    text-decoration:none;
    transition:all .2s;
    display:inline-block;
  }
  .btn-primary {
    background:var(--primary);
    color:#fff;
  }
  .btn-primary:hover {
    opacity:.9;
    transform:translateY(-2px);
  }
  .btn-secondary {
    background:var(--card);
    color:var(--text);
    border:1px solid var(--input-border);
  }
  .btn-secondary:hover {
    border-color:var(--accent);
  }

  /* Features Section */
  .features {
    padding:60px 24px;
    max-width:1200px;
    margin:0 auto;
  }
  .features h2 {
    text-align:center;
    font-size:36px;
    margin-bottom:48px;
    color:var(--accent);
  }
  .features-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
    gap:28px;
  }
  .feature-card {
    background:var(--card);
    padding:32px;
    border-radius:14px;
    border:1px solid var(--input-border);
    transition:transform .2s, border-color .2s;
  }
  .feature-card:hover {
    transform:translateY(-4px);
    border-color:var(--accent);
  }
  .feature-card h3 {
    font-size:22px;
    margin-bottom:12px;
    color:var(--accent);
  }
  .feature-card p {
    color:var(--muted);
    line-height:1.6;
  }
  .feature-icon {
    font-size:40px;
    margin-bottom:16px;
  }

  /* Pricing Section */
  .pricing {
    padding:60px 24px;
    max-width:1200px;
    margin:0 auto;
  }
  .pricing h2 {
    text-align:center;
    font-size:36px;
    margin-bottom:48px;
    color:var(--accent);
  }
  .pricing-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
    gap:28px;
  }
  .pricing-card {
    background:var(--card);
    padding:36px;
    border-radius:14px;
    border:2px solid var(--input-border);
    text-align:center;
    transition:transform .2s, border-color .2s;
  }
  .pricing-card:hover {
    transform:translateY(-4px);
    border-color:var(--accent);
  }
  .pricing-card.featured {
    border-color:var(--accent);
    position:relative;
  }
  .pricing-card.featured::before {
    content:"POPULAR";
    position:absolute;
    top:-12px;
    left:50%;
    transform:translateX(-50%);
    background:var(--accent);
    color:#fff;
    padding:4px 16px;
    border-radius:20px;
    font-size:12px;
    font-weight:700;
  }
  .pricing-card h3 {
    font-size:24px;
    margin-bottom:8px;
  }
  .pricing-card .price {
    font-size:42px;
    font-weight:700;
    color:var(--accent);
    margin:16px 0;
  }
  .pricing-card .price span {
    font-size:18px;
    color:var(--muted);
  }
  .pricing-card ul {
    list-style:none;
    margin:24px 0;
    text-align:left;
  }
  .pricing-card ul li {
    padding:8px 0;
    color:var(--muted);
  }
  .pricing-card ul li::before {
    content:"✓";
    color:var(--green);
    font-weight:700;
    margin-right:8px;
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
    .hero h1 { font-size:36px; }
    .hero p { font-size:18px; }
    nav .nav-links { 
      gap:16px; 
      font-size:14px; 
    }
    .dropdown-menu {
      left:-140px;
      right:auto;
    }
    .pricing-grid, .features-grid { grid-template-columns:1fr; }
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

  <section class="hero">
    <h1>Premium Game Server Hosting</h1>
    <p>Experience lightning-fast performance, 99.9% uptime, and 24/7 support. Deploy your Minecraft, Rust, or any game server in seconds with our powerful infrastructure.</p>
    <div class="hero-buttons">
      <a href="/signup.php" class="btn btn-primary">Get Started Free</a>
      <a href="<?= htmlspecialchars($config['ptero_url'] ?? '#') ?>" target="_blank" class="btn btn-secondary">View Panel</a>
    </div>
  </section>

  <section class="features" id="features">
    <h2>Why Choose EnderBit?</h2>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">⚡</div>
        <h3>Lightning Fast</h3>
        <p>Enterprise-grade NVMe SSDs and high-performance CPUs ensure your servers run at maximum speed with minimal latency.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🛡️</div>
        <h3>DDoS Protection</h3>
        <p>Advanced DDoS mitigation keeps your servers online and protected against even the largest attacks.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🚀</div>
        <h3>Instant Setup</h3>
        <p>Deploy your server in under 60 seconds with our automated setup. No technical knowledge required.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💪</div>
        <h3>99.9% Uptime</h3>
        <p>Our redundant infrastructure and monitoring systems ensure your server stays online when you need it most.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🎮</div>
        <h3>All Games Supported</h3>
        <p>Minecraft, Rust, ARK, CS:GO, Valheim, and more. One-click installers for popular games and mods.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">👨‍💻</div>
        <h3>24/7 Support</h3>
        <p>Expert support team available around the clock via Discord, email, and live chat to help with any issues.</p>
      </div>
    </div>
  </section>

  <section class="pricing" id="pricing">
    <h2>Free Hosting For All Needs</h2>
    <div class="pricing-grid">
      <div class="pricing-card">
        <h3>Personal</h3>
        <div class="price">Personal Projects</div>
        <ul>
          <li>2GB RAM</li>
          <li>1 CPU Core</li>
          <li>20GB NVMe Storage</li>
          <li>Unlimited Bandwidth</li>
          <li>DDoS Protection</li>
          <li>Premium Support</li>
        </ul>
        <a href="/signup.php" class="btn btn-secondary">Get Started</a>
      </div>
      <div class="pricing-card featured">
        <h3>Professional</h3>
        <div class="price">Large Scale Projects</div>
        <ul>
          <li>4GB RAM</li>
          <li>2 CPU Cores</li>
          <li>50GB NVMe Storage</li>
          <li>Unlimited Bandwidth</li>
          <li>DDoS Protection</li>
          <li>Premium Support</li>
        </ul>
        <a href="/signup.php" class="btn btn-primary">Get Started</a>
      </div>
      <div class="pricing-card">
        <h3>Enterprise</h3>
        <div class="price">Enterprise Solutions</div>
        <ul>
          <li>8GB RAM</li>
          <li>4 CPU Cores</li>
          <li>100GB NVMe Storage</li>
          <li>Unlimited Bandwidth</li>
          <li>DDoS Protection</li>
          <li>Premium Support</li>
        </ul>
        <a href="/signup.php" class="btn btn-secondary">Get Started</a>
      </div>
    </div>
  </section>

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

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if(target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});
</script>
</body>
</html>
