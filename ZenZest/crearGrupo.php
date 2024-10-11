<?php

require_once __DIR__.'/includes/config.php';
require_once 'includes/src/grupos/FormularioCrearGrupo.php';

$tituloPagina = 'Crear Grupo';

if(isset($_SESSION['nombre'])){

    $form = new \es\ucm\fdi\aw\grupos\FormularioCrearGrupo();
    $htmlFormCrearGrupo = $form->gestiona();

    $contenidoPrincipal = <<<EOS
        <h2>Crea aquí tu grupo</h2>
        <div class="group-form-creaG">
            $htmlFormCrearGrupo
        </div>
    </div>
    EOS;
}

$params = ['tituloPagina' => $tituloPagina, 'contenidoPrincipal' => $contenidoPrincipal];
$app->generaVista('/plantillas/plantilla.php', $params);
?>
