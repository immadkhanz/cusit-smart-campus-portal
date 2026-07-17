<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

$msg = '';

// Handle Admin Response Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'])) {
    verifyCSRF();
    $c_id = $_POST['complaint_id'];
    $status = $_POST['status'];
    $response = trim($_POST['admin_response'] ?? '');
    
    $stmt = $pdo->prepare("UPDATE complaints SET status = ?, admin_response = ? WHERE id = ?");
    if ($stmt->execute([$status, $response, $c_id])) {
        $msg = "Complaint #$c_id updated successfully.";
    }
}

// Fetch all complaints
$stmt = $pdo->query("
    SELECT c.*, u.name as student_name, u.email as student_email 
    FROM complaints c 
    JOIN users u ON c.student_id = u.id 
    ORDER BY CASE WHEN c.status = 'pending' THEN 1 WHEN c.status = 'in_progress' THEN 2 ELSE 3 END, c.created_at DESC
");
$all_complaints = $stmt->fetchAll();

// Check if a specific complaint is selected for review
$selected_id = $_GET['respond'] ?? null;
$selected_complaint = null;
if ($selected_id) {
    foreach ($all_complaints as $c) {
        if ($c['id'] == $selected_id) {
            $selected_complaint = $c;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints — Smart Campus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .form-control { width: 100%; padding: 0.8rem 1rem; background: rgba(10,22,40,0.5); border: 1px solid var(--border-color); border-radius: 12px; color: var(--text-main); font-family: inherit; font-size: 0.95rem; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--primary); background: rgba(59,130,246,0.05); }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header">
                <h1>Review Complaints</h1>
            </header>

            <?php if ($msg): ?>
                <div style="background:rgba(16,185,129,0.1); color:#6ee7b7; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(16,185,129,0.2);">
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <div class="content-grid">
                <!-- Data Table -->
                <div class="content-card anim-item" style="grid-column: <?= $selected_complaint ? '1 / 2' : '1 / -1' ?>;">
                    <h2 class="card-header">All Student Complaints</h2>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_complaints as $c): 
                                    $badge_class = 'badge-pending';
                                    if($c['status'] == 'resolved') $badge_class = 'badge-success';
                                    if($c['status'] == 'rejected') $badge_class = 'badge-pending'; 
                                ?>
                                <tr style="transition:0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                    <td style="color:var(--text-muted); font-size:0.85rem;">#<?= $c['id'] ?></td>
                                    <td>
                                        <div style="font-weight:600; font-size:0.95rem;"><?= htmlspecialchars($c['student_name']) ?></div>
                                        <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($c['student_email']) ?></div>
                                    </td>
                                    <td style="font-size:0.9rem;"><?= htmlspecialchars($c['subject']) ?></td>
                                    <td><span class="badge" style="background:rgba(255,255,255,0.05); color:var(--text-muted);"><?= ucfirst($c['priority']) ?></span></td>
                                    <td><span class="badge <?= $badge_class ?>"><?= str_replace('_', ' ', strtoupper($c['status'])) ?></span></td>
                                    <td>
                                        <a href="?respond=<?= $c['id'] ?>" class="btn-primary" style="padding:0.4rem 0.8rem;font-size:0.8rem;">Review</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(count($all_complaints) === 0): ?>
                                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">No complaints recorded yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Response Panel (Shows only if a complaint is selected) -->
                <?php if ($selected_complaint): ?>
                <div class="content-card anim-item" style="grid-column: 2 / 3; border: 1px solid var(--primary); box-shadow: 0 0 30px rgba(59,130,246,0.1);">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;border-bottom-color:rgba(59,130,246,0.3);">
                        <span>Review #<?= $selected_complaint['id'] ?></span>
                        <a href="complaints.php" style="color:var(--text-muted);text-decoration:none;font-size:1.5rem;line-height:1;">&times;</a>
                    </div>
                    
                    <div style="margin-bottom:2rem;">
                        <p style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.3rem;">Subject</p>
                        <p style="font-weight:600;margin-bottom:1.2rem;font-size:1.1rem;"><?= htmlspecialchars($selected_complaint['subject']) ?></p>
                        
                        <p style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.3rem;">Student Issue</p>
                        <p style="font-size:0.9rem;line-height:1.6;background:rgba(255,255,255,0.03);padding:1rem;border-radius:12px;color:#e2e8f0;"><?= nl2br(htmlspecialchars($selected_complaint['description'])) ?></p>
                    </div>

                    <form method="POST" action="complaints.php" onsubmit="return confirm('Submit this response?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="complaint_id" value="<?= $selected_complaint['id'] ?>">
                        <div class="form-group">
                            <label>Update Status</label>
                            <select name="status" class="form-control" style="color:var(--text-main);">
                                <option value="pending" <?= $selected_complaint['status']=='pending'?'selected':'' ?>>Pending</option>
                                <option value="in_progress" <?= $selected_complaint['status']=='in_progress'?'selected':'' ?>>In Progress</option>
                                <option value="resolved" <?= $selected_complaint['status']=='resolved'?'selected':'' ?>>Resolved</option>
                                <option value="rejected" <?= $selected_complaint['status']=='rejected'?'selected':'' ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Admin Remarks (Optional)</label>
                            <textarea name="admin_response" class="form-control" rows="4" placeholder="Reply to the student..."><?= htmlspecialchars($selected_complaint['admin_response'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%; margin-top:0.5rem; padding:0.9rem;">Update & Send Response</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>
