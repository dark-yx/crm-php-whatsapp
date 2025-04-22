            </main>
        </div>
    </div>
    
    <!-- Modal de confirmación -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar acción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">¿Está seguro de realizar esta acción?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmButton">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de carga -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mb-0" id="loadingMessage">Procesando...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts adicionales -->
    <script>
    // Funciones de utilidad
    function showLoading(message = 'Procesando...') {
        document.getElementById('loadingMessage').textContent = message;
        new bootstrap.Modal(document.getElementById('loadingModal')).show();
    }
    
    function hideLoading() {
        bootstrap.Modal.getInstance(document.getElementById('loadingModal')).hide();
    }
    
    function showConfirm(message, callback) {
        document.getElementById('confirmMessage').textContent = message;
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const confirmButton = document.getElementById('confirmButton');
        
        const handler = function() {
            confirmButton.removeEventListener('click', handler);
            callback();
            modal.hide();
        };
        
        confirmButton.addEventListener('click', handler);
        modal.show();
    }
    
    // Inicialización de tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
</body>
</html> 