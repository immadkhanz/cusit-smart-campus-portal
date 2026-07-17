<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit();
}

$error = '';

// Session timeout message
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please sign in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/db.php';
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent fixation attacks
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_name']     = $user['name'];
            $_SESSION['user_email']    = $user['email'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time']    = date('Y-m-d H:i:s');
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CUSIT Smart Campus Portal</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a1628;
            color: #e2e8f0;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #threejs-bg { position: fixed; inset: 0; z-index: 0; }
        #threejs-bg canvas { display: block; }

        .gradient-overlay {
            position: fixed; inset: 0; z-index: 1; pointer-events: none;
            background:
                radial-gradient(ellipse at 15% 20%, rgba(6,214,160,0.12) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 80%, rgba(251,191,36,0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(56,189,248,0.05) 0%, transparent 60%);
        }

        .auth-wrapper {
            position: relative; z-index: 10;
            width: 100%; max-width: 440px; padding: 1.5rem;
        }

        .auth-card {
            background: rgba(16, 32, 56, 0.7);
            border: 1px solid rgba(6, 214, 160, 0.1);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow:
                0 0 80px rgba(6,214,160,0.06),
                0 25px 50px rgba(0,0,0,0.4);
            opacity: 0;
            transform: translateY(30px);
        }

        .brand { text-align: center; margin-bottom: 2.5rem; }



        .brand h1 {
            font-size: 1.6rem; font-weight: 800;
            letter-spacing: -0.03em; margin-bottom: 0.3rem;
            background: linear-gradient(135deg, #e2e8f0, #06d6a0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .brand p { font-size: 0.85rem; color: #7c8db5; font-weight: 500; }

        .form-group { margin-bottom: 1.4rem; position: relative; }
        .form-group label {
            display: block; font-size: 0.78rem; font-weight: 600;
            color: #7c8db5; margin-bottom: 0.5rem;
            text-transform: uppercase; letter-spacing: 0.08em;
        }
        .form-group input {
            width: 100%; padding: 0.85rem 1rem;
            background: rgba(10, 22, 40, 0.6);
            border: 1px solid rgba(6, 214, 160, 0.1);
            border-radius: 12px; color: #e2e8f0;
            font-family: 'Inter', sans-serif; font-size: 0.92rem;
            transition: all 0.3s ease; outline: none;
        }
        .form-group input:focus {
            border-color: rgba(6,214,160,0.5);
            background: rgba(6,214,160,0.04);
            box-shadow: 0 0 0 3px rgba(6,214,160,0.1);
        }
        .form-group input::placeholder { color: #4a5b7a; }

        .error-msg {
            background: rgba(251,113,133,0.1); border: 1px solid rgba(251,113,133,0.2);
            color: #fb7185; padding: 0.8rem 1rem; border-radius: 12px;
            font-size: 0.82rem; margin-bottom: 1.2rem; text-align: center;
        }

        .btn-login {
            width: 100%; padding: 0.9rem;
            background: linear-gradient(135deg, #06d6a0, #0ea5e9);
            border: none; border-radius: 12px;
            color: #0a1628; font-family: 'Inter', sans-serif;
            font-size: 0.95rem; font-weight: 700;
            cursor: pointer; transition: all 0.3s ease;
            position: relative; overflow: hidden; margin-top: 0.5rem;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(6,214,160,0.35);
        }
        .btn-login:active { transform: translateY(0); }

        .auth-footer {
            text-align: center; margin-top: 1.8rem;
            font-size: 0.82rem; color: #7c8db5;
        }
        .auth-footer a {
            color: #06d6a0; text-decoration: none;
            font-weight: 600; transition: color 0.2s;
        }
        .auth-footer a:hover { color: #34d399; }

        @media (max-width: 480px) { .auth-card { padding: 2rem 1.5rem; } }

        /* Splash Screen */
        #splash-screen {
            position: fixed; inset: 0; z-index: 9999;
            background: #0a1628;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        #splash-logo {
            width: 140px; height: 140px;
            margin-bottom: 2rem;
            opacity: 0; transform: scale(0.8);
            filter: drop-shadow(0 10px 30px rgba(6,214,160,0.4));
        }
        #splash-text {
            font-size: 2.2rem; font-weight: 800;
            background: linear-gradient(135deg, #e2e8f0, #06d6a0);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            opacity: 0; transform: translateY(20px);
            letter-spacing: -0.03em;
        }
        #splash-loader {
            width: 240px; height: 4px; background: rgba(6,214,160,0.1);
            border-radius: 2px; margin-top: 2rem; overflow: hidden;
            opacity: 0;
        }
        #splash-progress {
            width: 0%; height: 100%; background: linear-gradient(90deg, #06d6a0, #0ea5e9);
            box-shadow: 0 0 10px rgba(6,214,160,0.5);
        }
    </style>
</head>
<body>

    <!-- Splash Screen -->
    <div id="splash-screen">
        <img src="assets/logo.png" id="splash-logo" alt="Smart Campus Logo">
        <div id="splash-text">CUSIT Smart Campus</div>
        <div id="splash-loader"><div id="splash-progress"></div></div>
    </div>

    <div id="threejs-bg"></div>
    <div class="gradient-overlay"></div>

    <div class="auth-wrapper">
        <div class="auth-card" id="authCard">
            <div class="brand">
                <img src="assets/logo.png" alt="Logo" style="width:75px; height:75px; margin-bottom:1rem; filter:drop-shadow(0 8px 20px rgba(6,214,160,0.3));">
                <h1>Smart Campus Portal</h1>
                <p>CUSIT — Sign in to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="you@cusit.edu.pk" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn-login">Sign In →</button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Create one</a>
            </div>
        </div>
    </div>

    <script>
    /* ── Three.js Particle Network ── */
    (function() {
        const container = document.getElementById('threejs-bg');
        const scene  = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth/window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        container.appendChild(renderer.domElement);

        const count = 120;
        const positions = new Float32Array(count * 3);
        const vels = [];
        for (let i = 0; i < count; i++) {
            positions[i*3]   = (Math.random()-0.5) * 14;
            positions[i*3+1] = (Math.random()-0.5) * 14;
            positions[i*3+2] = (Math.random()-0.5) * 6 - 2;
            vels.push({ x:(Math.random()-0.5)*0.008, y:(Math.random()-0.5)*0.008, z:(Math.random()-0.5)*0.004 });
        }
        const geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        const mat = new THREE.PointsMaterial({
            color: 0x06d6a0, size: 0.07, transparent: true, opacity: 0.75,
            blending: THREE.AdditiveBlending, depthWrite: false
        });
        const pts = new THREE.Points(geo, mat);
        scene.add(pts);

        /* Gold accent particles */
        const count2 = 40;
        const pos2 = new Float32Array(count2 * 3);
        const vels2 = [];
        for (let i = 0; i < count2; i++) {
            pos2[i*3]   = (Math.random()-0.5) * 12;
            pos2[i*3+1] = (Math.random()-0.5) * 12;
            pos2[i*3+2] = (Math.random()-0.5) * 5 - 1;
            vels2.push({ x:(Math.random()-0.5)*0.006, y:(Math.random()-0.5)*0.006, z:(Math.random()-0.5)*0.003 });
        }
        const geo2 = new THREE.BufferGeometry();
        geo2.setAttribute('position', new THREE.BufferAttribute(pos2, 3));
        const mat2 = new THREE.PointsMaterial({
            color: 0xfbbf24, size: 0.05, transparent: true, opacity: 0.5,
            blending: THREE.AdditiveBlending, depthWrite: false
        });
        const pts2 = new THREE.Points(geo2, mat2);
        scene.add(pts2);

        camera.position.z = 7;

        let mouseX = 0, mouseY = 0;
        document.addEventListener('mousemove', e => {
            mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
            mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
        });

        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });

        function animate() {
            requestAnimationFrame(animate);
            const pos = pts.geometry.attributes.position.array;
            for (let i = 0; i < count; i++) {
                pos[i*3] += vels[i].x; pos[i*3+1] += vels[i].y; pos[i*3+2] += vels[i].z;
                if (Math.abs(pos[i*3]) > 7) vels[i].x *= -1;
                if (Math.abs(pos[i*3+1]) > 7) vels[i].y *= -1;
                if (Math.abs(pos[i*3+2]) > 4) vels[i].z *= -1;
            }
            pts.geometry.attributes.position.needsUpdate = true;

            const p2 = pts2.geometry.attributes.position.array;
            for (let i = 0; i < count2; i++) {
                p2[i*3] += vels2[i].x; p2[i*3+1] += vels2[i].y; p2[i*3+2] += vels2[i].z;
                if (Math.abs(p2[i*3]) > 6) vels2[i].x *= -1;
                if (Math.abs(p2[i*3+1]) > 6) vels2[i].y *= -1;
                if (Math.abs(p2[i*3+2]) > 3) vels2[i].z *= -1;
            }
            pts2.geometry.attributes.position.needsUpdate = true;

            pts.rotation.y += mouseX * 0.0003;
            pts.rotation.x += mouseY * 0.0003;
            pts2.rotation.y -= mouseX * 0.0002;
            renderer.render(scene, camera);
        }
        animate();
    })();

    /* ── GSAP Intro & Entrance ── */
    const tl = gsap.timeline();
    tl.to('#splash-logo', { opacity: 1, scale: 1, duration: 1.2, ease: 'back.out(1.5)' })
      .to('#splash-text', { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=0.6')
      .to('#splash-loader', { opacity: 1, duration: 0.3 }, '-=0.2')
      .to('#splash-progress', { width: '100%', duration: 1.8, ease: 'power2.inOut' })
      .to('#splash-screen', { opacity: 0, duration: 0.8, ease: 'power2.inOut', onComplete: () => {
          document.getElementById('splash-screen').style.display = 'none';
      } })
      .to('#authCard', { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=0.3')
      .from('.form-group', { opacity: 0, y: 15, stagger: 0.1, duration: 0.5, ease: 'power2.out' }, '-=0.5');

    /* ── Custom Cursor ── */
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        document.body.style.cursor = 'none';
        document.querySelectorAll('*').forEach(el => el.style.cursor = 'none');
        const cur = document.createElement('div');
        cur.style.cssText = 'width:32px;height:32px;border:1.5px solid rgba(6,214,160,0.5);border-radius:50%;position:fixed;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);transition:width 0.25s,height 0.25s,background 0.25s;mix-blend-mode:difference;';
        const dot = document.createElement('div');
        dot.style.cssText = 'width:5px;height:5px;background:#06d6a0;border-radius:50%;position:fixed;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);';
        document.body.appendChild(cur); document.body.appendChild(dot);
        let cx=0,cy=0,dx=0,dy=0;
        document.addEventListener('mousemove', e => { dx=e.clientX; dy=e.clientY; dot.style.left=dx+'px'; dot.style.top=dy+'px'; });
        (function anim(){ cx+=(dx-cx)*0.15; cy+=(dy-cy)*0.15; cur.style.left=cx+'px'; cur.style.top=cy+'px'; requestAnimationFrame(anim); })();
        document.querySelectorAll('a,button,input').forEach(el => {
            el.addEventListener('mouseenter', () => { cur.style.width='50px'; cur.style.height='50px'; cur.style.background='rgba(6,214,160,0.08)'; });
            el.addEventListener('mouseleave', () => { cur.style.width='32px'; cur.style.height='32px'; cur.style.background='transparent'; });
        });
    }
    </script>

</body>
</html>
