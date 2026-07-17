<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireStudent();

$student_id = $_SESSION['user_id'];
$error = ''; $success = '';

// Check if student has an active complaint
$stmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ? AND status IN ('pending', 'in_progress')");
$stmt->execute([$student_id]);
$has_active = $stmt->fetchColumn() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_active) {
    verifyCSRF();
    $subject     = trim($_POST['subject'] ?? '');
    $category    = $_POST['category'] ?? '';
    $priority    = $_POST['priority'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    if (empty($subject) || empty($category) || empty($priority) || empty($description)) {
        $error = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO complaints (student_id, category, priority, subject, description) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$student_id, $category, $priority, $subject, $description])) {
            $success = "Complaint submitted successfully.";
            $has_active = true; // Update state
        } else {
            $error = "Error submitting complaint.";
        }
    }
}

// Fetch history
$stmt = $pdo->prepare("SELECT * FROM complaints WHERE student_id = ? ORDER BY created_at DESC");
$stmt->execute([$student_id]);
$my_complaints = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints — Smart Campus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .form-group { margin-bottom: 1rem; }
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
                <h1>Complaint Management</h1>
            </header>

            <div class="content-grid">
                <!-- Submit Form -->
                <div class="content-card anim-item" style="grid-column: 1 / 2;">
                    <h2 class="card-header">Lodge a Complaint</h2>
                    
                    <?php if ($error): ?><div style="background:rgba(239,68,68,0.1); color:#fca5a5; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(239,68,68,0.2);"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <?php if ($success): ?><div style="background:rgba(16,185,129,0.1); color:#6ee7b7; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(16,185,129,0.2);"><?= htmlspecialchars($success) ?></div><?php endif; ?>

                    <?php if ($has_active): ?>
                        <div style="background:rgba(245,158,11,0.05); border:1px solid rgba(245,158,11,0.2); padding:2rem; border-radius:12px; text-align:center;">
                            <div style="font-size:3rem; margin-bottom:1rem;">⏳</div>
                            <h3 style="color:#fcd34d; margin-bottom:0.8rem; font-size:1.2rem;">One Active Complaint Limit</h3>
                            <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.6; max-width:400px; margin:0 auto;">You already have an active complaint in progress. Please wait for the administration to resolve it before submitting a new one. This ensures fair processing times for all students.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" onsubmit="return confirm('Submit this complaint?')">
                            <?= csrfField() ?>
                            <div class="form-group">
                                <label>Subject / Issue Title</label>
                                <input type="text" name="subject" class="form-control" placeholder="e.g. Wi-Fi down in Lab 3" required>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="category" class="form-control" required style="color:var(--text-muted)">
                                        <option value="IT Support">IT Support</option>
                                        <option value="Hostel">Hostel / Accommodation</option>
                                        <option value="Academics">Academics</option>
                                        <option value="Administration">Administration</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Priority</label>
                                    <select name="priority" class="form-control" required style="color:var(--text-muted)">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Detailed Description</label>
                                <textarea name="description" class="form-control" rows="5" placeholder="Provide as many details as possible..." required></textarea>
                            </div>
                            <button type="submit" class="btn-primary" style="width:100%; margin-top:0.5rem; padding:0.9rem;">Submit Complaint</button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- History -->
                <div class="content-card anim-item" style="grid-column: 1 / -1; margin-top: 1.5rem;">
                    <h2 class="card-header">Complaint History</h2>
                    <?php if (count($my_complaints) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Subject</th>
                                        <th>Category</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Admin Response</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_complaints as $c): 
                                        $badge_class = 'badge-pending';
                                        if($c['status'] == 'resolved') $badge_class = 'badge-success';
                                        if($c['status'] == 'rejected') $badge_class = 'badge-pending';
                                    ?>
                                    <tr style="transition:0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                        <td style="font-size:0.85rem;color:var(--text-muted);"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                        <td><strong><?= htmlspecialchars($c['subject']) ?></strong></td>
                                        <td style="font-size:0.85rem;color:var(--text-muted);"><?= htmlspecialchars($c['category']) ?></td>
                                        <td><span class="badge" style="background:rgba(255,255,255,0.05); color:var(--text-muted);"><?= ucfirst($c['priority']) ?></span></td>
                                        <td><span class="badge <?= $badge_class ?>"><?= str_replace('_', ' ', strtoupper($c['status'])) ?></span></td>
                                        <td style="font-size:0.85rem;color:var(--text-muted); line-height:1.5;">
                                            <?= $c['admin_response'] ? htmlspecialchars($c['admin_response']) : '<i>Awaiting response...</i>' ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding:2rem; text-align:center; color:var(--text-muted);">
                            <p>You have not submitted any complaints yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
