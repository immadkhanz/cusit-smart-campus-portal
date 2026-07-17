<?php
/**
 * CUSIT Smart Campus Portal — Full Database Setup
 * Drops existing DB and rebuilds from scratch with rich demo data.
 * Run once: http://localhost/15024_skillathon/database/setup.php
 */

$host     = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ── Drop & Recreate Database ──
    $pdo->exec("DROP DATABASE IF EXISTS cusit_portal");
    $pdo->exec("CREATE DATABASE cusit_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE cusit_portal");

    // ══════════════════════════════════════
    // TABLE DEFINITIONS (10 tables)
    // ══════════════════════════════════════

    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','student') NOT NULL DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE complaints (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        category VARCHAR(100) NOT NULL,
        priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('pending','in_progress','resolved','rejected') NOT NULL DEFAULT 'pending',
        admin_response TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATETIME NOT NULL,
        venue VARCHAR(200) NOT NULL,
        total_seats INT NOT NULL DEFAULT 100,
        registered_count INT NOT NULL DEFAULT 0,
        status ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE event_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        student_id INT NOT NULL,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_event_student (event_id, student_id)
    )");

    $pdo->exec("CREATE TABLE fyp_groups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_name VARCHAR(150) NOT NULL,
        project_title VARCHAR(255) NOT NULL,
        description TEXT,
        supervisor VARCHAR(150) NULL,
        status ENUM('pending','approved','in_progress','completed','rejected') NOT NULL DEFAULT 'pending',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE fyp_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        student_id INT NOT NULL,
        FOREIGN KEY (group_id) REFERENCES fyp_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_group_student (group_id, student_id)
    )");

    $pdo->exec("CREATE TABLE fyp_milestones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
        due_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES fyp_groups(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_code VARCHAR(20) NOT NULL UNIQUE,
        course_name VARCHAR(200) NOT NULL,
        credits INT NOT NULL DEFAULT 3,
        instructor VARCHAR(150) NOT NULL,
        day ENUM('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
        time_start TIME NOT NULL,
        time_end TIME NOT NULL,
        room VARCHAR(50) NOT NULL,
        max_students INT NOT NULL DEFAULT 40,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE course_enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        student_id INT NOT NULL,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_course_student (course_id, student_id)
    )");

    $pdo->exec("CREATE TABLE attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        student_id INT NOT NULL,
        date DATE NOT NULL,
        status ENUM('present','absent') NOT NULL DEFAULT 'present',
        marked_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (marked_by) REFERENCES users(id),
        UNIQUE KEY unique_attendance (course_id, student_id, date)
    )");

    // ══════════════════════════════════════
    // SEED DATA
    // ══════════════════════════════════════

    // ── Users (1 admin + 5 students) ──
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (id,name,email,password,role) VALUES (?,?,?,?,?)")->execute([1,'Admin','admin@cusit.edu.pk',$pass,'admin']);

    $students = [
        [2, 'Ali Khan',       'ali.khan@cusit.edu.pk'],
        [3, 'Sara Ahmed',     'sara.ahmed@cusit.edu.pk'],
        [4, 'Usman Raza',     'usman.raza@cusit.edu.pk'],
        [5, 'Fatima Noor',    'fatima.noor@cusit.edu.pk'],
        [6, 'Hassan Malik',   'hassan.malik@cusit.edu.pk'],
    ];
    $sp = password_hash('student123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (id,name,email,password,role) VALUES (?,?,?,?,?)");
    foreach ($students as $s) { $stmt->execute([$s[0], $s[1], $s[2], $sp, 'student']); }

    // ── Courses (6) ──
    $pdo->exec("INSERT INTO courses (id,course_code,course_name,credits,instructor,day,time_start,time_end,room,max_students) VALUES
        (1,'CS-301','Database Systems',3,'Dr. Farhan Ahmed','Monday','09:00','10:30','Room 201',40),
        (2,'CS-302','Software Engineering',3,'Prof. Ayesha Malik','Tuesday','11:00','12:30','Room 305',35),
        (3,'CS-401','Artificial Intelligence',3,'Dr. Usman Tariq','Wednesday','14:00','15:30','Lab 4',30),
        (4,'CS-402','Computer Networks',3,'Dr. Saima Khan','Thursday','09:00','10:30','Room 102',40),
        (5,'CS-350','Web Development',3,'Prof. Bilal Raza','Friday','11:00','12:30','Lab 2',35),
        (6,'MT-201','Linear Algebra',3,'Dr. Nadia Ashraf','Monday','14:00','15:30','Room 401',45)
    ");

    // ── Course Enrollments ──
    $enrollments = [
        [1,2],[1,3],[1,4],[1,5],[1,6],  // CS-301: all 5 students
        [2,2],[2,3],[2,5],              // CS-302: Ali, Sara, Fatima
        [3,2],[3,4],[3,6],              // CS-401: Ali, Usman, Hassan
        [4,3],[4,5],[4,6],              // CS-402: Sara, Fatima, Hassan
        [5,2],[5,3],[5,4],[5,5],[5,6],  // CS-350: all 5
        [6,4],[6,6],                    // MT-201: Usman, Hassan
    ];
    $stmt = $pdo->prepare("INSERT INTO course_enrollments (course_id,student_id) VALUES (?,?)");
    foreach ($enrollments as $e) { $stmt->execute($e); }

    // ── Attendance Records (for CS-301 and CS-350, past 2 weeks) ──
    $att_stmt = $pdo->prepare("INSERT INTO attendance (course_id,student_id,date,status,marked_by) VALUES (?,?,?,?,1)");
    $dates_301 = ['2026-05-05','2026-05-12'];  // Mondays
    $dates_350 = ['2026-05-02','2026-05-09'];  // Fridays
    $all_students = [2,3,4,5,6];
    $statuses = ['present','present','present','absent','present','present','present','absent','present','present'];
    $i = 0;
    foreach ($dates_301 as $d) {
        foreach ($all_students as $sid) {
            $att_stmt->execute([1, $sid, $d, $statuses[$i % count($statuses)]]);
            $i++;
        }
    }
    $i = 0;
    foreach ($dates_350 as $d) {
        foreach ($all_students as $sid) {
            $att_stmt->execute([5, $sid, $d, $statuses[($i+3) % count($statuses)]]);
            $i++;
        }
    }

    // ── Events (4) ──
    $pdo->exec("INSERT INTO events (id,title,description,event_date,venue,total_seats,registered_count,status) VALUES
        (1,'CUSIT Tech Fest 2026','Annual technology festival featuring workshops, hackathons, and guest lectures from industry leaders.','2026-06-15 09:00:00','Main Auditorium',200,3,'upcoming'),
        (2,'AI & ML Workshop','Hands-on workshop covering Machine Learning fundamentals, neural networks, and real-world applications using Python.','2026-05-20 14:00:00','Lab 3',40,2,'upcoming'),
        (3,'Career Fair 2026','Meet top employers from Pakistan and abroad. Bring your CV and explore career opportunities.','2026-07-01 10:00:00','Convention Hall',500,0,'upcoming'),
        (4,'Cybersecurity Bootcamp','Intensive one-day bootcamp on ethical hacking, penetration testing, and network security.','2026-05-25 10:00:00','Lab 1',30,1,'upcoming')
    ");

    // ── Event Registrations ──
    $pdo->exec("INSERT INTO event_registrations (event_id,student_id) VALUES (1,2),(1,3),(1,5),(2,2),(2,4),(4,6)");

    // ── Complaints (4) ──
    $pdo->exec("INSERT INTO complaints (student_id,category,priority,subject,description,status,admin_response) VALUES
        (2,'IT Support','high','Wi-Fi not working in Lab 3','The Wi-Fi connection in Lab 3 has been down for 3 days. Students cannot access online resources during practicals. Please fix urgently.','in_progress','IT team has been notified. Expected fix by tomorrow.'),
        (3,'Academics','medium','Grade discrepancy in Database Systems','My mid-term grade shows 45/100 but I scored 72/100. I have the marked paper as proof. Please review.','pending',NULL),
        (5,'Hostel','urgent','Water supply issue in Block C','There has been no water supply in Block C hostel since yesterday morning. Students are severely affected.','resolved','Maintenance team fixed the pipeline. Issue resolved.'),
        (6,'Administration','low','Request for extra library hours during exams','Can the library timing be extended till 11 PM during the final exam period? Many students need late-night study access.','pending',NULL)
    ");

    // ── FYP Groups (2) ──
    $pdo->exec("INSERT INTO fyp_groups (id,group_name,project_title,description,supervisor,status,created_by) VALUES
        (1,'Team Alpha','Smart Campus Portal using PHP & MySQL','A comprehensive web-based portal for managing university operations including complaints, events, FYP tracking, and attendance management.','Dr. Farhan Ahmed','in_progress',2),
        (2,'Team Beta','AI-Powered Chatbot for Student Services','An intelligent chatbot using NLP to answer student queries about admission, fee structure, and campus facilities.',NULL,'pending',4)
    ");

    // ── FYP Members ──
    $pdo->exec("INSERT INTO fyp_members (group_id,student_id) VALUES (1,2),(1,3),(1,5),(2,4),(2,6)");

    // ── FYP Milestones ──
    $pdo->exec("INSERT INTO fyp_milestones (group_id,title,status,due_date) VALUES
        (1,'Proposal Defense','completed','2026-03-15'),
        (1,'SRS Document Submission','completed','2026-04-01'),
        (1,'Mid-Term Demo','in_progress','2026-05-20'),
        (1,'Final Defense','pending','2026-07-10'),
        (2,'Proposal Submission','pending','2026-05-25')
    ");

    // ── Announcements (4) ──
    $pdo->exec("INSERT INTO announcements (title,content,created_by,created_at) VALUES
        ('Welcome to Smart Campus Portal','The CUSIT Smart Campus Portal is now live! All students can access their dashboard to view courses, attendance, complaints, events, and FYP status. Use your university email to log in.',1,'2026-05-01 09:00:00'),
        ('FYP Group Submissions Open','Final Year Project group submissions are now open for Fall 2026. Students must form groups of 2-3 members and submit their proposals through the FYP Portal before the deadline.',1,'2026-05-05 10:30:00'),
        ('Mid-Term Exam Schedule Released','The mid-term examination schedule for Spring 2026 has been uploaded. Please check your course pages for individual exam dates and venue information.',1,'2026-05-10 08:00:00'),
        ('Campus Wi-Fi Maintenance Notice','Campus-wide Wi-Fi maintenance is scheduled for May 18th (Sunday) from 2 AM to 6 AM. Internet services will be temporarily unavailable during this window.',1,'2026-05-13 14:00:00')
    ");

    // ══════════════════════════════════════
    // SUCCESS OUTPUT
    // ══════════════════════════════════════

    echo '<!DOCTYPE html><html><head><style>
        body{font-family:Inter,sans-serif;background:#0a1628;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
        .card{background:rgba(6,214,160,.05);border:1px solid rgba(6,214,160,.15);border-radius:16px;padding:3rem;text-align:center;max-width:550px}
        h1{color:#06d6a0;margin-bottom:1rem;font-size:1.8rem} p{opacity:.8;line-height:2} a{color:#fbbf24;text-decoration:none;font-weight:600}
        .stat{display:inline-block;background:rgba(6,214,160,0.08);border:1px solid rgba(6,214,160,0.15);border-radius:10px;padding:0.4rem 0.8rem;margin:0.2rem;font-size:0.8rem;color:#06d6a0;}
    </style></head><body><div class="card">
        <h1>&#10004; Setup Complete!</h1>
        <p>10 tables created &bull; 6 users &bull; 6 courses &bull; 4 events &bull; 4 complaints &bull; 2 FYP groups &bull; 4 announcements &bull; 20+ attendance records &bull; 20+ enrollments</p>
        <div style="margin:1rem 0;">
            <span class="stat">users</span><span class="stat">complaints</span><span class="stat">events</span><span class="stat">event_registrations</span><span class="stat">fyp_groups</span><span class="stat">fyp_members</span><span class="stat">fyp_milestones</span><span class="stat">announcements</span><span class="stat">courses</span><span class="stat">course_enrollments</span><span class="stat">attendance</span>
        </div>
        <p><strong>Admin:</strong> admin@cusit.edu.pk / admin123</p>
        <p><strong>Any Student:</strong> ali.khan@cusit.edu.pk / student123</p>
        <p style="font-size:0.8rem;color:#94a3b8;margin-top:0.5rem">All 5 students use password: student123</p>
        <p style="margin-top:1.5rem"><a href="../login.php">&#8594; Go to Login</a></p>
    </div></body></html>';

} catch (PDOException $e) {
    echo '<h1 style="color:#ef4444;font-family:sans-serif;padding:2rem;">Setup Failed: ' . $e->getMessage() . '</h1>';
}
