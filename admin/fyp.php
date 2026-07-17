<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

$msg = '';

// Handle Approve / Reject / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $group_id = (int)($_POST['group_id'] ?? 0);

    if ($action === 'update_status' && $group_id) {
        $status = $_POST['status'];
        $supervisor = trim($_POST['supervisor'] ?? '');
        $stmt = $pdo->prepare("UPDATE fyp_groups SET status = ?, supervisor = ? WHERE id = ?");
        $stmt->execute([$status, $supervisor ?: null, $group_id]);
        $msg = "Group #$group_id updated.";
    }

    if ($action === 'add_milestone' && $group_id) {
        $title = trim($_POST['milestone_title']);
        $due = $_POST['due_date'] ?: null;
        if ($title) {
            $stmt = $pdo->prepare("INSERT INTO fyp_milestones (group_id, title, due_date) VALUES (?, ?, ?)");
            $stmt->execute([$group_id, $title, $due]);
            $msg = "Milestone added to Group #$group_id.";
        }
    }

    if ($action === 'update_milestone') {
        $m_id = (int)$_POST['milestone_id'];
        $m_status = $_POST['milestone_status'];
        $stmt = $pdo->prepare("UPDATE fyp_milestones SET status = ? WHERE id = ?");
        $stmt->execute([$m_status, $m_id]);
        $msg = "Milestone updated.";
    }
}

// Fetch all groups with member count
$groups = $pdo->query("
    SELECT g.*, u.name as creator_name,
           (SELECT COUNT(*) FROM fyp_members WHERE group_id = g.id) as member_count
    FROM fyp_groups g
    JOIN users u ON g.created_by = u.id
    ORDER BY CASE WHEN g.status = 'pending' THEN 1 WHEN g.status = 'approved' THEN 2 WHEN g.status = 'in_progress' THEN 3 ELSE 4 END, g.created_at DESC
")->fetchAll();

// If reviewing a specific group
$review_id = $_GET['review'] ?? null;
$review_group = null;
$review_members = [];
$review_milestones = [];
if ($review_id) {
    $stmt = $pdo->prepare("SELECT g.*, u.name as creator_name FROM fyp_groups g JOIN users u ON g.created_by = u.id WHERE g.id = ?");
    $stmt->execute([$review_id]);
    $review_group = $stmt->fetch();
    if ($review_group) {
        $stmt = $pdo->prepare("SELECT u.name, u.email FROM fyp_members m JOIN users u ON m.student_id = u.id WHERE m.group_id = ?");
        $stmt->execute([$review_id]);
        $review_members = $stmt->fetchAll();
        $stmt = $pdo->prepare("SELECT * FROM fyp_milestones WHERE group_id = ? ORDER BY due_date ASC");
        $stmt->execute([$review_id]);
        $review_milestones = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FYP Management — Smart Campus</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .form-control { width: 100%; padding: 0.7rem 0.9rem; background: rgba(10,22,40,0.5); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-family: inherit; font-size: 0.9rem; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--primary); }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header"><h1>FYP Management</h1></header>

            <?php if ($msg): ?><div style="background:rgba(16,185,129,0.1); color:#6ee7b7; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(16,185,129,0.2);"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

            <div class="content-grid">
                <!-- Groups Table -->
                <div class="content-card anim-item" style="grid-column: <?= $review_group ? '1/2' : '1/-1' ?>;">
                    <h2 class="card-header">All FYP Groups</h2>
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Group</th>
                                    <th>Project</th>
                                    <th>Members</th>
                                    <th>Supervisor</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groups as $g):
                                    $sc_map = ['pending'=>'#f59e0b','approved'=>'#10b981','in_progress'=>'#3b82f6','completed'=>'#8b5cf6','rejected'=>'#ef4444'];
                                    $sc = $sc_map[$g['status']] ?? '#94a3b8';
                                ?>
                                <tr style="transition:0.3s;<?= ($review_id == $g['id']) ? 'background:rgba(59,130,246,0.05);' : '' ?>" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='<?= ($review_id == $g['id']) ? 'rgba(59,130,246,0.05)' : 'transparent' ?>'">
                                    <td><strong style="color:var(--text-main);"><?= htmlspecialchars($g['group_name']) ?></strong><br><span style="font-size:0.75rem;color:var(--text-muted);">by <?= htmlspecialchars($g['creator_name']) ?></span></td>
                                    <td style="font-size:0.9rem;"><?= htmlspecialchars($g['project_title']) ?></td>
                                    <td style="text-align:center;"><?= $g['member_count'] ?></td>
                                    <td style="font-size:0.85rem; color:var(--text-muted);"><?= $g['supervisor'] ?: '—' ?></td>
                                    <td><span class="badge" style="background:<?= $sc ?>15; color:<?= $sc ?>; border:1px solid <?= $sc ?>30;"><?= ucfirst(str_replace('_',' ',$g['status'])) ?></span></td>
                                    <td><a href="?review=<?= $g['id'] ?>" class="btn-primary" style="padding:0.35rem 0.7rem;font-size:0.8rem;">Review</a></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(count($groups)===0): ?><tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">No FYP submissions yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Review Panel -->
                <?php if ($review_group): ?>
                <div class="content-card anim-item" style="grid-column:2/3; border:1px solid var(--primary); box-shadow:0 0 30px rgba(59,130,246,0.1);">
                    <div class="card-header">
                        <span>Review: <?= htmlspecialchars($review_group['group_name']) ?></span>
                        <a href="fyp.php" style="color:var(--text-muted);text-decoration:none;font-size:1.5rem;">&times;</a>
                    </div>

                    <p style="font-size:0.9rem; color:var(--text-muted); line-height:1.6; margin-bottom:1.5rem;"><?= nl2br(htmlspecialchars($review_group['description'] ?? 'No description provided.')) ?></p>

                    <!-- Members -->
                    <h4 style="font-size:0.85rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.8rem;">Team Members</h4>
                    <?php foreach ($review_members as $rm): ?>
                        <div style="font-size:0.9rem; padding:0.4rem 0; color:#e2e8f0;">• <?= htmlspecialchars($rm['name']) ?> <span style="color:var(--text-muted);font-size:0.8rem;">(<?= htmlspecialchars($rm['email']) ?>)</span></div>
                    <?php endforeach; ?>

                    <!-- Update Status / Supervisor -->
                    <form method="POST" style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border-color);" onsubmit="return confirm('Update this FYP group?')">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="group_id" value="<?= $review_group['id'] ?>">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control" style="color:var(--text-main);">
                                <?php foreach(['pending','approved','in_progress','completed','rejected'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $review_group['status']==$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Assign Supervisor</label>
                            <input type="text" name="supervisor" class="form-control" placeholder="e.g. Dr. Ahmed" value="<?= htmlspecialchars($review_group['supervisor'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%;padding:0.8rem;">Update Group</button>
                    </form>

                    <!-- Add Milestone -->
                    <form method="POST" style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border-color);" onsubmit="return confirm('Add this milestone?')">
                        <input type="hidden" name="action" value="add_milestone">
                        <input type="hidden" name="group_id" value="<?= $review_group['id'] ?>">
                        <h4 style="font-size:0.85rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.8rem;">Add Milestone</h4>
                        <div class="form-group">
                            <input type="text" name="milestone_title" class="form-control" placeholder="e.g. Proposal Defense" required>
                        </div>
                        <div class="form-group">
                            <input type="date" name="due_date" class="form-control" style="color-scheme:dark;">
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%;padding:0.7rem;font-size:0.85rem;">+ Add Milestone</button>
                    </form>

                    <!-- Existing Milestones -->
                    <?php if (count($review_milestones) > 0): ?>
                    <div style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border-color);">
                        <h4 style="font-size:0.85rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.8rem;">Current Milestones</h4>
                        <?php foreach ($review_milestones as $rm): ?>
                        <form method="POST" style="display:flex; gap:0.5rem; align-items:center; margin-bottom:0.5rem;">
                            <input type="hidden" name="action" value="update_milestone">
                            <input type="hidden" name="milestone_id" value="<?= $rm['id'] ?>">
                            <span style="flex:1;font-size:0.85rem;color:#e2e8f0;"><?= htmlspecialchars($rm['title']) ?></span>
                            <select name="milestone_status" class="form-control" data-original="<?= $rm['status'] ?>" style="width:auto;padding:0.4rem 0.6rem;font-size:0.8rem;color:var(--text-main);" onchange="if(confirm('Update milestone status?')){this.form.submit()}else{this.value=this.dataset.original}">
                                <option value="pending" <?= $rm['status']=='pending'?'selected':'' ?>>Pending</option>
                                <option value="in_progress" <?= $rm['status']=='in_progress'?'selected':'' ?>>In Progress</option>
                                <option value="completed" <?= $rm['status']=='completed'?'selected':'' ?>>Completed</option>
                            </select>
                        </form>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>
