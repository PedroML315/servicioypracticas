<style>
    /* Celda completa clickeable para radios dentro de tablas */
    td.radio-cell {
        padding: 0;
    }

    td.radio-cell > label {
        display: flex;
        width: 100%;
        height: 100%;
        margin: 0;
        padding: .6rem .4rem;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        transition: background .15s;
    }

    td.radio-cell > label:hover {
        background: rgba(42,126,93,.08);
        border-radius: .25rem;
    }

    /* Marca la celda seleccionada */
    td.radio-cell:has(input:checked) {
        background: rgba(1,100,61,.1);
    }

    td.radio-cell input[type="radio"] {
        transform: scale(1.25);
        accent-color: #01643D;
    }

    td.radio-cell label:focus-within {
        outline: 2px solid #01643D;
        outline-offset: -2px;
        border-radius: .25rem;
    }

    /* Sección de evaluación */
    .eval-section-title {
        font-size: .8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #00204a;
        padding: .5rem .75rem;
        background: rgba(0,32,74,.06);
        border-left: 3px solid #00204a;
        border-radius: 0 .4rem .4rem 0;
        margin-bottom: .75rem;
    }
</style>
<!-- Modal: Evaluación primer parcial de Participante -->
<div class="modal fade" id="evaluacionParcialParticipanteModal" tabindex="-1"
    aria-labelledby="evaluacionParticipanteLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header modal-header-gradient">
                <h5 class="modal-title" id="evaluacionParticipanteLabel">
                  <i class="fas fa-star me-2"></i>Evaluación Parcial del Practicante
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="evaluacionParticipanteForm" method="POST">
                <div class="modal-body">

                    <!-- Competencia y calidad en la práctica profesional -->
                    <div class="eval-section-title">1. Competencia y calidad en la práctica profesional</div>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">Rubros a evaluar</th>
                                    <th>No aprobado</th>
                                    <th>Insuficiente</th>
                                    <th>Suficiente</th>
                                    <th>Bien</th>
                                    <th>Muy bien</th>
                                    <th>Sobresaliente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rubros = [
                                    'Asistencia y puntualidad',
                                    'Aplicación de conocimientos',
                                    'Contribución a la solución de problemas',
                                    'Iniciativa',
                                    'Responsabilidad',
                                    'Logro de objetivos planteados'
                                ];
                                foreach ($rubros as $i => $rubro): ?>
                                    <tr>
                                        <td class="text-start"><?php echo $rubro; ?></td>
                                        <?php for ($val = 5; $val <= 10; $val++):
                                            $id = "rubro_{$i}_{$val}";
                                        ?>
                                            <td class="radio-cell">
                                                <label for="<?= $id ?>">
                                                    <input id="<?= $id ?>" type="radio"
                                                        name="rubro_<?= $i ?>" value="<?= $val ?>" required>
                                                </label>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    
                    <!-- Observaciones -->
                    <div class="eval-section-title">Observaciones</div>
                    <div class="mb-3">
                        <label for="parcialFortalezas" class="form-label">Fortalezas</label>
                        <textarea id="parcialFortalezas" name="parcialFortalezas" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="parcialDebilidades" class="form-label">Debilidades</label>
                        <textarea id="parcialDebilidades" name="parcialDebilidades" class="form-control"
                            rows="2"></textarea>
                    </div>

                    <!-- Evaluación de actitudes -->
                    <div class="eval-section-title">2. Evaluación de actitudes</div>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">Evaluación actitudinal</th>
                                    <th>Totalmente evidente</th>
                                    <th>Muy evidente</th>
                                    <th>Evidente</th>
                                    <th>Poco evidente</th>
                                    <th>No evidente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $actitudes = [
                                    'Demuestra interés en identificar y subsanar sus propias necesidades de aprendizaje',
                                    'Coopera eficiente y eficazmente con sus compañeros de trabajo, obtiene resultados esperados al trabajar en equipo',
                                    'Demuestra tolerancia ante las diferencias y propone estrategias para conciliar alternativas',
                                    'Expresa ideas verbalmente de forma clara, redacta distintos tipos de documentos con mínimos errores',
                                    'Busca soluciones efectivas tomando en cuenta reglas, normas y lineamientos establecidos por la autoridad',
                                    'Organiza actividades y materiales para obtener resultados en los tiempos establecidos, establece prioridades y las respeta',
                                    'Muestra interés y busca información adicional a la proporcionada',
                                    'Se adapta a situaciones nuevas, tolera el cambio y puede trabajar bajo presión',
                                    'Demuestra automotivación, se esmera en conseguir los resultados esperados, es dedicado y no espera necesariamente reconocimiento externo',
                                    'Se muestra receptivo, atento con la autoridad y responde de forma amable ante las exigencias'
                                ];
                                $escala = ['TE', 'ME', 'E', 'PE', 'NE'];
                                foreach ($actitudes as $i => $actitud): ?>
                                    <tr>
                                        <td class="text-start"><?php echo $actitud; ?></td>
                                        <?php foreach ($escala as $idx => $op):
                                            $id = "actitud_{$i}_{$idx}";
                                        ?>
                                            <td class="radio-cell">
                                                <label for="<?= $id ?>">
                                                    <input id="<?= $id ?>" type="radio"
                                                        name="actitud_<?= $i ?>" value="<?= $op ?>" required>
                                                </label>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                      <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                      <i class="fas fa-save me-1"></i>Guardar Evaluación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal: Evaluación final del participante -->
<div class="modal fade" id="evaluacionFinalParticipanteModal" tabindex="-1"
    aria-labelledby="evaluacionFinalParticipanteLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header modal-header-gradient">
                <h5 class="modal-title" id="evaluacionFinalParticipanteLabel">
                  <i class="fas fa-star me-2"></i>Evaluación Final del Practicante
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="evaluacionFinalParticipanteForm" method="POST">
                <div class="modal-body">

                    <!-- Competencia y calidad en la práctica profesional -->
                    <div class="eval-section-title">1. Competencia y calidad en la práctica profesional</div>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">Rubros a evaluar</th>
                                    <th>No aprobado</th>
                                    <th>Insuficiente</th>
                                    <th>Suficiente</th>
                                    <th>Bien</th>
                                    <th>Muy bien</th>
                                    <th>Sobresaliente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rubrosFinal = [
                                    'Asistencia y puntualidad',
                                    'Aplicación de conocimientos',
                                    'Contribución a la solución de problemas',
                                    'Iniciativa',
                                    'Responsabilidad',
                                    'Logro de objetivos planteados'
                                ];
                                foreach ($rubrosFinal as $i => $rubro): ?>
                                    <tr>
                                        <td class="text-start"><?php echo $rubro; ?></td>
                                        <?php for ($val = 5; $val <= 10; $val++):
                                            $id = "final_rubro_{$i}_{$val}";
                                        ?>
                                            <td class="radio-cell">
                                                <label for="<?= $id ?>">
                                                    <input id="<?= $id ?>" type="radio"
                                                        name="final_rubro_<?= $i ?>" value="<?= $val ?>" required>
                                                </label>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Observaciones -->
                    <div class="eval-section-title">Observaciones</div>
                    <div class="mb-3">
                        <label for="finalFortalezas" class="form-label">Fortalezas</label>
                        <textarea id="finalFortalezas" name="finalFortalezas" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="finalDebilidades" class="form-label">Debilidades</label>
                        <textarea id="finalDebilidades" name="finalDebilidades" class="form-control"
                            rows="2"></textarea>
                    </div>

                    <!-- Evaluación de actitudes -->
                    <div class="eval-section-title">2. Evaluación de actitudes</div>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">Evaluación actitudinal</th>
                                    <th>Totalmente evidente</th>
                                    <th>Muy evidente</th>
                                    <th>Evidente</th>
                                    <th>Poco evidente</th>
                                    <th>No evidente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $actitudesFinal = [
                                    'Demuestra interés en identificar y subsanar sus propias necesidades de aprendizaje',
                                    'Coopera eficiente y eficazmente con sus compañeros de trabajo, obtiene resultados esperados al trabajar en equipo',
                                    'Demuestra tolerancia ante las diferencias y propone estrategias para conciliar alternativas',
                                    'Expresa ideas verbalmente de forma clara, redacta distintos tipos de documentos con mínimos errores',
                                    'Busca soluciones efectivas tomando en cuenta reglas, normas y lineamientos establecidos por la autoridad',
                                    'Organiza actividades y materiales para obtener resultados en los tiempos establecidos, establece prioridades y las respeta',
                                    'Muestra interés y busca información adicional a la proporcionada',
                                    'Se adapta a situaciones nuevas, tolera el cambio y puede trabajar bajo presión',
                                    'Demuestra automotivación, se esmera en conseguir los resultados esperados, es dedicado y no espera necesariamente reconocimiento externo',
                                    'Se muestra receptivo, atento con la autoridad y responde de forma amable ante las exigencias'
                                ];
                                $escalaFinal = ['TE', 'ME', 'E', 'PE', 'NE'];
                                foreach ($actitudesFinal as $i => $actitud): ?>
                                    <tr>
                                        <td class="text-start"><?php echo $actitud; ?></td>
                                        <?php foreach ($escalaFinal as $idx => $op):
                                            $id = "final_actitud_{$i}_{$idx}";
                                        ?>
                                            <td class="radio-cell">
                                                <label for="<?= $id ?>">
                                                    <input id="<?= $id ?>" type="radio"
                                                        name="final_actitud_<?= $i ?>" value="<?= $op ?>" required>
                                                </label>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                      <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                      <i class="fas fa-save me-1"></i>Guardar Evaluación Final
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>