<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireStudent();

$student_id = $_SESSION['user_id'];
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enroll_course_id'])) {
        $course_id = (int)$_POST['enroll_course_id'];
        $c = $pdo->prepare("SELECT * FROM courses WHERE id = ?"); $c->execute([$course_id]); $course = $c->fetch();
        if (!$course) { $error = "Course not found."; }
        else {
            $enrolled = $pdo->prepare("SELECT COUNT(*) FROM course_enrollments WHERE course_id = ?"); $enrolled->execute([$course_id]);
            if ($enrolled->fetchColumn() >= $course['max_students']) { $error = "This course is full."; }
            else {
                try {
                    $pdo->prepare("INSERT INTO course_enrollments (course_id, student_id) VALUES (?, ?)")->execute([$course_id, $student_id]);
                    $msg = "Enrolled in " . $course['course_name'] . " successfully!";
                } catch (PDOException $e) { $error = "You are already enrolled in this course."; }
            }
        }
    }
    if (isset($_POST['drop_course_id'])) {
        $course_id = (int)$_POST['drop_course_id'];
        $pdo->prepare("DELETE FROM course_enrollments WHERE course_id = ? AND student_id = ?")->execute([$course_id, $student_id]);
        $msg = "Course dropped successfully.";
    }
}

// Fetch available courses with enrollment count and student enrollment status
$courses = $pdo->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id) as enrolled_count,
           (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id AND ce.student_id = ?) as is_enrolled
    FROM courses c ORDER BY c.day, c.time_start
");
$courses->execute([$student_id]);
$all_courses = $courses->fetchAll();

// My enrolled courses
$my_courses = $pdo->prepare("
    SELECT c.*, ce.enrolled_at FROM courses c 
    JOIN course_enrollments ce ON c.id = ce.course_id 
    WHERE ce.student_id = ? ORDER BY c.day, c.time_start
");
$my_courses->execute([$student_id]);
$enrolled_list = $my_courses->fetchAll();

$day_order = ['Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Course Enrollment — Smart Campus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .course-card { background: rgba(6,214,160,0.03); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; transition: all 0.3s; display: flex; flex-direction: column; }
        .course-card:hover { background: rgba(6,214,160,0.06); transform: translateY(-2px); box-shadow: 0 8px 30px rgba(6,214,160,0.08); }
        .course-code { font-size: 0.75rem; font-weight: 700; color: var(--accent); background: rgba(251,191,36,0.1); padding: 0.25rem 0.7rem; border-radius: 20px; display: inline-block; border: 1px solid rgba(251,191,36,0.15); }
        .schedule-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 0.78rem; color: var(--info); background: rgba(56,189,248,0.08); padding: 0.25rem 0.6rem; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header"><h1>📚 Course Enrollment</h1></header>
            <?php if ($msg): ?><div style="background:rgba(16,185,129,0.1); color:#6ee7b7; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(16,185,129,0.2);"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($error): ?><div style="background:rgba(239,68,68,0.1); color:#fca5a5; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(239,68,68,0.2);"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- My Schedule -->
            <?php if (count($enrolled_list) > 0): ?>
            <div class="content-card anim-item" style="margin-bottom:1.5rem;">
                <h2 class="card-header">📅 My Schedule (<?= count($enrolled_list) ?> courses)</h2>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead><tr><th>Code</th><th>Course</th><th>Day</th><th>Time</th><th>Room</th><th>Instructor</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($enrolled_list as $c): ?>
                            <tr style="transition:0.3s;" onmouseover="this.style.background='rgba(6,214,160,0.04)'" onmouseout="this.style.background='transparent'">
                                <td><span class="course-code"><?= $c['course_code'] ?></span></td>
                                <td style="font-weight:600; color:var(--text-main);"><?= htmlspecialchars($c['course_name']) ?></td>
                                <td style="color:var(--info);"><?= $c['day'] ?></td>
                                <td style="font-size:0.85rem; color:var(--text-muted);"><?= date('h:i A', strtotime($c['time_start'])) ?> - <?= date('h:i A', strtotime($c['time_end'])) ?></td>
                                <td style="font-size:0.85rem;"><?= $c['room'] ?></td>
                                <td style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($c['instructor']) ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Drop this course?')">
                                        <input type="hidden" name="drop_course_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn-primary" style="padding:0.35rem 0.8rem; font-size:0.78rem; background:rgba(251,113,133,0.15); color:var(--danger); border:1px solid rgba(251,113,133,0.2);">Drop</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Available Courses -->
            <h2 style="font-size:1.1rem; margin-bottom:1.2rem; color:var(--text-main);">Available Courses</h2>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:1.2rem;">
                <?php foreach ($all_courses as $c):
                    $is_full = $c['enrolled_count'] >= $c['max_students'];
                ?>
                <div class="course-card anim-item">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                        <span class="course-code"><?= $c['course_code'] ?></span>
                        <span style="font-size:0.75rem; color:var(--text-muted);"><?= $c['credits'] ?> Credits</span>
                    </div>
                    <h3 style="font-size:1.05rem; color:var(--text-main); margin-bottom:0.5rem;"><?= htmlspecialchars($c['course_name']) ?></h3>
                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.8rem;">👨‍🏫 <?= htmlspecialchars($c['instructor']) ?></p>
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1rem;">
                        <span class="schedule-badge">📆 <?= $c['day'] ?></span>
                        <span class="schedule-badge">🕐 <?= date('h:i A', strtotime($c['time_start'])) ?> - <?= date('h:i A', strtotime($c['time_end'])) ?></span>
                        <span class="schedule-badge">📍 <?= $c['room'] ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:var(--text-muted); margin-bottom:0.8rem;">
                        <span><?= $c['enrolled_count'] ?>/<?= $c['max_students'] ?> enrolled</span>
                        <span style="color:<?= $is_full ? 'var(--danger)' : 'var(--primary)' ?>"><?= $is_full ? 'Full' : ($c['max_students'] - $c['enrolled_count']) . ' seats left' ?></span>
                    </div>
                    <div style="margin-top:auto;">
                        <?php if ($c['is_enrolled']): ?>
                            <button class="btn-primary" disabled style="width:100%; padding:0.7rem; opacity:0.6; background:rgba(6,214,160,0.1); color:var(--primary); border:1px solid rgba(6,214,160,0.2);">✓ Enrolled</button>
                        <?php elseif ($is_full): ?>
                            <button class="btn-primary" disabled style="width:100%; padding:0.7rem; opacity:0.5;">Fully Booked</button>
                        <?php else: ?>
                            <form method="POST" onsubmit="return confirm('Enroll in this course?')"><input type="hidden" name="enroll_course_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn-primary" style="width:100%; padding:0.7rem;">Enroll Now</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>
