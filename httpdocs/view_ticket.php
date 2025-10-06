<?php
session_start();
require_once __DIR__ . '/config.php';

$ticketId = $_GET['id'] ?? '';
if (empty($ticketId)) {
    header("Location: support.php");
    exit;
}

// Load tickets
$ticketsFile = __DIR__ . '/tickets.json';
if (!file_exists($ticketsFile)) {
    header("Location: support.php?msg=" . urlencode("Ticket not found") . "&type=error");
    ex  <div class="page-container">
    <?php if ($justCreated): ?>
    <div class="success-banner">
      <h2>✅ Ticket Created Successfully!</h2>
      <p>Your ticket has been submitted and our support team will respond soon.</p>
      <p>A confirmation email has been sent to <strong><?= htmlspecialchars($ticket['email']) ?></strong></p>
      <p style="margin-top:8px;">💬 Check this page or your email for updates on your ticket.</p>
    </div>
    <?php endif; ?>>✅ Ticket Created Successfully!</h2>
      <p>Your ticket has been submitted and our support team will respond soon.</p>
      <p>A confirmation email has been sent to <strong><?= htmlspecialchars($ticket['email']) ?></strong></p>
      <p style="margin-top:8px;">💬 Check this page or your email for updates on your ticket.</p>
    </div>

$tickets = json_decode(file_get_contents($ticketsFile), true);
if (!is_array($tickets)) {
    header("Location: support.php?msg=" . urlencode("Ticket not found") . "&type=error");
    exit;
}

// Find ticket
$ticket = null;
foreach ($tickets as $t) {
    if ($t['id'] === $ticketId) {
        $ticket = $t;
        break;
    }
}

if (!$ticket) {
    header("Location: support.php?msg=" . urlencode("Ticket not found") . "&type=error");
    exit;
}

$justCreated = isset($_GET['created']);
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Ticket <?= htmlspecialchars($ticket['id']) ?> - EnderBit Support</title>
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

  /* Theme Toggle */
  .theme-toggle {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:8px;
    padding:6px 10px;
    font-size:14px;
    cursor:pointer;
    color:var(--text);
  }

  /* Container */
  .page-container {
    max-width:900px;
    margin:40px auto;
    padding:0 24px;
  }

  /* Success Banner */
  .success-banner {
    background:rgba(35,134,54,.15);
    border:1px solid var(--green);
    color:var(--green);
    padding:20px;
    border-radius:12px;
    margin-bottom:30px;
    text-align:center;
  }
  .success-banner h2 {
    margin-bottom:8px;
    font-size:24px;
  }
  .success-banner p {
    font-size:15px;
    margin:4px 0;
  }

  /* Ticket Card */
  .ticket-card {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:14px;
    padding:30px;
    margin-bottom:20px;
    box-shadow:0 4px 12px rgba(0,0,0,.3);
  }

  .ticket-header {
    border-bottom:2px solid var(--input-border);
    padding-bottom:20px;
    margin-bottom:20px;
  }
  .ticket-header h1 {
    font-size:28px;
    color:var(--accent);
    margin-bottom:12px;
  }
  .ticket-meta {
    display:flex;
    gap:24px;
    flex-wrap:wrap;
    font-size:14px;
    color:var(--muted);
  }
  .ticket-meta-item {
    display:flex;
    align-items:center;
    gap:6px;
  }
  .status-badge {
    display:inline-block;
    padding:4px 12px;
    border-radius:20px;
    font-size:13px;
    font-weight:600;
  }
  .status-open { background:rgba(35,134,54,.2); color:var(--green); }
  .status-closed { background:rgba(139,148,158,.2); color:var(--muted); }
  
  .category-badge {
    display:inline-block;
    padding:4px 12px;
    border-radius:20px;
    font-size:13px;
    font-weight:600;
    background:rgba(88,166,255,.15);
    color:var(--accent);
    border:1px solid var(--accent);
  }
  
  .priority-badge {
    display:inline-block;
    padding:4px 12px;
    border-radius:20px;
    font-size:13px;
    font-weight:600;
  }
  .priority-low { background:rgba(34,197,94,.15); color:var(--green); border:1px solid var(--green); }
  .priority-medium { background:rgba(234,179,8,.15); color:var(--yellow); border:1px solid var(--yellow); }
  .priority-high { background:rgba(249,115,22,.15); color:var(--yellow); border:1px solid var(--yellow); }
  .priority-urgent { background:rgba(239,68,68,.15); color:var(--red); border:1px solid var(--red); }
  
  .attachment-box {
    background:var(--input-bg);
    border:1px solid var(--input-border);
    border-radius:10px;
    padding:16px;
    margin-top:16px;
    display:flex;
    align-items:center;
    gap:12px;
  }
  .attachment-box a {
    color:var(--accent);
    text-decoration:none;
    font-weight:600;
  }
  .attachment-box a:hover {
    text-decoration:underline;
  }

  /* Messages */
  .messages {
    display:flex;
    flex-direction:column;
    gap:20px;
  }
  .message {
    background:var(--input-bg);
    border:1px solid var(--input-border);
    border-radius:12px;
    padding:20px;
    position:relative;
  }
  .message.user-message {
    border-left:4px solid var(--accent);
  }
  .message.admin-message {
    border-left:4px solid var(--yellow);
    background:rgba(240,136,62,.05);
  }
  .message-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:12px;
    padding-bottom:8px;
    border-bottom:1px solid var(--input-border);
  }
  .message-author {
    font-weight:600;
    font-size:15px;
    color:var(--accent);
  }
  .message.admin-message .message-author {
    color:var(--yellow);
  }
  .message-time {
    font-size:13px;
    color:var(--muted);
  }
  .message-content {
    line-height:1.6;
    color:var(--text);
    white-space:pre-wrap;
    word-wrap:break-word;
  }

  /* Back Button */
  .btn-back {
    display:inline-block;
    padding:14px 28px;
    background:var(--primary);
    color:#fff;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
    margin-top:20px;
    transition:all .2s;
  }
  .btn-back:hover {
    opacity:.9;
    transform:translateY(-2px);
  }

  /* Admin Reply Form */
  .admin-reply-section {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:14px;
    padding:30px;
    margin-bottom:20px;
    box-shadow:0 4px 12px rgba(0,0,0,.3);
  }
  .admin-reply-section h3 {
    color:var(--accent);
    margin-bottom:16px;
    font-size:20px;
  }
  .reply-form-group {
    margin-bottom:16px;
  }
  .reply-form-group textarea {
    width:100%;
    padding:14px;
    border:1px solid var(--input-border);
    border-radius:10px;
    background:var(--input-bg);
    color:var(--text);
    font-size:15px;
    font-family:inherit;
    resize:vertical;
    min-height:120px;
    transition:border-color .2s;
  }
  .reply-form-group textarea:focus {
    outline:none;
    border-color:var(--accent);
  }
  .btn-submit {
    padding:14px 32px;
    background:var(--primary);
    color:#fff;
    border:none;
    border-radius:10px;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:all .2s;
    margin-right:10px;
  }
  .btn-submit:hover {
    opacity:.9;
    transform:translateY(-2px);
  }
  .btn-close {
    padding:14px 32px;
    background:var(--red);
    color:#fff;
    border:none;
    border-radius:10px;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:all .2s;
  }
  .btn-close:hover {
    opacity:.9;
    transform:translateY(-2px);
  }
  .admin-notice {
    background:rgba(240,136,62,.1);
    border:1px solid var(--yellow);
    border-radius:8px;
    padding:12px;
    margin-bottom:16px;
    font-size:14px;
    color:var(--text);
  }

  /* Footer */
  footer {
    background:var(--card);
    border-top:1px solid var(--input-border);
    padding:32px 24px;
    text-align:center;
    color:var(--muted);
    margin-top:60px;
  }
  footer a {
    color:var(--accent);
    text-decoration:none;
  }

  @media (max-width: 768px) {
    .ticket-header h1 { font-size:22px; }
    .ticket-meta { gap:16px; }
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
        <a href="/support.php">Support</a>
        <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
      </div>
    </div>
  </nav>

  <div class="page-container">
    <?php if ($justCreated): ?>
    <div class="success-banner">
      <h2>✅ Ticket Created Successfully!</h2>
      <p>Your ticket has been submitted and our support team will respond soon.</p>
      <p>A confirmation email has been sent to <strong><?= htmlspecialchars($ticket['email']) ?></strong></p>
      <p style="margin-top:8px;">� Check this page or your email for updates on your ticket.</p>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
    <div class="success-banner">
      <h2><?= htmlspecialchars($_GET['msg']) ?></h2>
    </div>
    <?php endif; ?>

    <div class="ticket-card">
      <div class="ticket-header">
        <h1><?= htmlspecialchars($ticket['subject']) ?></h1>
        <div class="ticket-meta">
          <div class="ticket-meta-item">
            <span>🎫</span>
            <strong><?= htmlspecialchars($ticket['id']) ?></strong>
          </div>
          <?php if (!empty($ticket['category'])): ?>
          <div class="ticket-meta-item">
            <span class="category-badge">
              <?php
              $categoryIcons = [
                'technical' => '💻 Technical Support',
                'billing' => '💳 Billing',
                'account' => '👤 Account',
                'feature' => '✨ Feature Request',
                'other' => '❓ Other'
              ];
              echo htmlspecialchars($categoryIcons[$ticket['category']] ?? ucfirst($ticket['category']));
              ?>
            </span>
          </div>
          <?php endif; ?>
          <?php if (!empty($ticket['priority'])): ?>
          <div class="ticket-meta-item">
            <span class="priority-badge priority-<?= htmlspecialchars($ticket['priority']) ?>">
              <?php
              $priorityLabels = [
                'low' => '🟢 Low',
                'medium' => '🟡 Medium',
                'high' => '🟠 High',
                'urgent' => '🔴 Urgent'
              ];
              echo htmlspecialchars($priorityLabels[$ticket['priority']] ?? ucfirst($ticket['priority']));
              ?>
            </span>
          </div>
          <?php endif; ?>
          <div class="ticket-meta-item">
            <span>📧</span>
            <span><?= htmlspecialchars($ticket['email']) ?></span>
          </div>
          <div class="ticket-meta-item">
            <span>📅</span>
            <span><?= htmlspecialchars(date('M j, Y, g:i A', strtotime($ticket['created_at']))) ?></span>
          </div>
          <div class="ticket-meta-item">
            <span class="status-badge status-<?= htmlspecialchars($ticket['status']) ?>">
              <?= htmlspecialchars(ucfirst($ticket['status'])) ?>
            </span>
          </div>
        </div>
      </div>

      <div class="messages">
        <!-- Original Message -->
        <div class="message user-message">
          <div class="message-header">
            <span class="message-author">👤 You</span>
            <span class="message-time"><?= htmlspecialchars(date('M j, Y, g:i A', strtotime($ticket['created_at']))) ?></span>
          </div>
          <div class="message-content"><?= htmlspecialchars($ticket['description']) ?></div>
          <?php if (!empty($ticket['attachment'])): ?>
          <div class="attachment-box">
            <span>📎</span>
            <a href="<?= htmlspecialchars($ticket['attachment']) ?>" target="_blank" download>
              <?= htmlspecialchars(basename($ticket['attachment'])) ?>
            </a>
            <span style="color:var(--muted);font-size:13px;">(<?= number_format(filesize(__DIR__ . '/' . $ticket['attachment']) / 1024, 1) ?> KB)</span>
          </div>
          <?php endif; ?>
        </div>

        <!-- Replies -->
        <?php if (!empty($ticket['replies']) && is_array($ticket['replies'])): ?>
          <?php foreach ($ticket['replies'] as $reply): ?>
            <div class="message <?= $reply['is_admin'] ? 'admin-message' : 'user-message' ?>">
              <div class="message-header">
                <span class="message-author">
                  <?= $reply['is_admin'] ? '👨‍💼 Support Team' : '👤 You' ?>
                </span>
                <span class="message-time"><?= htmlspecialchars(date('M j, Y, g:i A', strtotime($reply['created_at']))) ?></span>
              </div>
              <div class="message-content"><?= htmlspecialchars($reply['message']) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
    <!-- Admin Reply Form -->
    <div class="admin-reply-section">
      <h3>👨‍💼 Admin Reply</h3>
      <div class="admin-notice">
        ⚠️ You are logged in as admin. Your reply will be sent to the ticket creator via email.
      </div>
      <form method="post" action="reply_ticket.php">
        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
        <input type="hidden" name="is_admin" value="1">
        <div class="reply-form-group">
          <textarea name="reply_message" placeholder="Type your reply to the customer..." required></textarea>
        </div>
        <button type="submit" class="btn-submit">Send Reply</button>
        <?php if ($ticket['status'] === 'open'): ?>
        <button type="submit" name="close_ticket" value="1" class="btn-close" onclick="return confirm('Are you sure you want to close this ticket?');">Close Ticket</button>
        <?php endif; ?>
      </form>
    </div>
    <?php elseif ($ticket['status'] === 'open'): ?>
    <!-- Client Reply Form -->
    <div class="admin-reply-section">
      <h3>💬 Add a Reply</h3>
      <p style="color:var(--muted);margin-bottom:16px;">Have more information to add? Reply to your ticket below.</p>
      <form method="post" action="reply_ticket.php">
        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
        <input type="hidden" name="is_admin" value="0">
        <div class="reply-form-group">
          <textarea name="reply_message" placeholder="Type your additional information here..." required></textarea>
        </div>
        <button type="submit" class="btn-submit">Send Reply</button>
      </form>
    </div>
    <?php else: ?>
    <div class="admin-reply-section">
      <h3>🔒 Ticket Closed</h3>
      <p style="color:var(--muted);margin-bottom:16px;">This ticket has been closed. If you need further assistance, you can reopen it below.</p>
      <form method="post" action="reply_ticket.php">
        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
        <input type="hidden" name="reopen_ticket" value="1">
        <button type="submit" class="btn-submit">Reopen Ticket</button>
      </form>
    </div>
    <?php endif; ?>

    <a href="/support.php" class="btn-back">← Back to Support</a>
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
