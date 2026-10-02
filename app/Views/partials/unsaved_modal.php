<div class="modal fade" id="modalUnsaved" tabindex="-1" role="dialog" aria-labelledby="modalUnsavedLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="modalUnsavedLabel">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Cambios sin guardar
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Tiene cambios sin guardar en el formulario.</p>
                <p class="mb-0">¿Qué desea hacer?</p>
            </div>
            <div class="modal-footer">
                <div class="row w-100 m-0">
                    <div class="col-12 col-sm-4 p-1">
                        <button type="button" class="btn btn-secondary btn-block h-100" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i> Cancelar
                        </button>
                    </div>
                    <div class="col-12 col-sm-4 p-1">
                        <button type="button" class="btn btn-danger btn-block h-100" id="btnLeaveWithoutSaving">
                            <i class="fas fa-sign-out-alt mr-1"></i> Salir sin guardar
                        </button>
                    </div>
                    <div class="col-12 col-sm-4 p-1">
                        <button type="button" class="btn btn-success btn-block h-100" id="btnSaveAndLeave">
                            <i class="fas fa-save mr-1"></i> Guardar y salir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    var dirtyForms = [];
    var pendingUrl = null;
    var activeForm = null;

    $("form").on("input change", "input, select, textarea", function() {
        var $f = $(this).closest("form");
        if ($f.length && !$f.hasClass("no-warn")) {
            activeForm = $f[0];
            if (dirtyForms.indexOf($f[0]) === -1) dirtyForms.push($f[0]);
        }
    });

    $(document).on("submit", "form", function() {
        dirtyForms.length = 0;
        activeForm = null;
    });

    function hasUnsaved() { return dirtyForms.length > 0; }

    $(document).on("click", "a[href]", function(e) {
        if (!hasUnsaved()) return;
        if ($(this).hasClass("no-warn")) return;
        var href = $(this).attr("href");
        if (!href || href === "#" || href.indexOf("javascript:") === 0) return;
        e.preventDefault();
        e.stopPropagation();
        pendingUrl = href;
        $("#modalUnsaved").modal("show");
    });

    $("#modalUnsaved").on("hidden.bs.modal", function() {
        pendingUrl = null;
    });

    $("#btnLeaveWithoutSaving").on("click", function() {
        dirtyForms.length = 0;
        activeForm = null;
        $("#modalUnsaved").modal("hide");
        if (pendingUrl) window.location.href = pendingUrl;
    });

    $("#btnSaveAndLeave").on("click", function() {
        var form = activeForm;
        dirtyForms.length = 0;
        activeForm = null;
        $("#modalUnsaved").modal("hide");
        if (!form) {
            if (pendingUrl) window.location.href = pendingUrl;
            return;
        }
        if (pendingUrl) {
            var $form = $(form);
            var $redirect = $form.find('input[name="redirect"]');
            if ($redirect.length) {
                $redirect.val(pendingUrl);
            } else {
                $("<input>").attr({ type: "hidden", name: "redirect" }).val(pendingUrl).appendTo($form);
            }
        }
        $(form).trigger("submit");
    });
});
</script>
