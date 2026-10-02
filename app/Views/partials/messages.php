<?php
$flash = getFlash();
if ($flash): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var icon = '<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'error' : ($flash['type'] === 'warning' ? 'warning' : 'info')) ?>';
    var msg = '<?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>';
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: icon,
            text: msg,
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
        });
    } else {
        var bg = icon === 'success' ? '#28a745' : (icon === 'error' ? '#dc3545' : (icon === 'warning' ? '#ffc107' : '#17a2b8'));
        var html = '<div style="position:fixed;top:20px;right:20px;z-index:9999;background:' + bg + ';color:#fff;padding:14px 20px;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,.2);font-weight:600;min-width:280px;">' + msg + '</div>';
        document.body.insertAdjacentHTML('beforeend', html);
        setTimeout(function() { var el = document.querySelector('[style*="z-index:9999"]'); if (el) el.remove(); }, 4000);
    }
});
</script>
<?php endif; ?>