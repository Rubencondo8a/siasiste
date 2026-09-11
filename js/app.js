/**
 * SIASISTE - Login
 * Vanilla JS | Sin frameworks
 * Mantiene funcionalidad actual: POST usuario/contrasena -> index.php?route=login
 */

(function () {
  'use strict';

  const form = document.getElementById('loginForm');
  const userInput = document.getElementById('loginUser');
  const passInput = document.getElementById('loginPass');
  const toggleBtn = document.getElementById('passwordToggle');
  const submitBtn = document.getElementById('loginButton');
  const btnText = submitBtn ? submitBtn.querySelector('.btn-text') : null;
  const formMessage = document.getElementById('formMessage');
  const loginError = document.getElementById('loginError');
  const forgotLink = document.getElementById('forgotLink');
  const secondaryBtn = document.getElementById('secondaryButton');
  const rememberCheck = document.getElementById('rememberMe');

  let isLoading = false;

  // --- recordar usuario (localStorage) ---
  try {
    const saved = localStorage.getItem('siasiste_remember_user');
    if (saved && userInput) {
      userInput.value = saved;
      if (rememberCheck) rememberCheck.checked = true;
    }
  } catch (e) {}

  // --- toggle password ---
  if (toggleBtn && passInput) {
    toggleBtn.addEventListener('click', function () {
      const isPassword = passInput.type === 'password';
      passInput.type = isPassword ? 'text' : 'password';
      toggleBtn.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
      toggleBtn.setAttribute('aria-pressed', String(isPassword));
      // cambiar icono
      const eyeOpen = toggleBtn.querySelector('.icon-eye-open');
      const eyeClose = toggleBtn.querySelector('.icon-eye-close');
      if (eyeOpen && eyeClose) {
        eyeOpen.style.display = isPassword ? 'none' : 'block';
        eyeClose.style.display = isPassword ? 'block' : 'none';
      }
      passInput.focus();
    });
  }

  // --- helpers ---
  function setFieldError(input, message) {
    const group = input.closest('.form-group');
    const errorEl = document.getElementById('error-' + input.id.replace('login', '').toLowerCase()) ||
                    (group ? group.querySelector('.field-error') : null);
    if (group) group.classList.toggle('has-error', Boolean(message));
    if (errorEl) errorEl.textContent = message || '';
    if (message) input.setAttribute('aria-invalid', 'true');
    else input.removeAttribute('aria-invalid');
  }

  function clearErrors() {
    setFieldError(userInput, '');
    setFieldError(passInput, '');
    hideMessage();
    if (loginError) loginError.innerHTML = '';
  }

  function showMessage(type, text) {
    if (!formMessage) return;
    formMessage.className = 'form-message show ' + type;
    const icon = type === 'success'
      ? '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M13.5 4.5L6.8 11.2L3.5 7.9"/></svg>'
      : '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="8" cy="8" r="6.5"/><path d="M8 5.5V8.5"/><circle cx="8" cy="11.2" r="0.9" fill="currentColor" stroke="none"/></svg>';
    formMessage.innerHTML = icon + '<span>' + escapeHtml(text) + '</span>';
  }

  function hideMessage() {
    if (!formMessage) return;
    formMessage.className = 'form-message';
    formMessage.innerHTML = '';
  }

  function showLegacyError(text) {
    if (loginError) {
      loginError.innerHTML = '<div class="alert alert-danger" role="alert" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:10px;padding:10px 12px;font-size:13px;">' + escapeHtml(text) + '</div>';
    }
  }

  function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function validateEmailLike(value) {
    // si contiene @ validar formato básico
    if (value.indexOf('@') === -1) return true;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  function validate() {
    let valid = true;
    const user = userInput.value.trim();
    const pass = passInput.value;

    if (!user) {
      setFieldError(userInput, 'Ingresa tu usuario o correo electrónico.');
      valid = false;
    } else if (!validateEmailLike(user)) {
      setFieldError(userInput, 'El correo electrónico no tiene un formato válido.');
      valid = false;
    } else {
      setFieldError(userInput, '');
    }

    if (!pass) {
      setFieldError(passInput, 'Ingresa tu contraseña.');
      valid = false;
    } else if (pass.length < 3) {
      setFieldError(passInput, 'La contraseña debe tener al menos 3 caracteres.');
      valid = false;
    } else {
      setFieldError(passInput, '');
    }

    if (!valid) {
      const firstError = form.querySelector('.has-error input');
      if (firstError) firstError.focus();
    }
    return valid;
  }

  // limpiar error al escribir
  [userInput, passInput].forEach(function (el) {
    if (!el) return;
    el.addEventListener('input', function () {
      const group = el.closest('.form-group');
      if (group && group.classList.contains('has-error')) {
        setFieldError(el, '');
      }
      hideMessage();
    });
  });

  function setLoading(loading) {
    isLoading = loading;
    if (!submitBtn) return;
    submitBtn.disabled = loading;
    submitBtn.classList.toggle('loading', loading);
    submitBtn.setAttribute('aria-busy', String(loading));
    if (btnText) btnText.textContent = loading ? 'Verificando...' : 'Iniciar sesión';
  }

  /**
   * Función preparada para conectar con API/backend.
   * Sustituir la lógica interna por fetch real cuando exista endpoint.
   * @param {string} username
   * @param {string} password
   * @returns {Promise<{success:boolean, redirect?:string, error?:string}>}
   */
  async function loginUser(username, password) {
    const fd = new FormData();
    fd.append('usuario', username);
    fd.append('contrasena', password);

    const res = await fetch(form.action, {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    // El backend puede devolver JSON o redirección
    const ct = res.headers.get('content-type') || '';
    if (ct.includes('application/json')) {
      return await res.json();
    }
    // fallback: si no es JSON, intentar parsear texto
    const text = await res.text();
    try { return JSON.parse(text); } catch (e) {
      if (res.ok) return { success: true, redirect: 'index.php?route=dashboard' };
      return { success: false, error: 'Respuesta inesperada del servidor' };
    }
  }

  // --- submit ---
  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      if (isLoading) return;

      clearErrors();

      if (!validate()) {
        showMessage('error', 'Revisa los campos marcados e intenta nuevamente.');
        return;
      }

      const username = userInput.value.trim();
      const password = passInput.value;

      setLoading(true);

      try {
        const data = await loginUser(username, password);

        if (data.success) {
          // guardar recordar
          try {
            if (rememberCheck && rememberCheck.checked) localStorage.setItem('siasiste_remember_user', username);
            else localStorage.removeItem('siasiste_remember_user');
          } catch (e) {}

          showMessage('success', '¡Bienvenido! Redirigiendo a SIASISTE...');
          // Simular breve espera como en el spec
          setTimeout(function () {
            window.location.href = data.redirect || 'index.php?route=dashboard';
          }, 700);
        } else {
          const msg = data.error || 'Credenciales incorrectas. Verifica tu usuario y contraseña.';
          showMessage('error', msg);
          showLegacyError(msg);
          setLoading(false);
          // manejar rate limit
          if (String(msg).toLowerCase().includes('demasiados intentos')) {
            submitBtn.disabled = true;
            setTimeout(function(){ submitBtn.disabled = false; setLoading(false); }, 3000);
          }
        }
      } catch (err) {
        showMessage('error', 'Error de conexión. Verifica tu internet e intenta nuevamente.');
        showLegacyError('Error de conexión');
        setLoading(false);
      }
    });
  }

  // --- forgot ---
  if (forgotLink) {
    forgotLink.addEventListener('click', function (e) {
      e.preventDefault();
      showMessage('error', 'Recuperación de contraseña próximamente. Contacta al administrador.');
      // preparado para conectar con backend: window.location.href = 'index.php?route=forgot-password';
    });
  }
  if (secondaryBtn) {
    secondaryBtn.addEventListener('click', function () {
      showMessage('error', 'Este método estará disponible próximamente (Google / Microsoft SSO).');
    });
  }

  // exponer para uso externo / tests
  window.loginUser = loginUser;
})();
