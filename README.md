# CUSIT Smart Campus Portal 🎓💻

A comprehensive, full-stack university management system built to bridge the gap between university administration and the student body. The portal provides secure, role-based access for Admins and Students, offering a suite of academic and management features.

## 🌟 Key Features

- **Multi-Role Dashboards:** Secure, role-based environments for Admins and Students.
- **FYP (Final Year Project) Management:** A centralized hub for project proposals, group formation, and milestone tracking.
- **Academics & Support:** 
  - Automated course enrollments
  - Daily attendance tracking
  - Priority-based student complaint ticketing system
- **Event Management:** RSVP system for campus events with dynamic seat-limit tracking.
- **Premium UI/UX:** 
  - Interactive 3D particle background (Three.js)
  - Smooth entrance animations (GSAP)
  - Sleek glassmorphism styling
  - Custom cursor interactions

## 🛠️ Tech Stack

- **Frontend:** HTML5, CSS3, GSAP, Three.js
- **Backend:** PHP (PDO)
- **Database:** MySQL (Relational schema with triggers and cascading keys)
- **Security:** Password Hashing, Prepared Statements, Session Fixation Prevention

## 🚀 Setup Instructions

1. Clone the repository.
2. Import the `database/schema.sql` into your MySQL database (or run `setup.php`).
3. Update `config/db.php` with your local database credentials.
4. Launch the project via a local server (e.g., XAMPP, WAMP, or Docker).

---
*Developed as part of the Web Engineering Final Lab Project.*
