<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireStudent();

$student_id = $_SESSION['user_id'];

// Student Stats
$active_complaints = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ? AND status != 'resolved'");
$active_complaints->execute([$student_id]);
$active_complaints = $active_complaints->fetchColumn();

$registered_events = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE student_id = ?");
$registered_events->execute([$student_id]);
$registered_events = $registered_events->fetchColumn();

$total_announcements = $pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();

// FYP Status
$stmt = $pdo->prepare("SELECT g.status FROM fyp_groups g JOIN fyp_members m ON g.id = m.group_id WHERE m.student_id = ?");
$stmt->execute([$student_id]);
$fyp_status_row = $stmt->fetch();
$fyp_status_text = $fyp_status_row ? ucfirst($fyp_status_row['status']) : 'Not Enrolled';

// Course & Attendance Stats
$enrolled_courses = $pdo->prepare("SELECT COUNT(*) FROM course_enrollments WHERE student_id = ?"); $enrolled_courses->execute([$student_id]); $enrolled_courses = $enrolled_courses->fetchColumn();
$att_total = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ?"); $att_total->execute([$student_id]); $att_total = $att_total->fetchColumn();
$att_present = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ? AND status = 'present'"); $att_present->execute([$student_id]); $att_present = $att_present->fetchColumn();
$att_percent = $att_total > 0 ? round(($att_present / $att_total) * 100) : 0;

// Fetch announcements
$recent_announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard — Smart Campus Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="page-header">
                <h1>Welcome back, <?= explode(' ', htmlspecialchars($_SESSION['user_name']))[0] ?> 👋</h1>
                <div class="user-profile">
                    <span style="font-size:0.8rem;color:var(--text-muted)">Student</span>
                    <strong style="font-size:0.9rem"><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                </div>
            </header>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Enrolled Courses</div>
                    <div class="stat-value" style="color: var(--primary);"><?= $enrolled_courses ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Attendance</div>
                    <div class="stat-value" style="color: <?= $att_percent >= 75 ? 'var(--primary)' : ($att_percent >= 50 ? 'var(--accent)' : 'var(--danger)') ?>;"><?= $att_percent ?>%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Active Complaints</div>
                    <div class="stat-value" style="color: var(--accent);"><?= $active_complaints ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Registered Events</div>
                    <div class="stat-value" style="color: var(--info);"><?= $registered_events ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Announcements</div>
                    <div class="stat-value" style="color: var(--info);"><?= $total_announcements ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">FYP Status</div>
                    <div style="font-size:1.5rem;font-weight:700;color:var(--primary);margin-top:10px;"><?= $fyp_status_text ?></div>
                </div>
            </div>
            
            <div class="content-grid">
                <div class="content-card" style="grid-column: 1 / -1;">
                    <h2 class="card-header">Campus Announcements</h2>
                    <?php if (count($recent_announcements) > 0): ?>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:1.5rem;">
                            <?php foreach ($recent_announcements as $ann): ?>
                                <div style="padding:1.5rem; background:rgba(6,214,160,0.03); border:1px solid rgba(6,214,160,0.08); border-radius:12px; transition:0.3s;" onmouseover="this.style.background='rgba(6,214,160,0.06)'" onmouseout="this.style.background='rgba(6,214,160,0.03)'">
                                    <h3 style="font-size:1.1rem; margin-bottom:0.5rem; color:var(--primary);"><?= htmlspecialchars($ann['title']) ?></h3>
                                    <p style="font-size:0.85rem; color:var(--text-muted); line-height:1.6; margin-bottom:1rem;"><?= htmlspecialchars($ann['content']) ?></p>
                                    <span style="font-size:0.75rem; color:var(--text-muted); background:rgba(6,214,160,0.06); padding:0.2rem 0.6rem; border-radius:10px;"><?= date('M d, Y', strtotime($ann['created_at'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="font-size:0.9rem;color:var(--text-muted);">No announcements yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
