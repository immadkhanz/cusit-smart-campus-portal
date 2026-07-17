<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit();
}

$error   = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/db.php';
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $success = 'Account created! You can now sign in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — CUSIT Smart Campus Portal</title>
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
                radial-gradient(ellipse at 85% 80%, rgba(251,191,36,0.08) 0%, transparent 50%);
        }

        .auth-wrapper { position: relative; z-index: 10; width: 100%; max-width: 440px; padding: 1.5rem; }

        .auth-card {
            background: rgba(16, 32, 56, 0.7);
            border: 1px solid rgba(6, 214, 160, 0.1);
            border-radius: 24px;
            padding: 2.5rem 2.5rem;
            backdrop-filter: blur(24px);
            box-shadow: 0 0 80px rgba(6,214,160,0.06), 0 25px 50px rgba(0,0,0,0.4);
            opacity: 0; transform: translateY(30px);
        }

        .brand { text-align: center; margin-bottom: 2rem; }
        .brand h1 {
            font-size: 1.5rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 0.3rem;
            background: linear-gradient(135deg, #e2e8f0, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .brand p  { font-size: 0.85rem; color: #7c8db5; font-weight: 500; }

        .form-group { margin-bottom: 1.2rem; }
        .form-group label {
            display: block; font-size: 0.78rem; font-weight: 600;
            color: #7c8db5; margin-bottom: 0.5rem;
            text-transform: uppercase; letter-spacing: 0.08em;
        }
        .form-group input {
            width: 100%; padding: 0.82rem 1rem;
            background: rgba(10, 22, 40, 0.6);
            border: 1px solid rgba(6, 214, 160, 0.1);
            border-radius: 12px; color: #e2e8f0;
            font-family: 'Inter', sans-serif; font-size: 0.92rem;
            transition: all 0.3s ease; outline: none;
        }
        .form-group input:focus {
            border-color: rgba(251,191,36,0.5);
            background: rgba(251,191,36,0.04);
            box-shadow: 0 0 0 3px rgba(251,191,36,0.1);
        }
        .form-group input::placeholder { color: #4a5b7a; }

        .error-msg {
            background: rgba(251,113,133,0.1); border: 1px solid rgba(251,113,133,0.2);
            color: #fb7185; padding: 0.7rem 1rem; border-radius: 10px;
            font-size: 0.82rem; margin-bottom: 1rem; text-align: center;
        }
        .success-msg {
            background: rgba(6,214,160,0.1); border: 1px solid rgba(6,214,160,0.2);
            color: #06d6a0; padding: 0.7rem 1rem; border-radius: 10px;
            font-size: 0.82rem; margin-bottom: 1rem; text-align: center;
        }

        .btn-register {
            width: 100%; padding: 0.9rem;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border: none; border-radius: 12px; color: #0a1628;
            font-family: 'Inter', sans-serif; font-size: 0.95rem; font-weight: 700;
            cursor: pointer; transition: all 0.3s ease; margin-top: 0.3rem;
        }
        .btn-register:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(251,191,36,0.35); }
        .btn-register:active { transform: translateY(0); }

        .auth-footer { text-align: center; margin-top: 1.5rem; font-size: 0.82rem; color: #7c8db5; }
        .auth-footer a { color: #fbbf24; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .auth-footer a:hover { color: #fcd34d; }

        @media (max-width: 480px) { .auth-card { padding: 2rem 1.5rem; } }
    </style>
</head>
<body>

    <div id="threejs-bg"></div>
    <div class="gradient-overlay"></div>

    <div class="auth-wrapper">
        <div class="auth-card" id="authCard">
            <div class="brand">
                <img src="assets/logo.png" alt="Logo" style="width:70px; height:70px; margin-bottom:1rem; filter:drop-shadow(0 8px 20px rgba(6,214,160,0.3));">
                <h1>Create Account</h1>
                <p>Join the CUSIT Smart Campus Portal</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-msg"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Your full name" required
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="you@cusit.edu.pk" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Re-enter password" required>
                </div>
                <button type="submit" class="btn-register">Create Account →</button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Sign in</a>
            </div>
        </div>
    </div>

    <script>
    /* ── Three.js Particle Network ── */
    (function() {
        const container = document.getElementById('threejs-bg');
        const scene  = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth/window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ antialias:true, alpha:true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        container.appendChild(renderer.domElement);

        const count = 100;
        const positions = new Float32Array(count*3);
        const vels = [];
        for (let i=0;i<count;i++){
            positions[i*3]=(Math.random()-0.5)*14;
            positions[i*3+1]=(Math.random()-0.5)*14;
            positions[i*3+2]=(Math.random()-0.5)*6-2;
            vels.push({x:(Math.random()-0.5)*0.008,y:(Math.random()-0.5)*0.008,z:(Math.random()-0.5)*0.004});
        }
        const geo=new THREE.BufferGeometry();
        geo.setAttribute('position',new THREE.BufferAttribute(positions,3));
        const mat=new THREE.PointsMaterial({color:0xfbbf24,size:0.06,transparent:true,opacity:0.7,blending:THREE.AdditiveBlending,depthWrite:false});
        const pts=new THREE.Points(geo,mat);
        scene.add(pts);
        camera.position.z=7;

        let mouseX=0,mouseY=0;
        document.addEventListener('mousemove',e=>{mouseX=(e.clientX/window.innerWidth-0.5)*2;mouseY=(e.clientY/window.innerHeight-0.5)*2;});
        window.addEventListener('resize',()=>{camera.aspect=window.innerWidth/window.innerHeight;camera.updateProjectionMatrix();renderer.setSize(window.innerWidth,window.innerHeight);});

        function animate(){
            requestAnimationFrame(animate);
            const pos=pts.geometry.attributes.position.array;
            for(let i=0;i<count;i++){
                pos[i*3]+=vels[i].x;pos[i*3+1]+=vels[i].y;pos[i*3+2]+=vels[i].z;
                if(Math.abs(pos[i*3])>7)vels[i].x*=-1;
                if(Math.abs(pos[i*3+1])>7)vels[i].y*=-1;
                if(Math.abs(pos[i*3+2])>4)vels[i].z*=-1;
            }
            pts.geometry.attributes.position.needsUpdate=true;
            pts.rotation.y+=mouseX*0.0003;pts.rotation.x+=mouseY*0.0003;
            renderer.render(scene,camera);
        }
        animate();
    })();

    gsap.to('#authCard',{opacity:1,y:0,duration:0.8,ease:'power3.out',delay:0.1});
    gsap.from('.form-group',{opacity:0,y:15,stagger:0.1,duration:0.5,ease:'power2.out',delay:0.3});

    /* ── Custom Cursor ── */
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        document.body.style.cursor = 'none';
        document.querySelectorAll('*').forEach(el => el.style.cursor = 'none');
        const cur = document.createElement('div');
        cur.style.cssText = 'width:32px;height:32px;border:1.5px solid rgba(251,191,36,0.5);border-radius:50%;position:fixed;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);transition:width 0.25s,height 0.25s,background 0.25s;mix-blend-mode:difference;';
        const dot = document.createElement('div');
        dot.style.cssText = 'width:5px;height:5px;background:#fbbf24;border-radius:50%;position:fixed;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);';
        document.body.appendChild(cur); document.body.appendChild(dot);
        let cx=0,cy=0,dx=0,dy=0;
        document.addEventListener('mousemove', e => { dx=e.clientX; dy=e.clientY; dot.style.left=dx+'px'; dot.style.top=dy+'px'; });
        (function anim(){ cx+=(dx-cx)*0.15; cy+=(dy-cy)*0.15; cur.style.left=cx+'px'; cur.style.top=cy+'px'; requestAnimationFrame(anim); })();
        document.querySelectorAll('a,button,input').forEach(el => {
            el.addEventListener('mouseenter', () => { cur.style.width='50px'; cur.style.height='50px'; cur.style.background='rgba(251,191,36,0.08)'; });
            el.addEventListener('mouseleave', () => { cur.style.width='32px'; cur.style.height='32px'; cur.style.background='transparent'; });
        });
    }
    </script>

</body>
</html>
