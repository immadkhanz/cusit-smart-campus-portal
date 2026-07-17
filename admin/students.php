<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

// Fetch all students with activity counts
$students = $pdo->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM complaints c WHERE c.student_id = u.id) as complaint_count,
        (SELECT COUNT(*) FROM event_registrations er WHERE er.student_id = u.id) as event_count,
        (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.student_id = u.id) as course_count,
        (SELECT g.group_name FROM fyp_groups g JOIN fyp_members m ON g.id = m.group_id WHERE m.student_id = u.id LIMIT 1) as fyp_group
    FROM users u WHERE u.role = 'student' ORDER BY u.created_at DESC
")->fetchAll();

// Detail view
$detail_id = $_GET['view'] ?? null;
$detail = null; $detail_complaints = []; $detail_events = []; $detail_courses = [];
if ($detail_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'"); $stmt->execute([$detail_id]); $detail = $stmt->fetch();
    if ($detail) {
        $stmt = $pdo->prepare("SELECT * FROM complaints WHERE student_id = ? ORDER BY created_at DESC LIMIT 5"); $stmt->execute([$detail_id]); $detail_complaints = $stmt->fetchAll();
        $stmt = $pdo->prepare("SELECT e.title, e.event_date, er.registered_at FROM event_registrations er JOIN events e ON er.event_id = e.id WHERE er.student_id = ? ORDER BY er.registered_at DESC LIMIT 5"); $stmt->execute([$detail_id]); $detail_events = $stmt->fetchAll();
        $stmt = $pdo->prepare("SELECT c.course_code, c.course_name, c.instructor FROM course_enrollments ce JOIN courses c ON ce.course_id = c.id WHERE ce.student_id = ?"); $stmt->execute([$detail_id]); $detail_courses = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Student Directory — Smart Campus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .search-box { width:100%; padding:0.9rem 1.2rem 0.9rem 3rem; background:rgba(10,22,40,0.5); border:1px solid var(--border-color); border-radius:14px; color:var(--text-main); font-family:inherit; font-size:0.95rem; transition:0.3s; outline:none; margin-bottom:1.5rem; }
        .search-box:focus { border-color:var(--primary); background:rgba(6,214,160,0.04); }
        .search-wrap { position:relative; }
        .search-wrap::before { content:'🔍'; position:absolute; left:1rem; top:50%; transform:translateY(-50%); }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header"><h1>👥 Student Directory</h1></header>

            <div class="content-grid">
                <div class="content-card anim-item" style="grid-column:<?= $detail ? '1/2' : '1/-1' ?>;">
                    <h2 class="card-header">All Students <span style="font-size:0.8rem;color:var(--text-muted);font-weight:400;">(<?= count($students) ?> total)</span></h2>
                    <div class="search-wrap"><input type="text" class="search-box" id="studentSearch" placeholder="Search by name or email..." oninput="filterStudents()"></div>
                    <div style="overflow-x:auto;">
                        <table class="data-table" id="studentTable">
                            <thead><tr><th>Student</th><th>Complaints</th><th>Events</th><th>Courses</th><th>FYP</th><th>Joined</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($students as $s): ?>
                                <tr class="student-row" data-name="<?= strtolower($s['name']) ?>" data-email="<?= strtolower($s['email']) ?>" style="transition:0.3s;<?= ($detail_id==$s['id'])?'background:rgba(6,214,160,0.05);':'' ?>" onmouseover="this.style.background='rgba(6,214,160,0.04)'" onmouseout="this.style.background='<?= ($detail_id==$s['id'])?'rgba(6,214,160,0.05)':'transparent' ?>'">
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:grid;place-items:center;font-weight:700;font-size:0.8rem;color:#0a1628;flex-shrink:0;"><?= strtoupper(substr($s['name'],0,1)) ?></div>
                                            <div><div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($s['name']) ?></div><div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($s['email']) ?></div></div>
                                        </div>
                                    </td>
                                    <td style="text-align:center;"><?= $s['complaint_count'] ?></td>
                                    <td style="text-align:center;"><?= $s['event_count'] ?></td>
                                    <td style="text-align:center;"><?= $s['course_count'] ?></td>
                                    <td style="font-size:0.8rem;color:var(--text-muted);"><?= $s['fyp_group'] ? htmlspecialchars($s['fyp_group']) : '—' ?></td>
                                    <td style="font-size:0.8rem;color:var(--text-muted);"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                                    <td><a href="?view=<?= $s['id'] ?>" class="btn-primary" style="padding:0.35rem 0.7rem;font-size:0.78rem;">View</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($detail): ?>
                <div class="content-card anim-item" style="grid-column:2/3; border:1px solid var(--primary); box-shadow:0 0 30px rgba(6,214,160,0.1);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                        <h2 class="card-header" style="margin:0;"><?= htmlspecialchars($detail['name']) ?></h2>
                        <a href="students.php" style="color:var(--text-muted);text-decoration:none;font-size:1.5rem;">&times;</a>
                    </div>
                    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1.5rem;">📧 <?= htmlspecialchars($detail['email']) ?> • Joined <?= date('M d, Y', strtotime($detail['created_at'])) ?></p>

                    <?php if (count($detail_courses) > 0): ?>
                    <h4 style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.6rem;">📚 Enrolled Courses</h4>
                    <?php foreach ($detail_courses as $dc): ?>
                        <div style="font-size:0.85rem;padding:0.4rem 0;color:var(--text-main);">• <strong style="color:var(--accent);"><?= $dc['course_code'] ?></strong> <?= htmlspecialchars($dc['course_name']) ?> <span style="color:var(--text-muted);font-size:0.75rem;">— <?= htmlspecialchars($dc['instructor']) ?></span></div>
                    <?php endforeach; ?>
                    <div style="margin:1rem 0;border-top:1px solid var(--border-color);"></div>
                    <?php endif; ?>

                    <?php if (count($detail_events) > 0): ?>
                    <h4 style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.6rem;">📅 Event Registrations</h4>
                    <?php foreach ($detail_events as $de): ?>
                        <div style="font-size:0.85rem;padding:0.4rem 0;color:var(--text-main);">• <?= htmlspecialchars($de['title']) ?> <span style="color:var(--text-muted);font-size:0.75rem;">— <?= date('M d', strtotime($de['event_date'])) ?></span></div>
                    <?php endforeach; ?>
                    <div style="margin:1rem 0;border-top:1px solid var(--border-color);"></div>
                    <?php endif; ?>

                    <?php if (count($detail_complaints) > 0): ?>
                    <h4 style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.6rem;">📩 Recent Complaints</h4>
                    <?php foreach ($detail_complaints as $dc): ?>
                        <div style="font-size:0.85rem;padding:0.4rem 0;">• <?= htmlspecialchars($dc['subject']) ?> <span class="badge" style="font-size:0.7rem;padding:0.15rem 0.5rem;background:rgba(6,214,160,0.08);color:var(--primary);"><?= strtoupper($dc['status']) ?></span></div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>
    function filterStudents() {
        const q = document.getElementById('studentSearch').value.toLowerCase();
        document.querySelectorAll('.student-row').forEach(row => {
            const match = row.dataset.name.includes(q) || row.dataset.email.includes(q);
            row.style.display = match ? '' : 'none';
        });
    }
    </script>
</body>
</html>
