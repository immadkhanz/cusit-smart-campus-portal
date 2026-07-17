<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$pending_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'pending'")->fetchColumn();
$upcoming_events = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'upcoming'")->fetchColumn();
$pending_fyp = $pdo->query("SELECT COUNT(*) FROM fyp_groups WHERE status = 'pending'")->fetchColumn();
$total_courses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$total_enrollments = $pdo->query("SELECT COUNT(*) FROM course_enrollments")->fetchColumn();

$recent_announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Smart Campus Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="page-header">
                <h1>Overview</h1>
                <div class="user-profile">
                    <span style="font-size:0.8rem;color:var(--text-muted)">Admin</span>
                    <strong style="font-size:0.9rem"><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                </div>
            </header>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Total Students</div>
                    <div class="stat-value" style="color: var(--primary);"><?= $total_students ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Pending Complaints</div>
                    <div class="stat-value" style="color: var(--accent);"><?= $pending_complaints ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Upcoming Events</div>
                    <div class="stat-value" style="color: var(--success);"><?= $upcoming_events ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">FYP Approvals</div>
                    <div class="stat-value" style="color: var(--info);"><?= $pending_fyp ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Active Courses</div>
                    <div class="stat-value" style="color: var(--primary);"><?= $total_courses ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Course Enrollments</div>
                    <div class="stat-value" style="color: var(--accent);"><?= $total_enrollments ?></div>
                </div>
            </div>
            
            <div class="content-grid">
                <div class="content-card">
                    <h2 class="card-header">Quick Actions</h2>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem;">
                        <a href="students.php" style="display:block; padding:1rem; background:rgba(6,214,160,0.06); border:1px solid rgba(6,214,160,0.12); border-radius:12px; text-decoration:none; color:var(--primary); font-size:0.88rem; font-weight:600; transition:0.3s;" onmouseover="this.style.background='rgba(6,214,160,0.12)'" onmouseout="this.style.background='rgba(6,214,160,0.06)'">👥 Student Directory</a>
                        <a href="courses.php" style="display:block; padding:1rem; background:rgba(56,189,248,0.06); border:1px solid rgba(56,189,248,0.12); border-radius:12px; text-decoration:none; color:var(--info); font-size:0.88rem; font-weight:600; transition:0.3s;" onmouseover="this.style.background='rgba(56,189,248,0.12)'" onmouseout="this.style.background='rgba(56,189,248,0.06)'">📚 Manage Courses</a>
                        <a href="attendance.php" style="display:block; padding:1rem; background:rgba(139,92,246,0.06); border:1px solid rgba(139,92,246,0.12); border-radius:12px; text-decoration:none; color:#a78bfa; font-size:0.88rem; font-weight:600; transition:0.3s;" onmouseover="this.style.background='rgba(139,92,246,0.12)'" onmouseout="this.style.background='rgba(139,92,246,0.06)'">📋 Mark Attendance</a>
                        <a href="complaints.php" style="display:block; padding:1rem; background:rgba(251,191,36,0.06); border:1px solid rgba(251,191,36,0.12); border-radius:12px; text-decoration:none; color:var(--accent); font-size:0.88rem; font-weight:600; transition:0.3s;" onmouseover="this.style.background='rgba(251,191,36,0.12)'" onmouseout="this.style.background='rgba(251,191,36,0.06)'">📩 Review Complaints</a>
                        <a href="events.php" style="display:block; padding:1rem; background:rgba(6,214,160,0.06); border:1px solid rgba(6,214,160,0.12); border-radius:12px; text-decoration:none; color:var(--primary); font-size:0.88rem; font-weight:600; transition:0.3s;" onmouseover="this.style.background='rgba(6,214,160,0.12)'" onmouseout="this.style.background='rgba(6,214,160,0.06)'">📅 Manage Events</a>
                        <a href="fyp.php" style="display:block; padding:1rem; background:rgba(56,189,248,0.06); border:1px solid rgba(56,189,248,0.12); border-radius:12px; text-decoration:none; color:var(--info); font-size:0.88rem; font-weight:600; transition:0.3s;" onmouseover="this.style.background='rgba(56,189,248,0.12)'" onmouseout="this.style.background='rgba(56,189,248,0.06)'">🎓 FYP Approvals</a>
                    </div>
                </div>
                
                <div class="content-card">
                    <h2 class="card-header">Recent Announcements</h2>
                    <?php if (count($recent_announcements) > 0): ?>
                        <div style="display:flex;flex-direction:column;gap:1rem;">
                            <?php foreach ($recent_announcements as $ann): ?>
                                <div style="padding-bottom:1rem; border-bottom:1px solid var(--border-color);">
                                    <h3 style="font-size:0.95rem; margin-bottom:0.3rem; color:var(--primary);"><?= htmlspecialchars($ann['title']) ?></h3>
                                    <p style="font-size:0.82rem; color:var(--text-muted); line-height:1.5; margin:0.3rem 0;"><?= htmlspecialchars(substr($ann['content'], 0, 100)) ?>...</p>
                                    <span style="font-size:0.75rem; color:var(--text-muted);"><?= date('M d, Y', strtotime($ann['created_at'])) ?></span>
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
