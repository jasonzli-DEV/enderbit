<?php
session_start();
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Support - EnderBit Game Server Hosting</title>
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
    background:linear-gradient(180deg,var(--bg),var(--bg-gradient)); color:var(--text); }

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

  /* Support Content */
  .support-container {
    min-height:calc(100vh - 200px);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:60px 24px;
  }
  .support-card {
    background:var(--card);
    border-radius:16px;
    padding:60px 50px;
    max-width:700px;
    width:100%;
    border:1px solid var(--input-border);
    box-shadow:0 12px 40px rgba(0,0,0,.4);
    text-align:center;
  }
  .support-icon {
    font-size:80px;
    margin-bottom:24px;
  }
  .support-card h1 {
    font-size:42px;
    margin-bottom:16px;
    color:var(--accent);
  }
  .support-card p {
    font-size:18px;
    color:var(--muted);
    line-height:1.7;
    margin-bottom:36px;
  }
  .support-methods {
    display:grid;
    gap:20px;
    margin:40px 0;
    text-align:left;
  }
  .support-method {
    background:var(--input-bg);
    padding:24px;
    border-radius:12px;
    border:1px solid var(--input-border);
    display:flex;
    align-items:center;
    gap:20px;
    transition:border-color .2s, transform .2s;
  }
  .support-method:hover {
    border-color:var(--accent);
    transform:translateX(5px);
  }
  .support-method-icon {
    font-size:36px;
    flex-shrink:0;
  }
  .support-method-info h3 {
    font-size:18px;
    margin-bottom:6px;
    color:var(--accent);
  }
  .support-method-info p {
    font-size:14px;
    color:var(--muted);
    margin:0;
  }
  .support-method-info a {
    color:var(--accent);
    text-decoration:none;
    font-weight:600;
  }
  .support-method-info a:hover {
    text-decoration:underline;
  }
  .btn-back {
    display:inline-block;
    padding:14px 32px;
    background:var(--primary);
    color:#fff;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
    font-size:16px;
    transition:all .2s;
    margin-top:20px;
  }
  .btn-back:hover {
    opacity:.9;
    transform:translateY(-2px);
  }

  /* Ticket Form */
  .ticket-form {
    margin:30px 0;
    text-align:left;
  }
  .form-group {
    margin-bottom:20px;
  }
  .form-group label {
    display:block;
    margin-bottom:8px;
    font-weight:600;
    color:var(--text);
    font-size:15px;
  }
  .form-group input,
  .form-group textarea {
    width:100%;
    padding:14px;
    border:1px solid var(--input-border);
    border-radius:10px;
    background:var(--input-bg);
    color:var(--text);
    font-size:15px;
    font-family:inherit;
    transition:border-color .2s;
  }
  .form-group input:focus,
  .form-group textarea:focus {
    outline:none;
    border-color:var(--accent);
  }
  .form-group textarea {
    resize:vertical;
    min-height:120px;
  }
  .btn-submit {
    width:100%;
    padding:16px;
    background:var(--primary);
    color:#fff;
    border:none;
    border-radius:10px;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:all .2s;
  }
  .btn-submit:hover {
    opacity:.9;
    transform:translateY(-2px);
  }
  .ticket-info {
    margin-top:30px;
    padding-top:20px;
    border-top:1px solid var(--input-border);
    font-size:14px;
    color:var(--muted);
  }
  .ticket-info p {
    margin:8px 0;
    font-size:14px;
  }
  .ticket-info a {
    color:var(--accent);
    text-decoration:none;
  }
  .ticket-info a:hover {
    text-decoration:underline;
  }
  .banner {
    padding:14px 18px;
    border-radius:10px;
    margin:20px 0;
    font-weight:500;
  }
  .banner-success {
    background:rgba(35,134,54,.15);
    color:var(--green);
    border:1px solid var(--green);
  }
  .banner-error {
    background:rgba(248,81,73,.15);
    color:var(--red);
    border:1px solid var(--red);
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
    .support-card { padding:40px 30px; }
    .support-card h1 { font-size:32px; }
    nav .nav-links { gap:16px; font-size:14px; }
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

  <div class="support-container">
    <div class="support-card">
      <div class="support-icon">🎫</div>
      <h1>Submit a Support Ticket</h1>
      <p>Having an issue? Submit a ticket and our support team will get back to you as soon as possible. You'll receive an email confirmation and can reply to that email to add more information to your ticket.</p>
      
      <?php if (isset($_GET['msg'])): ?>
        <div class="banner banner-<?= htmlspecialchars($_GET['type'] ?? 'success') ?>">
          <?= htmlspecialchars($_GET['msg']) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="create_ticket.php" class="ticket-form" enctype="multipart/form-data">
        <div class="form-group">
          <label for="email">Your Email *</label>
          <input type="email" id="email" name="email" required placeholder="your@email.com">
        </div>

        <div class="form-group">
          <label for="category">Category *</label>
          <select id="category" name="category" required style="width:100%;padding:14px;border-radius:10px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--text);font-size:15px;">
            <option value="">Select a category...</option>
            <option value="technical">💻 Technical Support</option>
            <option value="billing">💳 Get a Server</option>
            <option value="account">👤 Account Issues</option>
            <option value="feature">✨ Feature Request</option>
            <option value="other">❓ Other</option>
          </select>
        </div>

        <div class="form-group">
          <label for="priority">Priority *</label>
          <select id="priority" name="priority" required style="width:100%;padding:14px;border-radius:10px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--text);font-size:15px;">
            <option value="">Select priority level...</option>
            <option value="low">🟢 Low - General inquiry</option>
            <option value="medium" selected>🟡 Medium - Issue affecting usage</option>
            <option value="high">🟠 High - Significant problem</option>
            <option value="urgent">🔴 Urgent - Service down/critical</option>
          </select>
        </div>

        <div class="form-group">
          <label for="subject">Subject *</label>
          <input type="text" id="subject" name="subject" required placeholder="Brief description of your issue">
        </div>

        <div class="form-group">
          <label for="description">Issue Description *</label>
          <textarea id="description" name="description" required placeholder="Please provide detailed information about your issue..." rows="6"></textarea>
        </div>

        <div class="form-group">
          <label for="attachment">Attachment (Optional)</label>
          <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.log,.zip" style="width:100%;padding:14px;border-radius:10px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--text);font-size:15px;">
          <small style="color:var(--muted);font-size:13px;display:block;margin-top:6px;">Max 5MB. Allowed: JPG, PNG, GIF, PDF, TXT, LOG, ZIP</small>
        </div>

        <button type="submit" class="btn-submit">Submit Ticket</button>
      </form>

      <div class="ticket-info">
        <p><strong>📧 Email Support:</strong> <a href="mailto:support@enderbit.com">support@enderbit.com</a></p>
        <p><strong>⏱️ Response Time:</strong> Usually within 2-4 hours</p>
      </div>

      <a href="/" class="btn-back">Back to Home</a>
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
