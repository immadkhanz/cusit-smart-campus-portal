<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireStudent();

$student_id = $_SESSION['user_id'];

// Fetch enrolled courses with attendance stats
$courses = $pdo->prepare("
    SELECT c.*,
        (SELECT COUNT(*) FROM attendance a WHERE a.course_id = c.id AND a.student_id = ? AND a.status = 'present') as present_count,
        (SELECT COUNT(*) FROM attendance a WHERE a.course_id = c.id AND a.student_id = ? AND a.status = 'absent') as absent_count,
        (SELECT COUNT(*) FROM attendance a WHERE a.course_id = c.id AND a.student_id = ?) as total_classes
    FROM courses c
    JOIN course_enrollments ce ON c.id = ce.course_id
    WHERE ce.student_id = ?
    ORDER BY c.day, c.time_start
");
$courses->execute([$student_id, $student_id, $student_id, $student_id]);
$my_courses = $courses->fetchAll();

// If viewing detail for a specific course
$detail_id = $_GET['course'] ?? null;
$detail_records = [];
$detail_course = null;
if ($detail_id) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?"); $stmt->execute([$detail_id]); $detail_course = $stmt->fetch();
    if ($detail_course) {
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE course_id = ? AND student_id = ? ORDER BY date DESC");
        $stmt->execute([$detail_id, $student_id]);
        $detail_records = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>My Attendance — Smart Campus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .att-card { background: rgba(6,214,160,0.03); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; transition: all 0.3s; }
        .att-card:hover { background: rgba(6,214,160,0.06); transform: translateY(-2px); }
        .att-bar { height: 8px; border-radius: 4px; background: rgba(255,255,255,0.05); overflow: hidden; margin-top: 0.5rem; }
        .att-fill { height: 100%; border-radius: 4px; transition: width 0.6s ease; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .dot-present { background: #06d6a0; box-shadow: 0 0 6px rgba(6,214,160,0.4); }
        .dot-absent { background: #fb7185; box-shadow: 0 0 6px rgba(251,113,133,0.4); }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header"><h1>📊 My Attendance</h1></header>

            <?php if (count($my_courses) === 0): ?>
                <div class="content-card" style="text-align:center; padding:3rem;">
                    <div style="font-size:3rem; margin-bottom:1rem;">📚</div>
                    <h3 style="color:var(--text-main); margin-bottom:0.5rem;">No Courses Enrolled</h3>
                    <p style="color:var(--text-muted);">Enroll in courses first to track your attendance.</p>
                    <a href="courses.php" class="btn-primary" style="display:inline-block; margin-top:1rem; padding:0.7rem 1.5rem;">Browse Courses</a>
                </div>
            <?php else: ?>

            <!-- Overview Cards -->
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1.2rem; margin-bottom:2rem;">
                <?php foreach ($my_courses as $c):
                    $percent = $c['total_classes'] > 0 ? round(($c['present_count'] / $c['total_classes']) * 100) : 0;
                    $bar_color = $percent >= 75 ? '#06d6a0' : ($percent >= 50 ? '#fbbf24' : '#fb7185');
                ?>
                <a href="?course=<?= $c['id'] ?>" class="att-card anim-item" style="text-decoration:none; color:inherit; <?= ($detail_id == $c['id']) ? 'border-color:var(--primary); box-shadow:0 0 20px rgba(6,214,160,0.1);' : '' ?>">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem;">
                        <span style="font-size:0.75rem; font-weight:700; color:var(--accent); background:rgba(251,191,36,0.1); padding:0.2rem 0.6rem; border-radius:20px;"><?= $c['course_code'] ?></span>
                        <span style="font-size:1.3rem; font-weight:800; color:<?= $bar_color ?>;"><?= $percent ?>%</span>
                    </div>
                    <h3 style="font-size:1rem; color:var(--text-main); margin-bottom:0.5rem;"><?= htmlspecialchars($c['course_name']) ?></h3>
                    <div style="display:flex; gap:1rem; font-size:0.78rem; color:var(--text-muted); margin-bottom:0.5rem;">
                        <span style="color:#06d6a0;">✓ <?= $c['present_count'] ?> Present</span>
                        <span style="color:#fb7185;">✗ <?= $c['absent_count'] ?> Absent</span>
                    </div>
                    <div class="att-bar"><div class="att-fill" style="width:<?= $percent ?>%; background:<?= $bar_color ?>;"></div></div>
                    <p style="font-size:0.72rem; color:var(--text-muted); margin-top:0.4rem;"><?= $c['total_classes'] ?> total classes • Click for details</p>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Detail View -->
            <?php if ($detail_course): ?>
            <div class="content-card anim-item">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h2 class="card-header" style="margin:0;">📋 <?= htmlspecialchars($detail_course['course_name']) ?> — Attendance Log</h2>
                    <a href="attendance.php" style="color:var(--text-muted); text-decoration:none; font-size:1.5rem;">&times;</a>
                </div>
                <?php if (count($detail_records) > 0): ?>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead><tr><th>#</th><th>Date</th><th>Day</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($detail_records as $i => $r): ?>
                            <tr style="transition:0.3s;" onmouseover="this.style.background='rgba(6,214,160,0.04)'" onmouseout="this.style.background='transparent'">
                                <td style="color:var(--text-muted); font-size:0.85rem;"><?= count($detail_records) - $i ?></td>
                                <td style="font-weight:500;"><?= date('M d, Y', strtotime($r['date'])) ?></td>
                                <td style="color:var(--text-muted);"><?= date('l', strtotime($r['date'])) ?></td>
                                <td>
                                    <span style="display:inline-flex; align-items:center; gap:6px;">
                                        <span class="status-dot dot-<?= $r['status'] ?>"></span>
                                        <span style="font-weight:600; color:<?= $r['status']==='present' ? '#06d6a0' : '#fb7185' ?>;"><?= ucfirst($r['status']) ?></span>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p style="color:var(--text-muted); text-align:center; padding:2rem;">No attendance records yet for this course.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </main>
    </div>
</body>
</html>
