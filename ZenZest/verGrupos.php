<?php

require_once __DIR__.'/includes/config.php';

$tituloPagina = 'Ver Grupos';

if (isset($_SESSION['nombre'])) {
    $grupos = \es\ucm\fdi\aw\grupos\Grupo::todosLosGrupos();

    $contenidoPrincipal = <<<EOS
    <h2>Grupos</h2>
    EOS;

    $contenidoPrincipal .= '<div class="group-list">';
    foreach ($grupos as $grupo) {
        $foto_URL = ".";
        $foto_URL .= $grupo->getImagen();
        $nombre = $grupo->getNombre();
        $tamano = $grupo->getTamano();
        $miembros = $grupo->getMiembros($grupo->id());
        
        $contenidoPrincipal .= "<div class='group-item'>";
        $contenidoPrincipal .= "<div class='group-image'><img src='$foto_URL' alt='Imagen del grupo'></div>";
        $contenidoPrincipal .= "<div class='group-details'>";
        $contenidoPrincipal .= "<h2>$nombre</h2>";
        $contenidoPrincipal .= "<p>Tamaño: $tamano</p>";
        $contenidoPrincipal .= "<p>Miembros:</p><ul>";
        foreach ($miembros as $miembro) {
            
            $contenidoPrincipal .= "<li>{$miembro['Nombre']}</li>";
        }
        $contenidoPrincipal .= "</ul></div></div>";
    }
    $contenidoPrincipal .= '</div>';
} else {
    $contenidoPrincipal =  "<p>Por favor, <a href='login.php'>inicia sesión</a> para poder ver los grupos disponibles.</p>";
}

$params = ['tituloPagina' => $tituloPagina, 'contenidoPrincipal' => $contenidoPrincipal];
$app->generaVista('/plantillas/plantilla.php', $params);
?>
