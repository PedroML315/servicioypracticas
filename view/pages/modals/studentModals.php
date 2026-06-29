<style>
    

    .form-control.is-invalid {
            border-color: #dc3545;
        }

        .form-control.is-valid {
            border-color: #28a745;
        }

        .invalid-feedback {
            display: none;
            color: #dc3545;
        }

        .valid-feedback {
            display: none;
            color: #28a745;
        }

        .form-control.is-invalid ~ .invalid-feedback {
            display: block;
        }

        .form-control.is-valid ~ .valid-feedback {
            display: block;
        }

        .form-control.is-invalid ~ .fas.fa-exclamation-circle {
            color: #dc3545;
            display: inline-block;
        }

        .form-control.is-valid ~ .fas.fa-check-circle {
            color: #28a745;
            display: inline-block;
        }

        .fas.fa-exclamation-circle,
        .fas.fa-check-circle {
            display: none;
            position: absolute;
            right: 10px;
            top: calc(50% - 10px);
        }
</style>
<!-- Modal para registrar un nuevo estudiante -->
<div class="modal fade" id="registerStudentModal" tabindex="-1" aria-labelledby="registerStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">Registro de estudiantes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <form id="registerStudentForm">
                    <div class="row">
                        <!-- Matrícula -->
                        <div class="col-md-3 form-group position-relative">
                            <label for="matricula" class="form-label">Matrícula <i class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Ingresa tu matrícula de estudiante"></i></label>
                            <input type="text" class="form-control" id="matricula" name="matricula" placeholder="Ingresa tu matrícula" required>
                            <i class="fas fa-exclamation-circle"></i>
                            <i class="fas fa-check-circle"></i>
                            <div class="invalid-feedback">La matrícula debe contener solo números.</div>
                            <div class="valid-feedback">Matrícula válida.</div>
                        </div>

                        <!-- Nombre -->
                        <div class="col-md-3 form-group">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingresa tu nombre" required>
                        </div>

                        <!-- Apellido paterno -->
                        <div class="col-md-3 form-group">
                            <label for="apellidoPaterno" class="form-label">Apellido paterno *</label>
                            <input type="text" class="form-control" id="apellidoPaterno" name="apellidoPaterno" placeholder="Ingresa tu apellido paterno" required>
                        </div>

                        <!-- Apellido materno -->
                        <div class="col-md-3 form-group">
                            <label for="apellidoMaterno" class="form-label">Apellido materno</label>
                            <input type="text" class="form-control" id="apellidoMaterno" name="apellidoMaterno" placeholder="Ingresa tu apellido materno">
                        </div>
                            
                        <!-- Calle -->
                        <div class="col-md-4 form-group mt-3">
                            <label for="calle" class="form-label">Calle *</label>
                            <input type="text" class="form-control" id="calle" name="calle" placeholder="Ingresa tu calle" required>
                        </div>
                        
                        <!-- Número Exterior (opcional) -->
                        <div class="col-md-2 form-group mt-3">
                            <label for="numeroExterior" class="form-label">Número Exterior *</label>
                            <input type="text" class="form-control" id="numeroExterior" name="numeroExterior" placeholder="Ingresa tu número exterior" require>
                        </div>

                        <!-- Número Interior -->
                        <div class="col-md-2 form-group mt-3">
                            <label for="numeroInterior" class="form-label">Número Interior</label>
                            <input type="text" class="form-control" id="numeroInterior" name="numeroInterior" placeholder="Ingresa tu número interior">
                        </div>

                        <!-- Colonia -->
                        <div class="col-md-4 form-group mt-3">
                            <label for="colonia" class="form-label">Fraccionamiento/Colonia *</label>
                            <input type="text" class="form-control" id="colonia" name="colonia" placeholder="Ingresa tu colonia" required>
                        </div>

                        <!-- Código Postal -->
                        <div class="col-md-3 form-group mt-3">
                            <label for="codigoPostal" class="form-label">Código Postal *</label>
                            <input type="text" class="form-control" id="codigoPostal" name="codigoPostal" placeholder="Ingresa tu código postal" required>
                        </div>

                        <div class="col-md-2 form-group mt-3">
                            <label for="anioNacimiento" class="form-label">Año de nacimiento *</label>
                            <select class="form-select" id="anioNacimiento" name="anioNacimiento" required>
                                <option value="">Año</option>
                                <!-- Los años se pueden llenar dinámicamente desde JavaScript -->
                            </select>
                        </div>

                        <div class="col-md-2 form-group mt-3">
                            <label for="mesNacimiento" class="form-label">Mes de nacimiento *</label>
                            <select class="form-select" id="mesNacimiento" name="mesNacimiento" required>
                                <option value="">Mes</option>
                                <option value="1">Enero</option>
                                <option value="2">Febrero</option>
                                <option value="3">Marzo</option>
                                <option value="4">Abril</option>
                                <option value="5">Mayo</option>
                                <option value="6">Junio</option>
                                <option value="7">Julio</option>
                                <option value="8">Agosto</option>
                                <option value="9">Septiembre</option>
                                <option value="10">Octubre</option>
                                <option value="11">Noviembre</option>
                                <option value="12">Diciembre</option>
                            </select>
                        </div>

                        <div class="col-md-2 form-group mt-3">
                            <label for="diaNacimiento" class="form-label">Día de nacimiento *</label>
                            <select class="form-select" id="diaNacimiento" name="diaNacimiento" required>
                                <option value="">Día</option>
                            </select>
                        </div>

                        <!-- Género -->
                        <div class="col-md-3 form-group mt-3">
                            <label for="genero" class="form-label">Género *</label>
                            <select class="form-select" id="genero" name="genero" required>
                                <option value="1">Masculino</option>
                                <option value="2">Femenino</option>
                                <option value="0">Otro</option>
                            </select>
                        </div>

                        <!-- Licenciatura -->
                        <div class="col-md-6 form-group mt-3">
                            <label for="licenciatura" class="form-label">Licenciatura *</label>
                            <select class="form-select" id="licenciatura" name="licenciatura" required>
                            </select>
                        </div>

                        <!-- Tipo de Licenciatura -->
                        <div class="col-md-6 form-group mt-3">
                            <label for="tipoLicenciatura" class="form-label">Tipo de Licenciatura *</label>
                            <select class="form-select" id="tipoLicenciatura" name="tipoLicenciatura" required>
                                <option value="semestral">Semestral</option>
                                <option value="cuatrimestral">Cuatrimestral</option>
                            </select>
                        </div>

                        <!-- Grado -->
                        <div class="col-md-6 form-group mt-3">
                            <label for="grado" class="form-label">Grado *</label>
                            <input type="number" class="form-control" id="grado" name="grado" placeholder="Ingresa tu grado" required>
                        </div>

                        <!-- Correo Institucional -->
                        <div class="col-md-6 form-group mt-3 position-relative">
                            <label for="correoInstitucional" class="form-label">Correo Institucional *<i class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Debe ser de la forma @unimontrer.edu.mx"></i></label>
                            <input type="email" class="form-control" id="correoInstitucional" name="correoInstitucional" placeholder="correo@unimontrer.edu.mx" required>
                            <i class="fas fa-exclamation-circle"></i>
                            <i class="fas fa-check-circle"></i>
                            <div class="invalid-feedback">El correo debe ser de la forma @unimontrer.edu.mx.</div>
                            <div class="valid-feedback">Correo válido.</div>
                        </div>

                        <!-- Teléfono de contacto -->
                        <div class="col-md-4 form-group mt-3 position-relative">
                            <label for="telefonoContacto" class="form-label">Celular del alumno *<i class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Debe contener 10 dígitos"></i></label>
                            <input type="tel" class="form-control" id="telefonoContacto" name="telefonoContacto" placeholder="Ingresa tu teléfono de contacto" required>
                            <i class="fas fa-exclamation-circle"></i>
                            <i class="fas fa-check-circle"></i>
                            <div class="invalid-feedback">El teléfono de contacto debe contener 10 dígitos.</div>
                            <div class="valid-feedback">Teléfono válido.</div>
                        </div>

                        <!-- Teléfono de emergencia -->
                        <div class="col-md-4 form-group mt-3 position-relative">
                            <label for="telefonoEmergencia" class="form-label">Teléfono de Emergencia *<i class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Debe contener 10 dígitos"></i></label>
                            <input type="tel" class="form-control" id="telefonoEmergencia" name="telefonoEmergencia" placeholder="Ingresa tu teléfono de emergencia" required>
                            <i class="fas fa-exclamation-circle"></i>
                            <i class="fas fa-check-circle"></i>
                            <div class="invalid-feedback">El teléfono de emergencia debe contener 10 dígitos.</div>
                            <div class="valid-feedback">Teléfono válido.</div>
                        </div>

                        <!-- Parentesco -->
                        <div class="col-md-4 form-group mt-3">
                            <label for="parentesco" class="form-label">Parentesco *</label>
                            <input type="text" class="form-control" id="parentesco" name="parentesco" placeholder="Ingresa tu parentesco" required>
                        </div>

                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3" id="submitBtn">Registrar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteStudentModal" tabindex="-1" role="dialog" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteStudentModalLabel">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar a este estudiante?</p>
                <div class="form-group">
                    <label for="deleteReason">Razón de la baja</label>
                    <textarea id="deleteReason" class="form-control" rows="3" placeholder="Escribe el motivo de la baja"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="deleteStudent()">Dar de baja</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editStudentModal" tabindex="-1" role="dialog" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">Editar Estudiante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editStudentForm">
                    <div class="row">
                        <!-- Matrícula -->
                        <div class="col-md-3 form-group position-relative">
                            <label for="editMatricula" class="form-label">Matrícula <i class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Ingresa la matrícula del estudiante"></i></label>
                            <input type="text" class="form-control" id="editMatricula" name="matricula" placeholder="Ingresa la matrícula" required>
                            <i class="fas fa-exclamation-circle"></i>
                            <i class="fas fa-check-circle"></i>
                            <div class="invalid-feedback">La matrícula debe contener solo números.</div>
                            <div class="valid-feedback">Matrícula válida.</div>
                        </div>

                        <!-- Nombre -->
                        <div class="col-md-3 form-group">
                            <label for="editNombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="editNombre" name="firstname" placeholder="Ingresa el nombre" required>
                        </div>

                        <!-- Apellido paterno -->
                        <div class="col-md-6 form-group">
                            <label for="editApellidoPaterno" class="form-label">Apellido paterno *</label>
                            <input type="text" class="form-control" id="editLastname" name="lastname" placeholder="Ingresa el apellido paterno" required>
                        </div>
                        
                        <!-- Calle -->
                        <div class="col-md-4 form-group mt-3">
                            <label for="editCalle" class="form-label">Calle *</label>
                            <input type="text" class="form-control" id="editCalle" name="calle" placeholder="Ingresa la calle" required>
                        </div>

                        <!-- Número Exterior (opcional) -->
                        <div class="col-md-2 form-group mt-3">
                            <label for="editNumeroExterior" class="form-label">Número Exterior *</label>
                            <input type="text" class="form-control" id="editNumeroExterior" name="numeroExterior" placeholder="Ingresa el número exterior" required>
                        </div>

                        <!-- Número Interior -->
                        <div class="col-md-2 form-group mt-3">
                            <label for="editNumeroInterior" class="form-label">Número Interior</label>
                            <input type="text" class="form-control" id="editNumeroInterior" name="numeroInterior" placeholder="Ingresa el número interior">
                        </div>

                        <!-- Colonia -->
                        <div class="col-md-4 form-group mt-3">
                            <label for="editColonia" class="form-label">Fraccionamiento/Colonia *</label>
                            <input type="text" class="form-control" id="editColonia" name="colonia" placeholder="Ingresa la colonia" required>
                        </div>

                        <!-- Código Postal -->
                        <div class="col-md-3 form-group mt-3">
                            <label for="editCodigoPostal" class="form-label">Código Postal *</label>
                            <input type="text" class="form-control" id="editCodigoPostal" name="codigoPostal" placeholder="Ingresa el código postal" required>
                        </div>

                        <!-- Año de Nacimiento -->
                        <div class="col-md-2 form-group mt-3">
                            <label for="editAnioNacimiento" class="form-label">Año de nacimiento *</label>
                            <select class="form-select" id="editAnioNacimiento" name="anioNacimiento" required>
                                <option value="">Año</option>
                                <!-- Los años se pueden llenar dinámicamente desde JavaScript -->
                            </select>
                        </div>

                        <!-- Mes de Nacimiento -->
                        <div class="col-md-2 form-group mt-3">
                            <label for="editMesNacimiento" class="form-label">Mes de nacimiento *</label>
                            <select class="form-select" id="editMesNacimiento" name="mesNacimiento" required>
                                <option value="">Mes</option>
                                <option value="1">Enero</option>
                                <option value="2">Febrero</option>
                                <option value="3">Marzo</option>
                                <option value="4">Abril</option>
                                <option value="5">Mayo</option>
                                <option value="6">Junio</option>
                                <option value="7">Julio</option>
                                <option value="8">Agosto</option>
                                <option value="9">Septiembre</option>
                                <option value="10">Octubre</option>
                                <option value="11">Noviembre</option>
                                <option value="12">Diciembre</option>
                            </select>
                        </div>

                        <!-- Día de Nacimiento -->
                        <div class="col-md-2 form-group mt-3">
                            <label for="editDiaNacimiento" class="form-label">Día de nacimiento *</label>
                            <select class="form-select" id="editDiaNacimiento" name="diaNacimiento" required>
                                <option value="">Día</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                                <option value="13">13</option>
                                <option value="14">14</option>
                                <option value="15">15</option>
                                <option value="16">16</option>
                                <option value="17">17</option>
                                <option value="18">18</option>
                                <option value="19">19</option>
                                <option value="20">20</option>
                                <option value="21">21</option>
                                <option value="22">22</option>
                                <option value="23">23</option>
                                <option value="24">24</option>
                                <option value="25">25</option>
                                <option value="26">26</option>
                                <option value="27">27</option>
                                <option value="28">28</option>
                                <option value="29">29</option>
                                <option value="30">30</option>
                                <option value="31">31</option>

                            </select>
                        </div>

                        <!-- Género -->
                        <div class="col-md-3 form-group mt-3">
                            <label for="editGenero" class="form-label">Género *</label>
                            <select class="form-select" id="editGenero" name="genero" required>
                                <option value="1">Masculino</option>
                                <option value="2">Femenino</option>
                                <option value="0">Otro</option>
                            </select>
                        </div>

                        <!-- Licenciatura -->
                        <div class="col-md-6 form-group mt-3">
                            <label for="editLicenciatura" class="form-label">Licenciatura *</label>
                            <select class="form-select" id="editLicenciatura" name="licenciatura" required>
                            </select>
                        </div>

                        <!-- Tipo de Licenciatura -->
                        <div class="col-md-6 form-group mt-3">
                            <label for="editTipoLicenciatura" class="form-label">Tipo de Licenciatura *</label>
                            <select class="form-select" id="editTipoLicenciatura" name="tipoLicenciatura" required>
                                <option value="semestral">Semestral</option>
                                <option value="cuatrimestral">Cuatrimestral</option>
                            </select>
                        </div>

                        <!-- Grado -->
                        <div class="col-md-6 form-group mt-3">
                            <label for="editGrado" class="form-label">Grado *</label>
                            <input type="number" class="form-control" id="editGrado" name="grado" placeholder="Ingresa el grado" required>
                        </div>

                        <!-- Correo Institucional -->
                        <div class="col-md-6 form-group mt-3 position-relative">
                            <label for="editCorreoInstitucional" class="form-label">Correo Institucional *<i class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Debe ser de la forma @unimontrer.edu.mx"></i></label>
                            <input type="email" class="form-control" id="editCorreoInstitucional" name="correoInstitucional" placeholder="correo@unimontrer.edu.mx" required>
                            <i class="fas fa-exclamation-circle"></i>
                            <i class="fas fa-check-circle"></i>
                            <div class="invalid-feedback">El correo debe ser de la forma @unimontrer.edu.mx.</div>
                            <div class="valid-feedback">Correo válido.</div>
                        </div>

                        <!-- Teléfono de contacto -->
                        <div class="col-md-4 form-group mt-3 position-relative">
                            <label for="editTelefonoContacto" class="form-label">Celular del alumno *<i class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Debe contener 10 dígitos"></i></label>
                            <input type="tel" class="form-control" id="editTelefonoContacto" name="telefonoContacto" placeholder="Ingresa el teléfono de contacto" required>
                            <i class="fas fa-exclamation-circle"></i>
                            <i class="fas fa-check-circle"></i>
                            <div class="invalid-feedback">El teléfono de contacto debe contener 10 dígitos.</div>
                            <div class="valid-feedback">Teléfono válido.</div>
                        </div>

                        <!-- Teléfono de emergencia -->
                        <div class="col-md-4 form-group mt-3 position-relative">
                            <label for="editTelefonoEmergencia" class="form-label">Teléfono de Emergencia *<i class="fas fa-question-circle" data-toggle="tooltip" data-placement="top" title="Debe contener 10 dígitos"></i></label>
                            <input type="tel" class="form-control" id="editTelefonoEmergencia" name="telefonoEmergencia" placeholder="Ingresa el teléfono de emergencia" required>
                            <i class="fas fa-exclamation-circle"></i>
                            <i class="fas fa-check-circle"></i>
                            <div class="invalid-feedback">El teléfono de emergencia debe contener 10 dígitos.</div>
                            <div class="valid-feedback">Teléfono válido.</div>
                        </div>

                        <!-- Parentesco -->
                        <div class="col-md-4 form-group mt-3">
                            <label for="editParentesco" class="form-label">Parentesco *</label>
                            <input type="text" class="form-control" id="editParentesco" name="parentesco" placeholder="Ingresa el parentesco" required>
                        </div>
                        <input type="hidden" name="idStudent" id="idStudent">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3" id="editSubmitBtn">Guardar cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="commentsModal" tabindex="-1" role="dialog" aria-labelledby="commentsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commentsModalLabel">Causa de baja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="commentsContent">
                <!-- Aquí se mostrarán los comentarios -->
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title" id="studentModalLabel">Detalles del Alumno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Matrícula</h6>
                                    <p class="card-text" id="studentMatricula"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Nombre Completo</h6>
                                    <p class="card-text" id="studentFullName"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Email</h6>
                                    <p class="card-text" id="studentEmail"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Teléfono</h6>
                                    <p class="card-text" id="studentPhone"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Dirección</h6>
                                    <p class="card-text" id="studentAddress"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Grado Académico</h6>
                                    <p class="card-text" id="studentDegree"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Fecha de Nacimiento</h6>
                                    <p class="card-text" id="studentBirthday"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Género</h6>
                                    <p class="card-text" id="studentGender"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Contacto de Emergencia</h6>
                                    <p class="card-text" id="studentParent"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>