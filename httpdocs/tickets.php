<?php
ob_start(); // Start output buffering to allow cookies to be set

require_once __DIR__ . '/admin_session.php';
require_once __DIR__ . '/background_tasks.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/timezone_utils.php';
require_once __DIR__ . '/security.php';

// Set security headers
EnderBitSecurity::setSecurityHeaders();

// Initialize and validate admin session
EnderBitAdminSession::init();
if (!EnderBitAdminSession::isLoggedIn()) {
    header("Location: admin.php");
    exit;
}

// Run scheduled tasks
EnderBitBackgroundTasks::runScheduledTasks();

$msg = $_GET['msg'] ?? '';
$msgType = $_GET['type'] ?? 'success';

// Get tickets
$ticketsFile = __DIR__ . '/tickets.json';
$tickets = [];
$totalTickets = 0;
$openTickets = 0;
$closedTickets = 0;

if (file_exists($ticketsFile)) {
    $tickets = json_decode(file_get_contents($ticketsFile), true);
    if (is_array($tickets)) {
        $totalTickets = count($tickets);
        foreach ($tickets as $ticket) {
            if ($ticket['status'] === 'open') $openTickets++;
            if ($ticket['status'] === 'closed') $closedTickets++;
        }
        
        // Sort tickets by created_at descending
        usort($tickets, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
    }
}
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Ticket Management — EnderBit Admin</title>
<link rel="icon" type="image/png" href="/icon.png" sizes="96x96">
<style>
  :root {
    --bg:#0d1117; --card:#161b22; --accent:#58a6ff; --primary:#1f6feb;
    --muted:#8b949e; --green:#238636; --red:#f85149; --yellow:#f0883e;
    --text:#e6eef8; --input-bg:#0e1418; --input-border:#232629;
  }
  html,body{height:100%;margin:0;font-family:Inter,Arial,sans-serif;background:var(--bg);color:var(--text);}
  .page{min-height:100%;padding:28px;box-sizing:border-box;}
  .container{max-width:1400px;margin:0 auto;}
  .card{background:var(--card);border:1px solid var(--input-border);border-radius:12px;padding:24px;box-shadow:0 4px 12px rgba(0,0,0,.3);}
  h1{margin:0 0 24px;color:var(--accent);font-size:32px;}
  .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;}
  .page-header h1{margin:0;}
  .btn{display:inline-block;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;text-align:center;cursor:pointer;border:0;text-decoration:none;transition:all .2s;box-sizing:border-box;}
  .btn-secondary{background:var(--input-bg);color:var(--text);border:1px solid var(--input-border);}
  .btn-secondary:hover{opacity:.9;transform:translateY(-1px);}
  .btn-small{padding:8px 16px;font-size:13px;}
  
  .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:32px;}
  .stat-card{background:var(--card);border:1px solid var(--input-border);border-radius:12px;padding:24px;text-align:center;transition:all .2s;}
  .stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(88,166,255,.15);border-color:var(--accent);}
  .stat-value{font-size:36px;font-weight:700;color:var(--accent);margin:12px 0;}
  .stat-label{font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;font-weight:600;}
  .stat-icon{font-size:28px;margin-bottom:8px;}
  
  .ticket-card{background:var(--card);border:1px solid var(--input-border);border-radius:10px;padding:20px;margin-bottom:16px;transition:all .2s;}
  .ticket-card:hover{border-color:var(--accent);box-shadow:0 2px 8px rgba(88,166,255,.1);}
  .ticket-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--input-border);}
  .ticket-title{font-size:18px;font-weight:600;color:var(--text);margin-bottom:8px;}
  .ticket-meta{font-size:13px;color:var(--muted);line-height:1.6;}
  .status-badge{display:inline-block;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;}
  .status-open{background:rgba(34,197,94,.15);color:#22c55e;}
  .status-closed{background:rgba(239,68,68,.15);color:#ef4444;}
  .view-ticket-btn{display:inline-block;padding:10px 20px;background:var(--accent);color:#fff;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;transition:all .2s;}
  .view-ticket-btn:hover{opacity:.9;transform:translateY(-1px);}
  
  .filter-controls{background:var(--input-bg);border:1px solid var(--input-border);border-radius:10px;padding:20px;margin-bottom:20px;}
  .search-input{width:100%;padding:12px;border-radius:8px;border:1px solid var(--input-border);background:var(--card);color:var(--text);font-size:14px;margin-bottom:12px;box-sizing:border-box;}
  .search-input:focus{outline:none;border-color:var(--accent);}
  .filter-row{display:flex;gap:12px;flex-wrap:wrap;align-items:center;}
  .filter-select{flex:1;min-width:150px;padding:10px;border-radius:8px;border:1px solid var(--input-border);background:var(--card);color:var(--text);font-size:14px;}
  .filter-select:focus{outline:none;border-color:var(--accent);}
  .filter-results{margin-top:12px;font-size:13px;color:var(--muted);text-align:center;}
  .empty-state{text-align:center;padding:60px 20px;color:var(--muted);}
  .empty-state h3{color:var(--text);margin-bottom:8px;}
  
  .banner {
    position:fixed;
    left:-500px;
    top:20px;
    padding:14px 20px;
    border-radius:10px;
    min-width:280px;
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
</style>
</head>
<body>
  <?php if ($msg): ?>
    <div id="banner" class="banner <?= htmlspecialchars($msgType) ?> show">
      <span><?= htmlspecialchars($msg) ?></span>
      <span class="close" onclick="hideBanner()">×</span>
    </div>
  <?php endif; ?>

  <div class="page">
    <div class="container">
      <div class="page-header">
        <div>
          <h1>🎫 Ticket Management</h1>
          <span style="font-size:13px;color:var(--text-secondary);margin-top:4px;display:inline-block;">
            🌍 Showing times in your timezone: <?= getTimezoneAbbr() ?> (<?= getTimezoneOffset() ?>)
          </span>
        </div>
        <a href="/admin.php" class="btn btn-secondary">← Back to Admin Panel</a>
      </div>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">📊</div>
          <div class="stat-label">Total Tickets</div>
          <div class="stat-value"><?= $totalTickets ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🟢</div>
          <div class="stat-label">Open Tickets</div>
          <div class="stat-value" style="color:#22c55e;"><?= $openTickets ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🔴</div>
          <div class="stat-label">Closed Tickets</div>
          <div class="stat-value" style="color:#ef4444;"><?= $closedTickets ?></div>
        </div>
      </div>

      <div class="card">
        <div class="filter-controls">
          <input type="text" id="searchTickets" placeholder="🔍 Search by ID, email, subject, or keywords..." class="search-input">
          <div class="filter-row">
            <select id="filterStatus" class="filter-select">
              <option value="all">All Status</option>
              <option value="open">Open Only</option>
              <option value="closed">Closed Only</option>
            </select>
            <select id="filterPriority" class="filter-select">
              <option value="all">All Priorities</option>
              <option value="urgent">🔴 Urgent</option>
              <option value="high">🟠 High</option>
              <option value="medium">🟡 Medium</option>
              <option value="low">🟢 Low</option>
            </select>
            <select id="filterCategory" class="filter-select">
              <option value="all">All Categories</option>
              <option value="technical">💻 Technical</option>
              <option value="billing">💳 Billing</option>
              <option value="account">👤 Account</option>
              <option value="feature">✨ Feature</option>
              <option value="other">❓ Other</option>
            </select>
            <button id="resetFilters" class="btn btn-secondary btn-small">Reset Filters</button>
          </div>
          <div id="filterResults" class="filter-results"></div>
        </div>
        
        <?php if (count($tickets) > 0): ?>
          <?php foreach ($tickets as $ticket):
                    $ticketId = htmlspecialchars($ticket['id']);
                    $replyCount = isset($ticket['replies']) ? count($ticket['replies']) : 0;
                    ?>
                    <div class="ticket-card" id="ticket-<?= $ticketId ?>">
                      <div class="ticket-header">
                        <div style="flex:1;">
                          <div class="ticket-title">🎫 <?= htmlspecialchars($ticket['subject']) ?></div>
                          <div class="ticket-meta">
                            ID: <strong><?= $ticketId ?></strong> | 
                            <?php if (!empty($ticket['category'])): ?>
                              <?php
                              $categoryIcons = [
                                'technical' => '💻',
                                'billing' => '💳',
                                'account' => '👤',
                                'feature' => '✨',
                                'other' => '❓'
                              ];
                              $categoryNames = [
                                'technical' => 'Technical',
                                'billing' => 'Get a Server',
                                'account' => 'Account',
                                'feature' => 'Feature Request',
                                'other' => 'Other'
                              ];
                              $icon = $categoryIcons[$ticket['category']] ?? '📋';
                              $categoryName = $categoryNames[$ticket['category']] ?? ucfirst($ticket['category']);
                              echo $icon . ' <strong>' . htmlspecialchars($categoryName) . '</strong> | ';
                              ?>
                            <?php endif; ?>
                            <?php if (!empty($ticket['priority'])): ?>
                              <?php
                              $priorityIcons = [
                                'low' => '🟢',
                                'medium' => '🟡',
                                'high' => '🟠',
                                'urgent' => '🔴'
                              ];
                              $pIcon = $priorityIcons[$ticket['priority']] ?? '⚪';
                              echo $pIcon . ' <strong>' . htmlspecialchars(ucfirst($ticket['priority'])) . '</strong> | ';
                              ?>
                            <?php endif; ?>
                            From: <strong><?= htmlspecialchars($ticket['email']) ?></strong> | 
                            Created: <?= htmlspecialchars(formatDateTimeInUserTZ($ticket['created_at'], 'M j, Y, g:i A')) ?> <span style="color:var(--text-secondary);font-size:11px;">(<?= getTimezoneAbbr() ?>)</span> | 
                            <?php if ($replyCount > 0): ?>💬 <strong><?= $replyCount ?></strong> replies<?php endif; ?>
                            <?php if (!empty($ticket['attachment'])): ?> | 📎 Attachment<?php endif; ?>
                          </div>
                        </div>
                        <span class="status-badge status-<?= htmlspecialchars($ticket['status']) ?>">
                          <?= htmlspecialchars(ucfirst($ticket['status'])) ?>
                        </span>
                      </div>

                      <div style="margin-top:15px;">
                        <a href="/ticket/<?= $ticketId ?>" target="_blank" class="view-ticket-btn">View & Reply to Ticket</a>
                      </div>
                    </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <h3>No Support Tickets</h3>
            <p>No support tickets have been submitted yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<script>
// Search and Filter Functionality
(function() {
  const searchInput = document.getElementById('searchTickets');
  const filterStatus = document.getElementById('filterStatus');
  const filterPriority = document.getElementById('filterPriority');
  const filterCategory = document.getElementById('filterCategory');
  const resetBtn = document.getElementById('resetFilters');
  const filterResults = document.getElementById('filterResults');
  const ticketCards = document.querySelectorAll('.ticket-card');

  function applyFilters() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusFilter = filterStatus.value;
    const priorityFilter = filterPriority.value;
    const categoryFilter = filterCategory.value;

    let visibleCount = 0;
    let totalCount = ticketCards.length;

    ticketCards.forEach(card => {
      const ticketText = card.textContent.toLowerCase();
      const ticketStatus = card.querySelector('.status-badge').textContent.toLowerCase();
      const ticketMeta = card.querySelector('.ticket-meta').textContent.toLowerCase();
      
      const matchesSearch = searchTerm === '' || ticketText.includes(searchTerm);
      const matchesStatus = statusFilter === 'all' || ticketStatus.includes(statusFilter);
      const matchesPriority = priorityFilter === 'all' || ticketMeta.includes(priorityFilter);
      const matchesCategory = categoryFilter === 'all' || ticketMeta.includes(categoryFilter);

      if (matchesSearch && matchesStatus && matchesPriority && matchesCategory) {
        card.style.display = 'block';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    if (searchTerm || statusFilter !== 'all' || priorityFilter !== 'all' || categoryFilter !== 'all') {
      filterResults.textContent = `Showing ${visibleCount} of ${totalCount} tickets`;
    } else {
      filterResults.textContent = '';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
    filterPriority.addEventListener('change', applyFilters);
    filterCategory.addEventListener('change', applyFilters);
    
    resetBtn.addEventListener('click', function() {
      searchInput.value = '';
      filterStatus.value = 'all';
      filterPriority.value = 'all';
      filterCategory.value = 'all';
      applyFilters();
    });
  }
})();

function hideBanner(){
  const b = document.getElementById('banner');
  if (!b) return;
  b.classList.add('hide');
  b.classList.remove('show');
}
window.addEventListener('load', ()=>{
  const b = document.getElementById('banner');
  if (!b) return;
  b.classList.remove('hide');
  setTimeout(()=> b.classList.add('show'), 120);
  setTimeout(()=> hideBanner(), 5000);
});
</script>
</body>
</html>
