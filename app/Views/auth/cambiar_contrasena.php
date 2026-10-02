<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Sigametec</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; min-height: 100vh; display: flex; background: #f5f7fb; }

        .container { display: flex; width: 100%; min-height: 100vh; }

        /* --- Left Panel (40%) --- */
        .left-panel {
            width: 40%; background: #4E37E6;
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden; padding: 40px;
        }
        .left-panel::before {
            content: ''; position: absolute; top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: rgba(255,255,255,.02);
        }
        .left-content { position: relative; z-index: 1; text-align: center; max-width: 340px; }
        .rocket-icon {
            width: 90px; height: 90px; margin: 0 auto 24px;
            background: rgba(255,255,255,.12);
            backdrop-filter: blur(8px); border-radius: 24px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 16px 40px rgba(0,0,0,.15);
            animation: rocket-float 3s ease-in-out infinite;
        }
        @keyframes rocket-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .rocket-icon i { font-size: 2.5rem; color: #fff; }
        .app-name { font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 8px; letter-spacing: -.5px; }
        .app-tagline { font-size: .95rem; color: rgba(255,255,255,.6); font-weight: 300; line-height: 1.6; }

        /* --- Wavy divider --- */
        .wavy-divider {
            position: absolute; right: -1px; top: 0; bottom: 0; width: 120px;
            pointer-events: none; z-index: 2;
        }
        .wavy-divider svg { width: 100%; height: 100%; fill: #fff; }

        /* --- Right Panel (60%) --- */
        .right-panel {
            width: 60%; display: flex; align-items: center; justify-content: center;
            background: #fff; padding: 40px; position: relative;
        }
        .right-inner { width: 100%; max-width: 420px; }

        .form-title { font-size: 1.75rem; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
        .form-subtitle { font-size: .9rem; color: #8e8ea0; margin-bottom: 32px; font-weight: 400; }

        .security-badge {
            display: flex; align-items: center; gap: 12px;
            background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
            border-radius: 14px; padding: 12px 16px; margin-bottom: 24px; font-size: .85rem;
        }
        .security-badge i { font-size: 1.2rem; }

        /* --- Inputs --- */
        .input-group-modern { position: relative; margin-bottom: 24px; }
        .input-group-modern input {
            width: 100%; padding: 14px 40px 14px 0; font-size: .95rem; font-family: 'Poppins', sans-serif;
            border: none; border-bottom: 2px solid #e0e0e0; background: transparent;
            outline: none; transition: border-color .3s ease; color: #1a1a2e;
        }
        .input-group-modern input:focus { border-bottom-color: #4E37E6; }
        .input-group-modern input::placeholder { color: #b0b0c0; font-weight: 300; }
        .input-group-modern .input-icon {
            position: absolute; right: 0; top: 50%; transform: translateY(-50%);
            color: #c0c0d0; font-size: 1rem; transition: color .3s ease;
        }
        .input-group-modern input:focus ~ .input-icon { color: #4E37E6; }
        .input-group-modern label {
            display: block; font-size: .75rem; font-weight: 600; color: #6b6b80;
            text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;
        }
        .toggle-pass {
            position: absolute; right: 0; top: 50%; transform: translateY(-50%);
            border: none; background: transparent; color: #c0c0d0; cursor: pointer;
            font-size: 1rem; padding: 6px; transition: color .3s ease; z-index: 2;
        }
        .toggle-pass:hover { color: #4E37E6; }

        .pass-rules {
            background: #f8f9ff; border: 1px solid #e5e7f5; border-radius: 12px;
            padding: 12px 16px; margin: -8px 0 22px; font-size: .8rem; color: #6b6b80;
        }
        .pass-rules i { color: #4E37E6; margin-bottom: 6px; display: inline-block; }
        .pass-rules ul { margin: 0; padding-left: 18px; line-height: 1.7; }

        /* --- Buttons --- */
        .btn-modern {
            display: block; width: 100%; padding: 14px; font-size: 1rem; font-weight: 600;
            font-family: 'Poppins', sans-serif; border: none; border-radius: 16px;
            cursor: pointer; transition: all .3s ease; letter-spacing: .3px;
        }
        .btn-primary-modern {
            background: #4E37E6; color: #fff; margin-bottom: 14px;
            box-shadow: 0 8px 24px rgba(78,55,230,.3);
        }
        .btn-primary-modern:hover { background: #3d2bc4; transform: translateY(-2px); box-shadow: 0 12px 32px rgba(78,55,230,.4); }
        .btn-primary-modern:active { transform: translateY(0); }

        /* --- Message --- */
        .msg { padding: 12px 16px; border-radius: 12px; font-size: .85rem; margin-bottom: 20px; }
        .msg-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .msg-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .msg-warning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }

        /* --- Responsive --- */
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .left-panel { width: 100%; padding: 48px 24px 60px; min-height: 260px; }
            .wavy-divider { display: none; }
            .right-panel { width: 100%; padding: 36px 24px; }
            .rocket-icon { width: 70px; height: 70px; }
            .rocket-icon i { font-size: 2rem; }
            .app-name { font-size: 1.6rem; }
            .form-title { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Left Panel -->
    <div class="left-panel">
        <div class="left-content">
            <div class="rocket-icon">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div class="app-name">Sigametec</div>
            <div class="app-tagline">Por seguridad, debes actualizar tu contraseña antes de acceder a la plataforma.</div>
        </div>
        <div class="wavy-divider">
            <svg viewBox="0 0 120 800" preserveAspectRatio="none">
                <path d="M0,0 C40,100 20,200 60,300 C100,400 40,500 80,600 C120,700 60,750 120,800 L0,800 Z"/>
            </svg>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="right-panel">
        <div class="right-inner">
            <div class="form-title">Cambiar Contraseña</div>
            <div class="form-subtitle">Establece una nueva contraseña para tu cuenta</div>

            <div class="security-badge">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Tu contraseña es temporal (12345678). Tienes 30 minutos para cambiarla, de lo contrario deberás solicitar un nuevo restablecimiento.</span>
            </div>

            <?php
            $flash = getFlash();
            if ($flash): ?>
                <div class="msg msg-<?= $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'success' ? 'success' : 'warning') ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>auth/cambiarContrasena" method="POST">
                <?= csrfField() ?>

                <div class="input-group-modern">
                    <label>Contraseña actual</label>
                    <input type="password" name="current_password" placeholder="Tu contraseña temporal" required autofocus>
                    <button type="button" class="toggle-pass" onclick="togglePass(this)" title="Mostrar/ocultar contraseña"><i class="fas fa-eye"></i></button>
                </div>

                <div class="input-group-modern">
                    <label>Nueva contraseña</label>
                    <input type="password" name="new_password" placeholder="Mínimo 8 caracteres" required>
                    <button type="button" class="toggle-pass" onclick="togglePass(this)" title="Mostrar/ocultar contraseña"><i class="fas fa-eye"></i></button>
                </div>

                <div class="pass-rules">
                    <i class="fas fa-shield-halved"></i>
                    <ul>
                        <li>Al menos <strong>8 caracteres</strong>.</li>
                        <li>Al menos una <strong>mayúscula</strong> (A-Z).</li>
                        <li>Al menos una <strong>minúscula</strong> (a-z).</li>
                        <li>Al menos un <strong>número</strong> (0-9).</li>
                        <li>No puede repetir <strong>contraseñas usadas anteriormente</strong>.</li>
                    </ul>
                </div>

                <div class="input-group-modern">
                    <label>Confirmar nueva contraseña</label>
                    <input type="password" name="password_confirm" placeholder="Repite la nueva contraseña" required>
                    <button type="button" class="toggle-pass" onclick="togglePass(this)" title="Mostrar/ocultar contraseña"><i class="fas fa-eye"></i></button>
                </div>

                <button type="submit" class="btn-modern btn-primary-modern">
                    <i class="fas fa-save" style="margin-right:8px"></i>Actualizar Contraseña
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
    function togglePass(btn) {
        var input = btn.parentElement.querySelector('input');
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.querySelector('i').className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
    }
</script>
</body>
</html>
