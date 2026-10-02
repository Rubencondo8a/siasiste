<?php

declare(strict_types=1);

class AdminController
{
    private PDO $db;
    private Carrera $carreras;
    private Periodo $periodos;
    private Curso $cursos;
    private Ambiente $ambientes;
    private PlanEstudio $planes;
    private Rostro $rostros;
    private NivelMago $nivelesMago;
    private Beneficio $beneficios;
    private Canje $canjes;
    private Discusion $discusiones;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->carreras = new Carrera($db);
        $this->periodos = new Periodo($db);
        $this->cursos = new Curso($db);
        $this->ambientes = new Ambiente($db);
        $this->planes = new PlanEstudio($db);
        $this->rostros = new Rostro($db);
        $this->nivelesMago = new NivelMago($db);
        $this->beneficios = new Beneficio($db);
        $this->canjes = new Canje($db);
        $this->discusiones = new Discusion($db);
    }

    private function requireAdmin(): void
    {
        if (!isLoggedIn() || !isAdmin()) {
            setFlash('error', 'No tiene permiso para acceder a esta sección.');
            redirect(BASE_URL . 'auth/iniciarSesion');
        }
    }

    private function adminId(): int
    {
        return (int) $_SESSION['user_id'];
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function loadCarreras(): array
    {
        try {
            return $this->carreras->getAllActivas();
        } catch (PDOException $e) {
            return [];
        }
    }

    // ==================== DASHBOARD ====================

    public function inicio(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        try {
            $stats = $this->db->query("
                SELECT
                    SUM(rol = 'docente') AS totalDocentes,
                    SUM(rol = 'estudiante') AS totalEstudiantes,
                    (SELECT COUNT(*) FROM semanas) AS totalSemanas,
                    (SELECT COUNT(*) FROM periodos) AS totalPeriodos,
                    (SELECT COUNT(*) FROM cursos) AS totalCursos,
                    (SELECT COUNT(*) FROM carreras) AS totalCarreras
                FROM usuarios
            ")->fetch(PDO::FETCH_ASSOC);

            $totalDocentes    = (int) ($stats['totalDocentes'] ?? 0);
            $totalEstudiantes = (int) ($stats['totalEstudiantes'] ?? 0);
            $totalSemanas     = (int) ($stats['totalSemanas'] ?? 0);
            $totalPeriodos    = (int) ($stats['totalPeriodos'] ?? 0);
            $totalCursos      = (int) ($stats['totalCursos'] ?? 0);
            $totalCarreras    = (int) ($stats['totalCarreras'] ?? 0);

            $periodoActivo = $this->db->query("
                SELECT id, nombre, fecha_inicio, fecha_fin
                FROM periodos WHERE estado = 1 ORDER BY fecha_inicio DESC LIMIT 1
            ")->fetch(PDO::FETCH_ASSOC);

            $periodoActivo = $periodoActivo ?: null;

            if ($periodoActivo) {
                $cursosPorCarrera = $this->db->prepare("
                    SELECT c.nombre AS carrera, COUNT(cu.id) AS total
                    FROM carreras c
                    LEFT JOIN cursos cu ON cu.carrera_id = c.id AND cu.periodo_id = :periodo_id
                    GROUP BY c.id, c.nombre
                    ORDER BY total DESC, c.nombre ASC
                ");
                $cursosPorCarrera->execute([':periodo_id' => $periodoActivo['id']]);
                $cursosPorCarrera = $cursosPorCarrera->fetchAll(PDO::FETCH_ASSOC);

                $estudiantesPorCarrera = $this->db->prepare("
                    SELECT c.nombre AS carrera, COUNT(DISTINCT a.estudiante_id) AS total
                    FROM cursos cu
                    JOIN carreras c ON c.id = cu.carrera_id
                    LEFT JOIN asignaciones a ON a.curso_id = cu.id
                    WHERE cu.periodo_id = :periodo_id
                    GROUP BY c.id, c.nombre
                    ORDER BY total DESC
                ");
                $estudiantesPorCarrera->execute([':periodo_id' => $periodoActivo['id']]);
                $estudiantesPorCarrera = $estudiantesPorCarrera->fetchAll(PDO::FETCH_ASSOC);

                $semanasPeriodo = (int) $this->db->query("
                    SELECT COUNT(*) FROM semanas s
                    JOIN cursos cu ON cu.id = s.curso_id
                    WHERE cu.periodo_id = {$periodoActivo['id']}
                ")->fetchColumn();
            } else {
                $cursosPorCarrera = [];
                $estudiantesPorCarrera = [];
                $semanasPeriodo = 0;
            }
        } catch (PDOException $e) {
            $totalDocentes = $totalEstudiantes = $totalSemanas = $totalPeriodos = $totalCursos = $totalCarreras = 0;
            $periodoActivo = null;
            $cursosPorCarrera = [];
            $estudiantesPorCarrera = [];
            $semanasPeriodo = 0;
        }

        require __DIR__ . '/../Views/admin/dashboard.php';
    }

    // ==================== USUARIOS (admin + docente) ====================

    public function usuarios(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        try {
            $stmt = $this->db->query('SELECT id, username, apellidos, nombres, email, rol, estado, debe_cambiar_password, login_attempts, foto, created_at FROM usuarios WHERE rol IN ("admin", "docente") ORDER BY FIELD(rol, "admin", "docente"), apellidos ASC');
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar la lista de usuarios.');
            $users = [];
        }

        require __DIR__ . '/../Views/admin/usuarios.php';
    }

    public function formularioUsuario(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $user = null;

        if ($id !== null) {
            try {
                $stmt = $this->db->prepare('SELECT id, username, apellidos, nombres, email, rol, estado, foto FROM usuarios WHERE id = :id AND rol IN ("admin", "docente") LIMIT 1');
                $stmt->execute([':id' => $id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Usuario no encontrado.']); return; }
                    setFlash('error', 'Usuario no encontrado.');
                    redirect(BASE_URL . 'admin/usuarios');
                    return;
                }
            } catch (PDOException $e) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar datos.']); return; }
                setFlash('error', 'Error al cargar los datos del usuario.');
                redirect(BASE_URL . 'admin/usuarios');
                return;
            }
        }

        require __DIR__ . '/../Views/admin/formulario_usuario.php';
    }

    public function guardarUsuario(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no permitido.']); return; }
            redirect(BASE_URL . 'admin/usuarios');
            return;
        }

        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $rol = sanitize($_POST['rol'] ?? 'docente');
        $username = sanitize($_POST['username'] ?? '');
        $apellidos = formatApellidos($_POST['apellidos'] ?? '');
        $nombres = formatNombres($_POST['nombres'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $estado = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;

        if (!in_array($rol, ['admin', 'docente'])) {
            $rol = 'docente';
        }

        if (empty($apellidos) || empty($nombres) || empty($email) || empty($username)) {
            $msg = 'Apellidos, nombres, email y username son obligatorios.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioUsuario' . ($id ? "?id={$id}" : ''));
            return;
        }

        try {
            if ($id !== null) {
                $sql = 'UPDATE usuarios SET apellidos = :apellidos, nombres = :nombres, email = :email, username = :username, rol = :rol, estado = :estado';
                $params = [':apellidos' => $apellidos, ':nombres' => $nombres, ':email' => $email, ':username' => $username, ':rol' => $rol, ':estado' => $estado, ':id' => $id];

                if (!empty($password)) {
                    if ($password !== $password_confirm) {
                        $msg = 'Las contraseñas no coinciden.';
                        if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                        setFlash('error', $msg);
                        redirect(BASE_URL . 'admin/formularioUsuario?id=' . $id);
                        return;
                    }
                    if ((int) $_SESSION['user_id'] === $id) {
                        $sql .= ', password = :password';
                        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
                    } else {
                        $expira = date('Y-m-d H:i:s', time() + 1800);
                        $sql .= ', password = :password, debe_cambiar_password = 1, password_expira = :expira';
                        $params[':password'] = password_hash('12345678', PASSWORD_DEFAULT);
                        $params[':expira'] = $expira;
                        $password = '12345678';
                        $passwordTemporal = true;
                    }
                }

                $sql .= ' WHERE id = :id AND rol IN ("admin", "docente")';
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);

                if (!empty($_FILES['foto']['name'])) {
                    $newFoto = uploadProfilePhoto($_FILES['foto'], $id);
                    if ($newFoto) {
                        $this->db->prepare('UPDATE usuarios SET foto = :foto WHERE id = :id')->execute([':foto' => $newFoto, ':id' => $id]);
                        if ((int) $_SESSION['user_id'] === $id) {
                            $_SESSION['user_foto'] = $newFoto;
                        }
                    }
                }

                $msg = isset($passwordTemporal)
                    ? 'Usuario actualizado. Contraseña temporal: 12345678. Tiene 30 minutos para cambiarla.'
                    : 'Usuario actualizado correctamente.';
            } else {
                $password = '12345678';
                $expira = date('Y-m-d H:i:s', time() + 1800);
                $check = $this->db->prepare('SELECT id FROM usuarios WHERE email = :email OR username = :username LIMIT 1');
                $check->execute([':email' => $email, ':username' => $username]);
                if ($check->fetch()) {
                    $msg = 'El email o username ya está registrado.';
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'admin/formularioUsuario');
                    return;
                }

                $stmt = $this->db->prepare('INSERT INTO usuarios (username, apellidos, nombres, email, password, rol, estado, debe_cambiar_password, password_expira) VALUES (:username, :apellidos, :nombres, :email, :password, :rol, :estado, 1, :expira)');
                $stmt->execute([
                    ':username' => $username, ':apellidos' => $apellidos,
                    ':nombres' => $nombres, ':email' => $email, ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':rol' => $rol, ':estado' => $estado, ':expira' => $expira,
                ]);

                $newUserId = (int) $this->db->lastInsertId();
                if (!empty($_FILES['foto']['name'])) {
                    $newFoto = uploadProfilePhoto($_FILES['foto'], $newUserId);
                    if ($newFoto) {
                        $this->db->prepare('UPDATE usuarios SET foto = :foto WHERE id = :id')->execute([':foto' => $newFoto, ':id' => $newUserId]);
                    }
                }

                $msg = 'Usuario creado correctamente. Contraseña temporal: 12345678. Tiene 30 minutos para cambiarla.';            }
        } catch (PDOException $e) {
            $msg = 'Error al guardar el usuario. Verifique que el email/username no esté en uso.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioUsuario' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
        setFlash('success', $msg);
        redirect(BASE_URL . 'admin/usuarios');
    }

    public function restablecerPassword(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
        $origen = isset($_GET['origen']) ? sanitize($_GET['origen']) : (isset($_POST['origen']) ? sanitize($_POST['origen']) : 'usuarios');
        $backUrl = $origen === 'estudiantes' ? BASE_URL . 'admin/estudiantes' : BASE_URL . 'admin/usuarios';

        if ($id <= 0) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Usuario inválido.']); return; }
            setFlash('error', 'Usuario inválido.');
            redirect($backUrl);
            return;
        }

        if ((int) $_SESSION['user_id'] === $id) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'No puede restablecer la contraseña de su propia cuenta.']); return; }
            setFlash('error', 'No puede restablecer la contraseña de su propia cuenta.');
            redirect($backUrl);
            return;
        }

        try {
            $stmt = $this->db->prepare('SELECT id, username, apellidos, nombres, email, rol, estado FROM usuarios WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Usuario no encontrado.']); return; }
                setFlash('error', 'Usuario no encontrado.');
                redirect($backUrl);
                return;
            }

            $estaBloqueado = (int) $user['estado'] !== 1;

            $tempPassword = '12345678';
            $expira = date('Y-m-d H:i:s', time() + 1800);

            $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
            $this->db->prepare('UPDATE usuarios SET password = :password, debe_cambiar_password = 1, password_expira = :expira, estado = 1 WHERE id = :id')
                ->execute([':password' => $hash, ':id' => $id, ':expira' => $expira]);

            $message = 'Contraseña restablecida para ' . $user['nombres'] . ' ' . $user['apellidos'] . '. Contraseña temporal: ' . $tempPassword . '.';
            if ($estaBloqueado) {
                $message .= ' La cuenta ha sido desbloqueada.';
            }
            if ($isAjax) { jsonResponse(['success' => true, 'message' => $message]); return; }
            setFlash('success', $message);
            redirect($backUrl);
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al restablecer la contraseña.']); return; }
            setFlash('error', 'Error al restablecer la contraseña.');
            redirect($backUrl);
        }
    }

    public function toggleEstadoUsuario(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $origen = isset($_GET['origen']) ? sanitize($_GET['origen']) : 'usuarios';
        $backUrl = $origen === 'estudiantes' ? BASE_URL . 'admin/estudiantes' : BASE_URL . 'admin/usuarios';

        if ($id <= 0) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Usuario inválido.']); return; }
            setFlash('error', 'Usuario inválido.');
            redirect($backUrl);
            return;
        }

        if ((int) $_SESSION['user_id'] === $id) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'No puede bloquear su propia cuenta.']); return; }
            setFlash('error', 'No puede bloquear su propia cuenta.');
            redirect($backUrl);
            return;
        }

        try {
            $stmt = $this->db->prepare('SELECT id, apellidos, nombres, estado FROM usuarios WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Usuario no encontrado.']); return; }
                setFlash('error', 'Usuario no encontrado.');
                redirect($backUrl);
                return;
            }

            $nuevoEstado = (int) $user['estado'] === 0 ? 1 : 0;
            $this->db->prepare('UPDATE usuarios SET estado = :estado, login_attempts = 0, lockout_until = NULL WHERE id = :id')
                ->execute([':estado' => $nuevoEstado, ':id' => $id]);

            $nombre = trim($user['nombres'] . ' ' . $user['apellidos']);
            $mensaje = $nuevoEstado === 0
                ? 'Cuenta de ' . $nombre . ' bloqueada correctamente.'
                : 'Cuenta de ' . $nombre . ' desbloqueada correctamente.';

            if ($isAjax) { jsonResponse(['success' => true, 'message' => $mensaje]); return; }
            setFlash('success', $mensaje);
            redirect($backUrl);
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cambiar el estado.']); return; }
            setFlash('error', 'Error al cambiar el estado del usuario.');
            redirect($backUrl);
        }
    }

    public function desbloquearIntentos(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $origen = isset($_GET['origen']) ? sanitize($_GET['origen']) : 'usuarios';
        $backUrl = $origen === 'estudiantes' ? BASE_URL . 'admin/estudiantes' : BASE_URL . 'admin/usuarios';

        if ($id <= 0) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Usuario inválido.']); return; }
            setFlash('error', 'Usuario inválido.');
            redirect($backUrl);
            return;
        }

        try {
            $stmt = $this->db->prepare('SELECT id, apellidos, nombres, login_attempts FROM usuarios WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Usuario no encontrado.']); return; }
                setFlash('error', 'Usuario no encontrado.');
                redirect($backUrl);
                return;
            }

            if ((int) $user['login_attempts'] === 0) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Este usuario no tiene intentos bloqueados.']); return; }
                setFlash('warning', 'Este usuario no tiene intentos bloqueados.');
                redirect($backUrl);
                return;
            }

            resetUserLoginAttempts($this->db, $id);

            $nombre = trim($user['nombres'] . ' ' . $user['apellidos']);
            $mensaje = 'Intentos fallidos de ' . $nombre . ' limpiados. Ya puede iniciar sesión.';

            if ($isAjax) { jsonResponse(['success' => true, 'message' => $mensaje]); return; }
            setFlash('success', $mensaje);
            redirect($backUrl);
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al desbloquear.']); return; }
            setFlash('error', 'Error al limpiar los intentos.');
            redirect($backUrl);
        }
    }

    public function eliminarUsuario(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            $stmt = $this->db->prepare('DELETE FROM usuarios WHERE id = :id AND rol IN ("admin", "docente")');
            $stmt->execute([':id' => $id]);
            $success = $stmt->rowCount() > 0;
            $message = $success ? 'Usuario eliminado correctamente.' : 'Usuario no encontrado.';
        } catch (PDOException $e) {
            $success = false;
            $message = 'Error al eliminar el usuario. Puede tener registros asociados.';
        }

        if ($isAjax) { jsonResponse(['success' => $success, 'message' => $message]); return; }
        setFlash($success ? 'success' : 'error', $message);
        redirect(BASE_URL . 'admin/usuarios');
    }

    public function detalleUsuario(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $user = null;

        try {
            $stmt = $this->db->prepare('SELECT id, username, apellidos, nombres, email, rol, estado, debe_cambiar_password, foto, created_at FROM usuarios WHERE id = :id AND rol IN ("admin", "docente") LIMIT 1');
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Usuario no encontrado.']); return; }
                setFlash('error', 'Usuario no encontrado.');
                redirect(BASE_URL . 'admin/usuarios');
                return;
            }

            $cursos = [];
            if ($user['rol'] === 'docente') {
                $stmtCursos = $this->db->prepare('
                    SELECT c.nombre, c.codigo, p.nombre AS periodo_nombre
                    FROM cursos c
                    LEFT JOIN periodos p ON p.id = c.periodo_id
                    WHERE c.docente_id = :docente_id AND c.estado = 1
                    ORDER BY c.nombre ASC
                ');
                $stmtCursos->execute([':docente_id' => $id]);
                $cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar datos.']); return; }
            setFlash('error', 'Error al cargar datos.');
            redirect(BASE_URL . 'admin/usuarios');
            return;
        }

        require __DIR__ . '/../Views/admin/detalle_usuario.php';
    }

    // ==================== ESTUDIANTES ====================

    public function estudiantes(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        try {
            $stmt = $this->db->query('
                SELECT u.id, u.username, u.dni, u.apellidos, u.nombres, u.email, u.estado, u.debe_cambiar_password, u.login_attempts, u.created_at,
                       c.nombre AS carrera_nombre, c.codigo AS carrera_codigo,
                       (SELECT MIN(cu.ciclo) FROM asignaciones a
                        JOIN cursos cu ON cu.id = a.curso_id
                        WHERE a.estudiante_id = u.id) AS ciclo
                FROM usuarios u
                LEFT JOIN carreras c ON c.id = u.carrera_id
                WHERE u.rol = "estudiante"
                ORDER BY u.apellidos ASC
            ');
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar la lista de estudiantes.');
            $students = [];
        }

        $carreras = $this->loadCarreras();

        require __DIR__ . '/../Views/admin/estudiantes.php';
    }

    public function importarEstudiantes(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.']);
            return;
        }

        $carreraId = !empty($_POST['carrera_id']) ? (int) $_POST['carrera_id'] : null;

        if (empty($_FILES['archivo']) || ($_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            jsonResponse(['success' => false, 'message' => 'Seleccione un archivo Excel válido.']);
            return;
        }

        $tmp = $_FILES['archivo']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'], true)) {
            jsonResponse(['success' => false, 'message' => 'El archivo debe ser .xlsx o .xls.']);
            return;
        }

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmp);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($tmp);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (\Throwable $e) {
            jsonResponse(['success' => false, 'message' => 'No se pudo leer el archivo. Verifique el formato.']);
            return;
        }

        if (count($rows) < 2) {
            jsonResponse(['success' => false, 'message' => 'El archivo no contiene datos.']);
            return;
        }

        $header = array_map(fn($h) => strtolower(trim((string) $h)), $rows[0]);
        $findCol = function (string $name, string $alt = '') use ($header): int|false {
            $pos = array_search($name, $header, true);
            if ($pos === false && $alt !== '') $pos = array_search($alt, $header, true);
            if ($pos === false) {
                foreach ($header as $i => $h) {
                    if (strpos($h, $name) !== false) { $pos = $i; break; }
                }
            }
            return $pos;
        };
        $idxDni       = $findCol('dni');
        $idxApellidos = $findCol('apellidos', 'apellido');
        $idxNombres   = $findCol('nombres', 'nombre');
        $idxEmail     = $findCol('email', 'correo');
        $idxCarrera   = $findCol('carrera');

        $hasHeader = $idxDni !== false || $idxApellidos !== false || $idxNombres !== false || $idxEmail !== false;

        $carreraPorCodigo = [];
        foreach ($this->carreras->getAllActivas() as $c) {
            $carreraPorCodigo[strtolower($c['codigo'])] = (int) $c['id'];
        }

        $creados = 0;
        $omitidos = 0;
        $errores = [];
        $dnisEnArchivo = [];
        $stmtExisteDni = $this->db->prepare('SELECT id FROM usuarios WHERE dni = :dni LIMIT 1');
        $stmtExisteEmail = $this->db->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');

        $start = $hasHeader ? 1 : 0;
        for ($i = $start; $i < count($rows); $i++) {
            $row = array_values($rows[$i]);

            if ($hasHeader) {
                $dni = $idxDni !== false ? trim((string) ($row[$idxDni] ?? '')) : '';
                $apellidos = $idxApellidos !== false ? formatApellidos((string) ($row[$idxApellidos] ?? '')) : '';
                $nombres = $idxNombres !== false ? formatNombres((string) ($row[$idxNombres] ?? '')) : '';
                $email = $idxEmail !== false ? trim((string) ($row[$idxEmail] ?? '')) : '';
                $codigoCarrera = $idxCarrera !== false ? trim((string) ($row[$idxCarrera] ?? '')) : '';
            } else {
                $dni = trim((string) ($row[0] ?? ''));
                $apellidos = formatApellidos((string) ($row[1] ?? ''));
                $nombres = formatNombres((string) ($row[2] ?? ''));
                $email = trim((string) ($row[3] ?? ''));
                $codigoCarrera = '';
            }

            if ($apellidos === '' && $nombres === '' && $email === '' && $dni === '') continue;

            if ($apellidos === '' || $nombres === '' || $email === '') {
                $omitidos++;
                $errores[] = 'Fila ' . ($i + 1) . ': faltan apellidos, nombres o email.';
                continue;
            }

            if ($dni === '') {
                $omitidos++;
                $errores[] = 'Fila ' . ($i + 1) . ': falta el DNI.';
                continue;
            }

            if (isset($dnisEnArchivo[$dni])) {
                $omitidos++;
                $errores[] = 'Fila ' . ($i + 1) . ': DNI ' . $dni . ' está duplicado en el archivo (fila ' . $dnisEnArchivo[$dni] . ').';
                continue;
            }
            $dnisEnArchivo[$dni] = $i + 1;

            $rowCarrera = $carreraId;
            if ($codigoCarrera !== '' && isset($carreraPorCodigo[strtolower($codigoCarrera)])) {
                $rowCarrera = $carreraPorCodigo[strtolower($codigoCarrera)];
            }

            $stmtExisteDni->execute([':dni' => $dni]);
            if ($stmtExisteDni->fetch()) {
                $omitidos++;
                $errores[] = 'Fila ' . ($i + 1) . ': el DNI ' . $dni . ' ya está registrado en el sistema.';
                continue;
            }

            $stmtExisteEmail->execute([':email' => $email]);
            if ($stmtExisteEmail->fetch()) {
                $omitidos++;
                $errores[] = 'Fila ' . ($i + 1) . ': el email ' . $email . ' ya está registrado en el sistema.';
                continue;
            }

            $username = strtolower(substr($email, 0, strpos($email, '@') !== false ? strpos($email, '@') : strlen($email)));
            $base = $username;
            $counter = 1;
            while (true) {
                $check = $this->db->prepare('SELECT id FROM usuarios WHERE username = :username LIMIT 1');
                $check->execute([':username' => $username]);
                if (!$check->fetch()) break;
                $username = $base . $counter;
                $counter++;
            }

            $stmt = $this->db->prepare('INSERT INTO usuarios (username, apellidos, nombres, dni, email, password, rol, estado, carrera_id, debe_cambiar_password)
                VALUES (:username, :apellidos, :nombres, :dni, :email, :password, "estudiante", 1, :carrera_id, 1)');
            $stmt->execute([
                ':username' => $username,
                ':apellidos' => $apellidos,
                ':nombres' => $nombres,
                ':dni' => $dni,
                ':email' => $email,
                ':password' => password_hash('12345678', PASSWORD_DEFAULT),
                ':carrera_id' => $rowCarrera,
            ]);
            $creados++;
        }

        $message = 'Se importaron ' . $creados . ' estudiante(s).';
        if ($omitidos > 0) {
            $message .= ' ' . $omitidos . ' fila(s) omitida(s).';
        }
        jsonResponse([
            'success' => true,
            'message' => $message,
            'creados' => $creados,
            'omitidos' => $omitidos,
            'errores' => array_slice($errores, 0, 20),
        ]);
    }

    public function descargarPlantillaEstudiantes(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Estudiantes');
        $sheet->fromArray(['DNI', 'Apellidos', 'Nombres', 'Email', 'Carrera (código)'], null, 'A1');
        $sheet->fromArray(['12345678', 'García López', 'Juan Carlos', 'juan.garcia@instituto.edu', 'APSTI01'], null, 'A2');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="plantilla_estudiantes.xlsx"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function formularioEstudiante(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $student = null;
        $carreras = $this->loadCarreras();

        if ($id !== null) {
            try {
                $stmt = $this->db->prepare('SELECT id, username, dni, apellidos, nombres, email, estado, carrera_id FROM usuarios WHERE id = :id AND rol = "estudiante" LIMIT 1');
                $stmt->execute([':id' => $id]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$student) {
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Estudiante no encontrado.']); return; }
                    setFlash('error', 'Estudiante no encontrado.');
                    redirect(BASE_URL . 'admin/estudiantes');
                    return;
                }
            } catch (PDOException $e) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar datos.']); return; }
                setFlash('error', 'Error al cargar los datos del estudiante.');
                redirect(BASE_URL . 'admin/estudiantes');
                return;
            }
        }

        require __DIR__ . '/../Views/admin/formulario_estudiante.php';
    }

    public function guardarEstudiante(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no permitido.']); return; }
            redirect(BASE_URL . 'admin/estudiantes');
            return;
        }

        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $dni = sanitize($_POST['dni'] ?? '');
        $apellidos = formatApellidos($_POST['apellidos'] ?? '');
        $nombres = formatNombres($_POST['nombres'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $estado = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;
        $carrera_id = !empty($_POST['carrera_id']) ? (int) $_POST['carrera_id'] : null;

        if (empty($apellidos) || empty($nombres) || empty($email) || ($id === null && empty($username))) {
            $msg = 'Apellidos, nombres, email' . ($id === null ? ' y usuario' : '') . ' son obligatorios.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioEstudiante' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($username !== '') {
            $stmtUser = $this->db->prepare('SELECT id FROM usuarios WHERE username = :username AND id != :id LIMIT 1');
            $stmtUser->execute([':username' => $username, ':id' => $id ?? 0]);
            if ($stmtUser->fetch()) {
                $msg = 'Ya existe otro usuario con ese nombre de usuario.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'admin/formularioEstudiante' . ($id ? "?id={$id}" : ''));
                return;
            }
        }

        if ($dni !== '') {
            $stmtDni = $this->db->prepare('SELECT id FROM usuarios WHERE dni = :dni AND rol = "estudiante" AND id != :id LIMIT 1');
            $stmtDni->execute([':dni' => $dni, ':id' => $id ?? 0]);
            if ($stmtDni->fetch()) {
                $msg = 'Ya existe un estudiante registrado con ese DNI.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'admin/formularioEstudiante' . ($id ? "?id={$id}" : ''));
                return;
            }
        }

        try {
            if ($id !== null) {
                $sql = 'UPDATE usuarios SET dni = :dni, apellidos = :apellidos, nombres = :nombres, email = :email, estado = :estado, carrera_id = :carrera_id';
                $params = [':dni' => $dni, ':apellidos' => $apellidos, ':nombres' => $nombres, ':email' => $email, ':estado' => $estado, ':carrera_id' => $carrera_id, ':id' => $id];

                if (!empty($username)) {
                    $sql .= ', username = :username';
                    $params[':username'] = $username;
                }

                if (!empty($password)) {
                    if ($password !== $password_confirm) {
                        $msg = 'Las contraseñas no coinciden.';
                        if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                        setFlash('error', $msg);
                        redirect(BASE_URL . 'admin/formularioEstudiante?id=' . $id);
                        return;
                    }
                    $sql .= ', password = :password';
                    $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
                }

                $sql .= ' WHERE id = :id AND rol = "estudiante"';
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $msg = 'Estudiante actualizado correctamente.';
            } else {
                if (empty($password)) {
                    $msg = 'La contraseña es obligatoria para nuevos estudiantes.';
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'admin/formularioEstudiante');
                    return;
                }
                if ($password !== $password_confirm) {
                    $msg = 'Las contraseñas no coinciden.';
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'admin/formularioEstudiante');
                    return;
                }

                $stmt = $this->db->prepare('INSERT INTO usuarios (username, apellidos, nombres, dni, email, password, rol, estado, carrera_id) VALUES (:username, :apellidos, :nombres, :dni, :email, :password, "estudiante", :estado, :carrera_id)');
                $stmt->execute([
                    ':username' => $username, ':apellidos' => $apellidos,
                    ':nombres' => $nombres, ':dni' => $dni, ':email' => $email, ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':estado' => $estado, ':carrera_id' => $carrera_id,
                ]);
                $msg = 'Estudiante creado correctamente.';
            }
        } catch (PDOException $e) {
            $msg = 'Error al guardar el estudiante. Verifique que el email no esté en uso.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioEstudiante' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
        setFlash('success', $msg);
        redirect(BASE_URL . 'admin/estudiantes');
    }

    public function eliminarEstudiante(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            $stmt = $this->db->prepare('DELETE FROM usuarios WHERE id = :id AND rol = "estudiante"');
            $stmt->execute([':id' => $id]);
            $success = $stmt->rowCount() > 0;
            $message = $success ? 'Estudiante eliminado correctamente.' : 'Estudiante no encontrado.';
        } catch (PDOException $e) {
            $success = false;
            $message = 'Error al eliminar el estudiante. Puede tener registros asociados.';
        }

        if ($isAjax) { jsonResponse(['success' => $success, 'message' => $message]); return; }
        setFlash($success ? 'success' : 'error', $message);
        redirect(BASE_URL . 'admin/estudiantes');
    }

    public function detalleEstudiante(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $student = null;

        try {
            $stmt = $this->db->prepare('
                SELECT u.id, u.dni, u.apellidos, u.nombres, u.email, u.estado, u.created_at,
                       c.nombre AS carrera_nombre, c.codigo AS carrera_codigo
                FROM usuarios u
                LEFT JOIN carreras c ON c.id = u.carrera_id
                WHERE u.id = :id AND u.rol = "estudiante" LIMIT 1
            ');
            $stmt->execute([':id' => $id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Estudiante no encontrado.']); return; }
                setFlash('error', 'Estudiante no encontrado.');
                redirect(BASE_URL . 'admin/estudiantes');
                return;
            }

            $stmtCursos = $this->db->prepare('
                SELECT cr.nombre, cr.codigo, c.fecha_asignacion
                FROM asignaciones c
                INNER JOIN cursos cr ON cr.id = c.curso_id
                WHERE c.estudiante_id = :estudiante_id
                ORDER BY cr.nombre ASC
            ');
            $stmtCursos->execute([':estudiante_id' => $id]);
            $cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar datos.']); return; }
            setFlash('error', 'Error al cargar datos.');
            redirect(BASE_URL . 'admin/estudiantes');
            return;
        }

        require __DIR__ . '/../Views/admin/detalle_estudiante.php';
    }

    // ==================== PERIODOS ====================

    public function periodos(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        try {
            $periodos = $this->periodos->getAll();
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los periodos.');
            $periodos = [];
        }

        require __DIR__ . '/../Views/admin/periodos.php';
    }

    public function formularioPeriodo(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $periodo = null;

        if ($id !== null) {
            try {
                $periodo = $this->periodos->getById($id);

                if (!$periodo) {
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Periodo no encontrado.']); return; }
                    setFlash('error', 'Periodo no encontrado.');
                    redirect(BASE_URL . 'admin/periodos');
                    return;
                }
            } catch (PDOException $e) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar el periodo.']); return; }
                setFlash('error', 'Error al cargar el periodo.');
                redirect(BASE_URL . 'admin/periodos');
                return;
            }
        }

        require __DIR__ . '/../Views/admin/formulario_periodo.php';
    }

    public function guardarPeriodo(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no permitido.']); return; }
            redirect(BASE_URL . 'admin/periodos');
            return;
        }

        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $nombre = sanitize($_POST['nombre'] ?? '');
        $fecha_inicio = sanitize($_POST['fecha_inicio'] ?? '');
        $fecha_fin = sanitize($_POST['fecha_fin'] ?? '');
        $estado = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;

        if (empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
            $msg = 'Todos los campos son obligatorios.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioPeriodo' . ($id ? "?id={$id}" : ''));
            return;
        }

        $data = [
            'nombre' => $nombre,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'estado' => $estado,
        ];

        try {
            if ($id !== null) {
                $this->periodos->update($id, $data);
                $msg = 'Periodo actualizado correctamente.';
            } else {
                $this->periodos->create($data);
                $msg = 'Periodo creado correctamente.';
            }
        } catch (PDOException $e) {
            $msg = 'Error al guardar el periodo.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioPeriodo' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
        setFlash('success', $msg);
        redirect(BASE_URL . 'admin/periodos');
    }

    public function eliminarPeriodo(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            $success = $this->periodos->delete($id);
            $message = $success ? 'Periodo eliminado correctamente.' : 'Periodo no encontrado.';
        } catch (PDOException $e) {
            $success = false;
            $message = 'Error al eliminar el periodo. Puede tener cursos asociados.';
        }

        if ($isAjax) { jsonResponse(['success' => $success, 'message' => $message]); return; }
        setFlash($success ? 'success' : 'error', $message);
        redirect(BASE_URL . 'admin/periodos');
    }

    public function detallePeriodo(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $periodo = null;

        try {
            $periodo = $this->periodos->getById($id);

            if (!$periodo) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Periodo no encontrado.']); return; }
                setFlash('error', 'Periodo no encontrado.');
                redirect(BASE_URL . 'admin/periodos');
                return;
            }
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar datos.']); return; }
            setFlash('error', 'Error al cargar datos.');
            redirect(BASE_URL . 'admin/periodos');
            return;
        }

        require __DIR__ . '/../Views/admin/detalle_periodo.php';
    }

    // ==================== CURSOS ====================

    public function cursos(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        try {
            $cursos = $this->cursos->getAllWithCarrera();
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los cursos.');
            $cursos = [];
        }

        $carreras = $this->carreras->getAll();

        require __DIR__ . '/../Views/admin/cursos.php';
    }

    public function formularioCurso(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $curso = null;
        $carreras = $this->loadCarreras();
        $planesPorCarrera = [];
        foreach ($carreras as $c) {
            $planesPorCarrera[$c['id']] = $this->planes->getByCarreraActivos((int) $c['id']);
        }

        if ($id !== null) {
            try {
                $curso = $this->cursos->getById($id);

                if (!$curso) {
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Curso no encontrado.']); return; }
                    setFlash('error', 'Curso no encontrado.');
                    redirect(BASE_URL . 'admin/cursos');
                    return;
                }
            } catch (PDOException $e) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar datos.']); return; }
                setFlash('error', 'Error al cargar los datos.');
                redirect(BASE_URL . 'admin/cursos');
                return;
            }
        }

        require __DIR__ . '/../Views/admin/formulario_curso.php';
    }

    public function guardarCurso(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no permitido.']); return; }
            redirect(BASE_URL . 'admin/cursos');
            return;
        }

        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $nombre = sanitize($_POST['nombre'] ?? '');
        $codigo = sanitize($_POST['codigo'] ?? '');
        $ciclo = sanitize($_POST['ciclo'] ?? '');
        $carrera_id = !empty($_POST['carrera_id']) ? (int) $_POST['carrera_id'] : null;
        $plan_id = !empty($_POST['plan_id']) ? (int) $_POST['plan_id'] : null;
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $estado = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;

        if (empty($nombre) || empty($codigo) || $ciclo === '') {
            $msg = 'Nombre, código y ciclo son obligatorios.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioCurso' . ($id ? "?id={$id}" : ''));
            return;
        }

        if (!is_numeric($ciclo) || (int) $ciclo < 1) {
            $msg = 'El ciclo debe ser un número válido.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioCurso' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($carrera_id === null) {
            $msg = 'Debe seleccionar la carrera del curso.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioCurso' . ($id ? "?id={$id}" : ''));
            return;
        }

        $carrera = $this->carreras->getById($carrera_id);
        if (!$carrera) {
            $msg = 'La carrera seleccionada no existe.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioCurso' . ($id ? "?id={$id}" : ''));
            return;
        }
        if ((int) $ciclo > (int) $carrera['total_ciclos']) {
            $msg = 'El ciclo no puede superar el total de ciclos de la carrera (' . (int) $carrera['total_ciclos'] . ').';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioCurso' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($plan_id === null) {
            $msg = 'Debe seleccionar el plan de estudios del curso.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioCurso' . ($id ? "?id={$id}" : ''));
            return;
        }

        $plan = $this->planes->getById($plan_id);
        if (!$plan || (int) $plan['carrera_id'] !== $carrera_id) {
            $msg = 'El plan de estudios no pertenece a la carrera seleccionada.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioCurso' . ($id ? "?id={$id}" : ''));
            return;
        }

        $data = [
            'nombre' => $nombre,
            'codigo' => $codigo,
            'ciclo' => (string) (int) $ciclo,
            'carrera_id' => $carrera_id,
            'plan_id' => $plan_id,
            'descripcion' => $descripcion,
            'estado' => $estado,
        ];

        try {
            if ($id !== null) {
                $this->cursos->update($id, $data);
                $msg = 'Curso actualizado correctamente.';
            } else {
                if ($this->cursos->existsByCodigo($codigo)) {
                    $msg = 'El código del curso ya está en uso.';
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'admin/formularioCurso');
                    return;
                }

                $periodo = $this->periodos->getActivo();
                if (!$periodo) {
                    $msg = 'No hay un periodo activo configurado. Active un periodo antes de crear cursos.';
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'admin/formularioCurso');
                    return;
                }

                $data['periodo_id'] = (int) $periodo['id'];
                $this->cursos->create($data);
                $msg = 'Curso creado correctamente.';
            }
        } catch (PDOException $e) {
            $msg = 'Error al guardar el curso.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioCurso' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
        setFlash('success', $msg);
        redirect(BASE_URL . 'admin/cursos');
    }

    public function eliminarCurso(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            $success = $this->cursos->delete($id);
            $message = $success ? 'Curso eliminado correctamente.' : 'Curso no encontrado.';
        } catch (PDOException $e) {
            $success = false;
            $message = 'Error al eliminar el curso.';
        }

        if ($isAjax) { jsonResponse(['success' => $success, 'message' => $message]); return; }
        setFlash($success ? 'success' : 'error', $message);
        redirect(BASE_URL . 'admin/cursos');
    }

    public function detalleCurso(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $curso = null;

        try {
            $curso = $this->cursos->getByIdWithCarrera($id);

            if (!$curso) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Curso no encontrado.']); return; }
                setFlash('error', 'Curso no encontrado.');
                redirect(BASE_URL . 'admin/cursos');
                return;
            }

            $totalSemanas = $this->cursos->countSemanas($id);
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar datos.']); return; }
            setFlash('error', 'Error al cargar datos.');
            redirect(BASE_URL . 'admin/cursos');
            return;
        }

        require __DIR__ . '/../Views/admin/ficha_curso.php';
    }

    // ==================== ASIGNACIONES ====================

    public function asignaciones(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        try {
            $asignaciones = $this->cursos->getAsignaciones();
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar las asignaciones.');
            $asignaciones = [];
        }

        require __DIR__ . '/../Views/admin/asignaciones.php';
    }

    public function formularioAsignacion(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $asignacion = null;

        try {
            $periodos = $this->periodos->getAllActivos();
            $docentes = (new User($this->db))->getDocentesActivos();
            $cursos = $this->cursos->getAllActivos();

            if ($id !== null) {
                $asignacion = $this->cursos->getById($id);

                if (!$asignacion) {
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Asignación no encontrada.']); return; }
                    setFlash('error', 'Asignación no encontrada.');
                    redirect(BASE_URL . 'admin/asignaciones');
                    return;
                }
            }
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar los datos.']); return; }
            setFlash('error', 'Error al cargar los datos.');
            redirect(BASE_URL . 'admin/asignaciones');
            return;
        }

        require __DIR__ . '/../Views/admin/formulario_asignacion.php';
    }

    public function guardarAsignacion(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no permitido.']); return; }
            redirect(BASE_URL . 'admin/asignaciones');
            return;
        }

        $id = !empty($_POST['curso_id']) ? (int) $_POST['curso_id'] : 0;
        $docente_id = !empty($_POST['docente_id']) ? (int) $_POST['docente_id'] : null;
        $periodo_id = (int)($_POST['periodo_id'] ?? 0);

        if ($id === 0 || $periodo_id === 0) {
            $msg = 'Debe seleccionar un curso y un periodo.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioAsignacion');
            return;
        }

        try {
            $this->cursos->assignDocente($id, $docente_id, $periodo_id);
            $msg = 'Asignación guardada correctamente.';
        } catch (PDOException $e) {
            $msg = 'Error al guardar la asignación.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioAsignacion?id=' . $id);
            return;
        }

        if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
        setFlash('success', $msg);
        redirect(BASE_URL . 'admin/asignaciones');
    }

    public function eliminarAsignacion(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            $success = $this->cursos->unassignDocente($id);
            $message = $success ? 'Docente desasignado del curso.' : 'Curso no encontrado.';
        } catch (PDOException $e) {
            $success = false;
            $message = 'Error al desasignar.';
        }

        if ($isAjax) { jsonResponse(['success' => $success, 'message' => $message]); return; }
        setFlash($success ? 'success' : 'error', $message);
        redirect(BASE_URL . 'admin/asignaciones');
    }

    public function detalleAsignacion(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $asignacion = null;

        try {
            $asignacion = $this->cursos->getByIdWithAsignacion($id);

            if (!$asignacion) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Asignación no encontrada.']); return; }
                setFlash('error', 'Asignación no encontrada.');
                redirect(BASE_URL . 'admin/asignaciones');
                return;
            }

            $estudiantes = (new Asignacion($this->db))->getEstudiantesByCursoSimple($id);
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar datos.']); return; }
            setFlash('error', 'Error al cargar datos.');
            redirect(BASE_URL . 'admin/asignaciones');
            return;
        }

        require __DIR__ . '/../Views/admin/detalle_asignacion.php';
    }

    // ==================== CARRERAS ====================

    public function carreras(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        try {
            $carreras = $this->carreras->getAll();
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar las carreras.');
            $carreras = [];
        }

        $planesPorCarrera = [];
        foreach ($carreras as $c) {
            $planesPorCarrera[$c['id']] = $this->planes->getByCarrera((int) $c['id']);
        }

        require __DIR__ . '/../Views/admin/carreras.php';
    }

    public function formularioCarrera(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $carrera = null;

        if ($id !== null) {
            try {
                $carrera = $this->carreras->getById($id);

                if (!$carrera) {
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Carrera no encontrada.']); return; }
                    setFlash('error', 'Carrera no encontrada.');
                    redirect(BASE_URL . 'admin/carreras');
                    return;
                }
            } catch (PDOException $e) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar la carrera.']); return; }
                setFlash('error', 'Error al cargar la carrera.');
                redirect(BASE_URL . 'admin/carreras');
                return;
            }
        }

        require __DIR__ . '/../Views/admin/formulario_carrera.php';
    }

    public function guardarCarrera(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no permitido.']); return; }
            redirect(BASE_URL . 'admin/carreras');
            return;
        }

        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $nombre = sanitize($_POST['nombre'] ?? '');
        $codigo = sanitize($_POST['codigo'] ?? '');
        $total_ciclos = isset($_POST['total_ciclos']) ? (int) $_POST['total_ciclos'] : 6;
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $estado = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;

        if (empty($nombre) || empty($codigo)) {
            $msg = 'Nombre y código son obligatorios.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioCarrera' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($total_ciclos < 1 || $total_ciclos > 20) {
            $msg = 'El total de ciclos debe estar entre 1 y 20.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioCarrera' . ($id ? "?id={$id}" : ''));
            return;
        }

        try {
            if ($id !== null) {
                $this->carreras->update($id, [
                    'nombre' => $nombre,
                    'codigo' => $codigo,
                    'total_ciclos' => $total_ciclos,
                    'descripcion' => $descripcion,
                    'estado' => $estado,
                ]);
                $msg = 'Carrera actualizada correctamente.';
            } else {
                if ($this->carreras->existsByCodigo($codigo)) {
                    $msg = 'El código de la carrera ya está en uso.';
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'admin/formularioCarrera');
                    return;
                }

                $this->carreras->create([
                    'nombre' => $nombre,
                    'codigo' => $codigo,
                    'total_ciclos' => $total_ciclos,
                    'descripcion' => $descripcion,
                    'estado' => $estado,
                ]);
                $msg = 'Carrera creada correctamente.';
            }
        } catch (PDOException $e) {
            $msg = 'Error al guardar la carrera.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioCarrera' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
        setFlash('success', $msg);
        redirect(BASE_URL . 'admin/carreras');
    }

    public function eliminarCarrera(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            $success = $this->carreras->delete($id);
            $message = $success ? 'Carrera eliminada correctamente.' : 'Carrera no encontrada.';
        } catch (PDOException $e) {
            $success = false;
            $message = 'Error al eliminar la carrera.';
        }

        if ($isAjax) { jsonResponse(['success' => $success, 'message' => $message]); return; }
        setFlash($success ? 'success' : 'error', $message);
        redirect(BASE_URL . 'admin/carreras');
    }

    public function detalleCarrera(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $carrera = null;

        try {
            $carrera = $this->carreras->getById($id);

            if (!$carrera) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Carrera no encontrada.']); return; }
                setFlash('error', 'Carrera no encontrada.');
                redirect(BASE_URL . 'admin/carreras');
                return;
            }
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar datos.']); return; }
            setFlash('error', 'Error al cargar datos.');
            redirect(BASE_URL . 'admin/carreras');
            return;
        }

        $planes = $this->planes->getByCarrera($id);

        require __DIR__ . '/../Views/admin/detalle_carrera.php';
    }

    // ==================== PLANES DE ESTUDIO ====================

    public function planes(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        try {
            $carreras = $this->carreras->getAll();
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar las carreras.');
            $carreras = [];
        }

        $planesPorCarrera = [];
        foreach ($carreras as $c) {
            $planesPorCarrera[$c['id']] = $this->planes->getByCarrera((int) $c['id']);
        }

        require __DIR__ . '/../Views/admin/planes_de_estudio.php';
    }

    public function formularioPlan(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $plan = null;
        $carreras = $this->loadCarreras();

        if ($id !== null) {
            try {
                $plan = $this->planes->getById($id);

                if (!$plan) {
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Plan de estudios no encontrado.']); return; }
                    setFlash('error', 'Plan de estudios no encontrado.');
                    redirect(BASE_URL . 'admin/planes');
                    return;
                }
            } catch (PDOException $e) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar datos.']); return; }
                setFlash('error', 'Error al cargar los datos.');
                redirect(BASE_URL . 'admin/planes');
                return;
            }
        }

        require __DIR__ . '/../Views/admin/formulario_plan.php';
    }

    public function guardarPlan(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no permitido.']); return; }
            redirect(BASE_URL . 'admin/planes');
            return;
        }

        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $carrera_id = !empty($_POST['carrera_id']) ? (int) $_POST['carrera_id'] : null;
        $nombre = sanitize($_POST['nombre'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $estado = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;

        if ($carrera_id === null) {
            $msg = 'Debe seleccionar la carrera del plan.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioPlan' . ($id ? "?id={$id}" : ''));
            return;
        }

        if (empty($nombre)) {
            $msg = 'El nombre del plan es obligatorio.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioPlan' . ($id ? "?id={$id}" : ''));
            return;
        }

        try {
            if ($this->planes->exists($carrera_id, $nombre, $id)) {
                $msg = 'Ya existe un plan con ese nombre para la carrera seleccionada.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'admin/formularioPlan' . ($id ? "?id={$id}" : ''));
                return;
            }

            if ($id !== null) {
                $this->planes->update($id, [
                    'carrera_id' => $carrera_id,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'estado' => $estado,
                ]);
                $msg = 'Plan de estudios actualizado correctamente.';
            } else {
                $this->planes->create([
                    'carrera_id' => $carrera_id,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'estado' => $estado,
                ]);
                $msg = 'Plan de estudios creado correctamente.';
            }
        } catch (PDOException $e) {
            $msg = 'Error al guardar el plan de estudios.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioPlan' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
        setFlash('success', $msg);
        redirect(BASE_URL . 'admin/planes');
    }

    public function eliminarPlan(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            $success = $this->planes->delete($id);
            $message = $success ? 'Plan de estudios eliminado correctamente.' : 'Plan de estudios no encontrado.';
        } catch (PDOException $e) {
            $success = false;
            $message = 'Error al eliminar el plan de estudios.';
        }

        if ($isAjax) { jsonResponse(['success' => $success, 'message' => $message]); return; }
        setFlash($success ? 'success' : 'error', $message);
        redirect(BASE_URL . 'admin/planes');
    }

    public function detallePlan(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $plan = null;

        try {
            $plan = $this->planes->getByIdConCarrera($id);

            if (!$plan) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Plan de estudios no encontrado.']); return; }
                setFlash('error', 'Plan de estudios no encontrado.');
                redirect(BASE_URL . 'admin/planes');
                return;
            }

            $totalCursos = $this->planes->countCursos($id);
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar datos.']); return; }
            setFlash('error', 'Error al cargar datos.');
            redirect(BASE_URL . 'admin/planes');
            return;
        }

        require __DIR__ . '/../Views/admin/detalle_plan.php';
    }

    // ==================== AMBIENTES ====================

    public function ambientes(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        try {
            $ambientes = $this->ambientes->getAll();
        } catch (PDOException $e) {
            $ambientes = [];
        }

        require __DIR__ . '/../Views/admin/ambientes.php';
    }

    public function formularioAmbiente(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $ambiente = null;

        if ($id !== null) {
            try {
                $ambiente = $this->ambientes->getById($id);
                if (!$ambiente) {
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Ambiente no encontrado.']); return; }
                    setFlash('error', 'Ambiente no encontrado.');
                    redirect(BASE_URL . 'admin/ambientes');
                    return;
                }
            } catch (PDOException $e) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar.']); return; }
                setFlash('error', 'Error al cargar.');
                redirect(BASE_URL . 'admin/ambientes');
                return;
            }
        }

        require __DIR__ . '/../Views/admin/ambiente_form.php';
    }

    public function guardarAmbiente(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no válido.']); return; }
            redirect(BASE_URL . 'admin/ambientes');
            return;
        }

        $id         = (int) ($_POST['id'] ?? 0);
        $nombre     = sanitize($_POST['nombre'] ?? '');
        $ubicacion  = sanitize($_POST['ubicacion'] ?? '');
        $capacidad  = (int) ($_POST['capacidad'] ?? 0);
        $estado     = (int) ($_POST['estado'] ?? 1);

        if ($nombre === '') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'El nombre es obligatorio.']); return; }
            setFlash('error', 'El nombre es obligatorio.');
            redirect(BASE_URL . 'admin/formularioAmbiente' . ($id > 0 ? '?id=' . $id : ''));
            return;
        }

        try {
            if ($id > 0) {
                $this->ambientes->update($id, [
                    'nombre' => $nombre,
                    'ubicacion' => $ubicacion,
                    'capacidad' => $capacidad,
                    'estado' => $estado,
                ]);
                $msg = 'Ambiente actualizado correctamente.';
            } else {
                $this->ambientes->create([
                    'nombre' => $nombre,
                    'ubicacion' => $ubicacion,
                    'capacidad' => $capacidad,
                    'estado' => $estado,
                ]);
                $msg = 'Ambiente creado correctamente.';
            }

            if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
            setFlash('success', $msg);
            redirect(BASE_URL . 'admin/ambientes');
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al guardar.']); return; }
            setFlash('error', 'Error al guardar el ambiente.');
            redirect(BASE_URL . 'admin/ambientes');
        }
    }

    public function eliminarAmbiente(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            $success = $this->ambientes->delete($id);
            $msg = $success ? 'Ambiente eliminado.' : 'Ambiente no encontrado.';
        } catch (PDOException $e) {
            $success = false;
            $msg = 'Error al eliminar.';
        }

        if ($isAjax) { jsonResponse(['success' => $success, 'message' => $msg]); return; }
        setFlash($success ? 'success' : 'error', $msg);
        redirect(BASE_URL . 'admin/ambientes');
    }

    public function detalleAmbiente(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            $ambiente = $this->ambientes->getById($id);
            if (!$ambiente) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'No encontrado.']); return; }
                setFlash('error', 'Ambiente no encontrado.');
                redirect(BASE_URL . 'admin/ambientes');
                return;
            }
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar.']); return; }
            setFlash('error', 'Error al cargar datos.');
            redirect(BASE_URL . 'admin/ambientes');
            return;
        }

        require __DIR__ . '/../Views/admin/ambiente_detail.php';
    }

    // ==================== SEMANAS (reabrir) ====================

    public function reabrirSemana(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $semana_id = isset($_POST['semana_id']) ? (int) $_POST['semana_id'] : 0;
        if ($semana_id <= 0) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'ID inválido.']); return; }
            setFlash('error', 'ID inválido.');
            redirect(BASE_URL . 'admin/inicio');
            return;
        }

        try {
            $reopened = (new Semana($this->db))->reopen($semana_id);

            if ($reopened) {
                $this->db->prepare('UPDATE discusiones SET estado = \'abierta\' WHERE semana_id = ? AND estado = \'cerrada\'')->execute([$semana_id]);
                $msg = 'Semana reabierta correctamente.';
                if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
                setFlash('success', $msg);
            } else {
                $msg = 'Semana no encontrada.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
            }
        } catch (PDOException $e) {
            $msg = 'Error al reabrir la semana.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
        }

        redirect(BASE_URL . 'admin/inicio');
    }

    // ==================== CONFIGURACIÓN SMTP ====================

    public function perfil(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        require __DIR__ . '/../Views/docente/perfil.php';
    }

    public function guardarPerfil(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'admin/perfil');
            return;
        }

        $section = $_POST['section'] ?? '';
        $adminId = $this->adminId();

        if ($section === 'photo') {
            $file = $_FILES['foto'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $msg = 'Seleccione una imagen válida.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'admin/perfil');
                return;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowed)) {
                $msg = 'Formato no permitido. Use JPG, PNG, GIF o WEBP.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'admin/perfil');
                return;
            }

            if ($file['size'] > 2 * 1024 * 1024) {
                $msg = 'La imagen no debe superar 2MB.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'admin/perfil');
                return;
            }

            $foto = uploadProfilePhoto($file, $adminId);
            if ($foto) {
                try {
                    (new User($this->db))->updateFoto($adminId, $foto);
                    $_SESSION['user_foto'] = $foto;
                    $msg = 'Foto de perfil actualizada correctamente.';
                    if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg]); return; }
                    setFlash('success', $msg);
                } catch (PDOException $e) {
                    $msg = 'Error al guardar la foto en la base de datos.';
                    if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 500); return; }
                    setFlash('error', $msg);
                }
            } else {
                $msg = 'Error al subir la imagen.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
            }

            redirect(BASE_URL . 'admin/perfil');
            return;
        }

        if ($section === 'password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $msg = 'Todos los campos son obligatorios.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'admin/perfil');
                return;
            }

            if (!passwordComplejidadOk($newPassword)) {
                $msg = 'La nueva contraseña debe tener al menos 8 caracteres, con al menos una mayúscula, una minúscula y un número.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'admin/perfil');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $msg = 'Las contraseñas no coinciden.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'admin/perfil');
                return;
            }

            try {
                $user = (new User($this->db))->getPasswordById($adminId);

                if (!$user || !password_verify($currentPassword, $user)) {
                    $msg = 'La contraseña actual es incorrecta.';
                    if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'admin/perfil');
                    return;
                }

                if (passwordYaUsada($this->db, $adminId, $newPassword)) {
                    $msg = 'La nueva contraseña ya fue utilizada anteriormente. Elija una contraseña diferente.';
                    if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'admin/perfil');
                    return;
                }

                $this->db->beginTransaction();
                guardarPasswordHistorial($this->db, $adminId, $user);
                (new User($this->db))->updatePassword($adminId, $newPassword);
                $this->db->commit();

                $mailer = new Mailer($this->db);
                $mailer->sendProfileChangeNotification(
                    $_SESSION['user_email'] ?? '',
                    $_SESSION['user_nombre'] ?? '',
                    'password',
                    ['Contraseña' => 'Cambiada exitosamente'],
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );

                $msg = 'Contraseña actualizada correctamente.';
                if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg]); return; }
                setFlash('success', $msg);
            } catch (PDOException $e) {
                $msg = 'Error al actualizar la contraseña.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 500); return; }
                setFlash('error', $msg);
            }

            redirect(BASE_URL . 'admin/perfil');
            return;
        }

        if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Sección no válida.']); return; }
        redirect(BASE_URL . 'admin/perfil');
    }

    public function configurarCorreo(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $settings = $this->getSmtpSettings();

        require __DIR__ . '/../Views/admin/smtp_config.php';
    }

    public function guardarConfigCorreo(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no permitido.']); return; }
            redirect(BASE_URL . 'admin/configurarCorreo');
            return;
        }

        $data = [
            'smtp_host'       => sanitize($_POST['smtp_host'] ?? ''),
            'smtp_port'       => sanitize($_POST['smtp_port'] ?? '587'),
            'smtp_user'       => sanitize($_POST['smtp_user'] ?? ''),
            'smtp_pass'       => $_POST['smtp_pass'] ?? '',
            'smtp_encryption' => in_array($_POST['smtp_encryption'] ?? '', ['tls', 'ssl', 'none']) ? $_POST['smtp_encryption'] : 'tls',
            'mail_from_email' => sanitize($_POST['mail_from_email'] ?? ''),
            'mail_from_name'  => sanitize($_POST['mail_from_name'] ?? ''),
        ];

        if ($data['smtp_host'] === '' || $data['mail_from_email'] === '') {
            $msg = 'El host SMTP y el remitente son obligatorios.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/configurarCorreo');
            return;
        }

        if ($data['smtp_pass'] === '') {
            $current = $this->getSmtpSettings();
            $data['smtp_pass'] = $current['smtp_pass'] ?? '';
        }

        try {
            $stmt = $this->db->prepare('INSERT INTO configuraciones (clave, valor) VALUES (:clave, :valor)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)');

            foreach ($data as $clave => $valor) {
                $stmt->execute([':clave' => $clave, ':valor' => $valor]);
            }

            $msg = 'Configuración SMTP guardada correctamente.';
            if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
            setFlash('success', $msg);
            redirect(BASE_URL . 'admin/configurarCorreo');
        } catch (PDOException $e) {
            $msg = 'Error al guardar la configuración.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/configurarCorreo');
        }
    }

    private function getSmtpSettings(): array
    {
        $defaults = [
            'smtp_host'       => '',
            'smtp_port'       => '587',
            'smtp_user'       => '',
            'smtp_pass'       => '',
            'smtp_encryption' => 'tls',
            'mail_from_email' => '',
            'mail_from_name'  => 'Sigametec',
        ];

        try {
            $stmt = $this->db->query('SELECT clave, valor FROM configuraciones');
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            return $defaults;
        }

        return array_merge($defaults, $rows);
    }

    // ==================== REGISTRO DE ROSTROS (ADMIN) ====================

    public function formularioRostro(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $estudiante_id = isset($_GET['estudiante_id']) ? (int) $_GET['estudiante_id'] : 0;

        try {
            $stmt = $this->db->prepare('SELECT id, apellidos, nombres, foto FROM usuarios WHERE id = :id AND rol = "estudiante" LIMIT 1');
            $stmt->execute([':id' => $estudiante_id]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$estudiante) {
                jsonResponse(['success' => false, 'message' => 'Estudiante no encontrado.'], 404);
                return;
            }

            $registro = $this->rostros->getByEstudiante($estudiante_id);

            $isAjax = true;
            $guardarRostroUrl = BASE_URL . 'admin/guardarRostro';
            $eliminarRostroUrl = BASE_URL . 'admin/eliminarRostro';
            $nombreEstudiante = $estudiante['apellidos'] . ', ' . $estudiante['nombres'];
            $estudianteId = $estudiante_id;
            $yaRegistrado = $registro !== null;
            $fotoPath = $registro['foto_path'] ?? null;
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al cargar los datos.'], 500);
            return;
        }

        require __DIR__ . '/../Views/docente/registro_caras.php';
    }

    public function guardarRostro(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $estudiante_id = (int) ($_POST['estudiante_id'] ?? 0);
        $descriptor = trim((string) ($_POST['descriptor'] ?? ''));
        $foto = trim((string) ($_POST['foto'] ?? ''));

        $decoded = json_decode($descriptor, true);
        if (!is_array($decoded) || count($decoded) !== 128) {
            jsonResponse(['success' => false, 'message' => 'Descriptor facial no válido.']);
            return;
        }
        $descriptorLimpio = json_encode(array_map(fn($v) => (float) $v, $decoded), JSON_UNESCAPED_UNICODE);

        try {
            $stmt = $this->db->prepare('SELECT id FROM usuarios WHERE id = :id AND rol = "estudiante" LIMIT 1');
            $stmt->execute([':id' => $estudiante_id]);
            if (!$stmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Estudiante no encontrado.'], 404);
                return;
            }

            $fotoPath = saveFacePhoto($foto, $estudiante_id);
            if (!$fotoPath) {
                jsonResponse(['success' => false, 'message' => 'No se pudo guardar la foto.']);
                return;
            }

            $ok = $this->rostros->save($estudiante_id, $descriptorLimpio, $fotoPath, (int) $_SESSION['user_id']);
            if (!$ok) {
                jsonResponse(['success' => false, 'message' => 'Error al guardar el rostro.'], 500);
                return;
            }

            jsonResponse(['success' => true, 'message' => 'Rostro registrado correctamente.', 'redirect' => BASE_URL . 'admin/estudiantes']);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al guardar el rostro.'], 500);
        }
    }

    public function eliminarRostro(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $estudiante_id = (int) ($_POST['estudiante_id'] ?? 0);

        try {
            $stmt = $this->db->prepare('SELECT id FROM usuarios WHERE id = :id AND rol = "estudiante" LIMIT 1');
            $stmt->execute([':id' => $estudiante_id]);
            if (!$stmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Estudiante no encontrado.'], 404);
                return;
            }

            $registro = $this->rostros->getByEstudiante($estudiante_id);
            if ($registro && !empty($registro['foto_path'])) {
                $file = ROOT_PATH . 'public/' . $registro['foto_path'];
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            $this->rostros->delete($estudiante_id);
            jsonResponse(['success' => true, 'message' => 'Rostro eliminado correctamente.']);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al eliminar el rostro.'], 500);
        }
    }

    // ==================== NIVELES DE MAGIA ====================

    public function nivelesMago(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        try {
            $niveles = $this->nivelesMago->getAll();
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los niveles de magia.');
            $niveles = [];
        }

        require __DIR__ . '/../Views/admin/niveles_mago.php';
    }

    public function formularioNivel(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $nivel = null;
        $poderesTexto = '';

        if ($id !== null) {
            try {
                $nivel = $this->nivelesMago->getById($id);
                if (!$nivel) {
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Nivel no encontrado.']); return; }
                    setFlash('error', 'Nivel no encontrado.');
                    redirect(BASE_URL . 'admin/nivelesMago');
                    return;
                }
                $poderes = json_decode($nivel['poderes'] ?? '[]', true) ?: [];
                $poderesTexto = implode("\n", array_map(
                    fn($p) => trim((string) ($p['icono'] ?? '')) . '|' . trim((string) ($p['texto'] ?? '')),
                    $poderes
                ));
            } catch (PDOException $e) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar el nivel.']); return; }
                setFlash('error', 'Error al cargar el nivel.');
                redirect(BASE_URL . 'admin/nivelesMago');
                return;
            }
        }

        require __DIR__ . '/../Views/admin/nivel_form.php';
    }

    public function guardarNivel(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no permitido.']); return; }
            redirect(BASE_URL . 'admin/nivelesMago');
            return;
        }

        $id          = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $clave       = strtolower(trim(sanitize($_POST['clave'] ?? '')));
        $nombre      = trim(sanitize($_POST['nombre'] ?? ''));
        $rango       = (int) ($_POST['rango'] ?? 0);
        $min_nota    = (float) ($_POST['min_nota'] ?? 0);
        $max_nota    = (float) ($_POST['max_nota'] ?? 0);
        $mana        = (int) ($_POST['mana'] ?? 0);
        $icono       = trim(sanitize($_POST['icono'] ?? 'fas fa-star'));
        $clase       = trim(sanitize($_POST['clase'] ?? 'badge-secondary'));
        $avatar      = trim(sanitize($_POST['avatar'] ?? 'assets/img/avatar-iniciado.svg'));
        $mensaje     = trim(sanitize($_POST['mensaje'] ?? ''));
        $costoInput  = trim($_POST['costo_donacion'] ?? '');
        $colorMana   = trim($_POST['color_mana'] ?? '#ffc107');
        $estado      = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;

        if ($icono === '') $icono = 'fas fa-star';
        if ($clase === '') $clase = 'badge-secondary';
        if ($avatar === '') $avatar = 'assets/img/avatar-iniciado.svg';
        if ($colorMana === '' || !preg_match('/^#[0-9a-fA-F]{3,8}$/', $colorMana)) {
            $colorMana = '#ffc107';
        }

        $error = null;
        if ($nombre === '' || $clave === '') {
            $error = 'El nombre y la clave son obligatorios.';
        } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $clave)) {
            $error = 'La clave solo puede contener letras minúsculas, números y guion bajo (ej: aprendiz).';
        } elseif ($rango < 1) {
            $error = 'El rango debe ser un número mayor o igual a 1.';
        } elseif ($min_nota < 0 || $max_nota <= 0 || $max_nota > 20 || $max_nota <= $min_nota) {
            $error = 'El rango de notas es inválido. Mínimo debe ser menor que máximo (0 a 20).';
        } elseif ($mana < 0) {
            $error = 'La cantidad de maná no puede ser negativa.';
        }

        if ($error === null) {
            try {
                if ($this->nivelesMago->existeClave($clave, $id)) {
                    $error = 'Ya existe un nivel con la clave "' . $clave . '".';
                } elseif ($this->nivelesMago->existeRango($rango, $id)) {
                    $error = 'Ya existe un nivel con el rango ' . $rango . '.';
                } elseif ($this->nivelesMago->existeSolapamiento($min_nota, $max_nota, $id)) {
                    $error = 'El rango de notas se solapa con otro nivel.';
                }
            } catch (PDOException $e) {
                $error = 'Error al validar el nivel.';
            }
        }

        if ($error !== null) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $error]); return; }
            setFlash('error', $error);
            redirect(BASE_URL . 'admin/formularioNivel' . ($id ? "?id={$id}" : ''));
            return;
        }

        $poderes = [];
        foreach (explode("\n", $_POST['poderes'] ?? '') as $linea) {
            $linea = trim($linea);
            if ($linea === '') continue;
            $partes = array_map('trim', explode('|', $linea, 2));
            if (count($partes) === 2 && $partes[0] !== '' && $partes[1] !== '') {
                $poderes[] = ['icono' => $partes[0], 'texto' => $partes[1]];
            }
        }
        $poderesJson = $poderes ? json_encode($poderes, JSON_UNESCAPED_UNICODE) : null;

        $costoDonacion = null;
        if ($costoInput !== '') {
            $costoDonacion = (float) $costoInput;
            if ($costoDonacion <= 0) {
                $costoDonacion = null;
            }
        }

        $data = [
            'clave'          => $clave,
            'nombre'         => $nombre,
            'rango'          => $rango,
            'min_nota'       => $min_nota,
            'max_nota'       => $max_nota,
            'icono'          => $icono,
            'clase'          => $clase,
            'avatar'         => $avatar,
            'mana'          => $mana,
            'color_mana'    => $colorMana,
            'mensaje'        => $mensaje !== '' ? $mensaje : null,
            'poderes'        => $poderesJson,
            'costo_donacion' => $costoDonacion,
            'estado'         => $estado,
        ];

        try {
            if ($id !== null) {
                $this->nivelesMago->update($id, $data);
                $msg = 'Nivel actualizado correctamente.';
            } else {
                $this->nivelesMago->create($data);
                $msg = 'Nivel creado correctamente.';
            }
        } catch (PDOException $e) {
            $msg = 'Error al guardar el nivel.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioNivel' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
        setFlash('success', $msg);
        redirect(BASE_URL . 'admin/nivelesMago');
    }

    public function eliminarNivel(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            if ($this->nivelesMago->count() <= 1) {
                $success = false;
                $message = 'No se puede eliminar: debe existir al menos un nivel.';
            } else {
                $success = $this->nivelesMago->delete($id);
                $message = $success ? 'Nivel eliminado correctamente.' : 'Nivel no encontrado.';
            }
        } catch (PDOException $e) {
            $success = false;
            $message = 'Error al eliminar el nivel.';
        }

        if ($isAjax) { jsonResponse(['success' => $success, 'message' => $message]); return; }
        setFlash($success ? 'success' : 'error', $message);
        redirect(BASE_URL . 'admin/nivelesMago');
    }

    public function detalleNivel(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $nivel = null;

        try {
            $nivel = $this->nivelesMago->getById($id);
            if (!$nivel) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Nivel no encontrado.']); return; }
                setFlash('error', 'Nivel no encontrado.');
                redirect(BASE_URL . 'admin/nivelesMago');
                return;
            }
            $nivel['poderes'] = json_decode($nivel['poderes'] ?? '[]', true) ?: [];
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar el nivel.']); return; }
            setFlash('error', 'Error al cargar el nivel.');
            redirect(BASE_URL . 'admin/nivelesMago');
            return;
        }

        require __DIR__ . '/../Views/admin/nivel_detail.php';
    }

    // ==================== BENEFICIOS ====================

    public function beneficios(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        try {
            $lista = $this->beneficios->getAll();
            foreach ($lista as &$b) {
                $b['total_canjes'] = $this->beneficios->countCanjes((int) $b['id']);
            }
            unset($b);
        } catch (PDOException $e) {
            $lista = [];
        }

        require __DIR__ . '/../Views/admin/beneficios.php';
    }

    public function formularioBeneficio(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $beneficio = null;

        if ($id !== null) {
            try {
                $beneficio = $this->beneficios->getById($id);
                if (!$beneficio) {
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Beneficio no encontrado.']); return; }
                    setFlash('error', 'Beneficio no encontrado.');
                    redirect(BASE_URL . 'admin/beneficios');
                    return;
                }
            } catch (PDOException $e) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar el beneficio.']); return; }
                setFlash('error', 'Error al cargar el beneficio.');
                redirect(BASE_URL . 'admin/beneficios');
                return;
            }
        }

        require __DIR__ . '/../Views/admin/beneficio_form.php';
    }

    public function guardarBeneficio(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'admin/beneficios');
            return;
        }

        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $nombre = sanitize($_POST['nombre'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $tipo = sanitize($_POST['tipo'] ?? '');
        $costo = filter_input(INPUT_POST, 'costo_gemas', FILTER_VALIDATE_INT);
        $estado = isset($_POST['estado']) ? 1 : 0;

        $tiposValidos = ['punto_extra', 'extension_plazo', 'examen_extra'];

        if (empty($nombre) || empty($tipo) || $costo === false || $costo < 1) {
            $msg = 'Nombre, tipo y costo válido son obligatorios.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioBeneficio' . ($id ? "?id={$id}" : ''));
            return;
        }

        if (!in_array($tipo, $tiposValidos)) {
            $msg = 'Tipo de beneficio no válido.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioBeneficio' . ($id ? "?id={$id}" : ''));
            return;
        }

        $data = [
            'nombre' => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'tipo' => $tipo,
            'costo_gemas' => $costo,
            'puntos_bonus' => $tipo === 'punto_extra' ? max(1, (int) ($_POST['puntos_bonus'] ?? 1)) : 1,
            'estado' => $estado,
        ];

        try {
            if ($id !== null) {
                $this->beneficios->update($id, $data);
                $msg = 'Beneficio actualizado correctamente.';
            } else {
                $this->beneficios->create($data);
                $msg = 'Beneficio creado correctamente.';
            }
        } catch (PDOException $e) {
            $msg = 'Error al guardar el beneficio.';
            if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'admin/formularioBeneficio' . ($id ? "?id={$id}" : ''));
            return;
        }

        if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
        setFlash('success', $msg);
        redirect(BASE_URL . 'admin/beneficios');
    }

    public function eliminarBeneficio(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;
        $isAjax = isAjaxRequest();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            $canjesCount = $this->beneficios->countCanjes($id);
            if ($canjesCount > 0) {
                $success = false;
                $message = 'No se puede eliminar: tiene ' . $canjesCount . ' canje(s) registrado(s). Desactívelo en su lugar.';
            } else {
                $stmt = $this->db->prepare('DELETE FROM beneficios WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $success = $stmt->rowCount() > 0;
                $message = $success ? 'Beneficio eliminado correctamente.' : 'Beneficio no encontrado.';
            }
        } catch (PDOException $e) {
            $success = false;
            $message = 'Error al eliminar el beneficio.';
        }

        if ($isAjax) { jsonResponse(['success' => $success, 'message' => $message]); return; }
        setFlash($success ? 'success' : 'error', $message);
        redirect(BASE_URL . 'admin/beneficios');
    }

    public function detalleBeneficio(): void
    {
        $this->requireAdmin();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $beneficio = null;

        try {
            $beneficio = $this->beneficios->getById($id);
            if (!$beneficio) {
                if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Beneficio no encontrado.']); return; }
                setFlash('error', 'Beneficio no encontrado.');
                redirect(BASE_URL . 'admin/beneficios');
                return;
            }
            $beneficio['total_canjes'] = $this->beneficios->countCanjes($id);
        } catch (PDOException $e) {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Error al cargar el beneficio.']); return; }
            setFlash('error', 'Error al cargar el beneficio.');
            redirect(BASE_URL . 'admin/beneficios');
            return;
        }

        require __DIR__ . '/../Views/admin/beneficio_detail.php';
    }
}