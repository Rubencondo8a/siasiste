<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sigametec</title>
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
            position: sticky; top: 0; height: 100vh;
            overflow: hidden; padding: 40px;
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
        .right-inner { width: 100%; max-width: 460px; }

        .form-title { font-size: 1.75rem; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
        .form-subtitle { font-size: .9rem; color: #8e8ea0; margin-bottom: 28px; font-weight: 400; }

        /* --- Inputs --- */
        .form-row-modern { display: flex; gap: 16px; }
        .form-row-modern .input-group-modern { flex: 1; }
        .input-group-modern { position: relative; margin-bottom: 20px; }
        .input-group-modern input, .input-group-modern select {
            width: 100%; padding: 12px 40px 12px 0; font-size: .9rem; font-family: 'Poppins', sans-serif;
            border: none; border-bottom: 2px solid #e0e0e0; background: transparent;
            outline: none; transition: border-color .3s ease; color: #1a1a2e;
            -webkit-appearance: none; appearance: none; border-radius: 0;
        }
        .input-group-modern select { cursor: pointer; }
        .input-group-modern input:focus, .input-group-modern select:focus { border-bottom-color: #4E37E6; }
        .input-group-modern input::placeholder { color: #b0b0c0; font-weight: 300; }
        .input-group-modern .input-icon {
            position: absolute; right: 0; bottom: 14px;
            color: #c0c0d0; font-size: .95rem; transition: color .3s ease;
        }
        .input-group-modern input:focus ~ .input-icon,
        .input-group-modern select:focus ~ .input-icon { color: #4E37E6; }
        .input-group-modern label {
            display: block; font-size: .7rem; font-weight: 600; color: #6b6b80;
            text-transform: uppercase; letter-spacing: .5px; margin-bottom: 2px;
        }

        .pass-rules {
            background: #f8f9ff; border: 1px solid #e5e7f5; border-radius: 12px;
            padding: 12px 16px; margin: -8px 0 20px; font-size: .8rem; color: #6b6b80;
        }
        .pass-rules i { color: #4E37E6; margin-bottom: 6px; display: inline-block; }
        .pass-rules ul { margin: 8px 0 0; padding-left: 0; line-height: 1.7; list-style: none; }
        .pass-rules li { display: flex; align-items: center; gap: 8px; }
        .rule-icon { width: 18px; text-align: center; }
        .rule-ok { color: #16a34a; }
        .rule-bad { color: #c0c0d0; }
        .strength-wrap { margin: -4px 0 18px; }
        .strength-label { font-size: .75rem; font-weight: 600; color: #6b6b80; }
        .strength-bar { display: flex; gap: 6px; margin-top: 6px; }
        .strength-bar span { height: 6px; flex: 1; border-radius: 3px; background: #e5e7f5; transition: background .3s; }
        .match-msg { font-size: .78rem; margin-top: 4px; display: none; }
        .match-msg.show { display: block; }
        .match-msg.ok { color: #16a34a; }
        .match-msg.error { color: #dc2626; }
        #form-notice { display: none; }
        .pass-status { font-size: .78rem; font-weight: 600; margin-top: 6px; display: none; }
        .pass-status.show { display: flex; align-items: center; gap: 6px; }
        .pass-status.ok { color: #16a34a; }
        .pass-status.error { color: #dc2626; }
        #password.valid { border-bottom-color: #16a34a; }
        #password.invalid { border-bottom-color: #dc2626; }
        .toggle-pw { cursor: pointer; }
        .toggle-pw:hover { color: #4E37E6; }

        /* --- Buttons --- */
        .btn-modern {
            display: block; width: 100%; padding: 14px; font-size: 1rem; font-weight: 600;
            font-family: 'Poppins', sans-serif; border: none; border-radius: 16px;
            cursor: pointer; transition: all .3s ease; letter-spacing: .3px;
        }
        .btn-primary-modern {
            background: #4E37E6; color: #fff;
            box-shadow: 0 8px 24px rgba(78,55,230,.3);
        }
        .btn-primary-modern:hover { background: #3d2bc4; transform: translateY(-2px); box-shadow: 0 12px 32px rgba(78,55,230,.4); }
        .btn-primary-modern:active { transform: translateY(0); }

        .login-link { text-align: center; margin-top: 18px; }
        .login-link a {
            color: #4E37E6; font-weight: 600; text-decoration: none;
            font-size: .9rem; transition: color .3s;
        }
        .login-link a:hover { color: #3d2bc4; }

        /* --- Message --- */
        .msg { padding: 12px 16px; border-radius: 12px; font-size: .85rem; margin-bottom: 18px; }
        .msg-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .msg-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        /* --- Responsive --- */
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .left-panel { width: 100%; position: relative; height: auto; padding: 40px 24px 50px; min-height: 240px; }
            .wavy-divider { display: none; }
            .right-panel { width: 100%; padding: 28px 20px; }
            .form-row-modern { flex-direction: column; gap: 0; }
            .rocket-icon { width: 65px; height: 65px; }
            .rocket-icon i { font-size: 1.8rem; }
            .app-name { font-size: 1.5rem; }
            .form-title { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Left Panel -->
    <div class="left-panel">
        <div class="left-content">
            <div class="rocket-icon">
                <i class="fas fa-rocket"></i>
            </div>
            <div class="app-name">Sigametec</div>
            <div class="app-tagline">Sistema integrado de gamificación para educación técnica.</div>
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
            <div class="form-title">Crear Cuenta</div>
            <div class="form-subtitle">Regístrate como estudiante para comenzar</div>

            <?php
            $flash = getFlash();
            if ($flash): ?>
                <div class="msg msg-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>auth/registrar" method="POST">
                <?= csrfField() ?>

                <div class="form-row-modern">
                    <div class="input-group-modern">
                        <label>Nombres</label>
                        <input type="text" name="nombres" placeholder="Ej: Juan Carlos"
                               value="<?= htmlspecialchars($_POST['nombres'] ?? '') ?>" required autofocus>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    <div class="input-group-modern">
                        <label>Apellidos</label>
                        <input type="text" name="apellidos" placeholder="Ej: Pérez García"
                               value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-row-modern">
                    <div class="input-group-modern">
                        <label>DNI</label>
                        <input type="text" name="dni" placeholder="Número de documento"
                               value="<?= htmlspecialchars($_POST['dni'] ?? '') ?>" required maxlength="15">
                        <i class="fas fa-id-card input-icon"></i>
                    </div>
                    <div class="input-group-modern">
                        <label>Correo</label>
                        <input type="email" name="email" placeholder="correo@ejemplo.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>

                <div class="input-group-modern">
                    <label>Carrera</label>
                    <select name="carrera_id" required>
                        <option value="">Seleccione su carrera</option>
                        <?php foreach ($carreras as $carrera): ?>
                            <option value="<?= $carrera['id'] ?>" <?= (isset($_POST['carrera_id']) && $_POST['carrera_id'] == $carrera['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($carrera['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-graduation-cap input-icon"></i>
                </div>

                <div class="form-row-modern">
                    <div class="input-group-modern">
                        <label>Usuario</label>
                        <input type="text" name="username" placeholder="Nombre de usuario"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        <i class="fas fa-user-tag input-icon"></i>
                    </div>
                    <div class="input-group-modern">
                        <label>Contraseña</label>
                        <input type="password" name="password" id="password" placeholder="Mín. 8 caracteres" required minlength="8" autocomplete="new-password">
                        <i class="fas fa-eye input-icon toggle-pw" role="button" title="Mostrar/Ocultar contraseña"></i>
                    </div>
                </div>

                <span class="pass-status" id="pass-status"></span>

                <div class="pass-rules">
                    <i class="fas fa-shield-halved"></i>
                    <span style="font-weight:600;">La contraseña debe cumplir:</span>
                    <ul>
                        <li><i class="fas fa-times rule-icon rule-bad" id="rule-length"></i> Al menos <strong>8 caracteres</strong>.</li>
                        <li><i class="fas fa-times rule-icon rule-bad" id="rule-upper"></i> Al menos una <strong>mayúscula</strong> (A-Z).</li>
                        <li><i class="fas fa-times rule-icon rule-bad" id="rule-lower"></i> Al menos una <strong>minúscula</strong> (a-z).</li>
                        <li><i class="fas fa-times rule-icon rule-bad" id="rule-num"></i> Al menos un <strong>número</strong> (0-9).</li>
                    </ul>
                </div>
                <div class="strength-wrap">
                    <span class="strength-label" id="strength-label">Seguridad: Muy débil</span>
                    <div class="strength-bar">
                        <span id="sb1"></span><span id="sb2"></span><span id="sb3"></span><span id="sb4"></span>
                    </div>
                </div>

                <div class="input-group-modern">
                    <label>Confirmar Contraseña</label>
                    <input type="password" name="password_confirm" id="password_confirm" placeholder="Repite la contraseña" required minlength="8">
                    <i class="fas fa-eye input-icon toggle-pw" role="button" title="Mostrar/Ocultar contraseña"></i>
                </div>
                <div class="match-msg" id="pass-match-msg"></div>

                <div class="msg msg-error" id="form-notice">La contraseña no cumple los requisitos o las contraseñas no coinciden. Revise las reglas.</div>

                <button type="submit" class="btn-modern btn-primary-modern">
                    <i class="fas fa-user-plus" style="margin-right:8px"></i>Crear Cuenta
                </button>
            </form>

            <div class="login-link">
                <a href="<?= BASE_URL ?>auth/iniciarSesion">¿Ya tienes cuenta? Inicia sesión</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(function () {
    var $pw = $('#password');
    var $cf = $('#password_confirm');

    function setRule($el, ok) {
        $el.attr('class', 'fas ' + (ok ? 'fa-check' : 'fa-times') + ' rule-icon ' + (ok ? 'rule-ok' : 'rule-bad'));
    }

    function check() {
        var v = $pw.val();
        var rules = [
            { el: $('#rule-length'), ok: v.length >= 8 },
            { el: $('#rule-upper'),  ok: /[A-Z]/.test(v) },
            { el: $('#rule-lower'),  ok: /[a-z]/.test(v) },
            { el: $('#rule-num'),    ok: /\d/.test(v) }
        ];
        var met = 0;
        $.each(rules, function (i, r) { setRule(r.el, r.ok); if (r.ok) met++; });

        var texts  = ['Muy débil', 'Débil', 'Media', 'Fuerte', 'Muy fuerte'];
        var colors = ['#dc2626', '#dc2626', '#d97706', '#16a34a', '#16a34a'];
        $('#strength-label').text('Seguridad: ' + texts[met]).css('color', colors[met]);
        $.each([$('#sb1'), $('#sb2'), $('#sb3'), $('#sb4')], function (i, b) {
            b.css('background', i < met ? colors[met] : '#e5e7f5');
        });

        var $st = $('#pass-status');
        if (v.length === 0) {
            $st.removeClass('show');
            $pw.removeClass('valid invalid');
        } else if (met === 4) {
            $st.addClass('show').removeClass('ok error').addClass('ok');
            $st.html('<i class="fas fa-check-circle"></i> Cumple los requisitos');
            $pw.removeClass('invalid').addClass('valid');
        } else {
            $st.addClass('show').removeClass('ok error').addClass('error');
            $st.html('<i class="fas fa-times-circle"></i> No cumple los requisitos (cumple ' + met + ' de 4)');
            $pw.removeClass('valid').addClass('invalid');
        }

        var $msg = $('#pass-match-msg');
        if ($cf.val().length > 0) {
            var ok = v.length > 0 && v === $cf.val();
            $msg.addClass('show').removeClass('ok error').addClass(ok ? 'ok' : 'error');
            $msg.text(ok ? 'Las contraseñas coinciden' : 'Las contraseñas no coinciden');
            $cf.css('border-bottom-color', ok ? '#16a34a' : '#dc2626');
        } else {
            $msg.removeClass('show');
            $cf.css('border-bottom-color', '');
        }
    }

    $pw.on('input', check);
    $cf.on('input', check);

    $(document).on('click', '.toggle-pw', function () {
        var $i = $(this);
        var $inp = $i.closest('.input-group-modern').find('input');
        var show = $inp.attr('type') === 'password';
        $inp.attr('type', show ? 'text' : 'password');
        $i.toggleClass('fa-eye fa-eye-slash');
        $inp.focus();
    });

    $('form').on('submit', function (e) {
        var v = $pw.val();
        var ok = v.length >= 8 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /\d/.test(v) && v === $cf.val();
        if (!ok) {
            e.preventDefault();
            check();
            $('#form-notice').show();
            $('html, body').animate({ scrollTop: $('#form-notice').offset().top - 20 }, 300);
            return false;
        }
        $('#form-notice').hide();
    });

    check();
});
</script>
</body>
</html>