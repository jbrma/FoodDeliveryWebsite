<?php
namespace es\ucm\fdi\aw\usuarios;

use es\ucm\fdi\aw\Aplicacion;
use es\ucm\fdi\aw\Formulario;

class FormularioUsuarioAdmin extends Formulario
{
    public function __construct() {
        parent::__construct('formUserAdmin', []);
    }
    
    protected function generaCamposFormulario(&$datos)
    {
        // Se reutiliza el nombre de usuario introducido previamente o se deja en blanco
      

        // Se generan los mensajes de error si existen.
        $htmlErroresGlobales = self::generaListaErroresGlobales($this->errores);
        $erroresCampos = self::generaErroresCampos(['nombreUsuario', 'password'], $this->errores, 'span', array('class' => 'error'));
        $usuarios=Usuario::getAllUser();
        $html="";
        $html="<div style='display:flex;flex-direction:row;'>";
        foreach($usuarios as $usuario){
            $nombre=$usuario->getNombre();
            $email=$usuario->getEmail();
            $foto=$usuario->getFoto();
            $html.="<h3>$nombre</h3>";
            $html.="<h3>$email</h3>";
            $html .= "<img src='$foto' alt='Descripción de la imagen' style='max-width: 150px; max-height: 150px;'>";
            $html.="<br>";
        }
        $html.="</div>";
        // Se genera el HTML asociado a los campos del formulario y los mensajes de error.
        // $html = <<<EOF
        // $htmlErroresGlobales
        // <fieldset>
        //     <legend>Usuario y contraseña</legend>
        //     <div>
        //         <label for="nombreUsuario">Nombre de usuario:</label>
        //         <input id="nombreUsuario" type="text" name="nombreUsuario" value="$nombreUsuario" />
        //         {$erroresCampos['nombreUsuario']}
        //     </div>
        //     <div>
        //         <label for="password">Password:</label>
        //         <input id="password" type="password" name="password" />
        //         {$erroresCampos['password']}
        //     </div>
        //     <div>
        //         <button type="submit" name="login">Entrar</button>
        //     </div>
        // </fieldset>
        // EOF;
        return $html;
    }

    protected function procesaFormulario(&$datos)
    {
        $this->errores = [];
        $nombreUsuario = trim($datos['nombreUsuario'] ?? '');
        $nombreUsuario = filter_var($nombreUsuario, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ( ! $nombreUsuario || empty($nombreUsuario) ) {
            $this->errores['nombreUsuario'] = 'El nombre de usuario no puede estar vacío';
        }
        
        $password = trim($datos['password'] ?? '');
        $password = filter_var($password, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ( ! $password || empty($password) ) {
            $this->errores['password'] = 'El password no puede estar vacío.';
        }
        
        if (count($this->errores) === 0) {
            $usuario = Usuario::login($nombreUsuario, $password);
        
            if (!$usuario) {
                $this->errores[] = "El usuario o el password no coinciden";
            } else {
                $app = Aplicacion::getInstance();
                $app->login($usuario);
            }
        }
    }
}
