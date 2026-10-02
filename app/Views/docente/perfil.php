<?php
$extraHead = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>
<?php $profileController = $_SESSION['user_rol'] === 'admin' ? 'admin' : ($_SESSION['user_rol'] === 'docente' ? 'docente' : 'estudiante'); ?>
<?php $perfilData = $perfilData ?? null; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>css/perfil.css?v=<?= filemtime(ROOT_PATH . 'public/css/perfil.css') ?>">

<section class="content-header">
    <h1>Mi Perfil</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body text-center">
                    <img id="fotoPreview" src="<?= profilePhoto($_SESSION['user_foto'] ?? null) ?>" alt="Foto"
                         class="profile-user-img img-fluid img-circle" style="width:128px;height:128px;object-fit:cover;">
                    <h3 class="profile-username mt-2"><?= htmlspecialchars($_SESSION['user_nombre'] ?? '') ?></h3>
                    <p class="text-muted"><?= htmlspecialchars($_SESSION['user_username'] ?? '') ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-camera mr-1"></i> Cambiar Foto de Perfil</h3>
                </div>
                <div class="card-body">
                    <form id="photoForm" action="<?= BASE_URL . $profileController ?>/guardarPerfil" method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="section" value="photo">
                        <div class="form-group">
                            <label for="foto">Seleccionar imagen (JPG, PNG, GIF, WEBP — max 2MB)</label>
                            <input type="file" class="form-control-file" id="foto" name="foto" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small class="text-muted">Selecciona tu foto y ajusta la posicion antes de guardar.</small>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($perfilData !== null): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-edit mr-1"></i> Datos Personales</h3>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL . $profileController ?>/guardarPerfil" method="POST" data-ajax="true">
                        <?= csrfField() ?>
                        <input type="hidden" name="section" value="datos">
                        <div class="form-group">
                            <label for="dni">DNI</label>
                            <input type="text" class="form-control" id="dni" value="<?= htmlspecialchars($perfilData['dni'] ?? '') ?>" disabled>
                            <small class="text-muted">El DNI no puede ser modificado.</small>
                        </div>
                        <div class="form-group">
                            <label for="apellidos">Apellidos *</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?= htmlspecialchars($perfilData['apellidos'] ?? '') ?>" required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="nombres">Nombres *</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" value="<?= htmlspecialchars($perfilData['nombres'] ?? '') ?>" required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($perfilData['email'] ?? '') ?>" maxlength="150">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-lock mr-1"></i> Cambiar Contrasena</h3>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL . $profileController ?>/guardarPerfil" method="POST" data-ajax="true">
                        <?= csrfField() ?>
                        <input type="hidden" name="section" value="password">
                        <div class="form-group">
                            <label for="current_password">Contrasena Actual *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary toggle-pass-btn" data-target="current_password" title="Mostrar/ocultar contrasena">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="new_password">Nueva Contrasena *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary toggle-pass-btn" data-target="new_password" title="Mostrar/ocultar contrasena">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Minimo 8 caracteres, con mayuscula, minuscula y numero.</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirmar Nueva Contrasena *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary toggle-pass-btn" data-target="confirm_password" title="Mostrar/ocultar contrasena">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Cambiar Contrasena
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('foto');
    var preview = document.getElementById('fotoPreview');
    var form = document.getElementById('photoForm');

    if (!input || !preview || !form) return;

    input.addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) return;
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire('Imagen muy grande', 'El archivo no debe superar 2MB.', 'warning');
            input.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function(ev) {
            openCropModal(ev.target.result);
        };
        reader.readAsDataURL(file);
    });

    function openCropModal(imgSrc) {
        var cropper = null;
        Swal.fire({
            title: 'Ajustar Foto',
            html: '<p style="color:#666;font-size:13px;margin:0 0 12px">Arrastra para centrar tu rostro en el circulo</p>' +
                '<div class="mw-crop-wrap"><img id="swalCropImg" src="' + imgSrc + '" style="max-width:100%"></div>' +
                '<div class="mw-crop-zoom">' +
                '<button type="button" id="swalZoomOut" title="Alejar"><i class="fas fa-minus"></i></button>' +
                '<span id="swalZoomLevel">100%</span>' +
                '<button type="button" id="swalZoomIn" title="Acercar"><i class="fas fa-plus"></i></button>' +
                '</div>',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check"></i> Aplicar',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            confirmButtonColor: '#7c4dff',
            cancelButtonColor: '#6c757d',
            width: 420,
            padding: '20px',
            didOpen: function() {
                var cropImg = document.getElementById('swalCropImg');
                if (!cropImg) return;
                cropper = new Cropper(cropImg, {
                    viewMode: 1,
                    dragMode: 'move',
                    aspectRatio: 1,
                    autoCropArea: 0.9,
                    responsive: true,
                    background: false,
                    modal: false,
                    guides: false,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    ready: function() {
                        var z = document.getElementById('swalZoomLevel');
                        if (z) z.textContent = '100%';
                    },
                    zoom: function(e) {
                        var z = document.getElementById('swalZoomLevel');
                        if (z) z.textContent = Math.round(e.detail.ratio * 100) + '%';
                    }
                });
                var zi = document.getElementById('swalZoomIn');
                var zo = document.getElementById('swalZoomOut');
                if (zi) zi.addEventListener('click', function() { cropper.zoom(0.1); });
                if (zo) zo.addEventListener('click', function() { cropper.zoom(-0.1); });
            },
            preConfirm: function() {
                return new Promise(function(resolve) {
                    if (!cropper) { resolve(null); return; }
                    var canvas = cropper.getCroppedCanvas({
                        width: 400, height: 400,
                        imageSmoothingEnabled: true, imageSmoothingQuality: 'high'
                    });
                    canvas.toBlob(function(blob) {
                        resolve(blob);
                    }, 'image/jpeg', 0.92);
                });
            }
        }).then(function(result) {
            if (result.isConfirmed && result.value) {
                var blob = result.value;
                preview.src = URL.createObjectURL(blob);
                var fd = new FormData();
                fd.append('section', 'photo');
                fd.append('_csrf_token', form.querySelector('[name=_csrf_token]').value);
                fd.append('foto', blob, 'profile_crop.jpg');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        var resp;
                        try { resp = JSON.parse(xhr.responseText); } catch(ex) { resp = {success:false}; }
                        if (resp.success) {
                            Swal.fire({icon:'success',title:'Foto actualizada',text:resp.message||'Tu foto ha sido actualizada.',timer:1800,showConfirmButton:false});
                        } else {
                            Swal.fire('Error', resp.message||'No se pudo guardar.', 'error');
                        }
                    } else {
                        Swal.fire('Error', 'Error del servidor.', 'error');
                    }
                };
                xhr.onerror = function() {
                    Swal.fire('Error', 'No se pudo conectar al servidor.', 'error');
                };
                xhr.send(fd);
            } else {
                input.value = '';
            }
        });
    }

    document.querySelectorAll('.toggle-pass-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tgt = document.getElementById(btn.dataset.target);
            if (!tgt) return;
            var mostrar = tgt.type === 'password';
            tgt.type = mostrar ? 'text' : 'password';
            btn.querySelector('i').className = mostrar ? 'fas fa-eye-slash' : 'fas fa-eye';
            btn.title = mostrar ? 'Ocultar contrasena' : 'Mostrar contrasena';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php
$extraScripts = [BASE_URL . 'js/admin_modals.js'];
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>