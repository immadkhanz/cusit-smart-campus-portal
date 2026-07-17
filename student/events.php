<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireStudent();

$student_id = $_SESSION['user_id'];
$msg = ''; $error = '';

// Handle Registration using DB Transactions & Locks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event_id'])) {
    $event_id = (int)$_POST['register_event_id'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Lock the event row to prevent race conditions during high-traffic registration
        $stmt = $pdo->prepare("SELECT total_seats, registered_count, status FROM events WHERE id = ? FOR UPDATE");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
        
        if (!$event) {
            throw new Exception("Event not found.");
        }
        if ($event['status'] !== 'upcoming') {
            throw new Exception("Registration is closed for this event.");
        }
        if ($event['registered_count'] >= $event['total_seats']) {
            throw new Exception("Sorry, this event is fully booked.");
        }
        
        // 2. Check duplicate registration
        $stmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND student_id = ?");
        $stmt->execute([$event_id, $student_id]);
        if ($stmt->fetch()) {
            throw new Exception("You are already registered for this event.");
        }
        
        // 3. Register & Update Count
        $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, student_id) VALUES (?, ?)");
        $stmt->execute([$event_id, $student_id]);
        
        $stmt = $pdo->prepare("UPDATE events SET registered_count = registered_count + 1 WHERE id = ?");
        $stmt->execute([$event_id]);
        
        $pdo->commit();
        $msg = "Successfully registered for the event!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Fetch Upcoming Events
$stmt = $pdo->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id AND er.student_id = ?) as is_registered
    FROM events e 
    WHERE e.status = 'upcoming' 
    ORDER BY e.event_date ASC
");
$stmt->execute([$student_id]);
$upcoming_events = $stmt->fetchAll();

// Fetch My Registrations
$stmt = $pdo->prepare("
    SELECT e.title, e.event_date, e.venue, er.registered_at 
    FROM event_registrations er
    JOIN events e ON er.event_id = e.id
    WHERE er.student_id = ?
    ORDER BY e.event_date DESC
");
$stmt->execute([$student_id]);
$my_registrations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events — Smart Campus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .event-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s; position: relative; overflow: hidden; backdrop-filter: blur(10px); display:flex; flex-direction:column; }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3), 0 0 15px rgba(6,214,160,0.08); border-color: rgba(6,214,160,0.25); }
        .event-date { font-size: 0.8rem; color: var(--accent); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
        .event-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.8rem; color: var(--text-main); }
        .event-meta { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.2rem; line-height: 1.6; flex:1; }
        .progress-bar { width: 100%; height: 6px; background: rgba(6,214,160,0.1); border-radius: 3px; margin: 1rem 0; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, var(--primary), var(--accent)); border-radius: 3px; }
        
        .btn-register { width: 100%; padding: 0.8rem; border-radius: 8px; font-weight: 700; border: none; cursor: pointer; transition: 0.3s; font-family: inherit; }
        .btn-available { background: linear-gradient(135deg, var(--primary), #0ea5e9); color: #0a1628; }
        .btn-available:hover { box-shadow: 0 4px 15px var(--primary-glow); transform:translateY(-2px); }
        .btn-booked { background: rgba(251,113,133,0.06); color: var(--danger); border: 1px solid rgba(251,113,133,0.2); cursor: not-allowed; }
        .btn-registered { background: rgba(6,214,160,0.08); color: var(--primary); cursor: default; border: 1px solid rgba(6,214,160,0.25); }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header">
                <h1>Campus Events</h1>
            </header>

            <?php if ($msg): ?><div style="background:rgba(16,185,129,0.1); color:#6ee7b7; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(16,185,129,0.2);"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($error): ?><div style="background:rgba(239,68,68,0.1); color:#fca5a5; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid rgba(239,68,68,0.2);"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <h2 style="font-size:1.2rem; margin-bottom:1.5rem; color:var(--text-main);">Upcoming Events</h2>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                <?php foreach ($upcoming_events as $event): 
                    $percent = ($event['total_seats'] > 0) ? ($event['registered_count'] / $event['total_seats']) * 100 : 100;
                    $is_full = $event['registered_count'] >= $event['total_seats'];
                ?>
                <div class="event-card anim-item">
                    <div class="event-date">📅 <?= date('M d, Y - h:i A', strtotime($event['event_date'])) ?></div>
                    <div class="event-title"><?= htmlspecialchars($event['title']) ?></div>
                    <div class="event-meta">
                        <strong style="color:var(--text-main);">📍 <?= htmlspecialchars($event['venue']) ?></strong><br><br>
                        <?= htmlspecialchars(strlen($event['description']) > 100 ? substr($event['description'], 0, 100) . '...' : $event['description']) ?>
                    </div>
                    
                    <div style="margin-top:auto;">
                        <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:var(--text-muted); font-weight:500;">
                            <span><?= $event['registered_count'] ?> / <?= $event['total_seats'] ?> Registered</span>
                            <span style="color:<?= $is_full ? 'var(--danger)' : 'var(--primary)' ?>"><?= $event['total_seats'] - $event['registered_count'] ?> Seats Left</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $percent ?>%; <?= $is_full ? 'background:var(--danger);' : '' ?>"></div>
                        </div>
                        
                        <?php if ($event['is_registered']): ?>
                            <button class="btn-register btn-registered" disabled>✓ Registered</button>
                        <?php elseif ($is_full): ?>
                            <button class="btn-register btn-booked" disabled>Fully Booked</button>
                        <?php else: ?>
                            <form method="POST" onsubmit="return confirm('Register for this event?')">
                                <input type="hidden" name="register_event_id" value="<?= $event['id'] ?>">
                                <button type="submit" class="btn-register btn-available">Register Now</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(count($upcoming_events) === 0): ?>
                    <p style="color:var(--text-muted); grid-column:1/-1;">No upcoming events scheduled at the moment.</p>
                <?php endif; ?>
            </div>

            <h2 style="font-size:1.2rem; margin-bottom:1.5rem; color:var(--text-main);">My Registrations</h2>
            <div class="content-card anim-item">
                <?php if (count($my_registrations) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Date</th>
                                    <th>Venue</th>
                                    <th>Registered On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_registrations as $r): ?>
                                <tr style="transition:0.3s;" onmouseover="this.style.background='rgba(6,214,160,0.04)'" onmouseout="this.style.background='transparent'">
                                    <td style="font-weight:600; color:var(--text-main);"><?= htmlspecialchars($r['title']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($r['event_date'])) ?></td>
                                    <td><?= htmlspecialchars($r['venue']) ?></td>
                                    <td style="font-size:0.85rem;color:var(--text-muted);"><?= date('M d, Y', strtotime($r['registered_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted); padding:1rem 0;">You haven't registered for any events yet.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
