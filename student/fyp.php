<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireStudent();

$student_id = $_SESSION['user_id'];
$msg = ''; $error = '';

// Check if student already belongs to a group
$stmt = $pdo->prepare("SELECT g.* FROM fyp_groups g JOIN fyp_members m ON g.id = m.group_id WHERE m.student_id = ?");
$stmt->execute([$student_id]);
$my_group = $stmt->fetch();

// Handle Group Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_group' && !$my_group) {
    $group_name    = trim($_POST['group_name']);
    $project_title = trim($_POST['project_title']);
    $description   = trim($_POST['description']);
    $member_emails = array_filter(array_map('trim', explode(',', $_POST['member_emails'] ?? '')));

    if (empty($group_name) || empty($project_title)) {
        $error = "Group name and project title are required.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO fyp_groups (group_name, project_title, description, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$group_name, $project_title, $description, $student_id]);
            $group_id = $pdo->lastInsertId();

            // Add creator as member
            $stmt = $pdo->prepare("INSERT INTO fyp_members (group_id, student_id) VALUES (?, ?)");
            $stmt->execute([$group_id, $student_id]);

            // Add other members by email
            foreach ($member_emails as $email) {
                $s = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
                $s->execute([$email]);
                $member = $s->fetch();
                if ($member && $member['id'] != $student_id) {
                    // Check if member is already in another group
                    $chk = $pdo->prepare("SELECT id FROM fyp_members WHERE student_id = ?");
                    $chk->execute([$member['id']]);
                    if (!$chk->fetch()) {
                        $stmt->execute([$group_id, $member['id']]);
                    }
                }
            }

            $pdo->commit();
            $msg = "FYP Group created successfully! Awaiting admin approval.";

            // Refresh group data
            $stmt = $pdo->prepare("SELECT g.* FROM fyp_groups g JOIN fyp_members m ON g.id = m.group_id WHERE m.student_id = ?");
            $stmt->execute([$student_id]);
            $my_group = $stmt->fetch();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error creating group: " . $e->getMessage();
        }
    }
}

// Fetch group members & milestones if group exists
$members = [];
$milestones = [];
if ($my_group) {
    $stmt = $pdo->prepare("SELECT u.name, u.email FROM fyp_members m JOIN users u ON m.student_id = u.id WHERE m.group_id = ?");
    $stmt->execute([$my_group['id']]);
    $members = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM fyp_milestones WHERE group_id = ? ORDER BY due_date ASC");
    $stmt->execute([$my_group['id']]);
    $milestones = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FYP Portal — Smart Campus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .form-control { width: 100%; padding: 0.8rem 1rem; background: rgba(10,22,40,0.5); border: 1px solid var(--border-color); border-radius: 12px; color: var(--text-main); font-family: inherit; font-size: 0.95rem; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--primary); background: rgba(59,130,246,0.05); }
        .milestone-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 0.8rem; transition: 0.3s; }
        .milestone-item:hover { background: rgba(255,255,255,0.04); }
        .milestone-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
        .dot-pending { background: #64748b; }
        .dot-in_progress { background: #f59e0b; box-shadow: 0 0 8px rgba(245,158,11,0.4); }
        .dot-completed { background: #10b981; box-shadow: 0 0 8px rgba(16,185,129,0.4); }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header"><h1>FYP Portal</h1></header>

            <?php if ($msg): ?><div style="background:rgba(16,185,129,0.1); color:#6ee7b7; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(16,185,129,0.2);"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($error): ?><div style="background:rgba(239,68,68,0.1); color:#fca5a5; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(239,68,68,0.2);"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php if (!$my_group): ?>
            <!-- No group yet — show creation form -->
            <div class="content-card anim-item" style="max-width:700px;">
                <h2 class="card-header">Submit FYP Group Proposal</h2>
                <form method="POST" onsubmit="return confirm('Submit this FYP group?')">
                    <input type="hidden" name="action" value="create_group">
                    <div class="form-group">
                        <label>Group Name</label>
                        <input type="text" name="group_name" class="form-control" placeholder="e.g. Team Alpha" required>
                    </div>
                    <div class="form-group">
                        <label>Project Title</label>
                        <input type="text" name="project_title" class="form-control" placeholder="e.g. Smart Attendance System using AI" required>
                    </div>
                    <div class="form-group">
                        <label>Project Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Describe your project idea, objectives, and scope..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Team Member Emails (comma-separated)</label>
                        <input type="text" name="member_emails" class="form-control" placeholder="member1@cusit.edu.pk, member2@cusit.edu.pk">
                        <span style="font-size:0.75rem; color:var(--text-muted); margin-top:0.3rem; display:block;">You are automatically added. Other members must have registered accounts.</span>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%; padding:0.9rem;">Submit Proposal for Review</button>
                </form>
            </div>
            <?php else: ?>
            <!-- Group exists — show status -->
            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:1.5rem;">
                <!-- Project Overview Card -->
                <div class="content-card anim-item">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem;">
                        <div>
                            <h2 style="font-size:1.4rem; margin-bottom:0.3rem;"><?= htmlspecialchars($my_group['project_title']) ?></h2>
                            <span style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($my_group['group_name']) ?></span>
                        </div>
                        <?php
                            $status_colors = ['pending'=>'#f59e0b','approved'=>'#10b981','in_progress'=>'#3b82f6','completed'=>'#8b5cf6','rejected'=>'#ef4444'];
                            $sc = $status_colors[$my_group['status']] ?? '#94a3b8';
                        ?>
                        <span class="badge" style="background:<?= $sc ?>20; color:<?= $sc ?>; border:1px solid <?= $sc ?>40; font-size:0.85rem; padding:0.4rem 1rem;">
                            <?= ucfirst(str_replace('_',' ',$my_group['status'])) ?>
                        </span>
                    </div>
                    
                    <?php if ($my_group['description']): ?>
                        <p style="color:var(--text-muted); line-height:1.7; font-size:0.95rem; margin-bottom:1.5rem;"><?= nl2br(htmlspecialchars($my_group['description'])) ?></p>
                    <?php endif; ?>

                    <?php if ($my_group['supervisor']): ?>
                        <div style="background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.2); padding:1rem; border-radius:12px;">
                            <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em;">Assigned Supervisor</span>
                            <p style="font-size:1.1rem; font-weight:600; color:#a78bfa; margin-top:0.3rem;"><?= htmlspecialchars($my_group['supervisor']) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Milestones -->
                    <h3 style="font-size:1rem; margin:2rem 0 1rem; color:var(--text-main);">Project Milestones</h3>
                    <?php if (count($milestones) > 0): ?>
                        <?php foreach ($milestones as $m): ?>
                        <div class="milestone-item">
                            <div class="milestone-dot dot-<?= $m['status'] ?>"></div>
                            <div style="flex:1;">
                                <div style="font-weight:500; font-size:0.95rem;"><?= htmlspecialchars($m['title']) ?></div>
                                <?php if ($m['due_date']): ?>
                                    <span style="font-size:0.8rem; color:var(--text-muted);">Due: <?= date('M d, Y', strtotime($m['due_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="badge" style="background:rgba(255,255,255,0.05); color:var(--text-muted);"><?= ucfirst(str_replace('_',' ',$m['status'])) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--text-muted); font-size:0.9rem;">No milestones assigned yet. Your supervisor will add them after approval.</p>
                    <?php endif; ?>
                </div>

                <!-- Team Members Sidebar -->
                <div class="content-card anim-item">
                    <h2 class="card-header">Team Members</h2>
                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        <?php foreach ($members as $m): ?>
                        <div style="display:flex; align-items:center; gap:12px; padding:0.8rem; background:rgba(255,255,255,0.02); border-radius:10px;">
                            <div style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg, var(--primary), var(--accent)); display:grid; place-items:center; font-weight:700; font-size:0.85rem; color:#0a1628; flex-shrink:0;">
                                <?= strtoupper(substr($m['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight:500; font-size:0.9rem;"><?= htmlspecialchars($m['name']) ?></div>
                                <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($m['email']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>
