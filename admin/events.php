<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

$msg = ''; $error = '';

// Handle Event Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $title       = trim($_POST['title']);
        $description = trim($_POST['description']);
        $date        = $_POST['event_date'];
        $venue       = trim($_POST['venue']);
        $total_seats = (int)$_POST['total_seats'];
        
        if (empty($title) || empty($date) || empty($venue) || $total_seats < 1) {
            $error = "Please fill all required fields correctly.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, venue, total_seats) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $description, $date, $venue, $total_seats])) {
                $msg = "Event created successfully!";
            } else {
                $error = "Failed to create event.";
            }
        }
    } elseif ($_POST['action'] === 'update_status') {
        $event_id = (int)$_POST['event_id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
        $stmt->execute([$status, $event_id]);
        $msg = "Event status updated.";
    } elseif ($_POST['action'] === 'delete') {
        $event_id = (int)$_POST['event_id'];
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $msg = "Event deleted.";
    }
}

// Fetch all events
$events = $pdo->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events — Smart Campus</title>
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
                <h1>Manage Events</h1>
            </header>

            <?php if ($msg): ?><div style="background:rgba(16,185,129,0.1); color:#6ee7b7; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(16,185,129,0.2);"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($error): ?><div style="background:rgba(239,68,68,0.1); color:#fca5a5; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(239,68,68,0.2);"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="content-grid">
                <!-- Create Event Form -->
                <div class="content-card anim-item" style="grid-column: 1 / 2;">
                    <h2 class="card-header">Create New Event</h2>
                    <form method="POST" onsubmit="return confirm('Create this event?')">
                        <input type="hidden" name="action" value="create">
                        <div class="form-group">
                            <label>Event Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. AI Seminar" required>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                            <div class="form-group">
                                <label>Date & Time</label>
                                <input type="datetime-local" name="event_date" class="form-control" required style="color-scheme: dark;">
                            </div>
                            <div class="form-group">
                                <label>Total Seats (Limit)</label>
                                <input type="number" name="total_seats" class="form-control" min="1" value="50" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Venue</label>
                            <input type="text" name="venue" class="form-control" placeholder="e.g. Main Auditorium" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Event details..."></textarea>
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%; padding:0.9rem;">Publish Event</button>
                    </form>
                </div>

                <!-- Event List -->
                <div class="content-card anim-item" style="grid-column: 1 / -1; margin-top:1.5rem;">
                    <h2 class="card-header">All Events Overview</h2>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Date</th>
                                    <th>Venue</th>
                                    <th>Registrations vs Capacity</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $e): 
                                    $percent = ($e['total_seats'] > 0) ? ($e['registered_count'] / $e['total_seats']) * 100 : 0;
                                    $is_full = $e['registered_count'] >= $e['total_seats'];
                                ?>
                                <tr style="transition:0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                    <td style="color:var(--text-main);"><strong><?= htmlspecialchars($e['title']) ?></strong></td>
                                    <td style="color:var(--text-muted);font-size:0.9rem;"><?= date('M d, Y h:i A', strtotime($e['event_date'])) ?></td>
                                    <td style="color:var(--text-muted);font-size:0.9rem;"><?= htmlspecialchars($e['venue']) ?></td>
                                    <td style="min-width:180px;">
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <span style="font-weight:600; font-size:0.85rem; color:<?= $is_full ? '#ef4444' : '#10b981' ?>; min-width:45px;">
                                                <?= $e['registered_count'] ?>/<?= $e['total_seats'] ?>
                                            </span>
                                            <div style="flex:1;height:6px;background:rgba(255,255,255,0.1);border-radius:3px;overflow:hidden;">
                                                <div style="height:100%;width:<?= $percent ?>%;background:<?= $is_full ? '#ef4444' : 'var(--primary)' ?>;border-radius:3px;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="event_id" value="<?= $e['id'] ?>">
                                            <select name="status" class="form-control" data-original="<?= $e['status'] ?>" style="width:auto;padding:0.3rem 0.6rem;font-size:0.8rem;color:var(--text-main);" onchange="if(confirm('Update event status?')){this.form.submit()}else{this.value=this.dataset.original}">
                                                <option value="upcoming" <?= $e['status']=='upcoming'?'selected':'' ?>>Upcoming</option>
                                                <option value="ongoing" <?= $e['status']=='ongoing'?'selected':'' ?>>Ongoing</option>
                                                <option value="completed" <?= $e['status']=='completed'?'selected':'' ?>>Completed</option>
                                                <option value="cancelled" <?= $e['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Delete this event?')" style="margin:0;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="event_id" value="<?= $e['id'] ?>">
                                            <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:1.2rem; opacity:0.6; transition:0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'" title="Delete">&times;</button>
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
