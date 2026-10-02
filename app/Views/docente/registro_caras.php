<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>
<section class="content-header">
    <h1>Registrar Rostro</h1>
</section>
<section class="content"><div class="row"><div class="col-md-8"><div class="card"><div class="card-body">
<?php endif; ?>

<?php
$guardarRostroUrl = $guardarRostroUrl ?? (BASE_URL . 'estudiante/guardarRostro');
$nombreEstudiante = $nombreEstudiante ?? ($_SESSION['user_nombre'] ?? 'Estudiante');
$estudianteId = $estudianteId ?? (int) ($_SESSION['user_id'] ?? 0);
$cursoId = $cursoId ?? 0;
$fotoPath = $fotoPath ?? null;
$yaRegistrado = $yaRegistrado ?? false;
$botonActivarCamara = $botonActivarCamara ?? false;
?>

<div class="row">
    <div class="col-md-6">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-qrcode mr-1"></i> Escáner facial</h3>
            </div>
            <div class="card-body text-center">
                <div id="scannerWrap" class="scanner-wrap">
                    <video id="videoFace" autoplay muted playsinline></video>
                    <button type="button" id="btnToggleCamera" class="btn btn-sm btn-light position-absolute"
                            style="top:8px;right:8px;z-index:5;display:none;border-radius:50%;width:34px;height:34px;padding:0;"
                            title="Cambiar cámara">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <canvas id="canvasDetect"></canvas>
                    <canvas id="canvasFace" class="d-none"></canvas>
                    <div id="faceFrame">
                        <span class="corner tl"></span>
                        <span class="corner tr"></span>
                        <span class="corner bl"></span>
                        <span class="corner br"></span>
                        <div id="scanLine"></div>
                        <div id="frameHint">Alinee su rostro dentro del recuadro</div>
                    </div>
                    <?php if ($botonActivarCamara): ?>
                    <div id="cameraOverlay" class="camera-overlay">
                        <button type="button" id="btnActivarCamara" class="btn btn-primary btn-lg" disabled>
                            <i class="fas fa-video mr-2"></i> Activar cámara
                        </button>
                        <p class="mt-2 mb-0 small text-white-50">
                            <i class="fas fa-lock mr-1"></i> Su cámara solo se usa para el registro facial.
                        </p>
                    </div>
                    <?php endif; ?>
                    <div id="captureProgress" class="capture-progress d-none">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small"><i class="fas fa-check-circle mr-1"></i> Progreso de captura</span>
                            <strong class="small" id="captureCount">0 / 3</strong>
                        </div>
                        <div class="progress" style="height:12px;">
                            <div id="captureBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                 role="progressbar" style="width:0%"></div>
                        </div>
                    </div>
                </div>
                <div id="faceStatus" class="mt-2 text-muted small">Cargando cámara...</div>
                <div class="mt-3">
                    <button type="button" id="btnCapturarRostro" class="btn btn-primary" disabled>
                        <i class="fas fa-camera"></i> Capturar Rostro (0/3)
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-user mr-1"></i> <?= htmlspecialchars($nombreEstudiante) ?></h3></div>
            <div class="card-body text-center">
                <img id="facePreview" src="<?= $yaRegistrado ? facePhotoUrl($fotoPath) : (BASE_URL . 'assets/img/user-default.svg') ?>"
                     class="img-circle elevation-2"
                     style="width:160px;height:160px;object-fit:cover;border:3px solid #007bff;">
                <div id="facePreviewStatus" class="mt-2">
                    <?php if ($yaRegistrado): ?>
                        <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Rostro registrado</span>
                        <div class="mt-2">
                            <button type="button" id="btnActualizarRostro" class="btn btn-sm btn-warning">
                                <i class="fas fa-sync-alt"></i> Actualizar rostro
                            </button>
                            <button type="button" id="btnEliminarRostro" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> Eliminar rostro
                            </button>
                        </div>
                    <?php else: ?>
                        <span class="badge badge-secondary">Sin rostro registrado</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="alert alert-info small">
            <i class="fas fa-info-circle mr-1"></i>
            Colóquese de frente a la cámara, con buena iluminación y sin lentes oscuros.
            Se tomarán <strong>3 capturas</strong> para verificar que el rostro sea consistente.
            El registro se usa solo para la toma de asistencia.
        </div>
    </div>
</div>

<div id="captureResult" class="d-none">
    <div class="card card-success">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-user-check mr-1"></i> Rostro verificado</h3></div>
        <div class="card-body">
            <p id="captureResultMsg"></p>
            <div class="d-flex gap-2" style="gap:10px;">
                <button type="button" id="btnConfirmarRostro" class="btn btn-success">
                    <i class="fas fa-check mr-1"></i> <?= $yaRegistrado ? 'Confirmar y Actualizar' : 'Confirmar y Guardar' ?>
                </button>
                <button type="button" id="btnReintentarRostro" class="btn btn-outline-secondary">
                    <i class="fas fa-redo mr-1"></i> Volver a capturar
                </button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= BASE_URL ?>css/registro-caras.css?v=<?= filemtime(ROOT_PATH . 'public/css/registro-caras.css') ?>">

<input type="hidden" id="guardarRostroUrl" value="<?= $guardarRostroUrl ?>">
<input type="hidden" id="eliminarRostroUrl" value="<?= $eliminarRostroUrl ?? (BASE_URL . 'estudiante/eliminarRostro') ?>">
<input type="hidden" id="csrfToken" value="<?= generateCsrfToken() ?>">
<input type="hidden" id="rostroEstudianteId" value="<?= $estudianteId ?>">
<input type="hidden" id="rostroCursoId" value="<?= (int) $cursoId ?>">



<?php if (empty($isAjax)): ?>
</div></div></div></section>
<?php endif; ?>

<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script>
(function() {
    function initFaceApp() {
        (function() {
            var faceReady = false;
            var video = document.getElementById('videoFace');
            var canvas = document.getElementById('canvasFace');
            var canvasD = document.getElementById('canvasDetect');
            var statusEl = document.getElementById('faceStatus');
            var btnCapturar = document.getElementById('btnCapturarRostro');
            var preview = document.getElementById('facePreview');
            var captureResult = document.getElementById('captureResult');
            var captureResultMsg = document.getElementById('captureResultMsg');
            var btnConfirmar = document.getElementById('btnConfirmarRostro');
            var btnReintentar = document.getElementById('btnReintentarRostro');
            var btnEliminar = document.getElementById('btnEliminarRostro');
            var btnActualizar = document.getElementById('btnActualizarRostro');
            var frameEl = document.getElementById('faceFrame');
            var captureProgress = document.getElementById('captureProgress');
            var captureCount = document.getElementById('captureCount');
            var captureBar = document.getElementById('captureBar');
            var btnActivarCamara = document.getElementById('btnActivarCamara');
            var cameraOverlay = document.getElementById('cameraOverlay');
            var btnToggleCamera = document.getElementById('btnToggleCamera');
            var facingMode = 'user';

            var descriptorActual = null;
            var fotoBase64 = null;
            var stream = null;
            var capturas = [];
            var capturasFoto = [];
            var progreso = 0;
            var confirmado = false;
            var haciendoCaptura = false;
            var loopActive = false;
            var loopTimer = null;
            var modalAbierto = true;
            var frameRect = null;
            var cargandoModelos = false;

            var TARGET_CAPTURAS = 3;
            var UMBRAL_MAX_DIST = 0.55;
            var UMBRAL_LUZ_MIN = 50;
            var luzBaja = false;
            var lumCanvas = document.createElement('canvas');
            lumCanvas.width = 32;
            lumCanvas.height = 24;
            var MODELS_URL = window.BASE_URL + 'models';

            function dist(a, b) {
                var s = 0, d;
                for (var i = 0; i < a.length; i++) { d = a[i] - b[i]; s += d * d; }
                return Math.sqrt(s);
            }

            function avg(descs) {
                var out = [], s;
                for (var i = 0; i < 128; i++) {
                    s = 0;
                    for (var j = 0; j < descs.length; j++) s += descs[j][i];
                    out.push(s / descs.length);
                }
                return out;
            }

            function medirBrillo() {
                if (!video || video.readyState < 2 || !video.videoWidth) return -1;
                var ctx = lumCanvas.getContext('2d');
                var w = lumCanvas.width, h = lumCanvas.height;
                ctx.drawImage(video, 0, 0, w, h);
                var data = ctx.getImageData(0, 0, w, h).data;
                var suma = 0;
                for (var i = 0; i < data.length; i += 4) {
                    suma += 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                }
                return suma / (w * h);
            }

            function aplicarEstadoLuz() {
                var br = medirBrillo();
                if (br < 0) return;
                if (br < UMBRAL_LUZ_MIN) {
                    if (!luzBaja) {
                        luzBaja = true;
                        frameEl.classList.remove('ok');
                        statusEl.textContent = 'Poca iluminación. Mejore la luz para poder capturar.';
                    }
                    btnCapturar.disabled = true;
                } else {
                    if (luzBaja) {
                        luzBaja = false;
                        statusEl.textContent = 'Cámara lista. Colóquese dentro del recuadro.';
                    }
                    btnCapturar.disabled = false;
                }
            }

            function ajustarCanvas() {
                var w = canvasD.clientWidth, h = canvasD.clientHeight;
                if (!w || !h) return;
                canvasD.width = w;
                canvasD.height = h;
                frameRect = { x: w * 0.28, y: h * 0.17, w: w * 0.44, h: h * 0.66 };
            }

            function videoToDisplay(box) {
                var vw = video.videoWidth, vh = video.videoHeight;
                var cw = canvasD.width, ch = canvasD.height;
                if (!vw || !vh) return { x: box.x, y: box.y, width: box.width, height: box.height };
                var scale = Math.max(cw / vw, ch / vh);
                var dw = vw * scale, dh = vh * scale;
                var ox = (cw - dw) / 2, oy = (ch - dh) / 2;
                return { x: box.x * scale + ox, y: box.y * scale + oy, width: box.width * scale, height: box.height * scale };
            }

            function drawFrame(dets) {
                var ctx = canvasD.getContext('2d');
                var w = canvasD.width, h = canvasD.height;
                if (!w || !h) return;
                ctx.clearRect(0, 0, w, h);
                ctx.fillStyle = 'rgba(0,0,0,0.45)';
                ctx.fillRect(0, 0, w, h);
                ctx.clearRect(frameRect.x, frameRect.y, frameRect.w, frameRect.h);
                var caraEnMarco = false;
                if (dets && dets.length) {
                    var r = videoToDisplay(dets[0].box);
                    ctx.strokeStyle = '#00e676';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(r.x, r.y, r.width, r.height);
                    var cx = r.x + r.width / 2, cy = r.y + r.height / 2;
                    if (cx >= frameRect.x && cx <= frameRect.x + frameRect.w &&
                        cy >= frameRect.y && cy <= frameRect.y + frameRect.h) {
                        caraEnMarco = true;
                    }
                }
                frameEl.classList.toggle('ok', caraEnMarco);
            }

            function runTick() {
                if (!faceReady || !stream || confirmado || haciendoCaptura) return;
                if (video.readyState < 2) return;
                aplicarEstadoLuz();
                faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320 }))
                    .then(drawFrame)
                    .catch(function() {});
            }

            function startLoop() {
                if (loopActive) return;
                loopActive = true;
                loopTimer = setInterval(runTick, 200);
            }

            async function cargarModelos() {
                if (cargandoModelos) return;
                cargandoModelos = true;
                statusEl.textContent = 'Cargando modelos de reconocimiento...';
                try {
                    await faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL);
                    await faceapi.nets.faceLandmark68Net.loadFromUri(MODELS_URL);
                    await faceapi.nets.faceRecognitionNet.loadFromUri(MODELS_URL);
                    faceReady = true;
                    if (btnActivarCamara) {
                        btnActivarCamara.disabled = false;
                        statusEl.textContent = 'Presione "Activar cámara" para comenzar el registro.';
                    } else {
                        statusEl.textContent = 'Modelos listos. Encienda la cámara.';
                        encenderCamara();
                    }
                } catch (e) {
                    statusEl.textContent = 'Error al cargar los modelos. Recargue la página.';
                }
                cargandoModelos = false;
            }

            async function encenderCamara() {
                if (!faceReady) { statusEl.textContent = 'Esperando modelos...'; return; }
                if (!modalAbierto) return;
                if (stream) return;
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                        audio: false
                    });
                    if (btnActivarCamara && cameraOverlay) {
                        cameraOverlay.classList.add('hidden');
                    }
                    video.srcObject = stream;
                    ajustarCanvas();
                    startLoop();
                    btnCapturar.disabled = false;
                    if (btnToggleCamera) btnToggleCamera.style.display = '';
                    statusEl.textContent = 'Cámara lista. Colóquese dentro del recuadro.';
                } catch (e) {
                    statusEl.textContent = 'No se pudo acceder a la cámara: ' + e.name + '. Verifique permisos.';
                }
            }

            function actualizarProgreso() {
                var pct = Math.round((progreso / TARGET_CAPTURAS) * 100);
                captureCount.textContent = progreso + ' / ' + TARGET_CAPTURAS;
                captureBar.style.width = pct + '%';
                btnCapturar.innerHTML = '<i class="fas fa-camera"></i> Capturar Rostro (' + progreso + '/' + TARGET_CAPTURAS + ')';
            }

            async function capturar() {
                if (!faceReady || confirmado || haciendoCaptura) return;
                haciendoCaptura = true;
                statusEl.textContent = 'Capturando y verificando muestra...';
                btnCapturar.disabled = true;
                try {
                    if (medirBrillo() < UMBRAL_LUZ_MIN) {
                        statusEl.textContent = 'Poca iluminación. Mejore la luz y capture de nuevo.';
                        btnCapturar.disabled = false;
                        haciendoCaptura = false;
                        return;
                    }
                    var detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320 }))
                        .withFaceLandmarks()
                        .withFaceDescriptors();

                    if (!detections || detections.length === 0) {
                        statusEl.textContent = 'No se detectó ningún rostro. Colóquese dentro del recuadro.';
                        btnCapturar.disabled = false;
                        haciendoCaptura = false;
                        return;
                    }

                    var det = detections[0];
                    var box = det.detection.box;
                    var r = videoToDisplay(box);
                    var cx = r.x + r.width / 2, cy = r.y + r.height / 2;
                    if (cx < frameRect.x || cx > frameRect.x + frameRect.w ||
                        cy < frameRect.y || cy > frameRect.y + frameRect.h) {
                        statusEl.textContent = 'Coloque el rostro dentro del recuadro y capture de nuevo.';
                        btnCapturar.disabled = false;
                        haciendoCaptura = false;
                        return;
                    }

                    var vw = video.videoWidth, vh = video.videoHeight;
                    var mX = box.width * 0.5, mY = box.height * 0.5;
                    var sx = Math.max(0, box.x - mX), sy = Math.max(0, box.y - mY * 0.8);
                    var sw = Math.min(vw - sx, box.width + mX * 2);
                    var sh = Math.min(vh - sy, box.height + mY * 1.8);
                    canvas.width = Math.round(sw);
                    canvas.height = Math.round(sh);
                    canvas.getContext('2d').drawImage(video, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);

                    capturas.push(Array.from(det.descriptor));
                    capturasFoto.push(canvas.toDataURL('image/jpeg', 0.9));
                    progreso++;
                    actualizarProgreso();
                    captureProgress.classList.remove('d-none');

                    if (progreso >= TARGET_CAPTURAS) {
                        verificarCapturas();
                    } else {
                        statusEl.textContent = 'Muestra ' + progreso + ' de ' + TARGET_CAPTURAS +
                            ' correcta. Capture la muestra ' + (progreso + 1) + '.';
                    }
                } catch (e) {
                    console.error('Error al capturar:', e);
                    statusEl.textContent = 'Error al procesar la imagen: ' + (e && e.message ? e.message : e);
                }
                btnCapturar.disabled = false;
                haciendoCaptura = false;
            }

            function verificarCapturas() {
                var maxDist = 0, d;
                for (var i = 0; i < capturas.length; i++) {
                    for (var j = i + 1; j < capturas.length; j++) {
                        d = dist(capturas[i], capturas[j]);
                        if (d > maxDist) maxDist = d;
                    }
                }
                if (maxDist > UMBRAL_MAX_DIST) {
                    statusEl.textContent = 'Las capturas no fueron consistentes (diferencia ' + maxDist.toFixed(2) +
                        '). Intente de nuevo con mejor iluminación.';
                    capturas = [];
                    capturasFoto = [];
                    progreso = 0;
                    actualizarProgreso();
                    captureProgress.classList.add('d-none');
                    return;
                }

                var promedio = avg(capturas);
                descriptorActual = promedio;
                var best = 0, bestD = Infinity;
                for (var i = 0; i < capturas.length; i++) {
                    d = dist(capturas[i], promedio);
                    if (d < bestD) { bestD = d; best = i; }
                }
                fotoBase64 = capturasFoto[best];
                preview.src = fotoBase64;
                preview.style.objectFit = 'cover';

                confirmado = true;
                captureResultMsg.innerHTML = 'Se verificaron <strong>' + TARGET_CAPTURAS +
                    '</strong> capturas consistentes (diferencia máxima ' + maxDist.toFixed(3) +
                    '). Revise la vista previa y confirme para guardar.';
                captureResult.classList.remove('d-none');
                statusEl.textContent = 'Rostro verificado ' + TARGET_CAPTURAS + '/' + TARGET_CAPTURAS + '.';
                btnCapturar.disabled = true;
            }

            function reiniciarCaptura() {
                captureResult.classList.add('d-none');
                descriptorActual = null;
                fotoBase64 = null;
                capturas = [];
                capturasFoto = [];
                progreso = 0;
                confirmado = false;
                actualizarProgreso();
                captureProgress.classList.add('d-none');
                statusEl.textContent = 'Cámara lista. Colóquese dentro del recuadro.';
                btnCapturar.disabled = false;
            }

            function confirmarGuardar() {
                if (!descriptorActual) return;
                btnConfirmar.disabled = true;
                statusEl.textContent = 'Guardando...';
                var fd = new FormData();
                fd.append('_csrf_token', document.getElementById('csrfToken').value);
                fd.append('estudiante_id', document.getElementById('rostroEstudianteId').value);
                fd.append('descriptor', JSON.stringify(descriptorActual));
                fd.append('foto', fotoBase64);
                var cursoIdEl = document.getElementById('rostroCursoId');
                if (cursoIdEl && cursoIdEl.value) {
                    fd.append('curso_id', cursoIdEl.value);
                }

                fetch(document.getElementById('guardarRostroUrl').value, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    btnConfirmar.disabled = false;
                    if (res.success) {
                        statusEl.textContent = 'Rostro registrado correctamente.';
                        var msg = document.getElementById('facePreviewStatus');
                        msg.innerHTML = '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Rostro registrado</span>';
                        captureResult.classList.add('d-none');
                        if (window.AdminModals) {
                            AdminModals.showToast('success', res.message);
                        }
                        if (typeof res.redirect === 'string' && res.redirect) {
                            setTimeout(function() { window.location.href = res.redirect; }, 800);
                        }
                    } else {
                        statusEl.textContent = res.message || 'Error al guardar.';
                        if (window.AdminModals) {
                            AdminModals.showToast('error', res.message || 'Error al guardar.');
                        }
                    }
                })
                .catch(function() {
                    btnConfirmar.disabled = false;
                    statusEl.textContent = 'Error de conexión.';
                    if (window.AdminModals) {
                        AdminModals.showToast('error', 'Error de conexión.');
                    }
                });
            }

            function detenerCamara() {
                modalAbierto = false;
                if (loopTimer) {
                    clearInterval(loopTimer);
                    loopTimer = null;
                }
                loopActive = false;
                if (stream) {
                    stream.getTracks().forEach(function(t) { t.stop(); });
                    stream = null;
                }
                if (video) video.srcObject = null;
                if (btnToggleCamera) btnToggleCamera.style.display = 'none';
            }

            window.__faceRegistrarDetenerCamara = detenerCamara;
            window.__faceRegistrarIniciarCamara = iniciarCamara;

            async function cambiarCamara() {
                facingMode = (facingMode === 'user') ? 'environment' : 'user';
                if (stream) {
                    stream.getTracks().forEach(function(t) { t.stop(); });
                }
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: facingMode },
                        audio: false
                    });
                    video.srcObject = stream;
                } catch (e) {
                    statusEl.textContent = 'No se pudo cambiar la cámara: ' + e.name;
                    facingMode = (facingMode === 'user') ? 'environment' : 'user';
                }
            }
            if (btnToggleCamera) btnToggleCamera.addEventListener('click', cambiarCamara);

            function iniciarCamara() {
                if (!faceReady) return;
                modalAbierto = true;
                if (stream) return;
                encenderCamara();
            }

            if (btnEliminar) {
                btnEliminar.addEventListener('click', function() {
                    if (!confirm('¿Eliminar el rostro registrado?')) return;
                    var fd = new FormData();
                    fd.append('_csrf_token', document.getElementById('csrfToken').value);
                    fd.append('estudiante_id', document.getElementById('rostroEstudianteId').value);
                    var cursoIdEl = document.getElementById('rostroCursoId');
                    if (cursoIdEl && cursoIdEl.value) {
                        fd.append('curso_id', cursoIdEl.value);
                    }
                    fetch(document.getElementById('eliminarRostroUrl').value, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.success) {
                            window.location.reload();
                        } else if (window.AdminModals) {
                            AdminModals.showToast('error', res.message || 'Error al eliminar.');
                        }
                    });
                });
            }

            if (btnActualizar) {
                btnActualizar.addEventListener('click', function() {
                    reiniciarCaptura();
                    encenderCamara();
                    statusEl.textContent = 'Capture nuevamente el rostro para actualizarlo.';
                });
            }

            btnCapturar.addEventListener('click', capturar);
            btnConfirmar.addEventListener('click', confirmarGuardar);
            btnReintentar.addEventListener('click', reiniciarCaptura);

            if (btnActivarCamara) {
                btnActivarCamara.addEventListener('click', function() {
                    if (!faceReady) {
                        btnActivarCamara.disabled = true;
                        statusEl.textContent = 'Cargando modelos de reconocimiento...';
                        cargarModelos();
                        return;
                    }
                    encenderCamara();
                });
            }

            window.addEventListener('resize', ajustarCanvas);

            cargarModelos();
        })();
    }

    if (typeof faceapi !== 'undefined') {
        initFaceApp();
    } else {
        var s = document.createElement('script');
        s.src = window.BASE_URL + 'js/face-api/face-api.min.js';
        s.onload = initFaceApp;
        s.onerror = function() {
            var statusEl = document.getElementById('faceStatus');
            if (statusEl) statusEl.textContent = 'Error al cargar la librería de reconocimiento.';
        };
        document.head.appendChild(s);
    }

    $(document).off('hidden.bs.modal.faceRegistro').on('hidden.bs.modal.faceRegistro', '#adminModal', function() {
        if (window.__faceRegistrarDetenerCamara) {
            window.__faceRegistrarDetenerCamara();
        }
    });

    $(document).off('shown.bs.modal.faceRegistro').on('shown.bs.modal.faceRegistro', '#adminModal', function() {
        if (window.__faceRegistrarIniciarCamara) {
            window.__faceRegistrarIniciarCamara();
        }
    });
})();
</script>
<?php endif; ?>
