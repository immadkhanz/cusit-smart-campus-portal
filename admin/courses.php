<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $code = strtoupper(trim($_POST['course_code']));
        $name = trim($_POST['course_name']);
        $credits = (int)$_POST['credits'];
        $instructor = trim($_POST['instructor']);
        $day = $_POST['day'];
        $time_start = $_POST['time_start'];
        $time_end = $_POST['time_end'];
        $room = trim($_POST['room']);
        $max = (int)$_POST['max_students'];
        if (empty($code)||empty($name)||empty($instructor)||empty($room)||$credits<1||$max<1) { $error = "All fields are required."; }
        else {
            try {
                $pdo->prepare("INSERT INTO courses (course_code, course_name, credits, instructor, day, time_start, time_end, room, max_students) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$code,$name,$credits,$instructor,$day,$time_start,$time_end,$room,$max]);
                $msg = "Course $code created successfully!";
            } catch (PDOException $e) {
                $error = str_contains($e->getMessage(), 'Duplicate') ? "Course code $code already exists." : "Error creating course.";
            }
        }
    }
    if ($action === 'delete') {
        $id = (int)$_POST['course_id'];
        $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$id]);
        $msg = "Course deleted.";
    }
}

$courses = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id) as enrolled_count
    FROM courses c ORDER BY c.course_code
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Course Management — Smart Campus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .form-control { width: 100%; padding: 0.8rem 1rem; background: rgba(10,22,40,0.5); border: 1px solid var(--border-color); border-radius: 12px; color: var(--text-main); font-family: inherit; font-size: 0.95rem; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--primary); background: rgba(6,214,160,0.04); }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header"><h1>📚 Course Management</h1></header>
            <?php if ($msg): ?><div style="background:rgba(16,185,129,0.1); color:#6ee7b7; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(16,185,129,0.2);"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($error): ?><div style="background:rgba(239,68,68,0.1); color:#fca5a5; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(239,68,68,0.2);"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="content-grid">
                <div class="content-card anim-item" style="grid-column:1/2;">
                    <h2 class="card-header">Add New Course</h2>
                    <form method="POST" onsubmit="return confirm('Create this course?')">
                        <input type="hidden" name="action" value="create">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group"><label>Course Code</label><input type="text" name="course_code" class="form-control" placeholder="e.g. CS-301" required></div>
                            <div class="form-group"><label>Credits</label><input type="number" name="credits" class="form-control" min="1" max="6" value="3" required></div>
                        </div>
                        <div class="form-group"><label>Course Name</label><input type="text" name="course_name" class="form-control" placeholder="e.g. Database Systems" required></div>
                        <div class="form-group"><label>Instructor</label><input type="text" name="instructor" class="form-control" placeholder="e.g. Dr. Farhan Ahmed" required></div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
                            <div class="form-group"><label>Day</label>
                                <select name="day" class="form-control" required style="color:var(--text-main);">
                                    <option value="Monday">Monday</option><option value="Tuesday">Tuesday</option><option value="Wednesday">Wednesday</option><option value="Thursday">Thursday</option><option value="Friday">Friday</option>
                                </select>
                            </div>
                            <div class="form-group"><label>Start Time</label><input type="time" name="time_start" class="form-control" required style="color-scheme:dark;"></div>
                            <div class="form-group"><label>End Time</label><input type="time" name="time_end" class="form-control" required style="color-scheme:dark;"></div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group"><label>Room</label><input type="text" name="room" class="form-control" placeholder="e.g. Room 201" required></div>
                            <div class="form-group"><label>Max Students</label><input type="number" name="max_students" class="form-control" min="1" value="40" required></div>
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%;padding:0.9rem;">Create Course</button>
                    </form>
                </div>

                <div class="content-card anim-item" style="grid-column:1/-1; margin-top:1.5rem;">
                    <h2 class="card-header">All Courses <span style="font-size:0.8rem;color:var(--text-muted);font-weight:400;">(<?= count($courses) ?>)</span></h2>
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead><tr><th>Code</th><th>Course</th><th>Instructor</th><th>Schedule</th><th>Room</th><th>Enrolled</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($courses as $c): ?>
                                <tr style="transition:0.3s;" onmouseover="this.style.background='rgba(6,214,160,0.04)'" onmouseout="this.style.background='transparent'">
                                    <td><span style="font-weight:700;color:var(--accent);background:rgba(251,191,36,0.1);padding:0.2rem 0.6rem;border-radius:20px;font-size:0.78rem;"><?= $c['course_code'] ?></span></td>
                                    <td style="font-weight:600;color:var(--text-main);"><?= htmlspecialchars($c['course_name']) ?> <span style="color:var(--text-muted);font-size:0.75rem;">(<?= $c['credits'] ?> cr)</span></td>
                                    <td style="font-size:0.85rem;color:var(--text-muted);"><?= htmlspecialchars($c['instructor']) ?></td>
                                    <td style="font-size:0.85rem;"><span style="color:var(--info);"><?= $c['day'] ?></span> <?= date('h:i A', strtotime($c['time_start'])) ?>–<?= date('h:i A', strtotime($c['time_end'])) ?></td>
                                    <td style="font-size:0.85rem;"><?= $c['room'] ?></td>
                                    <td style="text-align:center;font-weight:600;color:<?= $c['enrolled_count']>=$c['max_students']?'var(--danger)':'var(--primary)' ?>;"><?= $c['enrolled_count'] ?>/<?= $c['max_students'] ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Delete this course? All enrollments and attendance will be lost.')" style="margin:0;">
                                            <input type="hidden" name="action" value="delete"><input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                            <button type="submit" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:1.2rem;opacity:0.6;transition:0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'" title="Delete">&times;</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
