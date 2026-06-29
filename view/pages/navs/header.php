<?php

$pagina = filter_input(INPUT_GET, 'pagina', FILTER_SANITIZE_STRING);
$pagina = $pagina ? $pagina : 'inicio';

function setActiveClass($pageName, $currentPage)
{
	return $pageName === $currentPage ? 'active disabled' : '';
}
?>
<!-- =======================
	 CSS ADICIONAL:
	 ======================= -->
<style>
	/* Que upote cada <li> dentro de navbar-hidden con margen inferior */
	.navbar-hidden {
		list-style: none;
		padding-left: 0;
	}

	.navbar-hidden li {
		margin-bottom: 0.75rem;
		/* separación entre cada item */
	}

	.menu-top i {
		margin-right: 0.5rem;
		/* espacio entre icono y texto */
		width: 20px;
	}
</style>

<header>
	<nav class="navbar">
		<div class="container">
			<div class="navbar-brand">
				<img src="view/assets/images/logo-color.png" alt="Logo" class="back-logo">
			</div>
			<button class="navbar-toggler boton-sombra" type="button" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon one"></span>
				<span class="navbar-toggler-icon two"></span>
				<span class="navbar-toggler-icon three"></span>
			</button>
		</div>
	</nav>

	<div class="navbar-collapse" id="navbarNav">
		<ul class="navbar-nav">
			<li class="nav-item mt-5">
				<?php
				if ($_SESSION["user"]['role'] == 'admin') {
					$role = 'Administrador';
					$name = $_SESSION["user"]['firstname'] . ' ' . $_SESSION["user"]['lastname'];
				} else if ($_SESSION["user"]['role'] == 'teacher') {
					$role = 'Encargado';
					$name = $_SESSION["user"]['firstname'] . ' ' . $_SESSION["user"]['lastname'];
				} else if ($_SESSION["user"]['role'] == 'organismo_externo') {
					$role = 'Organismo externo';
					$name = $_SESSION["user"]['empresa'] . ' - ' . $_SESSION["user"]['nombre_contacto'];
				} else {
					$role = 'Estudiante';
					$type = $_SESSION["user"]['type'];
					$name = $_SESSION["user"]['firstname'] . ' ' . $_SESSION["user"]['lastname'];
				}
				?>
				<div class="text-secondary small"><?php echo $role; ?></div>
				<h6><?php echo $name ?></h6>
			</li>
			<input type="hidden" id="role" value="<?php echo $_SESSION["user"]['role'] ?>">
			<?php if ($_SESSION["user"]['role'] == 'student'): ?>
				<input type="hidden" id="idStudent" value="<?php echo $_SESSION["user"]['idStudent'] ?>">
				<?php if ($_SESSION["user"]['type'] === 'universidad'):?>
					<input type="hidden" id="loginOn" value="<?php echo $_SESSION["user"]['loginOn'] ?>">
				<?php endif ?>
			<?php else: ?>
				<input type="hidden" id="idUser" value="<?php echo $_SESSION["user"]['id'] ?>">
				<?php if ($_SESSION["user"]['role'] !== 'organismo_externo'):?>
					<input type="hidden" id="idArea" value="<?php echo $_SESSION["user"]['idArea'] ?>">
				<?php endif ?>
			<?php endif ?>
		</ul>

		<!-- OJO: Cambié “nav-item mt-5 navbar-hidden” por “navbar-nav flex-column mt-5 navbar-hidden”
			 para que Bootstrap trate la lista como un menú vertical y respete los <li> como tal. -->
		<ul class="navbar-nav flex-column mt-5 navbar-hidden">
			<li>
				<a href="./" class="menu-top p-2 mb-2 <?= setActiveClass('inicio', $pagina) ?>">
					<i class="fa-duotone fa-house"></i> Tablero
				</a>
			</li>

			<?php if ($_SESSION["user"]['role'] == 'admin'): ?>

				<li>
					<a href="users" class="menu-top p-2 mb-2 <?= setActiveClass('users', $pagina) ?>">
						<i class="fa-duotone fa-users"></i> Usuarios
					</a>
				</li>
				<li>
					<a href="courses" class="menu-top p-2 mb-2 <?= setActiveClass('courses', $pagina) ?>">
						<i class="fad fa-school"></i> Ciclo escolar
					</a>
				</li>
				<li>
					<a href="degrees" class="menu-top p-2 mb-2 <?= setActiveClass('degrees', $pagina) ?>">
						<i class="fad fa-graduation-cap"></i> Licenciaturas
					</a>
				</li>
				<li>
					<a href="areas" class="menu-top p-2 mb-2 <?= setActiveClass('areas', $pagina) ?>">
						<i class="fad fa-ball-pile"></i> Áreas
					</a>
				</li>
				<li>
					<a href="events" class="menu-top p-2 mb-2 <?= setActiveClass('events', $pagina) ?>">
						<i class="fad fa-calendar"></i> Eventos
					</a>
				</li>
				<li>
					<a href="students" class="menu-top p-2 mb-2 <?= setActiveClass('students', $pagina) ?>">
						<i class="fad fa-user-graduate"></i> Estudiantes
					</a>
				</li>

			<?php elseif ($_SESSION["user"]['role'] == 'teacher'): ?>
				<li>
					<a href="students" class="menu-top p-2 mb-2 <?= setActiveClass('students', $pagina) ?>">
						<i class="fad fa-user-graduate"></i> Estudiantes
					</a>
				</li>
			<?php endif ?>
		</ul>
		<ul class="navbar-nav">
			<li class="nav-item mt-auto">
				<a class="nav-link px-3" href="#" onclick="logout()">
					<i class="fas fa-sign-out-alt"></i> Cerrar sesión
				</a>
			</li>
		</ul>
	</div>
</header>