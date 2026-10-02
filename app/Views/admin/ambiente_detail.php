<table class="table table-bordered nodt">
    <tr><th style="width:35%">Nombre</th><td><?= htmlspecialchars($ambiente['nombre']) ?></td></tr>
    <tr><th>Ubicación</th><td><?= htmlspecialchars($ambiente['ubicacion']) ?: '—' ?></td></tr>
    <tr><th>Capacidad</th><td><?= $ambiente['capacidad'] ?: '—' ?> personas</td></tr>
    <tr><th>Estado</th>
        <td>
            <?php if ($ambiente['estado']): ?>
                <span class="badge badge-success">Activo</span>
            <?php else: ?>
                <span class="badge badge-danger">Inactivo</span>
            <?php endif; ?>
        </td>
    </tr>
    <tr><th>Creado</th><td><?= $ambiente['created_at'] ?></td></tr>
</table>
