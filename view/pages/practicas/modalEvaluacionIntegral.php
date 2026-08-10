<style>
    .modal-content {
        border: none;
        border-radius: 1.5rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        overflow: hidden;
    }
    .modal-header-gradient {
        background: linear-gradient(135deg, #01643D, #00204a);
        color: white;
        border-bottom: none;
        padding: 1.5rem;
    }
    .eval-table {
        border-collapse: separate;
        border-spacing: 0 0.5rem;
        border: none;
    }
    .eval-table thead th {
        border: none;
        background: transparent;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.05em;
        padding-bottom: 1rem;
    }
    .eval-table tbody tr {
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border-radius: 0.75rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .eval-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        position: relative;
        z-index: 1;
    }
    .eval-table td {
        border: none;
        padding: 1rem 0.5rem;
        vertical-align: middle;
    }
    .eval-table td:first-child {
        border-radius: 0.75rem 0 0 0.75rem;
        padding-left: 1.5rem;
        font-weight: 500;
        color: #334155;
    }
    .eval-table td:last-child {
        border-radius: 0 0.75rem 0.75rem 0;
        padding-right: 1.5rem;
    }
    .radio-cell { padding: 0 !important; width: 12%; }
    .radio-wrapper {
        display: block;
        width: 100%;
        height: 100%;
        min-height: 3.5rem;
        cursor: pointer;
        position: relative;
    }
    .radio-wrapper input[type="radio"] {
        position: absolute;
        opacity: 0;
    }
    .radio-custom {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 24px;
        height: 24px;
        border: 2px solid #cbd5e1;
        border-radius: 50%;
        background: #f8fafc;
        transition: all 0.2s;
    }
    .radio-wrapper input[type="radio"]:checked ~ .radio-custom {
        border-color: #01643D;
        border-width: 7px;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(1,100,61,0.15);
    }
    .radio-wrapper:hover .radio-custom {
        border-color: #94a3b8;
        background: #f1f5f9;
    }
    .eval-section-title {
        font-size: 0.95rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;
        color: #00204a; padding: 0.75rem 1.25rem; background: rgba(0,32,74,0.04);
        border-radius: 0.75rem; margin-bottom: 1.5rem; margin-top: 1.5rem;
        display: flex; align-items: center; gap: 0.5rem;
    }
    .eval-textarea {
        background: #f8fafc;
        border: 2px solid transparent;
        border-radius: 0.75rem;
        padding: 1rem;
        transition: all 0.2s;
        font-size: 0.95rem;
    }
    .eval-textarea:focus {
        background: #fff;
        border-color: #c6db53;
        box-shadow: 0 0 0 4px rgba(198,219,83,0.15);
        outline: none;
    }
    .eval-btn {
        border-radius: 100px;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        transition: all 0.2s;
    }
    .eval-btn-submit {
        background: #01643D;
        color: white;
        border: none;
    }
    .eval-btn-submit:hover {
        background: #004d2e;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(1,100,61,0.2);
    }
</style>

<!-- MODAL FORMULARIO A (Empresa evalúa Alumno) -->
<div class="modal fade" id="modalEvalIntegralEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="formEvalIntegralEmpresa" class="modal-content">
            <div class="modal-header modal-header-gradient" style="background: linear-gradient(135deg, #01643D, #00204a); color: white;">
                <h5 class="modal-title"><i class="fas fa-clipboard-check me-2"></i>Evaluación al practicante - <span class="hito-label"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <input type="hidden" name="idStudent" id="evalA_idStudent">
            <input type="hidden" name="idPractica" id="evalA_idPractica">
            <input type="hidden" name="tipoHito" id="evalA_tipoHito">
            
            <div class="modal-body">
                <p class="text-muted mb-4">Evalúe el desempeño del alumno durante este periodo. Su opinión es fundamental para el proceso de mejora continua.</p>
                    
                    <div class="eval-section-title">1. Desempeño general</div>
                    <div class="table-responsive mb-4">
                        <table class="table eval-table align-middle text-center">
                            <thead>
                                <tr>
                                    <th class="text-start">Criterio</th>
                                    <th>1 - Deficiente</th>
                                    <th>2 - Regular</th>
                                    <th>3 - Bueno</th>
                                    <th>4 - Muy Bueno</th>
                                    <th>5 - Excelente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $criteriosA1 = [
                                    '1' => '¿Qué tan satisfecho(a) está con el desempeño general del practicante?',
                                    '2' => '¿El practicante cumple con las actividades asignadas de manera adecuada?',
                                    '3' => '¿El practicante muestra responsabilidad y compromiso con sus actividades?',
                                    '4' => '¿El practicante cumple con los horarios establecidos?'
                                ];
                                foreach ($criteriosA1 as $qIdx => $texto): ?>
                                    <tr>
                                        <td class="text-start"><?= $texto ?></td>
                                        <?php for ($v=1; $v<=5; $v++): ?>
                                        <td class="radio-cell">
                                            <label class="radio-wrapper"><input type="radio" name="q<?= $qIdx ?>" value="<?= $v ?>" required><span class="radio-custom"></span></label>
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="eval-section-title">2. Integración y actitud</div>
                    <div class="table-responsive mb-4">
                        <table class="table eval-table align-middle text-center">
                            <thead>
                                <tr>
                                    <th class="text-start">Actitud</th>
                                    <th>1 - Nunca</th>
                                    <th>2 - Rara vez</th>
                                    <th>3 - A veces</th>
                                    <th>4 - Casi siempre</th>
                                    <th>5 - Siempre</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $criteriosA2 = [
                                    '5' => '¿El practicante se ha integrado adecuadamente al equipo de trabajo?',
                                    '6' => '¿Mantiene una actitud profesional y respetuosa?',
                                    '7' => '¿Muestra disposición para aprender y recibir retroalimentación?'
                                ];
                                foreach ($criteriosA2 as $qIdx => $texto): ?>
                                    <tr>
                                        <td class="text-start"><?= $texto ?></td>
                                        <?php for ($v=1; $v<=5; $v++): ?>
                                        <td class="radio-cell">
                                            <label class="radio-wrapper"><input type="radio" name="q<?= $qIdx ?>" value="<?= $v ?>" required><span class="radio-custom"></span></label>
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="eval-section-title">3. Satisfacción de la empresa</div>
                    <div class="table-responsive mb-4">
                        <table class="table eval-table align-middle text-center">
                            <thead>
                                <tr>
                                    <th class="text-start">Criterio</th>
                                    <th>1 - Nada</th>
                                    <th>2 - Poco</th>
                                    <th>3 - Neutral</th>
                                    <th>4 - Satisfecho</th>
                                    <th>5 - Muy satisfecho</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $criteriosA3 = [
                                    '8' => '¿Considera que el practicante aporta valor a las actividades de la empresa?',
                                    '10' => 'En términos generales, ¿qué tan satisfecho(a) se siente con la participación del practicante?'
                                ];
                                foreach ($criteriosA3 as $qIdx => $texto): ?>
                                    <tr>
                                        <td class="text-start"><?= $texto ?></td>
                                        <?php for ($v=1; $v<=5; $v++): ?>
                                        <td class="radio-cell">
                                            <label class="radio-wrapper"><input type="radio" name="q<?= $qIdx ?>" value="<?= $v ?>" required><span class="radio-custom"></span></label>
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="pregunta_recomienda_container" class="mb-4 p-3 bg-light rounded border">
                        <label class="form-label fw-bold">9. ¿Recomendaría que el practicante continúe colaborando en la empresa?</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="q9" id="q9_si" value="1" required>
                                <label class="form-check-label" for="q9_si">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="q9" id="q9_no" value="0">
                                <label class="form-check-label" for="q9_no">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="eval-section-title">4. Comentarios</div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">11. ¿Qué fortalezas destacaría del practicante?</label>
                        <textarea class="eval-textarea w-100" name="q11" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">12. ¿Existe algún área de mejora que considere importante?</label>
                        <textarea class="eval-textarea w-100" name="q12" rows="3" required></textarea>
                    </div>

                </div>
            <div class="modal-footer border-0 pb-4 pe-4">
                <button type="button" class="btn eval-btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn eval-btn eval-btn-submit"><i class="fas fa-save me-2"></i>Guardar Evaluación</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL FORMULARIO B (Alumno evalúa Empresa) -->
<div class="modal fade" id="modalEvalIntegralAlumno" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="formEvalIntegralAlumno" class="modal-content">
            <div class="modal-header modal-header-gradient" style="background: linear-gradient(135deg, #01643D, #00204a); color: white;">
                <h5 class="modal-title"><i class="fas fa-star-half-alt me-2"></i>Evaluación a la organización receptora</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <input type="hidden" name="tipoHito" id="evalB_tipoHito">
            
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Tu opinión importa.</strong> Esta evaluación es confidencial y nos ayuda a medir la calidad de las organizaciones que reciben a nuestros alumnos.
                </div>
                    
                    <div class="eval-section-title">1. Ambiente Laboral</div>
                    <div class="table-responsive mb-4">
                        <table class="table eval-table align-middle text-center">
                            <thead>
                                <tr>
                                    <th class="text-start">Criterio</th>
                                    <th>1 - Totalmente en desacuerdo</th>
                                    <th>2 - En desacuerdo</th>
                                    <th>3 - Neutral</th>
                                    <th>4 - De acuerdo</th>
                                    <th>5 - Totalmente de acuerdo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $criteriosB1 = [
                                    '1' => '¿Qué tan satisfecho(a) te sientes con el ambiente laboral de la empresa?',
                                    '2' => '¿Te has sentido respetado(a) y tratado(a) de manera profesional?',
                                    '3' => '¿Te has integrado adecuadamente al equipo de trabajo?'
                                ];
                                foreach ($criteriosB1 as $qIdx => $texto): ?>
                                    <tr>
                                        <td class="text-start"><?= $texto ?></td>
                                        <?php for ($v=1; $v<=5; $v++): ?>
                                        <td class="radio-cell">
                                            <label class="radio-wrapper"><input type="radio" name="q<?= $qIdx ?>" value="<?= $v ?>" required><span class="radio-custom"></span></label>
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="eval-section-title">2. Actividades y Aprendizaje</div>
                    <div class="table-responsive mb-4">
                        <table class="table eval-table align-middle text-center">
                            <thead>
                                <tr>
                                    <th class="text-start">Criterio</th>
                                    <th>1 - Totalmente en desacuerdo</th>
                                    <th>2 - En desacuerdo</th>
                                    <th>3 - Neutral</th>
                                    <th>4 - De acuerdo</th>
                                    <th>5 - Totalmente de acuerdo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $criteriosB2 = [
                                    '4' => '¿Las actividades asignadas están relacionadas con tu perfil académico?',
                                    '5' => '¿Las actividades realizadas han contribuido a tu desarrollo profesional?',
                                    '6' => '¿Has recibido orientación suficiente para realizar tus actividades?',
                                    '7' => '¿Consideras que estás adquiriendo nuevos conocimientos y habilidades?'
                                ];
                                foreach ($criteriosB2 as $qIdx => $texto): ?>
                                    <tr>
                                        <td class="text-start"><?= $texto ?></td>
                                        <?php for ($v=1; $v<=5; $v++): ?>
                                        <td class="radio-cell">
                                            <label class="radio-wrapper"><input type="radio" name="q<?= $qIdx ?>" value="<?= $v ?>" required><span class="radio-custom"></span></label>
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="eval-section-title">3. Supervisión y seguimiento</div>
                    <div class="table-responsive mb-4">
                        <table class="table eval-table align-middle text-center">
                            <thead>
                                <tr>
                                    <th class="text-start">Criterio</th>
                                    <th>1 - Totalmente en desacuerdo</th>
                                    <th>2 - En desacuerdo</th>
                                    <th>3 - Neutral</th>
                                    <th>4 - De acuerdo</th>
                                    <th>5 - Totalmente de acuerdo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $criteriosB3 = [
                                    '8' => '¿Tu supervisor o responsable está disponible para resolver dudas?',
                                    '9' => '¿Recibes retroalimentación sobre tu desempeño?',
                                    '10' => '¿La empresa cumple con los horarios y condiciones acordadas para tu práctica?'
                                ];
                                foreach ($criteriosB3 as $qIdx => $texto): ?>
                                    <tr>
                                        <td class="text-start"><?= $texto ?></td>
                                        <?php for ($v=1; $v<=5; $v++): ?>
                                        <td class="radio-cell">
                                            <label class="radio-wrapper"><input type="radio" name="q<?= $qIdx ?>" value="<?= $v ?>" required><span class="radio-custom"></span></label>
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="eval-section-title">4. Satisfacción general</div>
                    <div class="table-responsive mb-4">
                        <table class="table eval-table align-middle text-center">
                            <thead>
                                <tr>
                                    <th class="text-start">Criterio</th>
                                    <th>1 - Nada satisfecho(a)</th>
                                    <th>2 - Poco satisfecho(a)</th>
                                    <th>3 - Neutral</th>
                                    <th>4 - Satisfecho(a)</th>
                                    <th>5 - Muy satisfecho(a)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $criteriosB4 = [
                                    '11' => '¿Te sientes satisfecho(a) con tu experiencia en esta empresa?',
                                    '13' => 'En términos generales, ¿cómo calificas tu experiencia en la empresa?'
                                ];
                                foreach ($criteriosB4 as $qIdx => $texto): ?>
                                    <tr>
                                        <td class="text-start"><?= $texto ?></td>
                                        <?php for ($v=1; $v<=5; $v++): ?>
                                        <td class="radio-cell">
                                            <label class="radio-wrapper"><input type="radio" name="q<?= $qIdx ?>" value="<?= $v ?>" required><span class="radio-custom"></span></label>
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="pregunta_recomienda_empresa_container" class="mb-4 p-3 bg-light rounded border">
                        <label class="form-label fw-bold">12. ¿Recomendarías esta empresa a otros estudiantes para realizar sus prácticas profesionales?</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="q12" id="q12_si" value="1" required>
                                <label class="form-check-label" for="q12_si">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="q12" id="q12_no" value="0">
                                <label class="form-check-label" for="q12_no">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="eval-section-title">5. Comentarios</div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">14. ¿Qué aspectos positivos destacarías de la empresa?</label>
                        <textarea class="eval-textarea w-100" name="q14" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">15. ¿Qué aspectos consideras que podrían mejorar?</label>
                        <textarea class="eval-textarea w-100" name="q15" rows="3" required></textarea>
                    </div>

                </div>
            <div class="modal-footer border-0 pb-4 pe-4">
                <button type="button" class="btn eval-btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn eval-btn eval-btn-submit"><i class="fas fa-paper-plane me-2"></i>Enviar Evaluación</button>
            </div>
        </form>
    </div>
</div>
