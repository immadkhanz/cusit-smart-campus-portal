<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireStudent();

// Fetch all announcements
$announcements = $pdo->query("SELECT a.*, u.name as author FROM announcements a JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements — Smart Campus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .ann-card { background: rgba(6,214,160,0.03); border: 1px solid var(--border-color); border-radius: 16px; padding: 2rem; margin-bottom: 1.2rem; transition: all 0.3s ease; position: relative; overflow: hidden; }
        .ann-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: linear-gradient(180deg, var(--primary), var(--accent)); border-radius: 4px 0 0 4px; }
        .ann-card:hover { background: rgba(6,214,160,0.06); border-color: rgba(6,214,160,0.2); transform: translateY(-2px); box-shadow: 0 8px 30px rgba(6,214,160,0.08); }
        .ann-badge { display: inline-flex; align-items: center; gap: 6px; padding: 0.3rem 0.8rem; background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.15); border-radius: 20px; font-size: 0.75rem; color: var(--accent); }
        .ann-search { width: 100%; padding: 0.9rem 1.2rem 0.9rem 3rem; background: rgba(10,22,40,0.5); border: 1px solid var(--border-color); border-radius: 14px; color: var(--text-main); font-family: inherit; font-size: 0.95rem; transition: 0.3s; outline: none; }
        .ann-search:focus { border-color: var(--primary); background: rgba(6,214,160,0.04); }
        .search-wrapper { position: relative; margin-bottom: 2rem; }
        .search-wrapper::before { content: '🔍'; position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: 1rem; }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header">
                <h1>📢 Announcements</h1>
                <div class="user-profile">
                    <span style="font-size:0.8rem;color:var(--text-muted)">Student</span>
                    <strong style="font-size:0.9rem"><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                </div>
            </header>

            <div class="search-wrapper">
                <input type="text" class="ann-search" id="annSearch" placeholder="Search announcements..." oninput="filterAnnouncements()">
            </div>

            <div id="annList">
                <?php if (count($announcements) > 0): ?>
                    <?php foreach ($announcements as $a): ?>
                    <div class="ann-card anim-item" data-title="<?= strtolower(htmlspecialchars($a['title'])) ?>" data-content="<?= strtolower(htmlspecialchars($a['content'])) ?>">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem; flex-wrap:wrap; gap:0.8rem;">
                            <h3 style="font-size:1.15rem; color:var(--text-main); flex:1; min-width:200px;"><?= htmlspecialchars($a['title']) ?></h3>
                            <div class="ann-badge">
                                📅 <?= date('M d, Y', strtotime($a['created_at'])) ?>
                            </div>
                        </div>
                        <p style="font-size:0.92rem; color:var(--text-muted); line-height:1.8; margin-bottom:1.2rem;"><?= nl2br(htmlspecialchars($a['content'])) ?></p>
                        <div style="display:flex; align-items:center; gap:0.8rem;">
                            <div style="width:28px; height:28px; border-radius:50%; background:linear-gradient(135deg, var(--primary), var(--accent)); display:grid; place-items:center; font-size:0.7rem; font-weight:700; color:#0a1628;">
                                <?= strtoupper(substr($a['author'], 0, 1)) ?>
                            </div>
                            <span style="font-size:0.8rem; color:var(--text-muted);">By <?= htmlspecialchars($a['author']) ?> • <?= date('h:i A', strtotime($a['created_at'])) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="content-card" style="text-align:center; padding:3rem;">
                        <div style="font-size:3rem; margin-bottom:1rem;">📭</div>
                        <h3 style="color:var(--text-main); margin-bottom:0.5rem;">No Announcements Yet</h3>
                        <p style="color:var(--text-muted); font-size:0.9rem;">The administration hasn't posted any announcements. Check back later!</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="noResults" style="display:none; text-align:center; padding:3rem; color:var(--text-muted);">
                <div style="font-size:3rem; margin-bottom:1rem;">🔍</div>
                <p>No announcements match your search.</p>
            </div>
        </main>
    </div>

    <script>
    function filterAnnouncements() {
        const q = document.getElementById('annSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.ann-card');
        let visible = 0;
        cards.forEach(card => {
            const match = card.dataset.title.includes(q) || card.dataset.content.includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        document.getElementById('noResults').style.display = visible === 0 && q ? 'block' : 'none';
    }
    </script>
</body>
</html>
