<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sigametec</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; min-height:100vh; display:flex; background:#0c0a1d; overflow:hidden; }

        .wrap { display:flex; width:100%; min-height:100vh; }

        /* ============ PANEL IZQUIERDO ============ */
        .left {
            width:48%; position:relative; display:flex; align-items:center; justify-content:center;
            background:#4E37E6;
            overflow:hidden;
        }

        /* Fondo animado geometrico */
        .bg-grid {
            position:absolute; inset:0; overflow:hidden; opacity:.25;
        }
        .bg-grid .line {
            position:absolute; background:rgba(255,255,255,.4);
        }
        .bg-grid .line-v { width:1px; height:100%; }
        .bg-grid .line-h { height:1px; width:100%; }

        .left-content { position:relative; z-index:2; text-align:center; padding:40px; max-width:400px; }

        /* Ilustracion principal SVG custom */
        .hero-illustration {
            width:260px; height:260px; margin:0 auto 36px; position:relative;
        }
        .hero-illustration svg { width:100%; height:100%; }

        .brand-name {
            font-family:'Space Grotesk',sans-serif; font-size:2.8rem; font-weight:700;
            margin-bottom:12px; letter-spacing:-1px;
        }
        .brand-sige { color:#fff; }
        .brand-tec { color:#E63777; }
        .brand-tagline {
            font-size:.95rem; color:rgba(255,255,255,.5); font-weight:300;
            line-height:1.7; max-width:300px; margin:0 auto 32px;
        }

        /* Stats gamificacion */
        .stats-row {
            display:flex; gap:16px; justify-content:center;
        }
        .stat-pill {
            display:flex; align-items:center; gap:6px;
            background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.06);
            border-radius:40px; padding:8px 16px; font-size:.75rem; color:rgba(255,255,255,.6);
            backdrop-filter:blur(8px);
        }
        .stat-pill i { font-size:.7rem; color:#E63777; }

        /* Floating elements */
        .float-el {
            position:absolute; opacity:.35; animation:floatUp 5s ease-in-out infinite;
        }
        .float-el:nth-child(1) { top:10%; left:8%; animation-delay:0s; }
        .float-el:nth-child(2) { top:70%; left:12%; animation-delay:1.2s; }
        .float-el:nth-child(3) { top:20%; right:10%; animation-delay:2.4s; }
        .float-el:nth-child(4) { bottom:15%; right:8%; animation-delay:0.6s; }
        .float-el:nth-child(5) { top:50%; left:5%; animation-delay:1.8s; }

        @keyframes floatUp {
            0%,100% { transform:translateY(0) rotate(0deg); }
            50% { transform:translateY(-18px) rotate(5deg); }
        }

        /* ============ PANEL DERECHO ============ */
        .right {
            width:52%; display:flex; align-items:center; justify-content:center;
            background:#f8f7fc; position:relative;
        }
        .right-bg {
            position:absolute; inset:0; opacity:.03;
            background-image: radial-gradient(circle at 2px 2px, #4E37E6 1px, transparent 0);
            background-size:32px 32px;
        }
        .right-inner { width:100%; max-width:440px; position:relative; z-index:1; padding:40px; }

        .form-header { margin-bottom:36px; }
        .form-header .welcome-icon {
            width:52px; height:52px; border-radius:16px;
            background:#4E37E6;
            display:flex; align-items:center; justify-content:center;
            margin-bottom:20px; box-shadow:0 8px 24px rgba(78,55,230,.25);
        }
        .form-header .welcome-icon i { font-size:1.2rem; color:#fff; }
        .form-title { font-family:'Space Grotesk',sans-serif; font-size:1.65rem; font-weight:700; color:#1a1040; margin-bottom:6px; }
        .form-subtitle { font-size:.88rem; color:#8b89a0; font-weight:400; }

        /* --- Flash Messages --- */
        .msg {
            padding:14px 16px; border-radius:12px; font-size:.82rem; margin-bottom:24px;
            display:flex; align-items:center; gap:10px; animation:slideIn .3s ease;
        }
        @keyframes slideIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
        .msg-error { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .msg-success { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .msg-warning { background:#fefce8; color:#a16207; border:1px solid #fde68a; }

        /* --- Inputs --- */
        .field { position:relative; margin-bottom:22px; }
        .field label {
            display:flex; align-items:center; gap:6px; font-size:.7rem; font-weight:600; color:#6b6980;
            text-transform:uppercase; letter-spacing:.8px; margin-bottom:6px;
        }
        .field .input-wrap {
            position:relative; display:flex; align-items:center;
            border:2px solid #e4e2ed; border-radius:12px; background:#fff;
            transition:all .25s ease;
        }
        .field .input-wrap:focus-within {
            border-color:#4E37E6; box-shadow:0 0 0 3px rgba(78,55,230,.08);
        }
        .field .input-wrap .ico {
            display:flex; align-items:center; justify-content:center;
            width:44px; flex-shrink:0; color:#b0aec0; font-size:.9rem;
            transition:color .25s ease;
        }
        .field .input-wrap:focus-within .ico { color:#4E37E6; }
        .field input[type="text"],
        .field input[type="password"] {
            flex:1; padding:13px 12px 13px 0; font-size:.9rem; font-family:'Inter',sans-serif;
            border:none; background:transparent; outline:none; color:#1a1040; min-width:0;
        }
        .field input::placeholder { color:#b8b6c8; font-weight:400; }

        .toggle-pass {
            display:flex; align-items:center; justify-content:center;
            width:40px; flex-shrink:0; background:none; border:none;
            cursor:pointer; color:#b0aec0; font-size:.9rem; padding:0;
            transition:color .25s ease;
        }
        .toggle-pass:hover { color:#4E37E6; }

        #userStatus {
            font-size:.85rem; display:none; transition:all .3s ease; line-height:1;
        }

        /* --- Links --- */
        .forgot-link {
            display:block; text-align:right; margin:-8px 0 22px;
        }
        .forgot-link a {
            color:#4E37E6; font-weight:500; font-size:.8rem; text-decoration:none;
            transition:color .2s;
        }
        .forgot-link a:hover { color:#3d2bc4; }

        /* --- Buttons --- */
        .btn {
            display:flex; align-items:center; justify-content:center; gap:8px;
            width:100%; padding:14px; font-size:.9rem; font-weight:600;
            font-family:'Inter',sans-serif; border:none; border-radius:12px;
            cursor:pointer; transition:all .25s ease; letter-spacing:.2px;
        }
        .btn-login {
            background:#4E37E6; color:#fff;
            box-shadow:0 6px 20px rgba(78,55,230,.3); margin-bottom:12px;
        }
        .btn-login:hover {
            transform:translateY(-2px); box-shadow:0 10px 28px rgba(78,55,230,.4);
        }
        .btn-login:active { transform:translateY(0); }
        .btn-login i { font-size:.85rem; }

        .btn-register {
            background:#fff; color:#1a1040; border:2px solid #e4e2ed;
        }
        .btn-register:hover { border-color:#4E37E6; color:#4E37E6; }
        .btn-register i { font-size:.85rem; }

        /* --- Footer --- */
        .alt-link {
            text-align:center; margin-top:24px; font-size:.82rem; color:#8b89a0;
        }
        .alt-link a {
            color:#4E37E6; font-weight:600; text-decoration:none;
        }
        .alt-link a:hover { color:#3d2bc4; }

        /* --- Responsive --- */
        @media(max-width:900px) {
            .left { display:none; }
            .right { width:100%; padding:36px 24px; min-height:100vh; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <!-- ======== IZQUIERDA ======== -->
    <div class="left">
        <!-- Grid de fondo -->
        <div class="bg-grid">
            <div class="line line-v" style="left:20%"></div>
            <div class="line line-v" style="left:40%"></div>
            <div class="line line-v" style="left:60%"></div>
            <div class="line line-v" style="left:80%"></div>
            <div class="line line-h" style="top:25%"></div>
            <div class="line line-h" style="top:50%"></div>
            <div class="line line-h" style="top:75%"></div>
        </div>

        <!-- Elementos flotantes -->
        <div class="float-el"><svg width="40" height="40" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
        <div class="float-el"><svg width="36" height="36" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg></div>
        <div class="float-el"><svg width="38" height="38" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="1.5" viewBox="0 0 24 24"><path d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg></div>
        <div class="float-el"><svg width="34" height="34" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="1.5" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>
        <div class="float-el"><svg width="32" height="32" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>

        <div class="left-content">
            <!-- Ilustracion hero SVG custom -->
            <div class="hero-illustration">
                <svg viewBox="0 0 260 260" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Circulo base -->
                    <circle cx="130" cy="130" r="110" fill="rgba(255,255,255,.04)" stroke="rgba(255,255,255,.08)" stroke-width="1"/>
                    <circle cx="130" cy="130" r="85" fill="rgba(255,255,255,.03)" stroke="rgba(255,255,255,.06)" stroke-width="1"/>
                    <!-- Monitor/pantalla -->
                    <rect x="75" y="70" width="110" height="80" rx="8" fill="#2d1b69" stroke="rgba(255,255,255,.2)" stroke-width="1.5"/>
                    <rect x="82" y="77" width="96" height="52" rx="4" fill="#1a1145"/>
                    <!-- Lineas de codigo en pantalla -->
                    <rect x="90" y="86" width="32" height="3" rx="1.5" fill="#4E37E6" opacity=".8"/>
                    <rect x="90" y="94" width="50" height="3" rx="1.5" fill="#E63777" opacity=".6"/>
                    <rect x="90" y="102" width="40" height="3" rx="1.5" fill="#6c5ce7" opacity=".5"/>
                    <rect x="90" y="110" width="55" height="3" rx="1.5" fill="#4E37E6" opacity=".4"/>
                    <rect x="90" y="118" width="25" height="3" rx="1.5" fill="#E63777" opacity=".3"/>
                    <!-- Soporte del monitor -->
                    <rect x="120" y="150" width="20" height="12" rx="2" fill="#2d1b69" stroke="rgba(255,255,255,.15)" stroke-width="1"/>
                    <rect x="105" y="162" width="50" height="5" rx="2.5" fill="#2d1b69" stroke="rgba(255,255,255,.15)" stroke-width="1"/>
                    <!-- Estrella gamificacion -->
                    <g transform="translate(185,58)">
                        <circle cx="0" cy="0" r="18" fill="#E63777" opacity=".15"/>
                        <path d="M0-10l2.2 6.5H9l-5.5 4 2.1 6.5L0 13l-5.6 4 2.1-6.5L-9 6.5h6.8z" fill="#E63777" opacity=".8"/>
                    </g>
                    <!-- Escudo/check -->
                    <g transform="translate(52,55)">
                        <circle cx="0" cy="0" r="14" fill="rgba(78,55,230,.3)"/>
                        <path d="M-5 0l3.5 3.5L7-4" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    </g>
                    <!-- XP badge -->
                    <g transform="translate(195,145)">
                        <rect x="-22" y="-10" width="44" height="20" rx="10" fill="rgba(255,255,255,.1)" stroke="rgba(255,255,255,.15)" stroke-width="1"/>
                        <text x="0" y="4" text-anchor="middle" fill="rgba(255,255,255,.7)" font-size="8" font-family="Inter" font-weight="600">+100 XP</text>
                    </g>
                </svg>
            </div>

            <div class="brand-name">
                <span class="brand-sige">sigame</span><span class="brand-tec">TEC</span>
            </div>
            <div class="brand-tagline">
                Sistema integrado de gamificación para educación técnica. Aprende, compite y progresa.
            </div>

            <div class="stats-row">
                <div class="stat-pill"><i class="fas fa-users"></i> Estudiantes activos</div>
                <div class="stat-pill"><i class="fas fa-trophy"></i> Logros</div>
                <div class="stat-pill"><i class="fas fa-star"></i> Niveles</div>
            </div>
        </div>

    </div>

    <!-- ======== DERECHA ======== -->
    <div class="right">
        <div class="right-bg"></div>
        <div class="right-inner">
            <div class="form-header">
                <div class="welcome-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="form-title">Bienvenido de nuevo</div>
                <div class="form-subtitle">Ingresa tus credenciales para acceder al sistema</div>
            </div>

            <?php $flash = getFlash(); if ($flash): ?>
                <div class="msg msg-<?= $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'warning' ? 'warning' : 'success') ?>">
                    <i class="fas fa-<?= $flash['type'] === 'error' ? 'exclamation-circle' : ($flash['type'] === 'warning' ? 'exclamation-triangle' : 'check-circle') ?>"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>auth/iniciarSesion" method="POST">
                <?= csrfField() ?>

                <div class="field">
                    <label>Usuario <span id="userStatus"></span></label>
                    <div class="input-wrap">
                        <span class="ico"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" id="loginUsername" placeholder="Usuario, correo o DNI"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                        <button type="button" class="toggle-pass" id="togglePassword" title="Mostrar contraseña" tabindex="-1" style="display:none">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label>Contraseña</label>
                    <div class="input-wrap">
                        <span class="ico"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="loginPassword" placeholder="Tu contraseña" required>
                        <button type="button" class="toggle-pass" id="togglePasswordBtn" title="Mostrar contraseña" tabindex="-1">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="forgot-link">
                    <a href="<?= BASE_URL ?>auth/recuperarContrasena">
                        <i class="fas fa-key" style="margin-right:4px"></i>¿Olvidaste tu contraseña?
                    </a>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    <span>Ingresar</span>
                </button>

                <button type="button" class="btn btn-register" onclick="location.href='<?= BASE_URL ?>auth/registrar'">
                    <i class="fas fa-user-plus"></i>
                    <span>Crear Cuenta</span>
                </button>
            </form>

            <div class="alt-link">
                ¿No tienes cuenta? <a href="<?= BASE_URL ?>auth/registrar">Regístrate aquí</a>
            </div>
        </div>
    </div>
</div>

<script>
/* Flash auto-fade */
(function(){
    var m=document.querySelector('.msg');
    if(m) setTimeout(function(){ m.style.transition='all .4s'; m.style.opacity='0'; m.style.transform='translateY(-8px)'; setTimeout(function(){ m.remove(); },400); },4500);
})();
</script>
<script>
/* Toggle password */
(function(){
    var btn=document.getElementById('togglePasswordBtn');
    var inp=document.getElementById('loginPassword');
    if(!btn||!inp)return;
    btn.addEventListener('click',function(){
        var show=inp.type==='password';
        inp.type=show?'text':'password';
        btn.innerHTML=show?'<i class="far fa-eye-slash"></i>':'<i class="far fa-eye"></i>';
        btn.title=show?'Ocultar contraseña':'Mostrar contraseña';
        inp.focus();
    });
})();
</script>
<script>
/* Validacion AJAX usuario */
(function(){
    var userInput=document.getElementById('loginUsername');
    var status=document.getElementById('userStatus');
    if(!userInput||!status)return;
    var timer=null, baseUrl='<?= BASE_URL ?>';
    userInput.addEventListener('input',function(){
        clearTimeout(timer);
        var val=this.value.trim();
        if(val.length<3){ status.style.display='none'; return; }
        timer=setTimeout(function(){
            var fd=new FormData(); fd.append('username',val);
            fetch(baseUrl+'auth/verificarUsuario',{method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(d){
                if(d.exists===true){
                    status.innerHTML='<i class="fas fa-check-circle" style="color:#16a34a"></i>';
                    status.title='Usuario encontrado';
                    status.style.display='inline';
                }else if(d.exists===false){
                    status.innerHTML='<i class="fas fa-times-circle" style="color:#dc2626"></i>';
                    status.title='Usuario no encontrado';
                    status.style.display='inline';
                }else{ status.style.display='none'; }
            }).catch(function(){ status.style.display='none'; });
        },500);
    });
})();
</script>
</body>
</html>
