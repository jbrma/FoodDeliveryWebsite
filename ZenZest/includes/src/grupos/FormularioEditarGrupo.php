<?php
namespace es\ucm\fdi\aw\grupos;

use es\ucm\fdi\aw\Aplicacion;
use es\ucm\fdi\aw\Formulario;
use es\ucm\fdi\aw\usuarios\Usuario;

class FormularioEditarGrupo extends Formulario {
    private $grupo;

    public function __construct($grupo) {
        parent::__construct('formEditarGrupo');
        $this->grupo = $grupo;
    }

    protected function generaCamposFormulario(&$datos) {
        $nombre = $datos['nombre'] ?? $this->grupo->getNombre();
        $tamano = $datos['tamano'] ?? $this->grupo->getTamano();
        $imagen = $datos['imagen'] ?? $this->grupo->getImagen();
        $idGrupo = $this->grupo->buscaIdPorNombre($nombre);
        $miembros = $this->grupo->getMiembros($idGrupo);

        $htmlErroresGlobales = self::generaListaErroresGlobales($this->errores);
        $erroresCampos = self::generaErroresCampos(['tamano', 'imagen', 'miembros', 'eliminados'], $this->errores, 'span', ['class' => 'error']);

        $htmlMiembros = '';
        foreach ($miembros as $miembro) {
            $htmlMiembros .= "<option value='{$miembro['Nombre']}' selected>{$miembro['Nombre']}</option>";
        }

        /*   No dice que se pueda modificar el nombre del grupo          
            <div class="form-group">
                <label for="nombre">Nombre del Grupo:</label>
                <input type="text" id="nombre" name="nombre" value="$nombre" required>
                {$erroresCampos['nombre']}
            </div>*/

        $html = <<<EOF
            $htmlErroresGlobales
            <div class="form-group">
                <label for="tamano">Tamaño del Grupo:</label>
                <input type="number" id="tamano" name="tamano" value="$tamano" min="1" required>
                {$erroresCampos['tamano']}
            </div>
            <div class="form-group">
                <label for="eliminados">Eliminar miembros del Grupo:</label>
                <select id="eliminados" name="eliminados[]" multiple>
                    $htmlMiembros
                </select>
                {$erroresCampos['eliminados']}
            </div>
            <div class="form-group">
                <label for="miembros">Nuevos miembros del Grupo:</label>
                <select id="miembros" name="miembros[]" multiple>
                    {$this->generaOpcionesUsuarios()}
                </select>
                {$erroresCampos['miembros']}
            </div>
            <div class="form-group">
                <label for="imagen">Imagen del grupo:</label>
                <input type="file" name="imagen" id="imagen" accept=".png, .jpg, .jpeg, .gif">
                {$erroresCampos['imagen']}
            </div>
            <button type="submit">Actualizar Grupo</button>
        EOF;
        return $html;
    }

    private function generaOpcionesUsuarios() {
        $usuarios = Usuario::todosLosUsuariosSinGrupo();
        $html = '';
        foreach ($usuarios as $usuario) {
            $html .= '<option value="' . $usuario['Nombre'] . '">' . $usuario['Nombre'] . '</option>';
        }
        return $html;
    }

    protected function procesaFormulario(&$datos) {
        $this->errores = [];

        $nombre = $datos['nombre'] ?? $this->grupo->getNombre();
        $idGrupo = $this->grupo->buscaIdPorNombre($nombre);

        $tamano = trim($datos['tamano'] ?? '');
        $tamano = filter_var($tamano, FILTER_SANITIZE_NUMBER_INT);
        if (!$tamano || $tamano <= 0) {
            $this->errores['tamano'] = 'El tamaño del grupo debe ser un número positivo.';
        }

        $eliminados = $datos['eliminados'] ?? [];
        $miembros = $datos['miembros'] ?? [];
        $cantidadUsuarios = Grupo::contarUsuariosEnGrupo($idGrupo);
        if (($cantidadUsuarios + count($miembros) - count($eliminados)) > $tamano) {
            $this->errores['miembros'] = 'El número de miembros no puede ser mayor al tamaño del grupo.';
        }

        // Si no hay nueva imagen, mantiene la anterior
        $gr = Grupo::buscaGrupo($idGrupo);
        $imagen = $gr->getImagen();
        if($datos['imagen'] != "")
            $imagen = "/img/grupos/". $datos['imagen'];


        if (count($this->errores) === 0) {

            /* Esto funciona con required, pero no es necesario subir una nueva imagen, se puede mantener la que había antes 
            
            $archivo = $_FILES['imagen'];
            $nombreArchivo = basename($archivo['name']);
            echo $archivo;
            $directorioDestino = dirname(__DIR__, 3) . '/img/grupos/';
            $rutaCarpeta = "/img/grupos/";
            $ruta_archivo = $directorioDestino . $nombreArchivo;
        
            // Verificamos si hay un archivo subido
            if (!empty($archivo['name'])) {
                $ruta_DB = $rutaCarpeta . $nombreArchivo;
                if (move_uploaded_file($archivo['tmp_name'], $ruta_archivo)) {
                    echo "El archivo se subio correctamente";
                } else {
                    $this->errores[] = 'Error al guardar la imagen.';
                }
            } else {
                // Si no se subió una nueva imagen, mantenemos la imagen existente
                $grupoExistente = Grupo::buscaGrupo($idGrupo);
                $ruta_DB = $grupoExistente->getImagen();
            }

            */


            if (!$this->grupo->actualiza($tamano, $imagen, $miembros)) {
                $this->errores[] = 'Error al guardar los cambios del grupo.';
            } else {
                foreach ($eliminados as $eliminado) {
                    $this->grupo->eliminaMiembro($eliminado);
                }
            }
        }
            
        header("Location: miGrupo.php");
        
        return $this->errores;
    }
}

?>
