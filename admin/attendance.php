<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

$msg = ''; $error = '';
$admin_id = $_SESSION['user_id'];

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark') {
    verifyCSRF();
    $course_id = (int)$_POST['course_id'];
    $date = $_POST['att_date'];
    $statuses = $_POST['status'] ?? [];
    $count = 0;
    foreach ($statuses as $student_id => $status) {
        $stmt = $pdo->prepare("INSERT INTO attendance (course_id, student_id, date, status, marked_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)");
        $stmt->execute([$course_id, (int)$student_id, $date, $status, $admin_id]);
        $count++;
    }
    $msg = "Attendance marked for $count students.";
}

// Fetch courses that have enrolled students
$courses = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id) as enrolled_count
    FROM courses c HAVING enrolled_count > 0 ORDER BY c.course_code
")->fetchAll();

// Selected course
$sel_course_id = $_GET['course'] ?? ($_POST['course_id'] ?? null);
$sel_date = $_GET['date'] ?? ($_POST['att_date'] ?? date('Y-m-d'));
$enrolled_students = [];
$existing_attendance = [];
$sel_course = null;

if ($sel_course_id) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?"); $stmt->execute([$sel_course_id]); $sel_course = $stmt->fetch();
    if ($sel_course) {
        $stmt = $pdo->prepare("SELECT u.id, u.name, u.email FROM course_enrollments ce JOIN users u ON ce.student_id = u.id WHERE ce.course_id = ? ORDER BY u.name");
        $stmt->execute([$sel_course_id]);
        $enrolled_students = $stmt->fetchAll();
        // Fetch existing attendance for this date
        $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE course_id = ? AND date = ?");
        $stmt->execute([$sel_course_id, $sel_date]);
        foreach ($stmt->fetchAll() as $a) { $existing_attendance[$a['student_id']] = $a['status']; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Mark Attendance — Smart Campus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .form-control { padding: 0.7rem 1rem; background: rgba(10,22,40,0.5); border: 1px solid var(--border-color); border-radius: 12px; color: var(--text-main); font-family: inherit; font-size: 0.95rem; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        .att-row { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(6,214,160,0.02); border: 1px solid var(--border-color); border-radius: 14px; margin-bottom: 0.6rem; transition: 0.3s; }
        .att-row:hover { background: rgba(6,214,160,0.05); }
        .radio-group { display: flex; gap: 0.5rem; }
        .radio-group label { display: flex; align-items: center; gap: 4px; padding: 0.4rem 0.8rem; border-radius: 8px; cursor: pointer; font-size: 0.82rem; font-weight: 600; transition: 0.2s; border: 1px solid transparent; }
        .radio-group input { display: none; }
        .radio-group input:checked + span { opacity: 1; }
        .radio-group span { opacity: 0.5; transition: 0.2s; }
        .r-present { background: rgba(6,214,160,0.08); color: #06d6a0; border-color: rgba(6,214,160,0.15); }
        .r-absent { background: rgba(251,113,133,0.08); color: #fb7185; border-color: rgba(251,113,133,0.15); }

        .radio-group input:checked ~ .r-indicator { opacity: 1; transform: scale(1.05); }
        .quick-btn { padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 10px; background: rgba(10,22,40,0.3); color: var(--text-muted); cursor: pointer; font-family: inherit; font-size: 0.82rem; transition: 0.2s; }
        .quick-btn:hover { border-color: var(--primary); color: var(--primary); background: rgba(6,214,160,0.05); }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header"><h1>📊 Mark Attendance</h1></header>
            <?php if ($msg): ?><div style="background:rgba(16,185,129,0.1); color:#6ee7b7; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(16,185,129,0.2);"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($error): ?><div style="background:rgba(239,68,68,0.1); color:#fca5a5; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(239,68,68,0.2);"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Course & Date Selector -->
            <div class="content-card anim-item" style="margin-bottom:1.5rem;">
                <h2 class="card-header">Select Course & Date</h2>
                <form method="GET" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
                    <div style="flex:2; min-width:200px;">
                        <label style="display:block;font-size:0.8rem;color:var(--text-muted);margin-bottom:0.4rem;text-transform:uppercase;">Course</label>
                        <select name="course" class="form-control" style="width:100%;color:var(--text-main);" required>
                            <option value="">— Select Course —</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($sel_course_id==$c['id'])?'selected':'' ?>><?= $c['course_code'] ?> — <?= htmlspecialchars($c['course_name']) ?> (<?= $c['enrolled_count'] ?> students)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex:1; min-width:150px;">
                        <label style="display:block;font-size:0.8rem;color:var(--text-muted);margin-bottom:0.4rem;text-transform:uppercase;">Date</label>
                        <input type="date" name="date" class="form-control" value="<?= $sel_date ?>" style="width:100%;color-scheme:dark;">
                    </div>
                    <button type="submit" class="btn-primary" style="padding:0.75rem 1.5rem;">Load Students</button>
                </form>
            </div>

            <!-- Attendance Sheet -->
            <?php if ($sel_course && count($enrolled_students) > 0): ?>
            <div class="content-card anim-item">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
                    <h2 class="card-header" style="margin:0;"><?= $sel_course['course_code'] ?> — <?= date('M d, Y', strtotime($sel_date)) ?> <span style="font-size:0.8rem;color:var(--text-muted);font-weight:400;">(<?= count($enrolled_students) ?> students)</span></h2>
                    <div style="display:flex;gap:0.5rem;">
                        <button type="button" class="quick-btn" onclick="markAll('present')">✓ All Present</button>
                        <button type="button" class="quick-btn" onclick="markAll('absent')">✗ All Absent</button>
                    </div>
                </div>

                <form method="POST" id="attendanceForm" onsubmit="return confirm('Save attendance for all students?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="mark">
                    <input type="hidden" name="course_id" value="<?= $sel_course_id ?>">
                    <input type="hidden" name="att_date" value="<?= $sel_date ?>">

                    <?php foreach ($enrolled_students as $i => $s):
                        $current = $existing_attendance[$s['id']] ?? 'present';
                    ?>
                    <div class="att-row">
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:grid;place-items:center;font-weight:700;font-size:0.8rem;color:#0a1628;flex-shrink:0;"><?= strtoupper(substr($s['name'],0,1)) ?></div>
                        <div style="flex:1;">
                            <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($s['name']) ?></div>
                            <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($s['email']) ?></div>
                        </div>
                        <div class="radio-group">
                            <label class="r-present"><input type="radio" name="status[<?= $s['id'] ?>]" value="present" <?= $current==='present'?'checked':'' ?>><span>✓ Present</span></label>
                            <label class="r-absent"><input type="radio" name="status[<?= $s['id'] ?>]" value="absent" <?= $current==='absent'?'checked':'' ?>><span>✗ Absent</span></label>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn-primary" style="width:100%;padding:1rem;margin-top:1rem;font-size:1rem;">💾 Save Attendance</button>
                </form>
            </div>
            <?php elseif ($sel_course_id && count($enrolled_students) === 0): ?>
                <div class="content-card" style="text-align:center;padding:3rem;">
                    <div style="font-size:3rem;margin-bottom:1rem;">📭</div>
                    <h3 style="color:var(--text-main);">No Students Enrolled</h3>
                    <p style="color:var(--text-muted);">This course has no enrolled students yet.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script>
    function markAll(status) {
        document.querySelectorAll(`input[value="${status}"]`).forEach(r => r.checked = true);
    }
    // Visual feedback for radio selections
    document.querySelectorAll('.radio-group input').forEach(radio => {
        radio.addEventListener('change', function() {
            this.closest('.radio-group').querySelectorAll('span').forEach(s => s.style.opacity = '0.5');
            this.nextElementSibling.style.opacity = '1';
        });
        if (radio.checked) radio.nextElementSibling.style.opacity = '1';
    });
    </script>
</body>
</html>
