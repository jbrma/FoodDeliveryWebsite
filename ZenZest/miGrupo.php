<?php

require_once __DIR__.'/includes/config.php';
require_once 'includes/src/grupos/FormularioEditarGrupo.php';

$tituloPagina = 'Mi Grupo';

if (isset($_SESSION['nombre'])) {
    $usuarioActual = \es\ucm\fdi\aw\usuarios\Usuario::buscaUsuario($_SESSION['nombre']);
    $grupo = \es\ucm\fdi\aw\grupos\Grupo::buscaGrupo($usuarioActual->getGrupoIdUsuarioSesion());

    if ($grupo) {
        $foto_URL = ".";
        $foto_URL .= $grupo->getImagen();
        $formulario = new \es\ucm\fdi\aw\grupos\FormularioEditarGrupo($grupo);
        $htmlForm = $formulario->gestiona();
        $nombre = $grupo->getNombre();

        $contenidoPrincipal = <<<EOS
        <h2>Mi Grupo: $nombre</h2>
        <div class="group-container-miG">
            <div class="group-image-miG">
                <img src='$foto_URL' alt="Imagen del grupo">
            </div>
            <div class="group-form-miG">
                $htmlForm
            </div>
        </div>
        EOS;
    } else {
        $contenidoPrincipal = "<p>No perteneces a ningún grupo. <a href='crearGrupo.php'>Crea un grupo</a>.</p>";
    }
} else {
    $contenidoPrincipal = "<p>Por favor, <a href='login.php'>inicia sesión</a> para ver tu grupo.</p>";
}

$params = ['tituloPagina' => $tituloPagina, 'contenidoPrincipal' => $contenidoPrincipal];
$app->generaVista('/plantillas/plantilla.php', $params);
?>
