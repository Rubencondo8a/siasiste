<?php

declare(strict_types=1);

class AuthController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function iniciarSesion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isLoggedIn()) {
            switch ($_SESSION['user_rol'] ?? '') {
                case 'admin':
                    redirect(BASE_URL . 'admin/inicio');
                    break;
                case 'docente':
                    redirect(BASE_URL . 'docente/inicio');
                    break;
                    case 'estudiante':
                        $_SESSION['_bienvenida_mago'] = true;
                        redirect(BASE_URL . 'estudiante/inicio');
                        break;
                default:
                    redirect(BASE_URL . 'auth/iniciarSesion');
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!validateCsrfToken($_POST['_csrf_token'] ?? null)) {
                setFlash('error', 'Solicitud inválida. Intente de nuevo.');
                redirect(BASE_URL . 'auth/iniciarSesion');
                return;
            }

            $username = sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                setFlash('error', 'Por favor complete todos los campos.');
                redirect(BASE_URL . 'auth/iniciarSesion');
                return;
            }

            try {
                $stmt = $this->db->prepare('SELECT id, username, apellidos, nombres, password, rol, estado, debe_cambiar_password, password_expira, foto, login_attempts, lockout_until FROM usuarios WHERE username = :login OR dni = :login_dni OR email = :login_email LIMIT 1');
                $stmt->execute([':login' => $username, ':login_dni' => $username, ':login_email' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    logError('Login fallido - usuario no existe', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                    setFlash('error', 'Usuario o contraseña incorrectos.');
                    redirect(BASE_URL . 'auth/iniciarSesion');
                    return;
                }

                if (isUserLocked($this->db, (int) $user['id'])) {
                    setFlash('error', 'Demasiados intentos fallidos. Espere 15 minutos o solicite al administrador que lo desbloquee.');
                    redirect(BASE_URL . 'auth/iniciarSesion');
                    return;
                }

                if (!$user['estado']) {
                    logError('Login bloqueado', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                    setFlash('error', 'Su cuenta está desactivada. Contacte al administrador.');
                    redirect(BASE_URL . 'auth/iniciarSesion');
                    return;
                }

                if (!password_verify($password, $user['password'])) {
                    incrementUserLoginAttempt($this->db, (int) $user['id']);
                    logError('Login fallido', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                    setFlash('error', 'Usuario o contraseña incorrectos.');
                    redirect(BASE_URL . 'auth/iniciarSesion');
                    return;
                }

                session_regenerate_id(true);
                resetLoginAttempts();
                resetUserLoginAttempts($this->db, (int) $user['id']);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_rol'] = $user['rol'];
                $_SESSION['user_nombre'] = trim(($user['apellidos'] ?? '') . ' ' . ($user['nombres'] ?? ''));
                $_SESSION['user_nombres'] = $user['nombres'] ?? '';
                $_SESSION['user_apellidos'] = $user['apellidos'] ?? '';
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_foto'] = $user['foto'] ?? null;
                $_SESSION['user_debe_cambiar_password'] = (int) ($user['debe_cambiar_password'] ?? 0) === 1;

                if ($_SESSION['user_debe_cambiar_password']) {
                    $expira = $user['password_expira'] ?? null;
                    if ($expira !== null && strtotime($expira) < time()) {
                        setFlash('warning', 'Su contraseña temporal ha expirado. Por seguridad, debe cambiarla ahora.');
                    } else {
                        setFlash('warning', 'Por seguridad, debes cambiar tu contraseña dentro de los próximos 30 minutos.');
                    }
                    redirect(BASE_URL . 'auth/cambiarContrasena');
                    return;
                }

                switch ($user['rol']) {
                    case 'admin':
                        redirect(BASE_URL . 'admin/inicio');
                        break;
                    case 'docente':
                        redirect(BASE_URL . 'docente/inicio');
                        break;
                    case 'estudiante':
                        redirect(BASE_URL . 'estudiante/inicio');
                        break;
                    default:
                        redirect(BASE_URL . 'auth/iniciarSesion');
                }
            } catch (PDOException $e) {
                setFlash('error', 'Error al iniciar sesión. Intente de nuevo.');
                redirect(BASE_URL . 'auth/iniciarSesion');
            }
            return;
        }

        require __DIR__ . '/../Views/auth/login.php';
    }

    public function cambiarContrasena(): void
    {
        if (empty($_SESSION['user_id'])) {
            redirect(BASE_URL . 'auth/iniciarSesion');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!validateCsrfToken($_POST['_csrf_token'] ?? null)) {
                setFlash('error', 'Solicitud inválida. Intente de nuevo.');
                redirect(BASE_URL . 'auth/cambiarContrasena');
                return;
            }

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword     = $_POST['new_password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($passwordConfirm)) {
                setFlash('error', 'Por favor complete todos los campos.');
                redirect(BASE_URL . 'auth/cambiarContrasena');
                return;
            }

            if (!passwordComplejidadOk($newPassword)) {
                setFlash('error', 'La contraseña debe tener al menos 8 caracteres, con al menos una mayúscula, una minúscula y un número.');
                redirect(BASE_URL . 'auth/cambiarContrasena');
                return;
            }

            if ($newPassword !== $passwordConfirm) {
                setFlash('error', 'Las contraseñas no coinciden.');
                redirect(BASE_URL . 'auth/cambiarContrasena');
                return;
            }

            try {
                $stmt = $this->db->prepare('SELECT password, debe_cambiar_password, password_expira FROM usuarios WHERE id = :id LIMIT 1');
                $stmt->execute([':id' => $_SESSION['user_id']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$row || !password_verify($currentPassword, $row['password'])) {
                    setFlash('error', 'La contraseña actual es incorrecta.');
                    redirect(BASE_URL . 'auth/cambiarContrasena');
                    return;
                }

                if (passwordYaUsada($this->db, (int) $_SESSION['user_id'], $newPassword)) {
                    setFlash('error', 'La nueva contraseña ya fue utilizada anteriormente. Elija una contraseña diferente.');
                    redirect(BASE_URL . 'auth/cambiarContrasena');
                    return;
                }

                $expira = $row['password_expira'] ?? null;
                if ((int) ($row['debe_cambiar_password'] ?? 0) === 1 && $expira !== null && strtotime($expira) < time()) {
                    setFlash('warning', 'Su contraseña anterior ha expirado. Se guardará la nueva contraseña.');
                }

                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $this->db->beginTransaction();
                guardarPasswordHistorial($this->db, (int) $_SESSION['user_id'], $row['password']);
                $this->db->prepare('UPDATE usuarios SET password = :password, debe_cambiar_password = 0, password_expira = NULL WHERE id = :id')
                    ->execute([':password' => $hash, ':id' => $_SESSION['user_id']]);
                $this->db->commit();

                $_SESSION['user_debe_cambiar_password'] = false;
                $rol = $_SESSION['user_rol'] ?? '';

                setFlash('success', 'Contraseña actualizada correctamente.');
                switch ($rol) {
                    case 'admin':
                        redirect(BASE_URL . 'admin/inicio');
                        break;
                    case 'docente':
                        redirect(BASE_URL . 'docente/inicio');
                        break;
                    case 'estudiante':
                        redirect(BASE_URL . 'estudiante/inicio');
                        break;
                    default:
                        redirect(BASE_URL . 'auth/iniciarSesion');
                }
            } catch (PDOException $e) {
                setFlash('error', 'Error al actualizar la contraseña. Intente de nuevo.');
                redirect(BASE_URL . 'auth/cambiarContrasena');
            }
            return;
        }

        require __DIR__ . '/../Views/auth/cambiar_contrasena.php';
    }

    public function cerrarSesion(): void
    {
        session_unset();
        session_destroy();
        session_start();
        setFlash('success', 'Sesión cerrada correctamente.');
        redirect(BASE_URL . 'auth/iniciarSesion');
    }

    public function verificarUsuario(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['exists' => false]);
            return;
        }

        $input = strtolower(trim(sanitize($_POST['username'] ?? '')));
        if (strlen($input) < 3) {
            echo json_encode(['exists' => null]);
            return;
        }

        try {
            $stmt = $this->db->prepare('SELECT id FROM usuarios WHERE (username = :u OR dni = :d OR email = :e) AND estado = 1 LIMIT 1');
            $stmt->execute([':u' => $input, ':d' => $input, ':e' => $input]);
            $found = $stmt->fetch() !== false;
            echo json_encode(['exists' => $found]);
        } catch (PDOException $e) {
            echo json_encode(['exists' => null]);
        }
    }

    public function registrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!validateCsrfToken($_POST['_csrf_token'] ?? null)) {
                setFlash('error', 'Solicitud inválida. Intente de nuevo.');
                redirect(BASE_URL . 'auth/registrar');
                return;
            }

            $nombres        = formatNombres($_POST['nombres'] ?? '');
            $apellidos      = formatApellidos($_POST['apellidos'] ?? '');
            $dni            = sanitize($_POST['dni'] ?? '');
            $email          = sanitize($_POST['email'] ?? '');
            $carrera_id     = (int) ($_POST['carrera_id'] ?? 0);
            $username       = sanitize($_POST['username'] ?? '');
            $password       = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if ($nombres === '' || $apellidos === '' || $dni === '' || $email === '' || $carrera_id === 0 || $username === '' || $password === '') {
                setFlash('error', 'Por favor complete todos los campos.');
                redirect(BASE_URL . 'auth/registrar');
                return;
            }

            if ($password !== $passwordConfirm) {
                setFlash('error', 'Las contraseñas no coinciden.');
                redirect(BASE_URL . 'auth/registrar');
                return;
            }

            if (!passwordComplejidadOk($password)) {
                setFlash('error', 'La contraseña debe tener al menos 8 caracteres, con al menos una mayúscula, una minúscula y un número.');
                redirect(BASE_URL . 'auth/registrar');
                return;
            }

            try {
                $stmt = $this->db->prepare('SELECT id FROM usuarios WHERE dni = :dni LIMIT 1');
                $stmt->execute([':dni' => $dni]);
                if ($stmt->fetch()) {
                    setFlash('error', 'El DNI ingresado ya está registrado.');
                    redirect(BASE_URL . 'auth/registrar');
                    return;
                }

                $stmt = $this->db->prepare('SELECT id FROM usuarios WHERE username = :username OR email = :email LIMIT 1');
                $stmt->execute([':username' => $username, ':email' => $email]);
                if ($stmt->fetch()) {
                    setFlash('error', 'El usuario o correo ya está registrado.');
                    redirect(BASE_URL . 'auth/registrar');
                    return;
                }

                $nombre = trim($nombres . ' ' . $apellidos);
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $this->db->prepare('
                    INSERT INTO usuarios (username, password, nombre, apellidos, nombres, email, dni, rol, estado, carrera_id)
                    VALUES (:username, :password, :nombre, :apellidos, :nombres, :email, :dni, :rol, 1, :carrera_id)
                ');
                $stmt->execute([
                    ':username'   => $username,
                    ':password'   => $hash,
                    ':nombre'     => $nombre,
                    ':apellidos'  => $apellidos,
                    ':nombres'    => $nombres,
                    ':email'      => $email,
                    ':dni'        => $dni,
                    ':rol'        => 'estudiante',
                    ':carrera_id' => $carrera_id,
                ]);

                setFlash('success', 'Cuenta creada correctamente. Ahora puedes iniciar sesión.');
                redirect(BASE_URL . 'auth/iniciarSesion');
            } catch (PDOException $e) {
                setFlash('error', 'Error al registrar. Intente de nuevo.');
                redirect(BASE_URL . 'auth/registrar');
            }
            return;
        }

        try {
            $stmt = $this->db->query('SELECT id, nombre FROM carreras WHERE estado = 1 ORDER BY nombre ASC');
            $carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $carreras = [];
        }

        require __DIR__ . '/../Views/auth/register.php';
    }

    // ==================== RECUPERACIÓN DE CONTRASEÑA ====================

    public function recuperarContrasena(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!validateCsrfToken($_POST['_csrf_token'] ?? null)) {
                setFlash('error', 'Solicitud inválida. Intente de nuevo.');
                redirect(BASE_URL . 'auth/recuperarContrasena');
                return;
            }

            $email = sanitize($_POST['email'] ?? '');

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                setFlash('error', 'Ingrese un correo electrónico válido.');
                redirect(BASE_URL . 'auth/recuperarContrasena');
                return;
            }

            try {
                $stmt = $this->db->prepare('SELECT id, username, apellidos, nombres, email, estado FROM usuarios WHERE email = :email AND estado = 1 LIMIT 1');
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    setFlash('warning', 'Si el correo está registrado y activo, recibirás un código de recuperación.');
                    redirect(BASE_URL . 'auth/recuperarContrasena');
                    return;
                }

                $this->db->prepare('UPDATE password_resets SET usado = 1 WHERE user_id = :user_id AND usado = 0')
                    ->execute([':user_id' => $user['id']]);

                $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expira = date('Y-m-d H:i:s', time() + 900);

                $stmt = $this->db->prepare('INSERT INTO password_resets (user_id, email, codigo, expira) VALUES (:user_id, :email, :codigo, :expira)');
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':email'   => $user['email'],
                    ':codigo'  => $codigo,
                    ':expira'  => $expira,
                ]);

                $mailer = new Mailer($this->db);
                if ($mailer->isConfigured()) {
                    $nombre = trim(($user['apellidos'] ?? '') . ' ' . ($user['nombres'] ?? ''));
                    $mailer->sendPasswordResetCode($user['email'], $nombre, $codigo);
                } else {
                    logError('SMTP no configurado, código de recuperación generado', ['user_id' => $user['id']]);
                }

                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_email'] = $user['email'];

                setFlash('success', 'Te enviamos un código de 6 dígitos a tu correo. Es válido por 15 minutos.');
                redirect(BASE_URL . 'auth/verificarCodigo');
            } catch (PDOException $e) {
                setFlash('error', 'Error al procesar la solicitud. Intente de nuevo.');
                redirect(BASE_URL . 'auth/recuperarContrasena');
            }
            return;
        }

        require __DIR__ . '/../Views/auth/recuperar_contrasena.php';
    }

    public function verificarCodigo(): void
    {
        if (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_email'])) {
            redirect(BASE_URL . 'auth/recuperarContrasena');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!validateCsrfToken($_POST['_csrf_token'] ?? null)) {
                setFlash('error', 'Solicitud inválida. Intente de nuevo.');
                redirect(BASE_URL . 'auth/verificarCodigo');
                return;
            }

            $codigo        = sanitize($_POST['codigo'] ?? '');
            $newPassword   = $_POST['new_password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if ($codigo === '' || $newPassword === '' || $passwordConfirm === '') {
                setFlash('error', 'Complete todos los campos.');
                redirect(BASE_URL . 'auth/verificarCodigo');
                return;
            }

            if ($newPassword !== $passwordConfirm) {
                setFlash('error', 'Las contraseñas no coinciden.');
                redirect(BASE_URL . 'auth/verificarCodigo');
                return;
            }

            if (!passwordComplejidadOk($newPassword)) {
                setFlash('error', 'La contraseña debe tener al menos 8 caracteres, con al menos una mayúscula, una minúscula y un número.');
                redirect(BASE_URL . 'auth/verificarCodigo');
                return;
            }

            try {
                $stmt = $this->db->prepare('
                    SELECT pr.id, pr.user_id, pr.email, pr.expira
                    FROM password_resets pr
                    WHERE pr.user_id = :user_id AND pr.email = :email AND pr.codigo = :codigo AND pr.usado = 0
                    ORDER BY pr.id DESC LIMIT 1
                ');
                $stmt->execute([
                    ':user_id' => $_SESSION['reset_user_id'],
                    ':email'   => $_SESSION['reset_email'],
                    ':codigo'  => $codigo,
                ]);
                $reset = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$reset) {
                    setFlash('error', 'Código incorrecto. Verifique e intente de nuevo.');
                    redirect(BASE_URL . 'auth/verificarCodigo');
                    return;
                }

                if (strtotime($reset['expira']) < time()) {
                    setFlash('error', 'El código ha expirado. Solicite uno nuevo.');
                    unset($_SESSION['reset_user_id'], $_SESSION['reset_email']);
                    redirect(BASE_URL . 'auth/recuperarContrasena');
                    return;
                }

                if (passwordYaUsada($this->db, (int) $reset['user_id'], $newPassword)) {
                    setFlash('error', 'La nueva contraseña ya fue utilizada anteriormente. Elija una contraseña diferente.');
                    redirect(BASE_URL . 'auth/verificarCodigo');
                    return;
                }

                $hash = password_hash($newPassword, PASSWORD_DEFAULT);

                $this->db->beginTransaction();
                $stmtHash = $this->db->prepare('SELECT password FROM usuarios WHERE id = :id LIMIT 1');
                $stmtHash->execute([':id' => $reset['user_id']]);
                $hashActual = $stmtHash->fetchColumn();
                if (is_string($hashActual) && $hashActual !== '') {
                    guardarPasswordHistorial($this->db, (int) $reset['user_id'], $hashActual);
                }
                $this->db->prepare('UPDATE usuarios SET password = :password, debe_cambiar_password = 0, password_expira = NULL WHERE id = :id')
                    ->execute([':password' => $hash, ':id' => $reset['user_id']]);
                $this->db->prepare('UPDATE password_resets SET usado = 1 WHERE id = :id')
                    ->execute([':id' => $reset['id']]);
                $this->db->commit();

                unset($_SESSION['reset_user_id'], $_SESSION['reset_email']);

                setFlash('success', 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.');
                redirect(BASE_URL . 'auth/iniciarSesion');
            } catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                setFlash('error', 'Error al actualizar la contraseña. Intente de nuevo.');
                redirect(BASE_URL . 'auth/verificarCodigo');
            }
            return;
        }

        require __DIR__ . '/../Views/auth/verificar_codigo.php';
    }
}
