<div class="modal fade" id="adminModal" tabindex="-1" role="dialog" aria-labelledby="adminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="adminModalLabel"></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="adminModalBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-info"></i>
                    <p class="mt-2">Cargando...</p>
                </div>
            </div>
            <div class="modal-footer" id="adminModalFooter" style="display:none;">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                <p id="deleteModalMessage">¿Está seguro de eliminar este registro?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#" id="deleteModalConfirm" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Eliminar
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
