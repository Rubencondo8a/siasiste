<?php

declare(strict_types=1);

class EstudianteController
{
    private PDO $db;
    private Curso $cursos;
    private Semana $semanas;
    private Calificacion $calificaciones;
    private Horario $horarios;
    private Asignacion $asignaciones;
    private AyudaCompanero $ayudas;
    private SolicitudExamen $solicitudes;
    private Asistencia $asistencias;
    private Rostro $rostros;
    private NivelMago $nivelesMago;
    private PuntoParticipacion $puntosModel;
    private Beneficio $beneficios;
    private Canje $canjes;
    private Apreciacion $apreciaciones;
    private Grupo $grupos;
    private Discusion $discusiones;
    private MensajeDiscusion $mensajesDiscusion;
    private DiscusionLeida $discusionLeida;
    private TypingIndicador $typing;
    private MensajeLike $likes;
    private MensajeAprobacion $aprobaciones;
    private ParticipacionVivo $participacionVivo;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->cursos = new Curso($db);
        $this->semanas = new Semana($db);
        $this->calificaciones = new Calificacion($db);
        $this->horarios = new Horario($db);
        $this->asignaciones = new Asignacion($db);
        $this->ayudas = new AyudaCompanero($db);
        $this->solicitudes = new SolicitudExamen($db);
        $this->asistencias = new Asistencia($db);
        $this->rostros = new Rostro($db);
        $this->nivelesMago = new NivelMago($db);
        $this->puntosModel = new PuntoParticipacion($db);
        $this->beneficios = new Beneficio($db);
        $this->canjes = new Canje($db);
        $this->apreciaciones = new Apreciacion($db);
        $this->grupos = new Grupo($db);
        $this->discusiones = new Discusion($db);
        $this->mensajesDiscusion = new MensajeDiscusion($db);
        $this->discusionLeida = new DiscusionLeida($db);
        $this->typing = new TypingIndicador($db);
        $this->likes = new MensajeLike($db);
        $this->aprobaciones = new MensajeAprobacion($db);
        $this->participacionVivo = new ParticipacionVivo($db);
    }

    private function nivelesDeMago(): array
    {
        try {
            return $this->nivelesMago->getAllActivos();
        } catch (PDOException $e) {
            return [];
        }
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function requireEstudiante(): void
    {
        if (!isLoggedIn() || !isEstudiante()) {
            setFlash('error', 'No tiene permiso para acceder a esta sección.');
            redirect(BASE_URL . 'auth/iniciarSesion');
        }
    }

    private function studentId(): int
    {
        return (int) $_SESSION['user_id'];
    }

    private function promedioGeneralEstudiante(int $studentId): float
    {
        try {
            $rows = $this->calificaciones->getResumenPorSemanas($studentId);
            $apreciaciones = $this->apreciaciones->getApreciacionesPorEstudiante($studentId);
            $totalPonderado = 0.0;
            $totalPorcentaje = 0.0;
            $semanas = [];
            foreach ($rows as $row) {
                $semanaId = (int) $row['id'];
                $cursoId = (int) $row['curso_id'];
                if (!isset($semanas[$semanaId])) {
                    $semanas[$semanaId] = ['sp' => 0.0, 'sw' => 0.0, 'curso_id' => $cursoId];
                }
                $semanas[$semanaId]['sp'] += (float) $row['nota'] * (float) $row['porcentaje'] / 100;
                $semanas[$semanaId]['sw'] += (float) $row['porcentaje'];
            }
            foreach ($semanas as $semanaId => $s) {
                if ($s['sw'] > 0) {
                    $prom = $s['sp'];
                    $cursoId = $s['curso_id'];
                    if (isset($apreciaciones[$semanaId][$cursoId])) {
                        $prom = max(0, $prom + $apreciaciones[$semanaId][$cursoId]);
                    }
                    $totalPonderado += $prom;
                    $totalPorcentaje += $s['sw'];
                }
            }
            return $totalPorcentaje > 0 ? round($totalPonderado / ($totalPorcentaje / 100), 2) : 0.0;
        } catch (PDOException $e) {
            return 0.0;
        }
    }

    private function fraccionCosto(float $costo): string
    {
        if (abs($costo - (1 / 512)) < 0.000001) return '1/512';
        if (abs($costo - (1 / 256)) < 0.000001) return '1/256';
        if (abs($costo - (1 / 128)) < 0.000001) return '1/128';
        if (abs($costo - (1 / 64)) < 0.000001) return '1/64';
        return number_format($costo, 6);
    }

    public function inicio(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();

        try {
            $rows = $this->calificaciones->getResumenPorSemanas($studentId);
            $apreciacionesEst = $this->apreciaciones->getApreciacionesPorEstudiante($studentId);

            $semanasData = [];
            foreach ($rows as $row) {
                $semanaId = $row['id'];
                $cursoId = (int) $row['curso_id'];
                if (!isset($semanasData[$semanaId])) {
                    $semanasData[$semanaId] = [
                        'id' => $semanaId,
                        'nombre' => $row['nombre'],
                        'numero_semana' => $row['numero_semana'],
                        'fecha_inicio' => $row['fecha_inicio'],
                        'fecha_fin' => $row['fecha_fin'],
                        'sumaPonderada' => 0.0,
                        'sumaPorcentajes' => 0.0,
                        'curso_id' => $cursoId,
                    ];
                }
                $semanasData[$semanaId]['sumaPonderada'] += (float) $row['nota'] * (float) $row['porcentaje'] / 100;
                $semanasData[$semanaId]['sumaPorcentajes'] += (float) $row['porcentaje'];
            }

            $semanasCalificadas = [];
            $totalPonderadoGeneral = 0.0;
            $totalPorcentajeGeneral = 0.0;

            foreach ($semanasData as $semana) {
                $promedio = $semana['sumaPorcentajes'] > 0
                    ? round($semana['sumaPonderada'] / ($semana['sumaPorcentajes'] / 100), 2)
                    : 0.0;

                $cursoId = $semana['curso_id'];
                if (isset($apreciacionesEst[$semana['id']][$cursoId])) {
                    $promedio = max(0, round($promedio + $apreciacionesEst[$semana['id']][$cursoId], 2));
                }

                $totalPonderadoGeneral += $semana['sumaPonderada'];
                $totalPorcentajeGeneral += $semana['sumaPorcentajes'];

                $semanasCalificadas[] = [
                    'nombre' => $semana['nombre'],
                    'numero_semana' => $semana['numero_semana'],
                    'fecha_inicio' => $semana['fecha_inicio'],
                    'fecha_fin' => $semana['fecha_fin'],
                    'promedio' => $promedio,
                    'id' => $semana['id'],
                ];
            }

            $promedioGeneral = 0.0;
            if ($totalPorcentajeGeneral > 0) {
                $promedioGeneral = round($totalPonderadoGeneral / ($totalPorcentajeGeneral / 100), 2);
            }
        } catch (PDOException $e) {
            $semanasCalificadas = [];
            $promedioGeneral = 0.0;
        }

        $niveles = $this->nivelesDeMago();
        $nivel = studentPowerMode((float) $promedioGeneral, $niveles);
        $manaNivel = (int) $nivel['mana'];
        $costoDonacion = costoDonacionMana($nivel);
        $puedeAyudar = $costoDonacion !== null;
        $esAprendiz = $nivel['clave'] === 'aprendiz';
        $cursosConCompaneros = [];
        $manaRecibido = [];
        $solicitudesEstudiante = [];
        $manaDisponible = 0.0;
        $manaConsumido = 0.0;
        $manaRecibidoTotal = 0.0;
        $porcentajeMana = 0.0;
        $nivelesMago = $niveles;

        try {
            $manaDisponible = $this->ayudas->balance($studentId, (float) $manaNivel);
            $manaConsumido = $this->ayudas->sumaCostoDonado($studentId);
            $manaRecibidoTotal = $this->ayudas->sumaTotalRecibida($studentId);
            $porcentajeMana = $manaNivel > 0 ? round($manaDisponible / $manaNivel * 100, 1) : 0.0;
            $cursosConCompaneros = $this->ayudas->getCursosConCompaneros($studentId);

            foreach ($cursosConCompaneros as &$cursoC) {
                $cursoId = (int) $cursoC['curso_id'];
                $semanasAyudadas = $this->ayudas->getSemanasAyudadasPorCompanero($studentId, $cursoId);
                $solicitudesPendientes = $this->solicitudes->getPendientesPorCurso($cursoId);
                $promediosPorSemana = [];
                foreach ($cursoC['semanas'] as $semana) {
                    $promediosPorSemana[(int) $semana['id']] = $this->calificaciones->getPromediosPorSemanaYCurso((int) $semana['id'], $cursoId);
                }

                foreach ($cursoC['companeros'] as &$comp) {
                    $compId = (int) $comp['id'];
                    $promedioComp = $this->promedioGeneralEstudiante($compId);
                    $comp['poder'] = studentPowerMode($promedioComp, $niveles);
                    $comp['puede_recibir'] = puedeRecibirMana($nivel, $comp['poder']);
                    $comp['visible'] = (($comp['poder']['rango'] ?? 0) < (int) $nivel['rango'])
                        || (((int) $nivel['rango']) === 1 && ($comp['poder']['rango'] ?? 0) === 1);

                    $semanas = [];
                    foreach ($cursoC['semanas'] as $semana) {
                        $semanaId = (int) $semana['id'];
                        $nota = $promediosPorSemana[$semanaId][$compId] ?? null;
                        $semanas[] = [
                            'semana_id' => $semanaId,
                            'numero_semana' => (int) $semana['numero_semana'],
                            'nombre' => $semana['nombre'],
                            'nota' => $nota,
                            'semaforo' => $nota !== null ? semaforoSemana((float) $nota) : null,
                            'ya_ayude' => in_array($semanaId, $semanasAyudadas[$compId] ?? [], true),
                            'tiene_pendiente' => in_array($semanaId, $solicitudesPendientes[$compId] ?? [], true),
                        ];
                    }
                    $comp['semanas'] = $semanas;
                }
                unset($comp);
                $cursoC['companeros'] = array_values(array_filter($cursoC['companeros'], fn($c) => $c['visible']));
            }
            unset($cursoC);
            $cursosConCompaneros = array_values(array_filter($cursosConCompaneros, fn($c) => !empty($c['companeros'])));

            $manaRecibido = $this->ayudas->getRecibidasPorCurso($studentId);
            $solicitudesEstudiante = $this->solicitudes->getPendientesPorEstudiante($studentId);
        } catch (PDOException $e) {
            $cursosConCompaneros = [];
            $manaRecibido = [];
            $solicitudesEstudiante = [];
            $manaDisponible = 0.0;
            $manaConsumido = 0.0;
            $manaRecibidoTotal = 0.0;
            $porcentajeMana = 0.0;
        }

        $asistenciaCursos = [];
        try {
            $asistenciaCursos = $this->asistencias->getResumenPorEstudiante($studentId);
            foreach ($asistenciaCursos as &$ac) {
                $ac['historial'] = $this->asistencias->getHistorialPorEstudianteYCurso($studentId, (int) $ac['curso_id']);
            }
            unset($ac);
        } catch (PDOException $e) {
            $asistenciaCursos = [];
        }

        $mostrarBienvenidaMago = !empty($_SESSION['_bienvenida_mago']);
        unset($_SESSION['_bienvenida_mago']);
        if (empty($_SESSION['_sigametec_mago_visto'])) {
            $_SESSION['_sigametec_mago_visto'] = true;
            $mostrarBienvenidaMago = true;
        }

        $resumenesPuntos = [];
        try {
            $cursosEst = $this->asignaciones->getCursosByEstudiante($studentId);
            foreach ($cursosEst as $cur) {
                $resumenesPuntos[(int) $cur['id']] = $this->puntosModel->getResumenEstudiante((int) $cur['id'], $studentId);
                $resumenesPuntos[(int) $cur['id']]['curso_nombre'] = $cur['nombre'];
                $resumenesPuntos[(int) $cur['id']]['curso_codigo'] = $cur['codigo'];
            }
        } catch (PDOException $e) {
            $resumenesPuntos = [];
        }

        require __DIR__ . '/../Views/estudiante/dashboard.php';
    }

    public function asistenciaCursoAjax(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();
        $cursoId = (int) ($_GET['curso_id'] ?? 0);
        if ($cursoId <= 0) { echo '<div class="text-center text-muted py-3">Curso no v&aacute;lido</div>'; return; }

        try {
            if (!$this->asignaciones->exists($studentId, $cursoId)) {
                echo '<div class="text-center text-muted py-3">Curso no encontrado</div>';
                return;
            }

            $curso = $this->cursos->getByIdParaEstudiante($cursoId);
            $resumen = $this->asistencias->getResumenPorEstudiante($studentId);
            $historial = $this->asistencias->getHistorialPorEstudianteYCurso($studentId, $cursoId);

            $resumenCurso = null;
            foreach ($resumen as $r) {
                if ((int) $r['curso_id'] === $cursoId) { $resumenCurso = $r; break; }
            }
        } catch (PDOException $e) {
            echo '<div class="text-center text-muted py-3">Error al cargar la asistencia</div>';
            return;
        }

        $total = $resumenCurso ? (int) $resumenCurso['total'] : 0;
        $presentes = $resumenCurso ? (int) $resumenCurso['presentes'] : 0;
        $tardanzas = $resumenCurso ? (int) $resumenCurso['tardanzas'] : 0;
        $ausencias = $resumenCurso ? (int) $resumenCurso['ausencias'] : 0;
        $justificados = $resumenCurso ? (int) $resumenCurso['justificados'] : 0;
        $asistio = $presentes + $tardanzas + $justificados;
        $pctBase = $total > 0 ? $asistio / $total * 100 : 0;
        $penalTardanza = $tardanzas > 0 ? min($tardanzas * 0.05, $pctBase) : 0;
        $pct = $total > 0 ? max(0, $pctBase - $penalTardanza) : 0;
        $pctAusencia = $total > 0 ? $ausencias / $total * 100 : 0;
        $riesgoAusencia = $pctAusencia > 30;
        $fmt = fn($v) => number_format($v, 2, '.', '');

        $estadoBadge = [
            'P' => ['label' => 'Presente',    'icon' => 'fa-check-circle', 'cls' => 'badge-success'],
            'T' => ['label' => 'Tardanza',    'icon' => 'fa-clock',        'cls' => 'badge-warning'],
            'A' => ['label' => 'Ausente',     'icon' => 'fa-times-circle', 'cls' => 'badge-danger'],
            'J' => ['label' => 'Justificado', 'icon' => 'fa-file-medical', 'cls' => 'badge-info'],
        ];
        ?>
        <div class="mb-2 text-center">
            <h5 class="mb-0"><?= htmlspecialchars($curso['nombre']) ?> <small class="text-muted">(<?= htmlspecialchars($curso['codigo']) ?>)</small></h5>
            <small class="text-muted"><?= htmlspecialchars($curso['ciclo']) ?> &middot; <?= htmlspecialchars($curso['periodo_nombre']) ?></small>
        </div>

        <div class="d-flex flex-wrap justify-content-center gap-2 mb-3" style="gap:10px;">
            <span class="badge badge-success px-3 py-2"><?= $presentes ?> Presente</span>
            <span class="badge badge-warning px-3 py-2"><?= $tardanzas ?> Tardanza</span>
            <span class="badge badge-danger px-3 py-2"><?= $ausencias ?> Ausente</span>
            <span class="badge badge-info px-3 py-2"><?= $justificados ?> Justificado</span>
        </div>

        <div class="mb-3" style="max-width:420px;margin:0 auto;">
            <div class="d-flex justify-content-between align-items-center small mb-1">
                <span class="text-success"><i class="fas fa-user-check mr-1"></i>Presente <strong><?= $fmt($pct) ?>%</strong></span>
                <?php if ($tardanzas > 0): ?>
                    <span class="text-warning" title="Cada tardanza descuenta 0.05% de tu presencia"><i class="fas fa-clock mr-1"></i>&minus;<?= $fmt($penalTardanza) ?>%</span>
                <?php endif; ?>
                <span class="<?= $riesgoAusencia ? 'text-danger font-weight-bold' : 'text-danger' ?>"><strong><?= $fmt($pctAusencia) ?>%</strong> Ausencia <i class="fas fa-user-times ml-1"></i></span>
            </div>
            <div class="position-relative" style="height:14px;">
                <div class="progress mb-0" style="height:14px;">
                    <div class="progress-bar bg-success" style="width:<?= $fmt($pct) ?>%;"></div>
                    <?php if ($penalTardanza > 0): ?>
                        <div class="progress-bar bg-warning" style="width:<?= $fmt($penalTardanza) ?>%;"></div>
                    <?php endif; ?>
                    <div class="progress-bar bg-danger" style="width:<?= $fmt($pctAusencia) ?>%;"></div>
                </div>
                <div style="position:absolute; top:-2px; left:70%; width:2px; height:18px; background:#343a40;"></div>
                <div style="position:absolute; top:-11px; left:70%; transform:translateX(-50%); font-size:.68rem; font-weight:700; color:#343a40;">30%</div>
            </div>
            <small class="text-muted d-block mt-1">
                Límite máximo de inasistencia: 30%
                <?php if ($tardanzas > 0): ?> &middot; Cada tardanza descuenta 0.05% de tu presencia.<?php endif; ?>
                <?php if ($riesgoAusencia): ?>
                    <span class="text-danger font-weight-bold d-block"><i class="fas fa-exclamation-triangle mr-1"></i>Superaste el 30% de inasistencias.</span>
                <?php endif; ?>
            </small>
        </div>

        <?php if (empty($historial)): ?>
            <div class="text-center text-muted py-3">
                <i class="fas fa-user-check" style="font-size:1.5rem;"></i><br>Sin asistencia registrada
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered nodt mb-0" style="font-size:.88rem;">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width:110px;">Fecha</th>
                            <th>Estado</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $h): ?>
                            <tr>
                                <td class="text-center"><?= date('d/m/Y', strtotime($h['fecha'])) ?></td>
                                <td>
                                    <span class="badge <?= $estadoBadge[$h['estado']]['cls'] ?? 'badge-secondary' ?>">
                                        <i class="fas <?= $estadoBadge[$h['estado']]['icon'] ?? 'fa-circle' ?> mr-1"></i>
                                        <?= $estadoBadge[$h['estado']]['label'] ?? htmlspecialchars($h['estado']) ?>
                                    </span>
                                </td>
                                <td class="text-muted">
                                    <?= !empty($h['observacion']) ? htmlspecialchars($h['observacion']) : '&mdash;' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php
    }

    public function ayudarCompanero(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();
        $receptorId = isset($_POST['receptor_id']) ? (int) $_POST['receptor_id'] : 0;
        $cursoId = isset($_POST['curso_id']) ? (int) $_POST['curso_id'] : 0;
        $semanaId = isset($_POST['semana_id']) ? (int) $_POST['semana_id'] : 0;

        if ($receptorId <= 0 || $cursoId <= 0 || $semanaId <= 0) {
            setFlash('error', 'Datos inválidos.');
            redirect(BASE_URL . 'estudiante/inicio');
            return;
        }

        if ($receptorId === $studentId) {
            setFlash('error', 'No puedes enviarte un maná a ti mismo.');
            redirect(BASE_URL . 'estudiante/inicio');
            return;
        }

        $niveles = $this->nivelesDeMago();
        $nivelDonante = studentPowerMode($this->promedioGeneralEstudiante($studentId), $niveles);
        $costoDonacion = costoDonacionMana($nivelDonante);
        if ($costoDonacion === null) {
            setFlash('error', 'Tu nivel Iniciado no permite transferir maná. Mejora tu promedio para poder ayudar a tus compañeros.');
            redirect(BASE_URL . 'estudiante/inicio');
            return;
        }

        try {
            $stmt = $this->db->prepare('SELECT apellidos, nombres FROM usuarios WHERE id = :id AND rol = "estudiante" LIMIT 1');
            $stmt->execute([':id' => $receptorId]);
            $receptorData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$receptorData) {
                setFlash('error', 'Compañero no encontrado.');
                redirect(BASE_URL . 'estudiante/inicio');
                return;
            }
            $receptorNombre = $receptorData['apellidos'] . ', ' . $receptorData['nombres'];

            $nivelReceptor = studentPowerMode($this->promedioGeneralEstudiante($receptorId), $niveles);
            if (!puedeRecibirMana($nivelDonante, $nivelReceptor)) {
                setFlash('error', 'Solo puedes enviar maná a compañeros de nivel inferior (' . $nivelDonante['nombre'] . ' puede ayudar a niveles menores que el suyo). ' . $receptorNombre . ' es ' . $nivelReceptor['nombre'] . '.');
                redirect(BASE_URL . 'estudiante/inicio');
                return;
            }

            if (!$this->asignaciones->exists($studentId, $cursoId)) {
                setFlash('error', 'No perteneces a ese curso.');
                redirect(BASE_URL . 'estudiante/inicio');
                return;
            }

            if (!$this->asignaciones->exists($receptorId, $cursoId)) {
                setFlash('error', 'El compañero no pertenece a ese curso.');
                redirect(BASE_URL . 'estudiante/inicio');
                return;
            }

            $semana = $this->semanas->getByIdSimple($semanaId);
            if (!$semana || (int) $semana['curso_id'] !== $cursoId) {
                setFlash('error', 'La semana elegida no pertenece al curso.');
                redirect(BASE_URL . 'estudiante/inicio');
                return;
            }

            if ($this->ayudas->yaAyudo($studentId, $receptorId, $cursoId, $semanaId)) {
                setFlash('error', 'Ya enviaste un maná a este compañero en esta semana.');
                redirect(BASE_URL . 'estudiante/inicio');
                return;
            }

            if ($this->solicitudes->tienePendiente($receptorId, $cursoId, $semanaId)) {
                setFlash('error', 'Este compañero ya tiene una oportunidad de examen pendiente en esa semana.');
                redirect(BASE_URL . 'estudiante/inicio');
                return;
            }

            $poblacion = $this->asignaciones->countByCurso($cursoId);
            $objetivo = $this->ayudas->objetivoMana($poblacion);

            if ($this->ayudas->sumaActiva($receptorId, $cursoId, $semanaId) >= $objetivo) {
                setFlash('error', 'Este compañero ya completó el maná para su oportunidad de examen en esa semana.');
                redirect(BASE_URL . 'estudiante/inicio');
                return;
            }

            $this->db->beginTransaction();

            $this->ayudas->registrar($studentId, $receptorId, $cursoId, $semanaId, 1.0, (float) $costoDonacion);

            $nuevoTotal = $this->ayudas->sumaActiva($receptorId, $cursoId, $semanaId);
            $solicitudCreada = false;

            if ($nuevoTotal >= $objetivo) {
                $this->solicitudes->crear($receptorId, $cursoId, $semanaId, $objetivo, $nuevoTotal);
                $this->ayudas->marcarUsadas($receptorId, $cursoId, $semanaId);
                $solicitudCreada = true;
            }

            $this->db->commit();

            $semanaNombre = 'Semana ' . $semana['numero_semana'];

            if ($solicitudCreada) {
                setFlash('success', '¡Completaste el maná! ' . $receptorNombre . ' ahora tiene oportunidad de examen en ' . $semanaNombre . '. Se notificará al docente.');
            } else {
                setFlash('success', 'Maná enviado a ' . $receptorNombre . ' en ' . $semanaNombre . ' (costo ' . $this->fraccionCosto($costoDonacion) . '). Aún falta maná para completar su oportunidad de examen.');
            }
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            setFlash('error', 'Error al enviar el maná. Intente nuevamente.');
        }

        redirect(BASE_URL . 'estudiante/inicio');
    }

    public function verSemana(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;

        try {
            $semana = $this->semanas->getByIdSimple($semana_id);

            if (!$semana) {
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'estudiante/inicio');
                return;
            }

            $detalles = $this->calificaciones->getPorSemanaCompleto($semana_id, $studentId);

            $promedioSemana = 0.0;
            $weightedSum = 0.0;
            $totalPercentage = 0.0;

            foreach ($detalles as $d) {
                if ($d['nota'] !== null) {
                    $weightedSum += (float) $d['nota'] * (float) $d['porcentaje'] / 100;
                    $totalPercentage += (float) $d['porcentaje'];
                }
            }

            if ($totalPercentage > 0) {
                $promedioSemana = round($weightedSum / ($totalPercentage / 100), 2);
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar las calificaciones.');
            redirect(BASE_URL . 'estudiante/inicio');
            return;
        }

        require __DIR__ . '/../Views/estudiante/ver_semana.php';
    }

    public function horarios(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();

        try {
            $horarios = $this->horarios->getByEstudiante($studentId);
        } catch (PDOException $e) {
            $horarios = [];
        }

        require __DIR__ . '/../Views/estudiante/horario.php';
    }

    public function informacionCurso(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $cursoId = (int) ($_GET['curso_id'] ?? 0);
        if ($cursoId <= 0) { http_response_code(400); echo '<div class="text-center text-muted">ID inválido</div>'; return; }

        try {
            $curso = $this->cursos->getByIdInformacion($cursoId);
            if ($curso) {
                $curso['total_estudiantes'] = $this->asignaciones->countByCurso($cursoId);
            }

            if (!$curso) { echo '<div class="text-center text-muted">Curso no encontrado</div>'; return; }

            $horarios = $this->horarios->getByCursoSimple($cursoId);
        } catch (PDOException $e) {
            echo '<div class="text-center text-muted">Error al cargar</div>';
            return;
        }
        ?>
        <div class="text-center mb-3">
            <h5 class="mb-1"><?= htmlspecialchars($curso['nombre']) ?></h5>
            <span class="badge badge-primary"><?= htmlspecialchars($curso['codigo']) ?></span>
        </div>
        <table class="table table-bordered nodt mb-0" style="font-size:.88rem;">
            <tbody>
                <tr>
                    <td class="font-weight-bold" style="width:140px;"><i class="fas fa-graduation-cap text-info mr-1"></i> Ciclo</td>
                    <td><?= htmlspecialchars($curso['ciclo']) ?></td>
                </tr>
                <tr>
                    <td class="font-weight-bold"><i class="fas fa-calendar text-success mr-1"></i> Periodo</td>
                    <td><?= htmlspecialchars($curso['periodo_nombre']) ?></td>
                </tr>
                <tr>
                    <td class="font-weight-bold"><i class="fas fa-chalkboard-teacher text-warning mr-1"></i> Docente</td>
                    <td><?= $curso['docente_nombres'] ? htmlspecialchars($curso['docente_apellidos'] . ', ' . $curso['docente_nombres']) : '&mdash;' ?></td>
                </tr>
                <tr>
                    <td class="font-weight-bold"><i class="fas fa-users text-danger mr-1"></i> Estudiantes</td>
                    <td><span class="badge badge-dark"><?= $curso['total_estudiantes'] ?></span> inscritos</td>
                </tr>
            </tbody>
        </table>
        <?php if (!empty($horarios)): ?>
        <div class="mt-3">
            <strong style="font-size:.85rem;"><i class="fas fa-clock text-primary mr-1"></i> Horarios</strong>
            <table class="table table-sm table-bordered nodt mb-0 mt-1" style="font-size:.82rem;">
                <thead class="thead-light">
                    <tr>
                        <th>Día</th>
                        <th class="text-center">Hora</th>
                        <th>Ambiente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($horarios as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($h['dia']) ?></td>
                            <td class="text-center"><?= htmlspecialchars(substr($h['hora_inicio'],0,5)) ?> - <?= htmlspecialchars(substr($h['hora_fin'],0,5)) ?></td>
                            <td><?= htmlspecialchars($h['ambiente_nombre'] ?? '&mdash;') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php
    }

    public function misCursos(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();

        try {
            $cursos = $this->cursos->getByEstudiante($studentId);
            foreach ($cursos as &$c) {
                $c['total_semanas'] = $this->cursos->countSemanas((int) $c['id']);
                $c['semanas_calificadas'] = $this->semanas->countEvaluadasPorEstudianteYCurso((int) $c['id'], $studentId);
                $resumen = $this->puntosModel->getResumenEstudiante((int) $c['id'], $studentId);
                $c['total_puntos'] = $resumen['total_puntos'];
                $c['cristales'] = $resumen['cristales'];
                $c['sobrante_puntos'] = $resumen['sobrante'];
                $aprecs = $this->apreciaciones->getByEstudianteCurso($studentId, (int) $c['id']);
                $c['total_apreciaciones'] = 0.0;
                foreach ($aprecs as $ap) {
                    $c['total_apreciaciones'] += (float) $ap['puntos'];
                }
                $c['grupo_actual'] = null;
                $c['semana_actual_id'] = null;
                $c['numero_semana_actual'] = null;
                $semanaActual = $this->semanas->getSemanaActualPorCurso((int) $c['id']);
                if ($semanaActual) {
                    $c['semana_actual_id'] = (int) $semanaActual['id'];
                    $c['numero_semana_actual'] = (int) $semanaActual['numero_semana'];
                    $grupoInfo = $this->grupos->getEstudiantesConGrupo((int) $semanaActual['id']);
                    if (isset($grupoInfo[$studentId])) {
                        $c['grupo_actual'] = $grupoInfo[$studentId];
                    }
                }
            }
            unset($c);

            // Participaciones activas para auto-registro (por curso)
            $stmtPa = $this->db->prepare('
                SELECT pa.*, s.numero_semana
                FROM participaciones_activas pa
                INNER JOIN semanas s ON s.id = pa.semana_id
                WHERE pa.curso_id IN (SELECT id FROM cursos WHERE id IN (
                    SELECT curso_id FROM asignaciones WHERE estudiante_id = :student
                ))
                AND pa.estado = "abierta"
                ORDER BY pa.id DESC
            ');
            $stmtPa->execute([':student' => $studentId]);
            $participacionesActivas = $stmtPa->fetchAll(PDO::FETCH_ASSOC);
            $paInfo = [];
            foreach ($participacionesActivas as $pa) {
                $cursoId = (int) $pa['curso_id'];
                $ya = $this->participacionVivo->getCola((int) $pa['id']);
                $yaRegistrado = false;
                foreach ($ya as $cCola) {
                    if ((int) $cCola['estudiante_id'] === $studentId && $cCola['estado'] !== 'eliminado') {
                        $yaRegistrado = true;
                        break;
                    }
                }
                $paInfo[$cursoId] = [
                    'id' => (int) $pa['id'],
                    'numero_semana' => (int) $pa['numero_semana'],
                    'ya_registrado' => $yaRegistrado,
                ];
            }
        } catch (PDOException $e) {
            $cursos = [];
            $paInfo = [];
        }

        require __DIR__ . '/../Views/estudiante/mis_cursos.php';
    }

    public function detalleCurso(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();
        $cursoId = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        if ($cursoId <= 0) {
            setFlash('error', 'Curso no válido.');
            redirect(BASE_URL . 'estudiante/misCursos');
            return;
        }

        try {
            $curso = $this->cursos->getByIdParaEstudiante($cursoId, true);

            if (!$curso) {
                setFlash('error', 'Curso no encontrado.');
                redirect(BASE_URL . 'estudiante/misCursos');
                return;
            }

            header('Content-Type: text/html; charset=utf-8');
            $semanas = $this->semanas->getCalificadasPorEstudianteYCurso($cursoId, $studentId);
            $apreciacionesCurso = $this->apreciaciones->getByEstudianteCurso($studentId, $cursoId);
            $apreciacionesMap = [];
            foreach ($apreciacionesCurso as $san) {
                $apreciacionesMap[(int) $san['semana_id']] = (float) $san['puntos'];
            }

            $semanasCalificadas = [];
            $totalPonderadoGeneral = 0.0;
            $totalPorcentajeGeneral = 0.0;

            foreach ($semanas as $semana) {
                $criteriaGrades = $this->calificaciones->getPorSemanaConNota((int) $semana['id'], $studentId);

                $sumaPonderada = 0.0;
                $sumaPorcentajes = 0.0;

                foreach ($criteriaGrades as $cg) {
                    $sumaPonderada += (float) $cg['nota'] * (float) $cg['porcentaje'] / 100;
                    $sumaPorcentajes += (float) $cg['porcentaje'];
                    $totalPonderadoGeneral += (float) $cg['nota'] * (float) $cg['porcentaje'] / 100;
                    $totalPorcentajeGeneral += (float) $cg['porcentaje'];
                }

                $promedio = $sumaPorcentajes > 0 ? round($sumaPonderada / ($sumaPorcentajes / 100), 2) : null;
                $apreciacion = $apreciacionesMap[(int) $semana['id']] ?? 0.0;
                if ($apreciacion !== 0.0 && $promedio !== null) {
                    $promedio = max(0, round($promedio + $apreciacion, 2));
                }

                $semanasCalificadas[] = [
                    'id' => $semana['id'],
                    'nombre' => $semana['nombre'],
                    'numero_semana' => $semana['numero_semana'],
                    'fecha_inicio' => $semana['fecha_inicio'],
                    'fecha_fin' => $semana['fecha_fin'],
                    'promedio' => $promedio,
                ];
            }

            $promedioGeneral = $totalPorcentajeGeneral > 0
                ? round($totalPonderadoGeneral / ($totalPorcentajeGeneral / 100), 2)
                : 0;

            $chartLabels = array_map(fn($s) => 'Sem ' . $s['numero_semana'], $semanasCalificadas);
            $chartData = array_map(fn($s) => $s['promedio'] ?? 0, $semanasCalificadas);
        } catch (PDOException $e) {
            $curso = null;
            $semanasCalificadas = [];
            $promedioGeneral = 0;
            $chartLabels = [];
            $chartData = [];
        }

        require __DIR__ . '/../Views/estudiante/ficha_curso.php';
    }

    public function gruposSemanaAjax(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();
        $semanaId = (int) ($_GET['semana_id'] ?? 0);

        if ($semanaId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Semana no válida.'], 400);
            return;
        }

        try {
            $semana = $this->semanas->getByIdSimple($semanaId);
            if (!$semana) {
                $this->jsonResponse(['success' => false, 'message' => 'Semana no encontrada.'], 404);
                return;
            }

            $grupos = $this->grupos->getBySemanaConIntegrantes($semanaId);

            $miGrupoId = $this->grupos->getGrupoIdDeEstudiante($semanaId, $studentId);

            $this->jsonResponse([
                'success' => true,
                'semana' => $semana,
                'grupos' => $grupos,
                'mi_grupo_id' => $miGrupoId,
                'mi_estudiante_id' => $studentId,
            ]);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al cargar grupos.'], 500);
        }
    }

    public function detalleCursoAjax(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();
        $cursoId = (int) ($_GET['curso_id'] ?? 0);
        if ($cursoId <= 0) { echo '<div class="text-center text-muted py-3">Curso no válido</div>'; return; }

        try {
            $curso = $this->cursos->getByIdParaEstudiante($cursoId);

            $semanas = $this->semanas->getCalificadasPorEstudianteYCurso($cursoId, $studentId);

            $semanasCalificadas = [];
            $totalPonderado = 0.0;
            $totalPorcentaje = 0.0;

            foreach ($semanas as $semana) {
                $grades = $this->calificaciones->getPorSemanaConNota((int) $semana['id'], $studentId);

                $sp = 0.0; $sw = 0.0;
                foreach ($grades as $g) { $sp += (float)$g['nota'] * (float)$g['porcentaje'] / 100; $sw += (float)$g['porcentaje']; $totalPonderado += (float)$g['nota'] * (float)$g['porcentaje'] / 100; $totalPorcentaje += (float)$g['porcentaje']; }
                $prom = $sw > 0 ? round($sp / ($sw / 100), 2) : null;
                $semanasCalificadas[] = ['id'=>$semana['id'],'nombre'=>$semana['nombre'],'numero_semana'=>$semana['numero_semana'],'fecha_inicio'=>$semana['fecha_inicio'],'fecha_fin'=>$semana['fecha_fin'],'promedio'=>$prom];
            }
            $promedioGeneral = $totalPorcentaje > 0 ? round($totalPonderado / ($totalPorcentaje / 100), 2) : 0;
        } catch (PDOException $e) { $curso = null; $semanasCalificadas = []; $promedioGeneral = 0; }

        if (!$curso) { echo '<div class="text-center text-muted py-3">Curso no encontrado</div>'; return; }
        ?>
        <div class="mb-2 text-center">
            <h5 class="mb-0"><?= htmlspecialchars($curso['nombre']) ?> <small class="text-muted">(<?= htmlspecialchars($curso['codigo']) ?>)</small></h5>
            <small class="text-muted"><?= htmlspecialchars($curso['ciclo']) ?> &middot; <?= htmlspecialchars($curso['periodo_nombre']) ?>
                <?= $curso['docente_nombres'] ? ' &middot; ' . htmlspecialchars($curso['docente_apellidos'] . ', ' . $curso['docente_nombres']) : '' ?></small>
        </div>
        <?php if (!empty($semanasCalificadas)): ?>
        <div class="text-center mb-3">
            Promedio Semestral: <strong class="<?= (float) $promedioGeneral >= 13 ? 'text-primary' : 'text-danger' ?>"><?= number_format((float) $promedioGeneral, 2) ?></strong>
        </div>
        <table class="table table-hover nodt mb-0" style="font-size:.88rem;">
            <thead class="thead-dark"><tr>
                <th class="text-center" style="width:45px;">#</th>
                <th>Semana</th>
                <th class="text-center" style="width:100px;">Fechas</th>
                <th class="text-center" style="width:90px;">Promedio</th>
            </tr></thead>
            <tbody>
            <?php foreach ($semanasCalificadas as $s): ?>
                <tr style="cursor:pointer;" onclick="showWeekDetail(<?= $s['id'] ?>)">
                    <td class="text-center align-middle"><strong><?= $s['numero_semana'] ?></strong></td>
                    <td class="align-middle"><?= htmlspecialchars($s['nombre']) ?></td>
                    <td class="text-center align-middle" style="font-size:.8rem;"><?= date('d/m', strtotime($s['fecha_inicio'])) ?> - <?= date('d/m', strtotime($s['fecha_fin'])) ?></td>
                    <td class="text-center align-middle"><strong class="<?= (float) $s['promedio'] >= 13 ? 'text-primary' : 'text-danger' ?>"><?= number_format((float) $s['promedio'], 2) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="text-center text-muted py-3"><i class="fas fa-folder-open" style="font-size:1.5rem;"></i><br>Sin notas registradas</div>
        <?php endif; ?>
        <?php
    }

    public function detallePuntosCurso(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();
        $cursoId = (int) ($_GET['curso_id'] ?? 0);

        if ($cursoId <= 0) { jsonResponse(['success' => false, 'message' => 'Curso no válido.']); return; }

        try {
            if (!$this->asignaciones->exists($studentId, $cursoId)) {
                jsonResponse(['success' => false, 'message' => 'Curso no encontrado.']); return;
            }

            $resumen = $this->puntosModel->getResumenEstudiante($cursoId, $studentId);
            $detalle = $this->puntosModel->getPorEstudiante($cursoId, $studentId);

            jsonResponse([
                'success' => true,
                'data' => [
                    'total_puntos' => $resumen['total_puntos'],
                    'cristales'    => $resumen['cristales'],
                    'sobrante'     => $resumen['sobrante'],
                    'detalle'      => $detalle,
                ],
            ]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al cargar los datos.']);
        }
    }

    public function detalleApreciacionesCurso(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();
        $cursoId = (int) ($_GET['curso_id'] ?? 0);

        if ($cursoId <= 0) { jsonResponse(['success' => false, 'message' => 'Curso no válido.']); return; }

        try {
            if (!$this->asignaciones->exists($studentId, $cursoId)) {
                jsonResponse(['success' => false, 'message' => 'Curso no encontrado.']); return;
            }

            $apreciaciones = $this->apreciaciones->getByEstudianteCurso($studentId, $cursoId);
            $total = 0.0;
            foreach ($apreciaciones as $ap) {
                $total += (float) $ap['puntos'];
            }

            jsonResponse([
                'success' => true,
                'data' => [
                    'total'        => $total,
                    'apreciaciones' => $apreciaciones,
                ],
            ]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al cargar los datos.']);
        }
    }

    public function graficoCursoAjax(): void
    {
        $this->requireEstudiante();
        @ob_clean();

        $studentId = $this->studentId();
        $cursoId = (int) ($_GET['curso_id'] ?? 0);
        if ($cursoId <= 0) { jsonResponse(['error' => 'Curso no válido']); return; }

        try {
            if (!$this->cursos->getById($cursoId)) { jsonResponse(['error' => 'Curso no encontrado']); return; }

            $semanas = $this->semanas->getCalificadasPorEstudianteYCurso($cursoId, $studentId);

            $labels = [];
            $data = [];
            $colors = [];

            foreach ($semanas as $semana) {
                $grades = $this->calificaciones->getPorSemanaConNota((int) $semana['id'], $studentId);

                $sp = 0.0; $sw = 0.0;
                foreach ($grades as $g) { $sp += (float)$g['nota'] * (float)$g['porcentaje'] / 100; $sw += (float)$g['porcentaje']; }
                $prom = $sw > 0 ? round($sp / ($sw / 100), 2) : null;

                $labels[] = 'Sem ' . $semana['numero_semana'];
                $data[] = $prom ?? 0;
                $colors[] = ($prom ?? 0) >= 13 ? '#0d6efd' : '#dc3545';
            }

            $resumen = $this->asistencias->getResumenPorEstudiante($studentId);
            $att = null;
            foreach ($resumen as $r) {
                if ((int) $r['curso_id'] === $cursoId) { $att = $r; break; }
            }

            $total = $att ? (int) $att['total'] : 0;
            $presentes = $att ? (int) $att['presentes'] : 0;
            $tardanzas = $att ? (int) $att['tardanzas'] : 0;
            $ausencias = $att ? (int) $att['ausencias'] : 0;
            $justificados = $att ? (int) $att['justificados'] : 0;
            $asistio = $presentes + $tardanzas + $justificados;
            $pctBase = $total > 0 ? $asistio / $total * 100 : 0;
            $penalTardanza = $tardanzas > 0 ? min($tardanzas * 0.05, $pctBase) : 0;
            $pct = $total > 0 ? max(0, $pctBase - $penalTardanza) : 0;
            $pctAusencia = $total > 0 ? $ausencias / $total * 100 : 0;

            $historial = $this->asistencias->getHistorialPorEstudianteYCurso($studentId, $cursoId);
            $attFechas = [];
            $attPres = []; $attTar = []; $attAus = []; $attJust = [];
            $cPres = 0; $cTar = 0; $cAus = 0; $cJust = 0; $n = 0;
            foreach ($historial as $h) {
                $n++;
                if ($h['estado'] === 'P') $cPres++;
                elseif ($h['estado'] === 'T') $cTar++;
                elseif ($h['estado'] === 'A') $cAus++;
                elseif ($h['estado'] === 'J') $cJust++;
                $attFechas[] = date('d/m', strtotime($h['fecha']));
                $attPres[] = round($cPres / $n * 100, 2);
                $attTar[] = round($cTar / $n * 100, 2);
                $attAus[] = round($cAus / $n * 100, 2);
                $attJust[] = round($cJust / $n * 100, 2);
            }

            jsonResponse([
                'success' => true,
                'labels' => $labels,
                'data' => $data,
                'colors' => $colors,
                'attFechas' => $attFechas,
                'attPres' => $attPres,
                'attTar' => $attTar,
                'attAus' => $attAus,
                'attJust' => $attJust,
                'asistencia' => [
                    'total' => $total,
                    'presentes' => $presentes,
                    'tardanzas' => $tardanzas,
                    'ausencias' => $ausencias,
                    'justificados' => $justificados,
                    'pct' => round($pct, 2),
                    'pctAusencia' => round($pctAusencia, 2),
                    'penalTardanza' => round($penalTardanza, 2),
                    'riesgo' => $pctAusencia > 30,
                ],
            ]);
        } catch (\Throwable $e) {
            jsonResponse(['error' => $e->getMessage()]);
        }
    }

    public function verSemanaAjax(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();
        $semanaId = (int) ($_GET['semana_id'] ?? 0);
        if ($semanaId <= 0) { echo '<div class="text-center text-muted py-3">Semana no válida</div>'; return; }

        try {
            $semana = $this->semanas->getByIdSimple($semanaId);
            if (!$semana) { echo '<div class="text-center text-muted py-3">Semana no encontrada</div>'; return; }

            $detalles = $this->calificaciones->getPorSemanaCompleto($semanaId, $studentId);

            $ws = 0.0; $wp = 0.0;
            foreach ($detalles as $d) { if ($d['nota'] !== null) { $ws += (float)$d['nota'] * (float)$d['porcentaje'] / 100; $wp += (float)$d['porcentaje']; } }
            $prom = $wp > 0 ? round($ws / ($wp / 100), 2) : 0;
        } catch (PDOException $e) { echo '<div class="text-center text-muted py-3">Error</div>'; return; }
        ?>
        <div class="mb-2">
            <button class="btn btn-sm btn-outline-secondary mb-2" onclick="showCourseDetailAjax(<?= $semana['id'] ?>)" id="btnBackWeeks">
                <i class="fas fa-arrow-left mr-1"></i> Volver a semanas
            </button>
            <h5 class="mb-0"><?= htmlspecialchars($semana['nombre']) ?> <small class="text-muted">Sem <?= $semana['numero_semana'] ?></small></h5>
            <small class="text-muted"><?= date('d/m/Y', strtotime($semana['fecha_inicio'])) ?> &mdash; <?= date('d/m/Y', strtotime($semana['fecha_fin'])) ?></small>
        </div>
        <table class="table table-bordered nodt mb-0" style="font-size:.88rem;">
            <thead class="thead-dark"><tr>
                <th>Criterio</th>
                <th class="text-center" style="width:80px;">% Peso</th>
                <th class="text-center" style="width:80px;">Nota</th>
            </tr></thead>
            <tbody>
            <?php foreach ($detalles as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['nombre']) ?></td>
                    <td class="text-center"><?= $d['porcentaje'] ?>%</td>
                    <td class="text-center">
                        <?php if ($d['nota'] !== null): ?>
                            <span class="badge <?= $d['nota'] >= 10 ? 'badge-success' : 'badge-danger' ?>" style="font-size:.9rem;"><?= $d['nota'] ?></span>
                        <?php else: ?>
                            <span class="text-muted">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-right mt-2">
            <strong class="<?= $prom >= 13 ? 'text-primary' : 'text-danger' ?>">Promedio: <?= number_format($prom, 2) ?></strong>
        </div>
        <?php
    }

    public function perfil(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        try {
            $stmt = $this->db->prepare('SELECT apellidos, nombres, dni, email FROM usuarios WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $this->studentId()]);
            $perfilData = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $perfilData = null;
        }

        require __DIR__ . '/../Views/docente/perfil.php';
    }

    public function miRostro(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();
        $isAjax = isAjaxRequest();

        try {
            $registro = $this->rostros->getByEstudiante($studentId);
        } catch (PDOException $e) {
            $registro = null;
        }

        $isAjax = $isAjax || isset($_GET['ajax']);
        $botonActivarCamara = true;
        require __DIR__ . '/../Views/docente/registro_caras.php';
    }

    public function guardarRostro(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $studentId = $this->studentId();
        $estudiante_id = (int) ($_POST['estudiante_id'] ?? 0);
        $descriptor = trim((string) ($_POST['descriptor'] ?? ''));
        $foto = trim((string) ($_POST['foto'] ?? ''));

        if ($estudiante_id <= 0 || $estudiante_id !== $studentId) {
            jsonResponse(['success' => false, 'message' => 'Estudiante no válido.']);
            return;
        }

        $decoded = json_decode($descriptor, true);
        if (!is_array($decoded) || count($decoded) !== 128) {
            jsonResponse(['success' => false, 'message' => 'Descriptor facial no válido.']);
            return;
        }
        $descriptorLimpio = json_encode(array_map(fn($v) => (float) $v, $decoded), JSON_UNESCAPED_UNICODE);

        try {
            $fotoPath = saveFacePhoto($foto, $studentId);
            if (!$fotoPath) {
                jsonResponse(['success' => false, 'message' => 'No se pudo guardar la foto.']);
                return;
            }

            $ok = $this->rostros->save($studentId, $descriptorLimpio, $fotoPath, $studentId);
            if (!$ok) {
                jsonResponse(['success' => false, 'message' => 'Error al guardar el rostro.'], 500);
                return;
            }

            jsonResponse(['success' => true, 'message' => 'Rostro registrado correctamente.', 'redirect' => BASE_URL . 'estudiante/miRostro']);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al guardar el rostro.'], 500);
        }
    }

    public function eliminarRostro(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $studentId = $this->studentId();
        $estudiante_id = (int) ($_POST['estudiante_id'] ?? 0);

        if ($estudiante_id <= 0 || $estudiante_id !== $studentId) {
            jsonResponse(['success' => false, 'message' => 'Estudiante no válido.']);
            return;
        }

        try {
            $registro = $this->rostros->getByEstudiante($studentId);
            if ($registro && !empty($registro['foto_path'])) {
                $file = ROOT_PATH . 'public/' . $registro['foto_path'];
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            $this->rostros->delete($studentId);
            jsonResponse(['success' => true, 'message' => 'Rostro eliminado correctamente.']);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al eliminar el rostro.'], 500);
        }
    }

    public function guardarPerfil(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $isAjax = isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'estudiante/perfil');
            return;
        }

        $section = $_POST['section'] ?? '';
        $studentId = $this->studentId();

        if ($section === 'photo') {
            $file = $_FILES['foto'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $msg = 'Seleccione una imagen válida.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'estudiante/perfil');
                return;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowed)) {
                $msg = 'Formato no permitido. Use JPG, PNG, GIF o WEBP.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'estudiante/perfil');
                return;
            }

            if ($file['size'] > 2 * 1024 * 1024) {
                $msg = 'La imagen no debe superar 2MB.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'estudiante/perfil');
                return;
            }

            $foto = uploadProfilePhoto($file, $studentId);
            if ($foto) {
                try {
                    (new User($this->db))->updateFoto($studentId, $foto);
                    $_SESSION['user_foto'] = $foto;
                    $msg = 'Foto de perfil actualizada correctamente.';
                    if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
                    setFlash('success', $msg);
                } catch (PDOException $e) {
                    $msg = 'Error al guardar la foto en la base de datos.';
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg], 500); return; }
                    setFlash('error', $msg);
                }
            } else {
                $msg = 'Error al subir la imagen.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
            }

            redirect(BASE_URL . 'estudiante/perfil');
            return;
        }

        if ($section === 'password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $msg = 'Todos los campos son obligatorios.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'estudiante/perfil');
                return;
            }

            if (!passwordComplejidadOk($newPassword)) {
                $msg = 'La nueva contraseña debe tener al menos 8 caracteres, con al menos una mayúscula, una minúscula y un número.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'estudiante/perfil');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $msg = 'Las contraseñas no coinciden.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'estudiante/perfil');
                return;
            }

            try {
                $user = (new User($this->db))->getPasswordById($studentId);

                if (!$user || !password_verify($currentPassword, $user)) {
                    $msg = 'La contraseña actual es incorrecta.';
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'estudiante/perfil');
                    return;
                }

                if (passwordYaUsada($this->db, $studentId, $newPassword)) {
                    $msg = 'La nueva contraseña ya fue utilizada anteriormente. Elija una contraseña diferente.';
                    if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'estudiante/perfil');
                    return;
                }

                $this->db->beginTransaction();
                guardarPasswordHistorial($this->db, $studentId, $user);
                (new User($this->db))->updatePassword($studentId, $newPassword);
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
                if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
                setFlash('success', $msg);
            } catch (PDOException $e) {
                $msg = 'Error al actualizar la contraseña.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg], 500); return; }
                setFlash('error', $msg);
            }

            redirect(BASE_URL . 'estudiante/perfil');
            return;
        }

        if ($section === 'datos') {
            $apellidos = trim($_POST['apellidos'] ?? '');
            $nombres = trim($_POST['nombres'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($apellidos) || empty($nombres)) {
                $msg = 'Los apellidos y nombres son obligatorios.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'estudiante/perfil');
                return;
            }

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msg = 'El correo electrónico no es válido.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'estudiante/perfil');
                return;
            }

            try {
                if (!empty($email)) {
                    $stmt = $this->db->prepare('SELECT id FROM usuarios WHERE email = :email AND id != :id LIMIT 1');
                    $stmt->execute([':email' => $email, ':id' => $studentId]);
                    if ($stmt->fetch()) {
                        $msg = 'El correo electrónico ya está en uso por otro usuario.';
                        if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg]); return; }
                        setFlash('error', $msg);
                        redirect(BASE_URL . 'estudiante/perfil');
                        return;
                    }
                }

                $oldStmt = $this->db->prepare('SELECT apellidos, nombres, email FROM usuarios WHERE id = :id');
                $oldStmt->execute([':id' => $studentId]);
                $old = $oldStmt->fetch(PDO::FETCH_ASSOC);

                $newApellidos = formatApellidos($apellidos);
                $newNombres = formatNombres($nombres);
                $newEmail = $email;

                $this->db->prepare('UPDATE usuarios SET apellidos = :apellidos, nombres = :nombres, email = :email WHERE id = :id')
                    ->execute([':apellidos' => $newApellidos, ':nombres' => $newNombres, ':email' => $newEmail, ':id' => $studentId]);

                $_SESSION['user_nombre'] = $newApellidos . ' ' . $newNombres;
                $_SESSION['user_nombres'] = $newNombres;
                $_SESSION['user_apellidos'] = $newApellidos;
                $_SESSION['user_email'] = $newEmail;

                if ($old) {
                    $cambios = [];
                    if (trim($old['apellidos']) !== $newApellidos) $cambios['Apellidos'] = $old['apellidos'] . ' → ' . $newApellidos;
                    if (trim($old['nombres']) !== $newNombres) $cambios['Nombres'] = $old['nombres'] . ' → ' . $newNombres;
                    if (trim($old['email']) !== $newEmail) $cambios['Email'] = $old['email'] . ' → ' . $newEmail;
                    if (!empty($cambios)) {
                        $mailer = new Mailer($this->db);
                        $mailer->sendProfileChangeNotification($newEmail, $newApellidos . ' ' . $newNombres, 'datos', $cambios, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '');
                    }
                }

                $msg = 'Datos personales actualizados correctamente.';
                if ($isAjax) { jsonResponse(['success' => true, 'message' => $msg]); return; }
                setFlash('success', $msg);
            } catch (PDOException $e) {
                $msg = 'Error al actualizar los datos.';
                if ($isAjax) { jsonResponse(['success' => false, 'message' => $msg], 500); return; }
                setFlash('error', $msg);
            }

            redirect(BASE_URL . 'estudiante/perfil');
            return;
        }

        if ($isAjax) { jsonResponse(['success' => false, 'message' => 'Sección no válida.']); return; }
        redirect(BASE_URL . 'estudiante/perfil');
    }

    // ==================== TIENDA DE BENEFICIOS ====================

    public function tienda(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();

        try {
            $beneficiosActivos = $this->beneficios->getAllActivos();
            $cursosAsignados = $this->asignaciones->getCursosByEstudiante($studentId);

            $resumenesPorCurso = [];
            $historialCanjes = $this->canjes->getPorEstudiante($studentId);

            foreach ($cursosAsignados as $curso) {
                $cursoId = (int) $curso['id'];
                $resumenesPorCurso[$cursoId] = $this->puntosModel->getResumenEstudiante($cursoId, $studentId);
                $resumenesPorCurso[$cursoId]['curso_nombre'] = $curso['nombre'];
                $resumenesPorCurso[$cursoId]['curso_codigo'] = $curso['codigo'];
            }
        } catch (PDOException $e) {
            $beneficiosActivos = [];
            $resumenesPorCurso = [];
            $historialCanjes = [];
        }

        require __DIR__ . '/../Views/estudiante/tienda.php';
    }

    public function semanasParaPuntoExtra(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();
        $cursoId = (int) ($_GET['curso_id'] ?? 0);

        if ($cursoId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Curso inválido.'], 400);
            return;
        }

        try {
            $semanas = $this->semanas->getAllByCursoSimple($cursoId);
            $resultado = [];

            foreach ($semanas as $sem) {
                $semanaId = (int) $sem['id'];
                $calificaciones = $this->calificaciones->getByWeekAndStudent($semanaId, $studentId);

                $notaMasBaja = null;
                $criterioMasBaja = null;
                foreach ($calificaciones as $cal) {
                    $nota = (float) $cal['nota'];
                    if ($notaMasBaja === null || $nota < $notaMasBaja) {
                        $notaMasBaja = $nota;
                        $criterioMasBaja = [
                            'id' => (int) $cal['criterio_id'],
                            'nombre' => $cal['criterio_nombre'],
                            'nota' => $nota,
                            'porcentaje' => (float) $cal['porcentaje'],
                        ];
                    }
                }

                $yaCanjeo = $this->canjes->yaCanjeo($studentId, 1, $cursoId, $semanaId);

                $resultado[] = [
                    'semana_id' => $semanaId,
                    'numero' => (int) $sem['numero_semana'],
                    'nombre' => $sem['nombre'],
                    'fecha_inicio' => $sem['fecha_inicio'],
                    'fecha_fin' => $sem['fecha_fin'],
                    'criterio_mas_baja' => $criterioMasBaja,
                    'nota_mas_baja' => $notaMasBaja,
                    'ya_canjeo' => $yaCanjeo,
                    'tiene_notas' => count($calificaciones) > 0,
                ];
            }

            jsonResponse(['success' => true, 'semanas' => $resultado]);
        } catch (\Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error al cargar semanas.'], 500);
        }
    }

    public function canjearBeneficio(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $studentId = $this->studentId();
        $beneficioId = (int) ($_POST['beneficio_id'] ?? 0);
        $cursoId = (int) ($_POST['curso_id'] ?? 0);
        $semanaId = (int) ($_POST['semana_id'] ?? 0);

        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
            return;
        }

        if ($beneficioId <= 0 || $cursoId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 400);
            return;
        }

        try {
            $beneficio = $this->beneficios->getById($beneficioId);
            if (!$beneficio || !$beneficio['estado']) {
                jsonResponse(['success' => false, 'message' => 'Beneficio no disponible.'], 404);
                return;
            }

            if ($beneficio['tipo'] === 'punto_extra' && $semanaId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Debe seleccionar una semana para el punto extra.'], 400);
                return;
            }

            $resumen = $this->puntosModel->getResumenEstudiante($cursoId, $studentId);
            $cristalesDisponibles = $resumen['cristales'];
            $costo = (int) $beneficio['costo_gemas'];

            if ($cristalesDisponibles < $costo) {
                jsonResponse(['success' => false, 'message' => 'No tiene suficientes cristales. Necesita ' . $costo . ' 💎, tiene ' . $cristalesDisponibles . ' 💎.']);
                return;
            }

            if ($this->canjes->yaCanjeo($studentId, $beneficioId, $cursoId, $semanaId)) {
                jsonResponse(['success' => false, 'message' => 'Ya canjeó este beneficio para esta semana.']);
                return;
            }

            $this->db->beginTransaction();

            $puntosAGastar = $costo * 16;

            $stmt = $this->db->prepare('
                UPDATE puntos_participacion SET puntos = GREATEST(puntos - :gastar, 0)
                WHERE estudiante_id = :eid AND curso_id = :cid
                ORDER BY id ASC LIMIT 1
            ');
            $stmt->execute([':gastar' => $puntosAGastar, ':eid' => $studentId, ':cid' => $cursoId]);

            $detalles = ['beneficio' => $beneficio['nombre'], 'curso_id' => $cursoId];

            if ($beneficio['tipo'] === 'punto_extra') {
                $calificaciones = $this->calificaciones->getByWeekAndStudent($semanaId, $studentId);

                if (empty($calificaciones)) {
                    $this->db->rollBack();
                    jsonResponse(['success' => false, 'message' => 'No tiene calificaciones en esta semana para aplicar el punto extra.']);
                    return;
                }

                $notaMasBaja = null;
                $criterioId = null;
                $criterioNombre = null;
                foreach ($calificaciones as $cal) {
                    $nota = (float) $cal['nota'];
                    if ($notaMasBaja === null || $nota < $notaMasBaja) {
                        $notaMasBaja = $nota;
                        $criterioId = (int) $cal['criterio_id'];
                        $criterioNombre = $cal['criterio_nombre'];
                    }
                }

                $puntosBonus = (int) ($beneficio['puntos_bonus'] ?? 1);
                $nuevaNota = min(20.0, $notaMasBaja + $puntosBonus);

                $this->calificaciones->createOrUpdate($studentId, $criterioId, $nuevaNota);

                $detalles['semana_id'] = $semanaId;
                $detalles['criterio_id'] = $criterioId;
                $detalles['criterio_nombre'] = $criterioNombre;
                $detalles['nota_anterior'] = $notaMasBaja;
                $detalles['nota_nueva'] = $nuevaNota;
                $detalles['puntos_bonus'] = $puntosBonus;
            }

            $this->canjes->create($studentId, $beneficioId, $cursoId, $semanaId > 0 ? $semanaId : null, $costo, $detalles);

            $this->db->commit();

            if ($beneficio['tipo'] === 'punto_extra') {
                jsonResponse(['success' => true, 'message' => '¡Punto extra aplicado! Se sumaron ' . ($beneficio['puntos_bonus'] ?? 1) . ' punto(s) al criterio "' . ($detalles['criterio_nombre'] ?? '') . '" de la semana (nota: ' . $detalles['nota_anterior'] . ' → ' . $detalles['nota_nueva'] . ').']);
            } else {
                jsonResponse(['success' => true, 'message' => '¡Canje exitoso! Beneficio "' . $beneficio['nombre'] . '" reclamado por ' . $costo . ' 💎 cristal(es).']);
            }
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            jsonResponse(['success' => false, 'message' => 'Error al procesar el canje.'], 500);
        }
    }

    // ==================== DISCUSIONES / DEBATES ====================

    public function discusiones(): void
    {
        if (!isLoggedIn() || !isEstudiante()) {
            setFlash('error', 'No tiene permiso.');
            redirect(BASE_URL . 'auth/iniciarSesion');
            return;
        }

        $estudianteId = (int) $_SESSION['user_id'];
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;

        try {
            $semana = $this->semanas->getByIdSimple($semana_id);
            if (!$semana) {
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'estudiante/misCursos');
                return;
            }

            $curso = $this->cursos->getByIdParaEstudiante((int) $semana['curso_id']);
            if (!$curso || !$this->asignaciones->exists($estudianteId, (int) $semana['curso_id'])) {
                setFlash('error', 'No tiene acceso a este curso.');
                redirect(BASE_URL . 'estudiante/misCursos');
                return;
            }

            $discusiones = $this->discusiones->getConMensajesNuevos($semana_id, $estudianteId);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar las discusiones.');
            $discusiones = [];
            $semana = null;
            $curso = null;
        }

        require __DIR__ . '/../Views/estudiante/discusiones.php';
    }

    public function discusionesPorCurso(): void
    {
        if (!isLoggedIn() || !isEstudiante()) {
            setFlash('error', 'No tiene permiso.');
            redirect(BASE_URL . 'auth/iniciarSesion');
            return;
        }

        $estudianteId = (int) $_SESSION['user_id'];
        $cursoId = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        if ($cursoId <= 0) {
            setFlash('error', 'Curso no válido.');
            redirect(BASE_URL . 'estudiante/misCursos');
            return;
        }

        try {
            $curso = $this->cursos->getByIdParaEstudiante($cursoId);
            if (!$curso || !$this->asignaciones->exists($estudianteId, $cursoId)) {
                setFlash('error', 'No tiene acceso a este curso.');
                redirect(BASE_URL . 'estudiante/misCursos');
                return;
            }

            $discusiones = $this->discusiones->getByCursoForEstudiante($cursoId, $estudianteId);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los debates.');
            redirect(BASE_URL . 'estudiante/misCursos');
            return;
        }

        require __DIR__ . '/../Views/estudiante/discusiones_curso.php';
    }

    public function chatDiscusion(): void
    {
        if (!isLoggedIn() || !isEstudiante()) {
            setFlash('error', 'No tiene permiso.');
            redirect(BASE_URL . 'auth/iniciarSesion');
            return;
        }

        $estudianteId = (int) $_SESSION['user_id'];
        $discusion_id = isset($_GET['discusion_id']) ? (int) $_GET['discusion_id'] : 0;

        try {
            $discusion = $this->discusiones->getById($discusion_id);
            if (!$discusion) {
                setFlash('error', 'Debate no encontrado.');
                redirect(BASE_URL . 'estudiante/misCursos');
                return;
            }

            $semana = $this->semanas->getByIdSimple((int) $discusion['semana_id']);
            if (!$semana) {
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'estudiante/misCursos');
                return;
            }

            $curso = $this->cursos->getByIdParaEstudiante((int) $semana['curso_id']);
            if (!$curso || !$this->asignaciones->exists($estudianteId, (int) $semana['curso_id'])) {
                setFlash('error', 'No tiene acceso a este debate.');
                redirect(BASE_URL . 'estudiante/misCursos');
                return;
            }

            $mensajes = $this->mensajesDiscusion->getByDiscusion($discusion_id, 200);
            $aprobaciones = $this->aprobaciones->getPorDiscusion($discusion_id);
            $this->discusionLeida->marcarLeida($discusion_id, $estudianteId, $mensajes ? (int) end($mensajes)['id'] : null);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar el debate.');
            redirect(BASE_URL . 'estudiante/misCursos');
            return;
        }

        require __DIR__ . '/../Views/estudiante/chat_discusion.php';
    }

    public function enviarMensaje(): void
    {
        if (!isLoggedIn() || !isEstudiante()) {
            jsonResponse(['success' => false, 'message' => 'No tiene permiso.'], 403);
            return;
        }

        $estudianteId = (int) $_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $discusion_id = (int) ($_POST['discusion_id'] ?? 0);
        $mensaje = trim((string) ($_POST['mensaje'] ?? ''));

        if ($discusion_id <= 0 || $mensaje === '') {
            jsonResponse(['success' => false, 'message' => 'Mensaje vacío.'], 400);
            return;
        }

        try {
            $discusion = $this->discusiones->getById($discusion_id);
            if (!$discusion) {
                jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
                return;
            }

            $semana = $this->semanas->getByIdSimple((int) $discusion['semana_id']);
            if (!$semana) {
                jsonResponse(['success' => false, 'message' => 'Semana no encontrada.'], 404);
                return;
            }

            $curso = $this->cursos->getByIdParaEstudiante((int) $semana['curso_id']);
            if (!$curso || !$this->asignaciones->exists($estudianteId, (int) $semana['curso_id'])) {
                jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
                return;
            }

            if ($discusion['estado'] === 'cerrada') {
                jsonResponse(['success' => false, 'message' => 'Este debate está cerrado.']);
                return;
            }

            if ($semana['cerrada']) {
                jsonResponse(['success' => false, 'message' => 'La semana está cerrada.']);
                return;
            }

            $id = $this->mensajesDiscusion->create($discusion_id, $estudianteId, $mensaje);
            $this->discusionLeida->marcarLeida($discusion_id, $estudianteId, $id);

            jsonResponse(['success' => true, 'message' => 'Mensaje enviado.', 'id' => $id]);
        } catch (PDOException $e) {
            logError('Error enviando mensaje estudiante', ['message' => $e->getMessage()]);
            jsonResponse(['success' => false, 'message' => 'Error al enviar el mensaje.'], 500);
        }
    }

    public function editarMensaje(): void
    {
        if (!isLoggedIn() || !isEstudiante()) {
            jsonResponse(['success' => false, 'message' => 'No tiene permiso.'], 403);
            return;
        }

        $estudianteId = (int) $_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $mensaje_id = (int) ($_POST['mensaje_id'] ?? 0);
        $mensaje = trim((string) ($_POST['mensaje'] ?? ''));

        if ($mensaje_id <= 0 || $mensaje === '') {
            jsonResponse(['success' => false, 'message' => 'Mensaje no válido.'], 400);
            return;
        }

        try {
            $msg = $this->mensajesDiscusion->getById($mensaje_id);
            if (!$msg) {
                jsonResponse(['success' => false, 'message' => 'Mensaje no encontrado.'], 404);
                return;
            }

            if ((int) $msg['usuario_id'] !== $estudianteId) {
                jsonResponse(['success' => false, 'message' => 'Solo puedes editar tus propios mensajes.'], 403);
                return;
            }

            $this->mensajesDiscusion->update($mensaje_id, $estudianteId, $mensaje);
            jsonResponse(['success' => true, 'message' => 'Mensaje editado.']);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al editar el mensaje.'], 500);
        }
    }

    public function obtenerMensajes(): void
    {
        if (!isLoggedIn() || !isEstudiante()) {
            jsonResponse(['success' => false, 'message' => 'No tiene permiso.'], 403);
            return;
        }

        $estudianteId = (int) $_SESSION['user_id'];
        $discusion_id = (int) ($_GET['discusion_id'] ?? 0);
        $after_id = (int) ($_GET['after_id'] ?? 0);

        if ($discusion_id <= 0) {
            jsonResponse(['success' => false, 'message' => 'Parámetros inválidos.'], 400);
            return;
        }

        try {
            $discusion = $this->discusiones->getById($discusion_id);
            if (!$discusion) {
                jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
                return;
            }

            $semana = $this->semanas->getByIdSimple((int) $discusion['semana_id']);
            if (!$semana || !$this->asignaciones->exists($estudianteId, (int) $semana['curso_id'])) {
                jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
                return;
            }

            $mensajes = $this->mensajesDiscusion->getByDiscusion($discusion_id, 100, $after_id);
            $since = $_GET['since'] ?? '';
            $editados = $since !== '' ? $this->mensajesDiscusion->getEditadosDesde($discusion_id, $since) : [];
            $likesMap = $this->likes->countForDiscusion($discusion_id, $estudianteId);
            $anuncios = $since !== '' ? $this->likes->getAnunciosDesde($discusion_id, $since) : [];
            $aprobaciones = $this->aprobaciones->getPorDiscusion($discusion_id);

            if (!empty($mensajes)) {
                $ultimoId = end($mensajes)['id'];
                $this->discusionLeida->marcarLeida($discusion_id, $estudianteId, $ultimoId);
            }

            jsonResponse(['success' => true, 'mensajes' => $mensajes, 'editados' => $editados, 'estado' => $discusion['estado'], 'typing' => $this->typing->getTypingUsers($discusion_id, $estudianteId), 'likes' => $likesMap, 'anuncios' => $anuncios, 'aprobaciones' => (object) $aprobaciones]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al obtener mensajes.'], 500);
        }
    }

    public function indicarEscritura(): void
    {
        if (!isLoggedIn() || !isEstudiante()) {
            jsonResponse(['success' => false, 'message' => 'No tiene permiso.'], 403);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }
        $estudianteId = (int) $_SESSION['user_id'];
        $discusion_id = (int) ($_POST['discusion_id'] ?? 0);
        if ($discusion_id <= 0) {
            jsonResponse(['success' => false, 'message' => 'Inválido.'], 400);
            return;
        }
        $action = $_POST['action'] ?? 'typing';
        if ($action === 'stop') {
            $this->typing->clearTyping($estudianteId, $discusion_id);
        } else {
            $this->typing->setTyping($estudianteId, $discusion_id);
        }
        jsonResponse(['success' => true]);
    }

    public function darLikeMensaje(): void
    {
        if (!isLoggedIn() || !isEstudiante()) {
            jsonResponse(['success' => false, 'message' => 'No tiene permiso.'], 403);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $estudianteId = (int) $_SESSION['user_id'];
        $mensaje_id = (int) ($_POST['mensaje_id'] ?? 0);
        if ($mensaje_id <= 0) {
            jsonResponse(['success' => false, 'message' => 'Inválido.'], 400);
            return;
        }

        try {
            $msg = $this->mensajesDiscusion->getById($mensaje_id);
            if (!$msg) {
                jsonResponse(['success' => false, 'message' => 'Mensaje no encontrado.'], 404);
                return;
            }
            if ((int) $msg['usuario_id'] === $estudianteId) {
                jsonResponse(['success' => false, 'message' => 'No puedes dar like a tu propio mensaje.']);
                return;
            }

            $discusion = $this->discusiones->getById((int) $msg['discusion_id']);
            if (!$discusion) {
                jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
                return;
            }

            $semana = $this->semanas->getByIdSimple((int) $discusion['semana_id']);
            if (!$semana || !$this->asignaciones->exists($estudianteId, (int) $semana['curso_id'])) {
                jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
                return;
            }

            if ($discusion['estado'] !== 'abierta') {
                jsonResponse(['success' => false, 'message' => 'El debate está cerrado.']);
                return;
            }

            // Solo los comentarios con check del docente admiten likes.
            if (!$this->aprobaciones->isAprobado($mensaje_id)) {
                jsonResponse(['success' => false, 'message' => 'El docente aún no habilita los likes en este comentario.']);
                return;
            }

            $resultado = $this->likes->toggle($mensaje_id, $estudianteId);

            $stmt = $this->db->prepare('SELECT COUNT(*) FROM mensaje_likes WHERE mensaje_id = :m');
            $stmt->execute([':m' => $mensaje_id]);
            $totalLikes = (int) $stmt->fetchColumn();

            $otorgado = false;
            $autorNombre = '';
            if ($resultado === 'liked') {
                $autorStmt = $this->db->prepare('SELECT rol, CONCAT(nombres, \' \', apellidos) AS nombre FROM usuarios WHERE id = :id');
                $autorStmt->execute([':id' => (int) $msg['usuario_id']]);
                $autor = $autorStmt->fetch(PDO::FETCH_ASSOC);

                if ($autor && $autor['rol'] === 'estudiante') {
                    $autorNombre = $autor['nombre'];
                    $totalLikesEst = $this->likes->countLikesEstudianteEnSemana((int) $msg['usuario_id'], (int) $discusion['semana_id']);
                    $nuevosPuntos = min(5, intdiv($totalLikesEst, 2));
                    $yaOtorgados = $this->likes->puntosOtorgadosEnSemana((int) $msg['usuario_id'], (int) $discusion['semana_id']);

                    if ($nuevosPuntos > $yaOtorgados) {
                        $this->likes->registrarPunto((int) $msg['usuario_id'], (int) $discusion['semana_id'], (int) $discusion['id'], $mensaje_id);

                        $motivoDebateId = (new MotivoPunto($this->db))->getIdPorClave('debate_likes');
                        $insPunto = $this->db->prepare('
                            INSERT INTO puntos_participacion (semana_id, curso_id, estudiante_id, puntos, motivo, motivo_id, docente_id)
                            VALUES (:semana_id, :curso_id, :estudiante_id, 1, :motivo, :motivo_id, :docente_id)
                        ');
                        $insPunto->execute([
                            ':semana_id' => (int) $discusion['semana_id'],
                            ':curso_id' => (int) $semana['curso_id'],
                            ':estudiante_id' => (int) $msg['usuario_id'],
                            ':motivo' => 'Buen aporte en debate (apoyos de compañeros)',
                            ':motivo_id' => $motivoDebateId,
                            ':docente_id' => (int) $discusion['creado_por'],
                        ]);
                        $otorgado = true;
                    }
                }
            }

            jsonResponse([
                'success' => true,
                'liked' => $resultado === 'liked',
                'total_likes' => $totalLikes,
                'otorgado' => $otorgado,
                'autor_nombre' => $autorNombre,
            ]);
        } catch (PDOException $e) {
            logError('Error al dar like', ['message' => $e->getMessage()]);
            jsonResponse(['success' => false, 'message' => 'Error al procesar el like.'], 500);
        }
    }

    /** Lista de usuarios que dieron like a un mensaje del debate */
    public function likesDeMensaje(): void
    {
        $this->requireEstudiante();
        $studentId = $this->studentId();
        $mensajeId = (int) ($_GET['mensaje_id'] ?? 0);

        if ($mensajeId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Mensaje no valido.'], 400);
            return;
        }

        try {
            $st = $this->db->prepare(
                'SELECT d.id, d.semana_id FROM mensajes_discusion md
                 INNER JOIN discusiones d ON d.id = md.discusion_id
                 WHERE md.id = :mid'
            );
            $st->execute([':mid' => $mensajeId]);
            $disc = $st->fetch(PDO::FETCH_ASSOC);
            if (!$disc) { jsonResponse(['success' => false, 'message' => 'Mensaje no encontrado.'], 404); return; }

            $semana = $this->semanas->getBasicForDocente((int) $disc['semana_id'], $studentId);
            if (!$semana || !$this->asignaciones->exists((int) $semana["curso_id"], $this->studentId())) { jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403); return; }

            $stL = $this->db->prepare(
                'SELECT u.nombres, u.apellidos, u.username, l.created_at
                 FROM mensaje_likes l
                 INNER JOIN usuarios u ON u.id = l.usuario_id
                 WHERE l.mensaje_id = :mid
                 ORDER BY l.created_at ASC'
            );
            $stL->execute([':mid' => $mensajeId]);
            jsonResponse(['success' => true, 'likes' => $stL->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            logError('Error obteniendo likes', ['message' => $e->getMessage()]);
            jsonResponse(['success' => false, 'message' => 'Error al obtener los likes.'], 500);
        }
    }

    /**
     * Devuelve las participaciones activas visibles para el estudiante (para el botón web).
     */
    public function miParticipacionActiva(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        $studentId = $this->studentId();

        try {
            $stmt = $this->db->prepare('
                SELECT pa.id, pa.curso_id, pa.semana_id, pa.docente_id, c.nombre AS curso_nombre,
                       s.numero_semana, s.nombre AS semana_nombre
                FROM participaciones_activas pa
                INNER JOIN asignaciones a ON a.curso_id = pa.curso_id AND a.estudiante_id = :student
                INNER JOIN cursos c ON c.id = pa.curso_id
                INNER JOIN semanas s ON s.id = pa.semana_id
                WHERE pa.estado = "abierta" AND pa.curso_id = a.curso_id
                ORDER BY pa.id DESC
            ');
            $stmt->execute([':student' => $studentId]);
            $listas = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $pa) {
                $yaRegistrado = $this->participacionVivo->getCola((int) $pa['id']);
                $marcado = false;
                foreach ($yaRegistrado as $c) {
                    if ((int) $c['estudiante_id'] === $studentId && $c['estado'] !== 'eliminado') {
                        $marcado = true;
                        break;
                    }
                }
                $pa['ya_registrado'] = $marcado;
                $listas[] = $pa;
            }
            $this->jsonResponse(['success' => true, 'participaciones' => $listas]);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al consultar participaciones.'], 500);
        }
    }

    /**
     * El estudiante presiona para auto-registrarse en la cola de la participación activa.
     */
    public function autoRegistrarParticipacion(): void
    {
        $this->requireEstudiante();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }
        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
            return;
        }

        $studentId = $this->studentId();
        $participacionId = (int) ($_POST['participacion_id'] ?? 0);

        if ($participacionId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
            return;
        }

        try {
            // Verificar que la participación esté abierta y que el estudiante pertenezca al curso
            $stmt = $this->db->prepare('
                SELECT pa.*, c.nombre AS curso_nombre
                FROM participaciones_activas pa
                INNER JOIN cursos c ON c.id = pa.curso_id
                WHERE pa.id = :id AND pa.estado = "abierta"
            ');
            $stmt->execute([':id' => $participacionId]);
            $pa = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$pa) {
                $this->jsonResponse(['success' => false, 'message' => 'La participación no está activa.'], 404);
                return;
            }
            if (!$this->asignaciones->exists($studentId, (int) $pa['curso_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No perteneces a este curso.'], 403);
                return;
            }

            $res = $this->participacionVivo->autoRegistrar($participacionId, $studentId);
            if (!$res['ok']) {
                $this->jsonResponse(['success' => false, 'message' => $res['message']], 409);
                return;
            }
            $this->jsonResponse(['success' => true, 'message' => 'Te registraste en el orden #' . $res['orden'] . ' del curso ' . $pa['curso_nombre'] . '.', 'orden' => $res['orden']]);
        } catch (PDOException $e) {
            logError('Error en auto-registro participación: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al registrarte.'], 500);
        }
    }

}
