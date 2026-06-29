<style>
/* Estilos para el visor de evaluaciones (Solo Lectura) */
.visor-eval-container {
    background: #f8f9fc;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}
.visor-question {
    font-weight: 600;
    color: #00204a;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}
.visor-answer {
    background: #fff;
    border: 1px solid #e4e9ef;
    border-radius: 0.4rem;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    color: #374151;
}
.visor-stars {
    color: #c6db53;
    font-size: 1.2rem;
}
.visor-stars .fas {
    color: #01643D;
}
.visor-stars .far {
    color: #cbd5e1;
}
.visor-empty {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
    font-style: italic;
}
.nav-pills-eval .nav-link {
    color: #00204a;
    font-weight: 600;
    border-radius: 0.5rem;
}
.nav-pills-eval .nav-link.active {
    background-color: #01643D;
    color: #fff;
}
</style>

<!-- Modal: Visor de Evaluaciones Admin -->
<div class="modal fade" id="modalVerEvaluacionesAdmin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="sip-modal-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-white">
                    <i class="fas fa-file-signature me-2"></i>Visor de Evaluaciones Integrales
                </h5>
                <button type="button" class="btn-close sip-modal-header" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                
                <!-- Navegación por Fases -->
                <ul class="nav nav-pills nav-pills-eval nav-fill mb-4" id="evaluacionesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="fase1-tab" data-bs-toggle="tab" data-bs-target="#fase1-content" type="button" role="tab" aria-controls="fase1-content" aria-selected="true">
                            Fase 1 (180 horas)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="fase2-tab" data-bs-toggle="tab" data-bs-target="#fase2-content" type="button" role="tab" aria-controls="fase2-content" aria-selected="false">
                            Fase 2 (360 horas)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="evaluacionesTabsContent">
                    
                    <!-- FASE 1 -->
                    <div class="tab-pane fade show active" id="fase1-content" role="tabpanel" aria-labelledby="fase1-tab">
                        <div class="row">
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold text-center mb-3 text-secondary"><i class="fas fa-building me-2"></i>Empresa evaluando al Alumno</h6>
                                <div id="visor-fase1-empresa">
                                    <div class="visor-empty"><i class="fas fa-spinner fa-spin me-2"></i>Cargando datos...</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-center mb-3 text-secondary"><i class="fas fa-user-graduate me-2"></i>Alumno evaluando a la Empresa</h6>
                                <div id="visor-fase1-alumno">
                                    <div class="visor-empty"><i class="fas fa-spinner fa-spin me-2"></i>Cargando datos...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FASE 2 -->
                    <div class="tab-pane fade" id="fase2-content" role="tabpanel" aria-labelledby="fase2-tab">
                        <div class="row">
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold text-center mb-3 text-secondary"><i class="fas fa-building me-2"></i>Empresa evaluando al Alumno</h6>
                                <div id="visor-fase2-empresa">
                                    <div class="visor-empty"><i class="fas fa-spinner fa-spin me-2"></i>Cargando datos...</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-center mb-3 text-secondary"><i class="fas fa-user-graduate me-2"></i>Alumno evaluando a la Empresa</h6>
                                <div id="visor-fase2-alumno">
                                    <div class="visor-empty"><i class="fas fa-spinner fa-spin me-2"></i>Cargando datos...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cerrar Visor</button>
            </div>
        </div>
    </div>
</div>
