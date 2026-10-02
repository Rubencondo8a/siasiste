<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Semanas - <?= htmlspecialchars($curso['nombre']) ?> <small>(<?= htmlspecialchars($curso['codigo']) ?>)</small></h1>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-secondary d-none" id="btnCerrarSeleccionados" disabled>
                <i class="fas fa-lock"></i> Cerrar <span id="selectedCount">(0)</span>
            </button>
            <a href="#" class="btn btn-info" onclick="AdminModals.openModal('<?= BASE_URL ?>docente/formularioSemana?curso_id=<?= $curso['id'] ?>', 'Nueva Semana', 'lg'); return false;">
                <i class="fas fa-plus"></i> Nueva Semana
            </a>
            <a href="<?= BASE_URL ?>docente/exportarNotasSemana?curso_id=<?= $curso['id'] ?>" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Exportar
            </a>
        </div>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-body table-responsive p-0">
            <?php if (!empty($semanas)): ?>
                <table class="table table-bordered table-striped table-hover table-sm mb-0 small" id="semanasTable">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:36px;">
                                <input type="checkbox" id="selectAll" title="Seleccionar todo">
                            </th>
                            <th class="text-center" style="width:80px;">N° Semana</th>
                            <th>Título</th>
                            <th style="max-width:200px;">Descripción</th>
                            <th class="text-center" style="width:220px;">Rango de Fecha</th>
                            <th class="text-center" style="width:100px;">Criterios</th>
                            <th class="text-center" style="width:80px;">💎 Puntos</th>
                            <th class="text-center" style="width:90px;">Estado</th>
                            <th class="text-center" style="width:200px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($semanas as $semana): ?>
                            <?php $cerrada = (int) ($semana['cerrada'] ?? 0); ?>
                            <tr>
                                <td class="text-center">
                                    <?php if (!$cerrada): ?>
                                        <input type="checkbox" class="semana-check" data-id="<?= $semana['id'] ?>">
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info"><?= $semana['numero_semana'] ?></span>
                                </td>
                                <td style="font-size:1rem;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                    data-toggle="tooltip" title="<?= htmlspecialchars($semana['nombre']) ?>">
                                    <?= htmlspecialchars($semana['nombre']) ?>
                                </td>
                                <td class="text-muted" style="font-size:1rem;max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                    data-toggle="tooltip" title="<?= htmlspecialchars($semana['descripcion'] ?? '') ?>">
                                    <?php
                                        $desc = $semana['descripcion'] ?? '';
                                        echo htmlspecialchars(mb_strlen($desc) > 40 ? mb_substr($desc, 0, 40) . '...' : $desc);
                                    ?>
                                </td>
                                <td class="text-center">
                                    <i class="fas fa-calendar-alt text-muted"></i>
                                    <?= formatDate($semana['fecha_inicio']) ?> - <?= formatDate($semana['fecha_fin']) ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= BASE_URL ?>docente/criterios?semana_id=<?= $semana['id'] ?>" style="text-decoration:none;color:inherit">
                                        <div class="d-flex align-items-center justify-content-center" style="gap:8px">
                                            <span class="badge badge-secondary"><?= $semana['total_criterios'] ?></span>
                                            <div style="width:60px">
                                                <?php
                                                $pct = (int) $semana['suma_porcentajes'];
                                                $color = $pct >= 100 ? 'success' : ($pct >= 50 ? 'warning' : 'info');
                                                ?>
                                                <div class="progress progress-xs" style="height:6px;margin:0" title="<?= $pct ?>%">
                                                    <div class="progress-bar bg-<?= $color ?>" style="width:<?= min($pct, 100) ?>%"></div>
                                                </div>
                                                <small class="text-muted" style="font-size:.65rem"><?= $pct ?>%</small>
                                            </div>
                                        </div>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <?php $totalPts = $semana['total_puntos'] ?? 0; ?>
                                    <a href="<?= BASE_URL ?>docente/asignarPuntos?curso_id=<?= $curso['id'] ?>&semana_id=<?= $semana['id'] ?>" style="text-decoration:none;color:inherit" title="Asignar puntos de participación">
                                        <span class="badge badge-warning" style="font-size:.8rem;">
                                            <i class="fas fa-gem mr-1"></i><?= $totalPts ?> pts
                                        </span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <?php if ($cerrada): ?>
                                        <span class="badge badge-danger"><i class="fas fa-lock"></i> Cerrada</span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Abierta</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>docente/criterios?semana_id=<?= $semana['id'] ?>" class="btn btn-outline-primary" title="Ver criterios">
                                            <i class="fas fa-clipboard-list"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>docente/notas?semana_id=<?= $semana['id'] ?>" class="btn btn-outline-success" title="Ver notas">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>docente/equipos?semana_id=<?= $semana['id'] ?>" class="btn btn-outline-info" title="Equipos por semana">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>docente/discusiones?semana_id=<?= $semana['id'] ?>" class="btn btn-outline-secondary" title="Debates">
                                            <i class="fas fa-comments"></i>
                                        </a>
                                        <?php if ($cerrada): ?>
                                            <button class="btn btn-outline-warning" disabled title="Editar (semana cerrada)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" disabled title="Eliminar (semana cerrada)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <a href="#" class="btn btn-warning" title="Editar" onclick="AdminModals.openModal('<?= BASE_URL ?>docente/formularioSemana?curso_id=<?= $curso['id'] ?>&id=<?= $semana['id'] ?>', 'Editar Semana', 'lg'); return false;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="btn btn-danger" title="Eliminar" onclick="AdminModals.confirmDelete('<?= BASE_URL ?>docente/eliminarSemana?curso_id=<?= $curso['id'] ?>&id=<?= $semana['id'] ?>', '¿Está seguro? Se eliminarán todos los criterios y calificaciones asociadas.'); return false;">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-4">
                    <div class="callout callout-info mb-0">
                        <h5>Sin semanas registradas</h5>
                        <p>No hay semanas registradas para este curso. Haga clic en "Nueva Semana" para comenzar.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="<?= BASE_URL ?>docente/semanas" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Mis Cursos
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php
$extraScripts = [BASE_URL . 'js/admin_modals.js'];
$inlineScripts = ['$(document).ready(function() {
    var $btnCerrar = $("#btnCerrarSeleccionados");
    var $selectAll = $("#selectAll");

    $(\'[data-toggle="tooltip"]\').tooltip();
    function actualizarContador() {
        var checked = $(".semana-check:checked").length;
        if (checked > 0) {
            $btnCerrar.removeClass("d-none").prop("disabled", false);
            $("#selectedCount").text("(" + checked + ")");
        } else {
            $btnCerrar.addClass("d-none").prop("disabled", true);
        }
    }

    $(document).on("change", ".semana-check", actualizarContador);

    $selectAll.on("change", function() {
        $(".semana-check").prop("checked", this.checked);
        actualizarContador();
    });

    $btnCerrar.on("click", function() {
        var ids = [];
        $(".semana-check:checked").each(function() {
            ids.push($(this).data("id"));
        });
        if (ids.length === 0) return;

        Swal.fire({
            title: "¿Cerrar " + ids.length + " semana(s)?",
            text: "Una vez cerradas no podrá editarlas ni modificar notas. Solo el administrador puede reabrirlas.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<i class=\"fas fa-lock\"></i> Cerrar",
            cancelButtonText: "Cancelar"
        }).then(function(result) {
            if (!result.isConfirmed) return;

            var pendientes = ids.length;
            var errores = 0;
            var completados = 0;

            ids.forEach(function(id) {
                $.ajax({
                    url: "' . BASE_URL . 'docente/alternarBloqueoSemana",
                    type: "POST",
                    data: { semana_id: id, _csrf_token: $("meta[name=csrf-token]").attr("content") },
                    dataType: "json",
                    success: function(res) {
                        pendientes--;
                        if (res.success) {
                            completados++;
                        } else {
                            errores++;
                        }
                        if (pendientes === 0) {
                            if (errores > 0) {
                                AdminModals.showToast("warning", completados + " cerradas, " + errores + " con error.");
                            } else {
                                AdminModals.showToast("success", completados + " semana(s) cerrada(s) correctamente.");
                            }
                            setTimeout(function() { location.reload(); }, 1000);
                        }
                    },
                    error: function() {
                        pendientes--;
                        errores++;
                        if (pendientes === 0) {
                            AdminModals.showToast("error", completados + " cerradas, " + errores + " con error.");
                            setTimeout(function() { location.reload(); }, 1000);
                        }
                    }
                });
            });
        });
    });
});'];
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>