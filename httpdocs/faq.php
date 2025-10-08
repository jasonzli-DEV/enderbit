<?php
session_start();
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Knowledge Base - EnderBit</title>
<link rel="icon" type="image/png" sizes="96x96" href="/icon.png">
<style>
  :root {
    --bg:#0d1117; --card:#161b22; --accent:#58a6ff; --primary:#1f6feb;
    --muted:#8b949e; --green:#238636; --red:#f85149; --yellow:#f0883e;
    --text:#e6eef8; --input-bg:#0e1418; --input-border:#232629;
    --bg-gradient:#07101a;
  }
  [data-theme="light"] {
    --bg:#eff6ff; --card:#ffffff; --accent:#3b82f6; --primary:#2563eb;
    --muted:#64748b; --green:#16a34a; --red:#dc2626; --yellow:#ea580c;
    --text:#1e3a8a; --input-bg:#ffffff; --input-border:#bfdbfe;
    --bg-gradient:#dbeafe;
  }

  * { margin:0; padding:0; box-sizing:border-box; }
  html,body { min-height:100%; font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
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

  /* Hero Section */
  .hero {
    text-align:center;
    padding:60px 24px 40px;
    max-width:800px;
    margin:0 auto;
  }
  .hero h1 {
    font-size:48px;
    color:var(--accent);
    margin-bottom:16px;
  }
  .hero p {
    font-size:18px;
    color:var(--muted);
    margin-bottom:32px;
  }

  /* Search Box */
  .search-box {
    max-width:600px;
    margin:0 auto 60px;
    padding:0 24px;
  }
  .search-input {
    width:100%;
    padding:16px 20px;
    border-radius:12px;
    border:2px solid var(--input-border);
    background:var(--input-bg);
    color:var(--text);
    font-size:16px;
    transition:border-color .2s;
  }
  .search-input:focus {
    outline:none;
    border-color:var(--accent);
  }

  /* Categories */
  .categories {
    max-width:1200px;
    margin:0 auto;
    padding:0 24px 60px;
  }
  .category-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
    gap:24px;
  }
  .category-card {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:14px;
    padding:28px;
    transition:all .3s;
    cursor:pointer;
  }
  .category-card:hover {
    transform:translateY(-4px);
    box-shadow:0 8px 24px rgba(0,0,0,.4);
    border-color:var(--accent);
  }
  .category-icon {
    font-size:42px;
    margin-bottom:16px;
  }
  .category-title {
    font-size:22px;
    font-weight:700;
    color:var(--text);
    margin-bottom:12px;
  }
  .category-desc {
    color:var(--muted);
    font-size:15px;
    line-height:1.6;
    margin-bottom:16px;
  }
  .article-count {
    color:var(--accent);
    font-size:14px;
    font-weight:600;
  }

  /* FAQ Section */
  .faq-section {
    max-width:900px;
    margin:0 auto;
    padding:0 24px 60px;
  }
  .faq-section h2 {
    font-size:32px;
    color:var(--accent);
    margin-bottom:32px;
    text-align:center;
  }
  .faq-item {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:12px;
    margin-bottom:16px;
    overflow:hidden;
  }
  .faq-question {
    padding:20px 24px;
    font-size:18px;
    font-weight:600;
    color:var(--text);
    cursor:pointer;
    display:flex;
    justify-content:space-between;
    align-items:center;
    transition:background .2s;
  }
  .faq-question:hover {
    background:var(--input-bg);
  }
  .faq-question .icon {
    font-size:20px;
    transition:transform .3s;
  }
  .faq-item.active .icon {
    transform:rotate(180deg);
  }
  .faq-answer {
    max-height:0;
    overflow:hidden;
    transition:max-height .3s ease;
    padding:0 24px;
  }
  .faq-item.active .faq-answer {
    max-height:2000px;
    padding:0 24px 20px;
    overflow:visible;
  }
  .faq-answer p {
    color:var(--muted);
    line-height:1.8;
    margin-bottom:12px;
  }
  .faq-answer code {
    background:var(--input-bg);
    padding:2px 8px;
    border-radius:4px;
    font-family:monospace;
    color:var(--accent);
  }
  .faq-answer ol, .faq-answer ul {
    color:var(--muted);
    line-height:1.8;
    margin-left:20px;
    margin-bottom:12px;
  }

  /* Contact CTA */
  .contact-cta {
    max-width:700px;
    margin:40px auto 60px;
    padding:32px;
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:14px;
    text-align:center;
  }
  .contact-cta h3 {
    font-size:24px;
    color:var(--text);
    margin-bottom:12px;
  }
  .contact-cta p {
    color:var(--muted);
    margin-bottom:24px;
  }
  .btn {
    display:inline-block;
    padding:14px 32px;
    background:var(--primary);
    color:#fff;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
    transition:all .2s;
  }
  .btn:hover {
    opacity:.9;
    transform:translateY(-2px);
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

  @media (max-width: 768px) {
    .hero h1 { font-size:36px; }
    .category-grid { grid-template-columns:1fr; }
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

  <div class="hero">
    <h1>📚 Knowledge Base</h1>
    <p>Find answers to common questions and learn how to get the most out of EnderBit</p>
  </div>

  <div class="search-box">
    <input type="text" class="search-input" id="kb-search" placeholder="🔍 Search for help..." onkeyup="searchFAQ()">
  </div>

  <div class="categories">
    <div class="category-grid">
      <div class="category-card" onclick="scrollToSection('getting-started')">
        <div class="category-icon">🚀</div>
        <div class="category-title">Getting Started</div>
        <div class="category-desc">New to EnderBit? Learn the basics and get your server running quickly.</div>
        <div class="article-count">6 articles</div>
      </div>

      <div class="category-card" onclick="scrollToSection('troubleshooting')">
        <div class="category-icon">🔧</div>
        <div class="category-title">Troubleshooting</div>
        <div class="category-desc">Common issues and how to fix them. Get your server back online fast.</div>
        <div class="article-count">6 articles</div>
      </div>

      <div class="category-card" onclick="scrollToSection('server-management')">
        <div class="category-icon">🎛️</div>
        <div class="category-title">Server Management</div>
        <div class="category-desc">Backups, databases, security, and administrative tools.</div>
        <div class="article-count">8 articles</div>
      </div>

      <div class="category-card" onclick="scrollToSection('advanced')">
        <div class="category-icon">⚙️</div>
        <div class="category-title">Advanced</div>
        <div class="category-desc">Mods, plugins, networks, custom domains, and advanced configuration.</div>
        <div class="article-count">9 articles</div>
      </div>
    </div>
  </div>

  <div class="faq-section" id="getting-started">
    <h2>🚀 Getting Started</h2>
    
    <div class="faq-item" data-keywords="free hosting indie developer personal project student open source nonprofit">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Do you offer free hosting for indie developers and personal projects?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p><strong>Yes!</strong> EnderBit is committed to supporting indie developers, students, and personal projects. We provide <strong>FREE hosting services</strong> to qualifying projects.</p>
        
        <p><strong>Who Qualifies:</strong></p>
        <ul>
          <li>🎮 <strong>Indie Game Developers:</strong> Testing servers for your game development</li>
          <li>👨‍💻 <strong>Open Source Projects:</strong> Community-driven projects with public repositories</li>
          <li>🎓 <strong>Students & Educators:</strong> Educational projects and learning environments</li>
          <li>💡 <strong>Personal Projects:</strong> Non-commercial hobby projects and experiments</li>
          <li>🌟 <strong>Content Creators:</strong> Small community servers for your followers</li>
        </ul>
        
        <p><strong>How to Apply:</strong></p>
        <ol>
          <li>Create an account on EnderBit</li>
          <li>Go to <strong>Support</strong> and create a ticket</li>
          <li>Select the <strong>"Get a Server"</strong> category</li>
          <li>In your ticket, mention:
            <ul style="margin-top:8px;">
              <li>That you're applying for free hosting</li>
              <li>Description of your project</li>
              <li>Why you need the server (development, learning, community, etc.)</li>
              <li>Link to your project (GitHub, portfolio, etc.) if available</li>
            </ul>
          </li>
          <li>Our team will review and respond within 48 hours</li>
        </ol>
        
        <p><strong>What You Get:</strong></p>
        <ul>
          <li>Free server hosting (specs based on your needs)</li>
          <li>Full control panel access</li>
          <li>Community support</li>
          <li>No time limits - keep it as long as you need it!</li>
        </ul>
        
        <p style="background:var(--input-bg);padding:12px;border-radius:8px;margin-top:12px;">
          <strong>💡 Note:</strong> Free hosting is provided on a case-by-case basis. We prioritize projects that align with our mission to support learning, creativity, and community building.
        </p>
      </div>
    </div>
    
    <div class="faq-item" data-keywords="account register signup create">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How do I create an account?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Creating an account is quick and easy:</p>
        <ol>
          <li>Click on the <strong>Sign Up</strong> button in the navigation bar</li>
          <li>Fill out the registration form with your details</li>
          <li>Complete the reCAPTCHA verification</li>
          <li>Check your email for verification (if enabled)</li>
          <li>Wait for admin approval (if required)</li>
          <li>Log in to your panel and start using our services!</li>
        </ol>
      </div>
    </div>

    <div class="faq-item" data-keywords="server create new start first">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How do I create my first server?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>After logging into your panel:</p>
        <ol>
          <li>Create a Support Ticket</li>
          <li>Select Get a Server</li>
          <li>Tell us your game type (Minecraft, Terraria, etc.)</li>
          <li>Describe your use case and requirements</li>
          <li>Click <strong>Submit Ticket</strong></li>
        </ol>
        <p>Your server will be ready within 48 hours!</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="login panel access pterodactyl">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Where is the game panel located?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>You can access your game panel by clicking the <strong>Login</strong> button in the navigation bar, or by visiting:</p>
        <p><code><?= htmlspecialchars($config['ptero_url'] ?? 'https://panel.enderbit.com') ?></code></p>
        <p>Use the email and password you created during registration.</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="connect join server ip address port">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How do I connect to my server?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>To connect to your server:</p>
        <ol>
          <li>Log into your game panel</li>
          <li>Navigate to your server</li>
          <li>Find the <strong>Server IP</strong> and <strong>Port</strong> in the server details</li>
          <li>Copy the connection information</li>
          <li>Open your game client and add the server using the IP:Port</li>
        </ol>
        <p>Make sure your server is running (green status) before connecting!</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="upload files ftp sftp filemanager">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How do I upload files to my server?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>There are two ways to upload files:</p>
        <p><strong>Method 1: Web File Manager (Easiest)</strong></p>
        <ol>
          <li>Log into your game panel</li>
          <li>Go to your server</li>
          <li>Click on <strong>Files</strong></li>
          <li>Use the <strong>Upload</strong> button to upload files</li>
        </ol>
        <p><strong>Method 2: SFTP (For large files)</strong></p>
        <ol>
          <li>Download an SFTP client like FileZilla</li>
          <li>Get your SFTP credentials from the panel in the <strong>Settings</strong> tab</li>
          <li>Connect using SFTP and upload files</li>
        </ol>
      </div>
    </div>
  </div>

  <div class="faq-section" id="troubleshooting">
    <h2>🔧 Troubleshooting</h2>
    
    <div class="faq-item" data-keywords="server not starting offline crash down">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>My server won't start. What should I do?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Try these steps in order:</p>
        <ol>
          <li><strong>Check the console</strong> - Look for error messages in the server console</li>
          <li><strong>Review recent changes</strong> - Did you recently install a mod or plugin? Try removing it</li>
          <li><strong>Check server resources</strong> - Make sure you haven't exceeded RAM or disk limits</li>
          <li><strong>Verify file integrity</strong> - Ensure all config files are valid (no syntax errors)</li>
          <li><strong>Restart the server</strong> - Click the restart button in the panel</li>
          <li><strong>Check server logs</strong> - Look at the logs for specific error messages</li>
        </ol>
        <p>If issues persist, create a support ticket with console logs attached.</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="lag tps performance slow">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>My server is lagging. How can I improve performance?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Try these optimization tips:</p>
        <ul>
          <li><strong>Reduce view distance</strong> - Lower values in server.properties</li>
          <li><strong>Limit entities</strong> - Too many mobs can cause lag</li>
          <li><strong>Remove heavy plugins/mods</strong> - Some mods are resource-intensive</li>
          <li><strong>Request more resources</strong> - Contact support if consistently hitting limits</li>
          <li><strong>Optimize chunks</strong> - Pre-generate world chunks to reduce load</li>
          <li><strong>Use server optimization mods</strong> - Like Paper, Lithium, or Phosphor</li>
        </ul>
      </div>
    </div>

    <div class="faq-item" data-keywords="cant connect connection refused timeout">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>I can't connect to my server</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Check these common issues:</p>
        <ol>
          <li><strong>Server status</strong> - Make sure the server is running (green in panel)</li>
          <li><strong>Correct IP/Port</strong> - Double-check you're using the right connection info</li>
          <li><strong>Firewall</strong> - Ensure your firewall isn't blocking the connection</li>
          <li><strong>Game version</strong> - Client version must match server version</li>
          <li><strong>Whitelist</strong> - Check if whitelist is enabled and you're added</li>
          <li><strong>Server capacity</strong> - Make sure server isn't at max players</li>
        </ol>
      </div>
    </div>

    <div class="faq-item" data-keywords="forgot password reset login">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>I forgot my password. How do I reset it?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>To reset your password:</p>
        <ol>
          <li>Go to the login page</li>
          <li>Click <strong>Forgot Password</strong></li>
          <li>Enter your email address</li>
          <li>Check your email for reset link</li>
          <li>Click the link and create a new password</li>
        </ol>
        <p>If you don't receive an email, check your spam folder or contact support.</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="backup restore save world">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How do I backup my server?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>We recommend regular backups:</p>
        <p><strong>Manual Backup:</strong></p>
        <ol>
          <li>Go to your server in the panel</li>
          <li>Click <strong>Backups</strong></li>
          <li>Click <strong>Create Backup</strong></li>
          <li>Wait for backup to complete</li>
          <li>Download backup to your computer for safekeeping</li>
        </ol>
        <p><strong>Automatic Backups:</strong> Daily automated backups are available based on your server configuration.</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="out of memory ram disk space full">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>I'm running out of disk space or RAM</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p><strong>For Disk Space:</strong></p>
        <ul>
          <li>Delete old backups you don't need</li>
          <li>Remove unused worlds or maps</li>
          <li>Clear server logs if they're large</li>
          <li>Remove unnecessary mods/plugins</li>
          <li>Contact support if you need additional storage</li>
        </ul>
        <p><strong>For RAM:</strong></p>
        <ul>
          <li>Restart your server to clear memory</li>
          <li>Reduce render distance and entity limits</li>
          <li>Remove memory-intensive mods</li>
          <li>Contact support to discuss your server requirements</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="faq-section" id="server-management">
    <h2>🎛️ Server Management</h2>
    
    <div class="faq-item" data-keywords="console command execute admin">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How do I execute console commands?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>To run console commands:</p>
        <ol>
          <li>Log into your game panel</li>
          <li>Navigate to your server</li>
          <li>Click on <strong>Console</strong></li>
          <li>Type your command in the input field</li>
          <li>Press Enter to execute</li>
        </ol>
        <p>Common commands: <code>op username</code>, <code>whitelist add username</code>, <code>gamemode creative username</code></p>
      </div>
    </div>

    <div class="faq-item" data-keywords="schedule task cron restart automatic">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Can I schedule automatic restarts?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Yes! Scheduled tasks are available in the panel:</p>
        <ol>
          <li>Go to your server in the panel</li>
          <li>Click <strong>Schedules</strong></li>
          <li>Click <strong>Create Schedule</strong></li>
          <li>Set the time and frequency</li>
          <li>Choose action (restart, backup, command)</li>
          <li>Save and enable</li>
        </ol>
        <p>Recommended: Daily restarts at 4 AM to clear memory and improve performance.</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="database mysql mariadb sql">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Do you provide MySQL databases?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Yes! MySQL databases are available:</p>
        <ol>
          <li>Go to your server in the panel</li>
          <li>Click <strong>Databases</strong></li>
          <li>Click <strong>Create Database</strong></li>
          <li>Copy the connection details</li>
          <li>Use in your plugins/mods configuration</li>
        </ol>
        <p>Each server can have multiple databases based on your requirements.</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="ddos protection security attack">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Are servers protected from DDoS attacks?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Yes! All EnderBit servers come with enterprise-grade DDoS protection:</p>
        <ul>
          <li><strong>Layer 4 & Layer 7 Protection:</strong> Against volumetric and application-layer attacks</li>
          <li><strong>Real-time Mitigation:</strong> Automatic detection and filtering of malicious traffic</li>
          <li><strong>No Downtime:</strong> Protection activates instantly without interrupting your server</li>
          <li><strong>Global Network:</strong> Traffic filtered through our worldwide network infrastructure</li>
        </ul>
        <p>Your server stays online even during large-scale attacks.</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="backup automatic schedule restore data">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How do automatic backups work?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>EnderBit provides comprehensive backup solutions:</p>
        <p><strong>Automatic Backups:</strong></p>
        <ul>
          <li>Daily backups of your entire server</li>
          <li>Kept for 7 days by default</li>
          <li>Zero-impact on performance - runs during low usage</li>
        </ul>
        <p><strong>Manual Backups:</strong></p>
        <ul>
          <li>Create backups anytime from the control panel</li>
          <li>Download backups to your computer</li>
          <li>One-click restore from any backup</li>
        </ul>
        <p><strong>Scheduled Backups:</strong></p>
        <ul>
          <li>Set custom backup schedules (hourly, daily, weekly)</li>
          <li>Enable via the Admin Panel → Backup Management</li>
        </ul>
      </div>
    </div>

    <div class="faq-item" data-keywords="server location datacenter region ping latency">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Where are your servers located?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>We operate multiple datacenters worldwide for optimal performance:</p>
        <ul>
          <li><strong>North America:</strong> Los Angeles, Dallas, New York, Miami</li>
          <li><strong>Europe:</strong> London, Frankfurt, Amsterdam, Paris</li>
          <li><strong>Asia-Pacific:</strong> Singapore, Tokyo, Sydney</li>
          <li><strong>South America:</strong> São Paulo</li>
        </ul>
        <p>Choose the location closest to your players during signup for the lowest latency!</p>
        <p><em>Note: Location availability depends on capacity and your use case.</em></p>
      </div>
    </div>

    <div class="faq-item" data-keywords="whitelist blacklist ban player permission">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How do I whitelist or ban players?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p><strong>Whitelist (Vanilla/Spigot/Paper):</strong></p>
        <pre style="background:var(--input-bg);padding:12px;border-radius:6px;overflow-x:auto;">
/whitelist on
/whitelist add PlayerName
/whitelist remove PlayerName
/whitelist list</pre>
        
        <p><strong>Ban Players:</strong></p>
        <pre style="background:var(--input-bg);padding:12px;border-radius:6px;overflow-x:auto;">
/ban PlayerName [reason]
/ban-ip IP_Address
/pardon PlayerName
/banlist</pre>
        
        <p><strong>Using Plugins:</strong></p>
        <ul>
          <li><strong>LuckPerms:</strong> Advanced permission management</li>
          <li><strong>EssentialsX:</strong> Includes ban/kick/whitelist commands</li>
          <li><strong>AdvancedBan:</strong> Temporary bans with reasons</li>
        </ul>
      </div>
    </div>

    <div class="faq-item" data-keywords="support ticket response time help">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How fast do you respond to support tickets?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Our support team strives for fast, helpful responses:</p>
        <ul>
          <li><strong>Critical Issues:</strong> Within 30 minutes (server down, major bugs)</li>
          <li><strong>High Priority:</strong> Within 2-4 hours (performance issues, errors)</li>
          <li><strong>Normal Requests:</strong> Within 24 hours (general questions, guidance)</li>
          <li><strong>Low Priority:</strong> Within 48 hours (feature requests, suggestions)</li>
        </ul>
        <p><strong>Business Hours:</strong> 9 AM - 11 PM EST, 7 days a week</p>
        <p><em>All users receive the same level of support - everyone is equal at EnderBit!</em></p>
      </div>
    </div>
  </div>

  <div class="faq-section" id="advanced">
    <h2>⚙️ Advanced</h2>
    
    <div class="faq-item" data-keywords="mod plugin install forge bukkit spigot">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How do I install mods or plugins?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p><strong>For Forge/Fabric Mods:</strong></p>
        <ol>
          <li>Stop your server</li>
          <li>Upload mod files to the <code>/mods</code> folder</li>
          <li>Start your server</li>
        </ol>
        <p><strong>For Bukkit/Spigot/Paper Plugins:</strong></p>
        <ol>
          <li>Stop your server</li>
          <li>Upload plugin .jar files to the <code>/plugins</code> folder</li>
          <li>Start your server</li>
          <li>Configure plugins in the <code>/plugins/PluginName</code> folder</li>
        </ol>
        <p>Always download mods/plugins from trusted sources!</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="custom domain subdomain dns">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Can I use a custom domain for my server?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Yes! You can use a custom domain:</p>
        <ol>
          <li>Purchase a domain from a registrar (Namecheap, GoDaddy, etc.)</li>
          <li>Create an <strong>A record</strong> pointing to your server IP</li>
          <li>Or create an <strong>SRV record</strong> for port forwarding</li>
          <li>Wait for DNS propagation (can take up to 48 hours)</li>
        </ol>
        <p>Example SRV record: <code>_minecraft._tcp.play.yourdomain.com</code></p>
      </div>
    </div>

    <div class="faq-item" data-keywords="java version update 8 11 17 21">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>What Java version should I use?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Java version depends on your server type:</p>
        <table style="width:100%;border-collapse:collapse;margin-top:12px;">
          <tr style="background:var(--input-bg);">
            <th style="padding:8px;text-align:left;border:1px solid var(--input-border);">Server Version</th>
            <th style="padding:8px;text-align:left;border:1px solid var(--input-border);">Java Version</th>
          </tr>
          <tr>
            <td style="padding:8px;border:1px solid var(--input-border);">Minecraft 1.16 and below</td>
            <td style="padding:8px;border:1px solid var(--input-border);">Java 8 or 11</td>
          </tr>
          <tr>
            <td style="padding:8px;border:1px solid var(--input-border);">Minecraft 1.17 - 1.20.4</td>
            <td style="padding:8px;border:1px solid var(--input-border);">Java 17</td>
          </tr>
          <tr>
            <td style="padding:8px;border:1px solid var(--input-border);">Minecraft 1.20.5+</td>
            <td style="padding:8px;border:1px solid var(--input-border);">Java 21</td>
          </tr>
          <tr>
            <td style="padding:8px;border:1px solid var(--input-border);">Modded (Forge/Fabric)</td>
            <td style="padding:8px;border:1px solid var(--input-border);">Check modpack requirements</td>
          </tr>
        </table>
        <p style="margin-top:12px;">Change Java version in your server's Startup settings in the control panel.</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="subserver bungee waterfall velocity network proxy">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Can I run a server network with multiple servers?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Yes! You can create a multi-server network:</p>
        <p><strong>Setup Guide:</strong></p>
        <ol>
          <li>Order multiple servers (one for proxy, others for game servers)</li>
          <li>Install <strong>Velocity</strong> or <strong>Waterfall</strong> on your proxy server</li>
          <li>Configure backend servers to connect to the proxy</li>
          <li>Players connect to the proxy IP, which routes them to backend servers</li>
        </ol>
        <p><strong>Benefits:</strong></p>
        <ul>
          <li>Separate lobbies, minigames, and survival servers</li>
          <li>Players switch servers without disconnecting</li>
          <li>Better performance by distributing load</li>
          <li>Easier maintenance - update servers individually</li>
        </ul>
        <p>Need help? Create a support ticket and we'll guide you through the setup!</p>
      </div>
    </div>

    <div class="faq-item" data-keywords="support ticket response time help">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How fast do you respond to support tickets?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Our support team strives for fast, helpful responses:</p>
        <ul>
          <li><strong>Critical Issues:</strong> Within 30 minutes (server down, major bugs)</li>
          <li><strong>High Priority:</strong> Within 2-4 hours (performance issues, errors)</li>
          <li><strong>Normal Requests:</strong> Within 24 hours (general questions, guidance)</li>
          <li><strong>Low Priority:</strong> Within 48 hours (feature requests, suggestions)</li>
        </ul>
        <p><strong>Business Hours:</strong> 9 AM - 11 PM EST, 7 days a week</p>
        <p><em>All users receive the same level of support - everyone is equal at EnderBit!</em></p>
      </div>
    </div>

    <div class="faq-item" data-keywords="discord bot hosting nodejs python">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Can I host Discord bots on EnderBit?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Yes! EnderBit supports Discord bot hosting for Node.js and Python bots.</p>
        <p><strong>Supported Languages:</strong></p>
        <ul>
          <li><strong>Node.js:</strong> Discord.js, Eris, Discordie</li>
          <li><strong>Python:</strong> Discord.py, Hikari, Nextcord</li>
          <li><strong>Java:</strong> JDA (Java Discord API)</li>
        </ul>
        <p><strong>Setup Steps:</strong></p>
        <ol>
          <li>Create a new server with "Discord Bot" server type</li>
          <li>Upload your bot files via FTP or File Manager</li>
          <li>Configure your bot token in environment variables</li>
          <li>Start your bot from the control panel</li>
        </ol>
        <p><strong>Requirements:</strong></p>
        <ul>
          <li>Bot token from Discord Developer Portal</li>
          <li>Package.json (Node.js) or requirements.txt (Python)</li>
          <li>Main bot file (index.js, bot.py, etc.)</li>
        </ul>
        <p><em>Note: Keep your bot token secure and never share it publicly!</em></p>
      </div>
    </div>

    <div class="faq-item" data-keywords="ddos protection attack mitigation security">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Do you offer DDoS protection?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Yes! All EnderBit servers come with enterprise-grade DDoS protection included at no extra cost.</p>
        <p><strong>Protection Features:</strong></p>
        <ul>
          <li><strong>Layer 3/4 Protection:</strong> Blocks volumetric attacks up to 100 Gbps</li>
          <li><strong>Layer 7 Protection:</strong> Application-layer filtering for game packets</li>
          <li><strong>Automatic Mitigation:</strong> Attacks are detected and blocked within seconds</li>
          <li><strong>Always-On Protection:</strong> No manual activation needed</li>
        </ul>
        <p><strong>What We Protect Against:</strong></p>
        <ul>
          <li>UDP/TCP floods</li>
          <li>SYN floods and amplification attacks</li>
          <li>Application-layer attacks</li>
          <li>Bot-based attacks</li>
        </ul>
        <p><em>Your server stays online even during attacks. Our infrastructure handles the traffic so you don't have to worry!</em></p>
      </div>
    </div>

    <div class="faq-item" data-keywords="custom domain subdomain dns cname">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Can I use a custom domain for my server?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Yes! You can connect a custom domain or subdomain to your EnderBit server.</p>
        <p><strong>Setup Steps:</strong></p>
        <ol>
          <li>Get your server IP from the control panel</li>
          <li>Go to your domain registrar (Namecheap, GoDaddy, Cloudflare, etc.)</li>
          <li>Create an A record pointing to your server IP</li>
          <li>Wait 5-30 minutes for DNS propagation</li>
          <li>Connect using your custom domain!</li>
        </ol>
        <p><strong>Example DNS Configuration:</strong></p>
        <pre style="background:var(--input-bg);padding:12px;border-radius:6px;overflow-x:auto;">
Type: A Record
Name: play (or @ for root domain)
Value: 123.45.67.89 (your server IP)
TTL: 3600</pre>
        <p><strong>For SRV Records (custom port):</strong></p>
        <pre style="background:var(--input-bg);padding:12px;border-radius:6px;overflow-x:auto;">
Type: SRV
Name: _minecraft._tcp.play
Priority: 0
Weight: 5
Port: 25565
Target: play.yourdomain.com</pre>
        <p><em>Need help with DNS setup? Create a support ticket with your domain name and we'll assist you!</em></p>
      </div>
    </div>

    <div class="faq-item" data-keywords="modpack forge fabric curseforge ftb technic">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How do I install modpacks like FTB or CurseForge?</span>
        <span class="icon">▼</span>
      </div>
      <div class="faq-answer">
        <p>Installing modpacks on EnderBit is simple with our one-click installer!</p>
        <p><strong>Method 1: One-Click Installer (Recommended)</strong></p>
        <ol>
          <li>Go to your server control panel</li>
          <li>Click "Modpacks" in the sidebar</li>
          <li>Browse popular modpacks (FTB, RLCraft, All The Mods, etc.)</li>
          <li>Click "Install" - done in 2-5 minutes!</li>
        </ol>
        <p><strong>Method 2: Manual Installation</strong></p>
        <ol>
          <li>Download modpack server files from CurseForge/FTB</li>
          <li>Stop your server</li>
          <li>Upload modpack files via FTP or File Manager</li>
          <li>Update startup command if needed</li>
          <li>Start server and wait for world generation</li>
        </ol>
        <p><strong>Popular Modpacks Available:</strong></p>
        <ul>
          <li>All The Mods 9 (ATM9)</li>
          <li>RLCraft</li>
          <li>FTB Inferno</li>
          <li>Create: Above and Beyond</li>
          <li>Enigmatica 9</li>
          <li>Better Minecraft</li>
        </ul>
        <p><em>Make sure you have enough RAM! Most modpacks need 4-8GB minimum.</em></p>
      </div>
    </div>
  </div>

  <div class="contact-cta">
    <h3>Still need help?</h3>
    <p>Can't find what you're looking for? Our support team is here to help!</p>
    <a href="/support.php" class="btn">Create Support Ticket</a>
  </div>

  <footer>
    <p>&copy; 2025 EnderBit. All rights reserved. | <a href="mailto:support@enderbit.com">support@enderbit.com</a> | <a href="/admin.php">Admin</a></p>
  </footer>

<script>
// Theme toggle
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

// FAQ Toggle
function toggleFAQ(element) {
  const item = element.parentElement;
  const wasActive = item.classList.contains('active');
  
  // Close all items
  document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
  
  // Open clicked item if it wasn't active
  if (!wasActive) {
    item.classList.add('active');
  }
}

// Search functionality
function searchFAQ() {
  const searchTerm = document.getElementById('kb-search').value.toLowerCase();
  const faqItems = document.querySelectorAll('.faq-item');
  
  faqItems.forEach(item => {
    const question = item.querySelector('.faq-question').textContent.toLowerCase();
    const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
    const keywords = item.getAttribute('data-keywords') || '';
    
    if (question.includes(searchTerm) || answer.includes(searchTerm) || keywords.includes(searchTerm)) {
      item.style.display = 'block';
      if (searchTerm.length > 2) {
        item.classList.add('active');
      }
    } else {
      item.style.display = 'none';
    }
  });
  
  // Show all if search is empty
  if (searchTerm === '') {
    faqItems.forEach(item => {
      item.style.display = 'block';
      item.classList.remove('active');
    });
  }
}

// Scroll to section
function scrollToSection(sectionId) {
  const section = document.getElementById(sectionId);
  if (section) {
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}
</script>
</body>
</html>
