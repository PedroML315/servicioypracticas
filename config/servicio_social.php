<?php
/**
 * Reglas de Servicio Social.
 *
 * licenciaturas_sin_servicio: idDegree (tabla degrees) que no realizan el Servicio Social en la
 * plataforma (pedido de la Coordinación, octubre de 2026): 1 NUTRICION y 2 FISIOTERAPIA.
 * - El registro de SS no las ofrece y las rechaza (RegisterStudent.php y ajax.forms.php addStudent).
 * - Al terminar Prácticas, esos alumnos no ven el aviso «Continúa con tu Servicio Social».
 */
return [
    'licenciaturas_sin_servicio' => [1, 2],
];
