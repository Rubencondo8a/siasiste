const API_BASE = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/public/index.php';

function isLoggedIn() {
    return document.cookie.split(';').some(c => c.trim().startsWith('PHPSESSID='));
}

function togglePass() {
    const pass = document.getElementById('loginPass');
    const icon = document.getElementById('eyeIcon');
    if (pass.type === 'password') {
        pass.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        pass.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}

async function handleLogin(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const usuario = document.getElementById('loginUser').value.trim();
    const contrasena = document.getElementById('loginPass').value;

    if (!usuario || !contrasena) {
        Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Ingrese usuario y contraseña' });
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Ingresando...';

    try {
        const res = await fetch(`${API_BASE}?route=login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ usuario, contrasena })
        });
        const data = await res.json();

        if (data.success) {
            window.location.href = data.redirect || API_BASE + '?route=dashboard';
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Credenciales incorrectas' });
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar Sesión';
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo conectar con el servidor' });
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar Sesión';
    }
}
