<?php $pageTitle = $pageTitle ?? 'Vigilar examen'; ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-broadcast-tower mr-2 text-danger"></i><?= htmlspecialchars($examen['titulo']) ?></h1>
        <div class="d-flex align-items-center">
            <div id="reloj" class="mr-3 font-weight-bold" style="font-size:1.6rem;">--:--</div>
            <a href="<?= BASE_URL ?>examen/preguntas?examen_id=<?= (int)$examen['id'] ?>" class="btn btn-outline-secondary mr-2">Volver</a>
            <button type="button" id="btnFinalizar" class="btn btn-danger" <?= $sesion['estado'] !== 'activa' ? 'disabled' : '' ?>>
                <i class="fas fa-stop-circle"></i> Finalizar ahora
            </button>
        </div>
    </div>
</section>

<section class="content">
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="info-box mb-0"><span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
                <div class="info-box-content"><span class="info-box-text">Ingresaron</span><span class="info-box-number" id="statTotal">0</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box mb-0"><span class="info-box-icon bg-warning"><i class="fas fa-pen"></i></span>
                <div class="info-box-content"><span class="info-box-text">Rindiendo</span><span class="info-box-number" id="statCurso">0</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box mb-0"><span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                <div class="info-box-content"><span class="info-box-text">Finalizados</span><span class="info-box-number" id="statFin">0</span></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-striped w-100 nodt">
                <thead><tr><th>Estudiante</th><th>Estado</th><th>Puntaje</th><th>Nota (20)</th></tr></thead>
                <tbody id="tbodyIntentos">
                    <tr><td colspan="4" class="text-center text-muted py-3">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
(function () {
    const sesionId = <?= (int) $sesion['id'] ?>;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let finalizada = <?= $sesion['estado'] === 'activa' ? 'false' : 'true' ?>;
    let timer = null;
    let restanteLocal = <?= max(0, strtotime($sesion['fin_en']) - time()) ?>;

    setInterval(function () {
        if (!finalizada && restanteLocal > 0) {
            restanteLocal--;
            document.getElementById('reloj').textContent = fmtSeg(restanteLocal);
        }
    }, 1000);

    function fmtSeg(seg) {
        const h = Math.floor(seg / 3600), m = Math.floor((seg % 3600) / 60), s = seg % 60;
        return (h > 0 ? String(h).padStart(2, '0') + ':' : '') +
               String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    async function poll() {
        try {
            const res = await fetch(BASE_URL + 'examen/progreso?sesion_id=' + sesionId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (!data.success) return;

            document.getElementById('statTotal').textContent = data.total_estudiantes;
            document.getElementById('statCurso').textContent = data.en_curso;
            document.getElementById('statFin').textContent = data.finalizados;

            if (!finalizada) {
                restanteLocal = data.restante_segundos;
                document.getElementById('reloj').textContent = fmtSeg(data.restante_segundos);
            } else {
                detener('00:00');
            }

            const tbody = document.getElementById('tbodyIntentos');
            tbody.innerHTML = '';
            if (!data.intentos.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Aún no hay estudiantes ingresados.</td></tr>';
            }
            data.intentos.forEach(i => {
                const badge = i.estado === 'en_curso'
                    ? '<span class="badge badge-warning">Rindiendo</span>'
                    : (i.estado === 'finalizado'
                        ? '<span class="badge badge-success">Finalizado</span>'
                        : '<span class="badge badge-secondary">Expirado</span>');
                const nota = i.nota !== null ? parseFloat(i.nota).toFixed(1) : '—';
                const pts = i.puntaje !== null ? parseFloat(i.puntaje).toFixed(1) : '—';
                tbody.insertAdjacentHTML('beforeend',
                    '<tr><td>' + i.estudiante_nombre + '</td><td>' + badge +
                    '</td><td>' + pts + '</td><td><strong>' + nota + '</strong></td></tr>');
            });

            if (data.estado === 'finalizada' && !finalizada) {
                detener('Finalizado');
                Swal.fire('Sesión finalizada', 'Las notas fueron registradas en el criterio.', 'success');
            }
        } catch (e) { /* reintenta */ }
    }

    function detener(texto) {
        finalizada = true;
        clearInterval(timer);
        document.getElementById('reloj').textContent = texto || '00:00';
        const btn = document.getElementById('btnFinalizar');
        if (btn) btn.disabled = true;
    }

    document.getElementById('btnFinalizar')?.addEventListener('click', () => {
        Swal.fire({
            title: '¿Finalizar el examen ahora?',
            text: 'Los intentos en curso se calificarán con lo respondido.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, finalizar', cancelButtonText: 'Cancelar'
        }).then(async r => {
            if (!r.isConfirmed) return;
            const fd = new FormData();
            fd.append('sesion_id', sesionId);
            fd.append('_csrf_token', csrf);
            const res = await fetch(BASE_URL + 'examen/finalizarEnVivo', {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success) { detener(); poll(); }
            else Swal.fire('Error', data.message, 'error');
        });
    });

    poll();
    timer = setInterval(poll, 3000);
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
