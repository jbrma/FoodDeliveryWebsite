<?php
namespace es\ucm\fdi\aw\grupos;

use es\ucm\fdi\aw\Aplicacion;
use es\ucm\fdi\aw\Formulario;
use es\ucm\fdi\aw\usuarios\Usuario;

class FormularioCrearGrupo extends Formulario {
    public function __construct() {
        parent::__construct('formCrearGrupo', ['urlRedireccion' => Aplicacion::getInstance()->resuelve('/verGrupos.php'), 'enctype' => 'multipart/form-data']);
    }

    protected function generaCamposFormulario(&$datos) {
        $nombre = $datos['nombre'] ?? '';
        $tamano = $datos['tamano'] ?? '';
        $imagen = $datos['imagen'] ?? '';

        $htmlErroresGlobales = self::generaListaErroresGlobales($this->errores);
        $erroresCampos = self::generaErroresCampos(['nombre', 'tamano','imagen','miembros'], $this->errores, 'span', array('class' => 'error'));
     

        $html = <<<EOF
        $htmlErroresGlobales
            <div class="form-group">
                <label for="nombre">Nombre del grupo:</label>
                <input type="text" name="nombre" id="nombre" required>
                {$erroresCampos['nombre']}
            </div>
            <div class="form-group">
                <label for="tamano">Tamaño del grupo:</label>
                <input type="number" name="tamano" id="tamano" min="1" required>
                {$erroresCampos['tamano']}
            </div>
            <div class="form-group">
                <label for="imagen">Imagen del grupo:</label>
                <input type="file" name="imagen" id="imagen" accept=".png, .jpg, .jpeg, .gif" required>
                {$erroresCampos['imagen']}
            </div>
            <div class="form-group">
                <label for="miembros">Miembros del grupo:</label>
                <select name="miembros[]" id="miembros" multiple required>
                     {$this->generaOpcionesUsuarios()}
                </select>
                {$erroresCampos['miembros']}
            </div>
            <button type="submit">Crear Grupo</button>
        EOF;
        return $html;
    }

    private function generaOpcionesUsuarios() {
        $usuarios = Usuario::todosLosUsuariosSinGrupo();
        $html = '';
        foreach ($usuarios as $usuario) {
            $html .= '<option value="'.$usuario['Nombre'].'">'.$usuario['Nombre'].'</option>';
        }
        return $html;
    }

    protected function procesaFormulario(&$datos) {
        $this->errores = [];
        
        $nombre = trim($datos['nombre'] ?? '');
        $nombre = filter_var($nombre, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!$nombre || empty($nombre)) {
            $this->errores['nombre'] = 'El nombre del grupo no puede estar vacío.';
        }

        $tamano = trim($datos['tamano'] ?? '');
        $tamano = filter_var($tamano, FILTER_SANITIZE_NUMBER_INT);
        if (!$tamano || $tamano <= 0) {
            $this->errores['tamano'] = 'El tamaño del grupo debe ser un número positivo.';
        }

        $miembros = $datos['miembros'] ?? [];
        if (empty($miembros)) {
            $this->errores['miembros'] = 'Debe seleccionar al menos un miembro.';
        } elseif (count($miembros) > $tamano) {
            $this->errores['miembros'] = 'El número de miembros no puede ser mayor al tamaño del grupo.';
        }

        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            $this->errores['imagen'] = 'Debe subir una imagen.';
        }

        if (count($this->errores) === 0) {
            $archivo = $_FILES['imagen'];
            $nombreArchivo = basename($archivo['name']);

            $directorioDestino = dirname(__DIR__, 3) . '/img/grupos/';
            $rutaCarpeta = "/img/grupos/";
            $ruta_archivo = $directorioDestino . $nombreArchivo;
            $ruta_DB = $rutaCarpeta . $nombreArchivo;

            if (move_uploaded_file($archivo['tmp_name'], $ruta_archivo)) {
                echo "El archivo se subio correctamente";
            } else {
                $this->errores[] = 'Error al guardar la imagen.';
            }
            
            $grupo = new Grupo($nombre, $tamano, $ruta_DB);
            if(!$grupo->guarda($miembros)){
                $idGrupo = $grupo->id();
                $this->errores[] = "El grupo ya existe o algo salio mal en la creacion";
            }
        }

        return $this->errores;
    }
}
?>
