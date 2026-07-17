<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

$msg = ''; $error = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $title   = trim($_POST['title']);
        $content = trim($_POST['content']);
        if (empty($title) || empty($content)) {
            $error = "Title and content are required.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$title, $content, $_SESSION['user_id']]);
            $msg = "Announcement published successfully!";
        }
    }
    if ($_POST['action'] === 'delete' && isset($_POST['ann_id'])) {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$_POST['ann_id']]);
        $msg = "Announcement deleted.";
    }
}

// Fetch all
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
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .form-control { width: 100%; padding: 0.8rem 1rem; background: rgba(10,22,40,0.5); border: 1px solid var(--border-color); border-radius: 12px; color: var(--text-main); font-family: inherit; font-size: 0.95rem; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--primary); background: rgba(6,214,160,0.04); }
        .ann-card { background: rgba(6,214,160,0.03); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.5rem; margin-bottom: 1rem; transition: 0.3s; position: relative; }
        .ann-card:hover { background: rgba(6,214,160,0.06); border-color: rgba(6,214,160,0.2); }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header"><h1>Announcements</h1></header>

            <?php if ($msg): ?><div style="background:rgba(16,185,129,0.1); color:#6ee7b7; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(16,185,129,0.2);"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($error): ?><div style="background:rgba(239,68,68,0.1); color:#fca5a5; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(239,68,68,0.2);"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">
                <!-- Create Form -->
                <div class="content-card anim-item">
                    <h2 class="card-header">Publish New Announcement</h2>
                    <form method="POST" onsubmit="return confirm('Publish this announcement?')">
                        <input type="hidden" name="action" value="create">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Mid-Term Exam Schedule" required>
                        </div>
                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="content" class="form-control" rows="6" placeholder="Write the announcement details here..." required></textarea>
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%; padding:0.9rem;">Publish Announcement</button>
                    </form>
                </div>

                <!-- List -->
                <div class="content-card anim-item">
                    <h2 class="card-header">All Announcements <span style="font-size:0.8rem;color:var(--text-muted);font-weight:400;">(<?= count($announcements) ?> total)</span></h2>
                    <div style="max-height:500px; overflow-y:auto; padding-right:0.5rem;">
                        <?php foreach ($announcements as $a): ?>
                        <div class="ann-card">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.8rem;">
                                <h3 style="font-size:1.05rem; color:var(--text-main); flex:1;"><?= htmlspecialchars($a['title']) ?></h3>
                                <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this announcement?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="ann_id" value="<?= $a['id'] ?>">
                                    <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:1.1rem; padding:0 0.3rem; opacity:0.6; transition:0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'" title="Delete">&times;</button>
                                </form>
                            </div>
                            <p style="font-size:0.9rem; color:var(--text-muted); line-height:1.6; margin-bottom:0.8rem;"><?= nl2br(htmlspecialchars(strlen($a['content']) > 150 ? substr($a['content'],0,150).'...' : $a['content'])) ?></p>
                            <div style="display:flex; gap:1rem; font-size:0.75rem; color:var(--text-muted);">
                                <span>By <?= htmlspecialchars($a['author']) ?></span>
                                <span><?= date('M d, Y — h:i A', strtotime($a['created_at'])) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(count($announcements) === 0): ?>
                            <p style="text-align:center; color:var(--text-muted); padding:2rem;">No announcements yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
