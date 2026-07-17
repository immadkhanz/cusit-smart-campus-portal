<?php
$role = $_SESSION['role'] ?? 'student';
$current_page = basename($_SERVER['PHP_SELF']);
$base_url = '/' . basename(dirname(__DIR__)) . '/';
?>
<link rel="icon" type="image/png" href="<?= $base_url ?>assets/logo.png">
<script>
// Apply theme instantly before page renders to prevent flash
const savedTheme = localStorage.getItem('theme') || 'dark';
document.documentElement.setAttribute('data-theme', savedTheme);
</script>
<aside class="sidebar">
    <div class="brand">
        <img src="<?= $base_url ?>assets/logo.png" alt="Logo" style="width:36px; height:36px; border-radius:10px; flex-shrink:0; filter:drop-shadow(0 4px 8px rgba(6,214,160,0.3));">
        Smart Campus
    </div>
    
    <ul class="nav-menu">
        <?php if ($role === 'admin'): ?>
            <li class="nav-item"><a href="<?= $base_url ?>admin/dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>admin/students.php" class="<?= $current_page == 'students.php' ? 'active' : '' ?>">👥 Students</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>admin/courses.php" class="<?= $current_page == 'courses.php' ? 'active' : '' ?>">📚 Courses</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>admin/attendance.php" class="<?= $current_page == 'attendance.php' ? 'active' : '' ?>">📋 Attendance</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>admin/complaints.php" class="<?= $current_page == 'complaints.php' ? 'active' : '' ?>">📩 Complaints</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>admin/events.php" class="<?= $current_page == 'events.php' ? 'active' : '' ?>">📅 Events</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>admin/fyp.php" class="<?= $current_page == 'fyp.php' ? 'active' : '' ?>">🎓 FYP Management</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>admin/announcements.php" class="<?= $current_page == 'announcements.php' ? 'active' : '' ?>">📢 Announcements</a></li>
        <?php else: ?>
            <li class="nav-item"><a href="<?= $base_url ?>student/dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>student/courses.php" class="<?= $current_page == 'courses.php' ? 'active' : '' ?>">📚 My Courses</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>student/attendance.php" class="<?= $current_page == 'attendance.php' ? 'active' : '' ?>">📋 Attendance</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>student/complaints.php" class="<?= $current_page == 'complaints.php' ? 'active' : '' ?>">📩 My Complaints</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>student/events.php" class="<?= $current_page == 'events.php' ? 'active' : '' ?>">📅 Events</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>student/fyp.php" class="<?= $current_page == 'fyp.php' ? 'active' : '' ?>">🎓 FYP Portal</a></li>
            <li class="nav-item"><a href="<?= $base_url ?>student/announcements.php" class="<?= $current_page == 'announcements.php' ? 'active' : '' ?>">📢 Announcements</a></li>
        <?php endif; ?>
    </ul>
    
    
    <div style="margin-top:auto; padding-top:1rem; border-top:1px solid var(--border-color); display:flex; flex-direction:column; gap:0.5rem;">
        <button id="themeToggle" style="display:flex; align-items:center; justify-content:space-between; width:100%; padding:0.8rem 1.2rem; background:var(--card-bg); border:1px solid var(--border-color); border-radius:12px; color:var(--text-main); font-family:inherit; font-weight:600; cursor:pointer; transition:all 0.3s;">
            <span style="display:flex; align-items:center; gap:0.6rem;">🌓 Theme</span>
            <span id="themeIcon" style="font-size:1.1rem;">🌙</span>
        </button>
        <a href="<?= $base_url ?>logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</aside>

<!-- Premium Libraries (loaded with sidebar on every page) -->
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/lenis@1.1.18/dist/lenis.min.js" defer></script>
<script src="<?= $base_url ?>assets/js/premium.js" defer></script>

<script>
// Theme Toggle Logic
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('themeToggle');
    const icon = document.getElementById('themeIcon');
    const doc = document.documentElement;
    
    if (doc.getAttribute('data-theme') === 'light') {
        icon.textContent = '☀️';
    }

    btn.addEventListener('click', () => {
        const isLight = doc.getAttribute('data-theme') === 'light';
        const newTheme = isLight ? 'dark' : 'light';
        doc.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        icon.textContent = newTheme === 'light' ? '☀️' : '🌙';
    });

    // ── AJAX Badge Polling ──
    function fetchBadges() {
        fetch('<?= $base_url ?>includes/badges_api.php')
            .then(r => r.json())
            .then(data => {
                if (data.error) return;
                Object.keys(data).forEach(key => {
                    const count = data[key];
                    // Find nav links that match this key
                    document.querySelectorAll('.nav-item a').forEach(link => {
                        const text = link.textContent.toLowerCase();
                        if (text.includes(key.replace('_', ' ')) || text.includes(key)) {
                            // Remove old badge
                            const old = link.querySelector('.badge-dot');
                            if (old) old.remove();
                            // Add badge if count > 0
                            if (count > 0) {
                                const dot = document.createElement('span');
                                dot.className = 'badge-dot';
                                dot.textContent = count;
                                dot.style.cssText = 'background:#06d6a0;color:#0a1628;font-size:0.65rem;font-weight:800;padding:1px 6px;border-radius:10px;margin-left:auto;min-width:18px;text-align:center;';
                                link.appendChild(dot);
                            }
                        }
                    });
                });
            })
            .catch(() => {});
    }
    fetchBadges();
    setInterval(fetchBadges, 30000); // Poll every 30 seconds
});
</script>
