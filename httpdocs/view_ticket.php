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
    exit;
}

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
    margin-top:8px;
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
    display:flex;
    align-items:center;
    gap:4px;
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
  .canned-responses {
    margin-bottom:16px;
  }
  .canned-responses select {
    width:100%;
    padding:12px;
    border:1px solid var(--input-border);
    border-radius:10px;
    background:var(--input-bg);
    color:var(--text);
    font-size:15px;
    cursor:pointer;
    transition:border-color .2s;
  }
  .canned-responses select:focus {
    outline:none;
    border-color:var(--accent);
  }
  .canned-responses label {
    display:block;
    margin-bottom:8px;
    color:var(--text);
    font-weight:600;
    font-size:14px;
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

  /* Internal Notes (Admin Only) */
  .internal-notes-section {
    background:rgba(88,166,255,.08);
    border:2px solid var(--accent);
    border-radius:14px;
    padding:30px;
    margin-bottom:20px;
    box-shadow:0 4px 12px rgba(88,166,255,.15);
  }
  .internal-notes-section h3 {
    color:var(--accent);
    margin-bottom:8px;
    font-size:20px;
    display:flex;
    align-items:center;
    gap:8px;
  }
  .notes-list {
    margin:16px 0;
  }
  .internal-note {
    background:var(--card);
    border:1px solid var(--accent);
    border-left:4px solid var(--accent);
    border-radius:10px;
    padding:16px;
    margin-bottom:12px;
  }
  .note-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
    padding-bottom:8px;
    border-bottom:1px solid var(--input-border);
  }
  .note-author {
    font-weight:600;
    font-size:14px;
    color:var(--accent);
  }
  .note-time {
    font-size:12px;
    color:var(--muted);
  }
  .note-content {
    line-height:1.6;
    color:var(--text);
    font-size:14px;
    white-space:pre-wrap;
    word-wrap:break-word;
  }
  .btn-note {
    padding:12px 24px;
    background:var(--accent);
    color:#fff;
    border:none;
    border-radius:10px;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
    transition:all .2s;
  }
  .btn-note:hover {
    opacity:.9;
    transform:translateY(-2px);
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
            <span class="message-author">
              <?= (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) ? '👤 Customer' : '👤 You' ?>
            </span>
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
                  <?php
                  if ($reply['is_admin']) {
                    echo '👨‍💼 Support Team';
                  } else {
                    echo (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) ? '👤 Customer' : '👤 You';
                  }
                  ?>
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
    <!-- Internal Notes Section (Admin Only) -->
    <div class="internal-notes-section">
      <h3>🔒 Internal Notes (Admin Only)</h3>
      <p style="color:var(--muted);font-size:13px;margin-bottom:16px;">
        These notes are private and only visible to admins. Customers cannot see internal notes.
      </p>
      
      <!-- Display existing internal notes -->
      <?php if (!empty($ticket['internal_notes']) && is_array($ticket['internal_notes'])): ?>
        <div class="notes-list">
          <?php foreach ($ticket['internal_notes'] as $note): ?>
            <div class="internal-note">
              <div class="note-header">
                <span class="note-author">🔒 <?= htmlspecialchars($note['author'] ?? 'Admin') ?></span>
                <span class="note-time"><?= htmlspecialchars(date('M j, Y, g:i A', strtotime($note['created_at']))) ?></span>
              </div>
              <div class="note-content"><?= nl2br(htmlspecialchars($note['note'])) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color:var(--muted);font-style:italic;margin-bottom:16px;">No internal notes yet.</p>
      <?php endif; ?>
      
      <!-- Add new internal note -->
      <form method="post" action="reply_ticket.php" style="margin-top:20px;">
        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
        <input type="hidden" name="add_internal_note" value="1">
        <div class="reply-form-group">
          <textarea name="internal_note" placeholder="Add a private note (e.g., troubleshooting steps, escalation info, customer history...)..." rows="4" style="background:rgba(88,166,255,.05);border-color:var(--accent);"></textarea>
        </div>
        <button type="submit" class="btn-note">💾 Save Internal Note</button>
      </form>
    </div>
    
    <!-- Admin Reply Form -->
    <div class="admin-reply-section">
      <h3>👨‍💼 Admin Reply</h3>
      <div class="admin-notice">
        ⚠️ You are logged in as admin. Your reply will be sent to the ticket creator via email.
      </div>
      <form method="post" action="reply_ticket.php">
        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
        <input type="hidden" name="is_admin" value="1">
        
        <!-- Canned Responses -->
        <div class="canned-responses">
          <label for="canned-select">📋 Quick Responses (Optional)</label>
          <select id="canned-select" onchange="insertCannedResponse(this.value)">
            <option value="">-- Select a template to insert --</option>
            <option value="greeting">👋 Greeting & Acknowledge</option>
            <option value="investigating">🔍 Investigating Issue</option>
            <option value="need_more_info">ℹ️ Need More Information</option>
            <option value="resolved">✅ Issue Resolved</option>
            <option value="server_restart">🔄 Server Restart Instructions</option>
            <option value="billing_info">💳 Billing Information</option>
            <option value="account_verified">✓ Account Verified</option>
            <option value="feature_request">✨ Feature Request Response</option>
            <option value="apologize">🙏 Apologize for Inconvenience</option>
            <option value="escalated">⬆️ Escalated to Technical Team</option>
          </select>
        </div>
        
        <div class="reply-form-group">
          <textarea id="reply-textarea" name="reply_message" placeholder="Type your reply to the customer..." required></textarea>
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

// Canned responses templates
const cannedResponses = {
  greeting: "Hello,\n\nThank you for contacting EnderBit Support! We've received your ticket and our team is reviewing your request.\n\nWe'll get back to you as soon as possible with a solution.\n\nBest regards,\nEnderBit Support Team",
  
  investigating: "Hello,\n\nThank you for bringing this to our attention. Our technical team is currently investigating this issue.\n\nWe'll update you as soon as we have more information.\n\nBest regards,\nEnderBit Support Team",
  
  need_more_info: "Hello,\n\nTo help us resolve your issue more effectively, could you please provide the following additional information:\n\n- [Please specify what information is needed]\n\nOnce we have these details, we'll be able to assist you further.\n\nThank you for your cooperation!\n\nBest regards,\nEnderBit Support Team",
  
  resolved: "Hello,\n\nGreat news! We've successfully resolved the issue you reported.\n\nEverything should now be working as expected. If you experience any further problems or have additional questions, please don't hesitate to reach out.\n\nThank you for your patience!\n\nBest regards,\nEnderBit Support Team",
  
  server_restart: "Hello,\n\nTo resolve this issue, please try restarting your server:\n\n1. Log into your game panel\n2. Navigate to your server\n3. Click the 'Restart' button\n4. Wait 2-3 minutes for the server to fully restart\n\nIf the issue persists after restarting, please let us know and we'll investigate further.\n\nBest regards,\nEnderBit Support Team",

  server_info: "Hello,\n\nRegarding your server inquiry:\n\nYour current plan: [Plan Name]If you have any questions about your server or would like to make changes to your plan, please let us know.\n\nBest regards,\nEnderBit Support Team",

  account_verified: "Hello,\n\nGood news! Your account has been successfully verified and approved.\n\nYou can now log in to your panel and start using our services:\n<?= htmlspecialchars($config['ptero_url'] ?? 'https://panel.enderbit.com') ?>\n\nIf you need any assistance getting started, feel free to reach out!\n\nWelcome to EnderBit!\n\nBest regards,\nEnderBit Support Team",
  
  feature_request: "Hello,\n\nThank you for your feature request! We really appreciate your feedback.\n\nWe've added your suggestion to our feature request list and our development team will review it. While we can't guarantee implementation, we carefully consider all user feedback.\n\nWe'll keep you updated if this feature is added in the future.\n\nThank you for helping us improve!\n\nBest regards,\nEnderBit Support Team",
  
  apologize: "Hello,\n\nWe sincerely apologize for the inconvenience you've experienced.\n\nWe understand how frustrating this must be, and we're working hard to resolve this issue as quickly as possible. Your satisfaction is our top priority.\n\nThank you for your patience and understanding.\n\nBest regards,\nEnderBit Support Team",
  
  escalated: "Hello,\n\nThank you for your patience. We've escalated your ticket to our senior technical team for further investigation.\n\nThey will review your case in detail and get back to you with a solution. This may take 24-48 hours.\n\nWe appreciate your understanding.\n\nBest regards,\nEnderBit Support Team"
};

function insertCannedResponse(template) {
  if (template && cannedResponses[template]) {
    const textarea = document.getElementById('reply-textarea');
    if (textarea) {
      // Replace textarea content with the selected template
      textarea.value = cannedResponses[template];
      // Reset select dropdown
      document.getElementById('canned-select').value = '';
      // Focus textarea
      textarea.focus();
    }
  }
}
</script>
</body>
</html>
