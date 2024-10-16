<?php
namespace es\ucm\fdi\aw\usuarios;

use es\ucm\fdi\aw\Aplicacion;
use es\ucm\fdi\aw\MagicProperties;

class Usuario
{
    use MagicProperties;

    public const ADMIN_ROLE = "admin";

    public const USER_ROLE = "cliente";

    public static function login($nombreUsuario, $password)
    {
        $usuario = self::buscaUsuario($nombreUsuario);
        if ($usuario && $usuario->compruebaPassword($password)) {
            return $usuario;
        }
        return false;
    }
    
    public static function crea($nombreUsuario, $password, $email, $foto, $rol)
    {
        $user = new Usuario($nombreUsuario, $email,self::hashPassword($password), "",$rol);
        if($user->guarda()){
            return $user;
        }
        else{
            return false;
        }
    }

    public static function buscaUsuario($nombreUsuario)
    {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $nombreUsuario = html_entity_decode($nombreUsuario, ENT_QUOTES, 'UTF-8');
        $query = sprintf("SELECT * FROM Usuario U WHERE U.Nombre='%s'", $conn->real_escape_string($nombreUsuario));
        $rs = $conn->query($query);
        $result = false;
        if ($rs) {
            $fila = $rs->fetch_assoc();
            if ($fila) {
                $result = new Usuario($fila['Nombre'], $fila['Email'], $fila['Password_hash'], $fila['Foto_de_perfil'], $fila['Rol'], $fila['Grupo']);
            }
            $rs->free();
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
        }
        return $result;
    }

    public static function buscaPorId($idUsuario)
    {
        $conn = Aplicacion::getInstance()->getConexionBd();
       
        $query = sprintf("SELECT * FROM Usuarios WHERE id=%d", $idUsuario);
        $rs = $conn->query($query);
        $result = false;
        if ($rs) {
            $fila = $rs->fetch_assoc();
            if ($fila) {
                $result = new Usuario($fila['nombreUsuario'], $fila['password'], $fila['nombre'], $fila['id'], $fila['Grupo']);
            }
            $rs->free();
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
        }
        return $result;
    }
    
    private static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    private static function cargaRoles($usuario)
    {
        $roles=[];
            
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf("SELECT RU.rol FROM RolesUsuario RU WHERE RU.usuario=%d", $usuario->id);
        $rs = $conn->query($query);
        if ($rs) {
            $roles = $rs->fetch_all(MYSQLI_ASSOC);
            $rs->free();

            $usuario->roles = [];
            foreach($roles as $rol) {
                $usuario->roles[] = $rol['rol'];
            }
            return $usuario;

        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
        }
        return false;
    }
   
    private static function inserta($usuario)
    {
        $result = false;
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query=sprintf("INSERT INTO Usuario (Nombre, Email, Password_hash, Foto_de_perfil, Rol, Grupo) VALUES ('%s', '%s', '%s', '%s', '%s','%s')"
            , $conn->real_escape_string($usuario->nombre)
            , $conn->real_escape_string($usuario->email)
            , $conn->real_escape_string($usuario->password)
            , $conn->real_escape_string($usuario->foto)
            , $conn->real_escape_string($usuario->roles)
            , is_null($usuario->grupo) ? 'NULL' : $usuario->grupo
        );
        if ( $conn->query($query) ) {
            // $usuario->id = $conn->insert_id;
            // $result = self::insertaRoles($usuario);
            $result = true;
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
        }
        return $result;
    }
   
    private static function insertaRoles($usuario)
    {
        $conn = Aplicacion::getInstance()->getConexionBd();
        foreach($usuario->roles as $rol) {
            $query = sprintf("INSERT INTO RolesUsuario(usuario, rol) VALUES (%d, %d)"
                , $usuario->id
                , $rol
            );
            if ( ! $conn->query($query) ) {
                error_log("Error BD ({$conn->errno}): {$conn->error}");
                return false;
            }
        }
        return $usuario;
    }
    
    private static function actualiza($usuario)
    {
        $result = false;
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query=sprintf("UPDATE Usuarios U SET nombreUsuario = '%s', nombre='%s', password='%s' WHERE U.id=%d"
            , $conn->real_escape_string($usuario->nombreUsuario)
            , $conn->real_escape_string($usuario->nombre)
            , $conn->real_escape_string($usuario->password)
            , $usuario->id
        );
        if ( $conn->query($query) ) {
            $result = self::borraRoles($usuario);
            if ($result) {
                $result = self::insertaRoles($usuario);
            }
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
        }
        
        return $result;
    }
   
    private static function borraRoles($usuario)
    {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf("DELETE FROM RolesUsuario RU WHERE RU.usuario = %d"
            , $usuario->id
        );
        if ( ! $conn->query($query) ) {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
            return false;
        }
        return $usuario;
    }
    
    private static function borra($usuario)
    {
        return self::borraPorId($usuario->id);
    }
    
    private static function borraPorId($idUsuario)
    {
        if (!$idUsuario) {
            return false;
        } 
        /* Los roles se borran en cascada por la FK
         * $result = self::borraRoles($usuario) !== false;
         */
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf("DELETE FROM Usuarios U WHERE U.id = %d"
            , $idUsuario
        );
        if ( ! $conn->query($query) ) {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
            return false;
        }
        return true;
    }

    /* JORGE */

    public static function todosLosUsuariosSinGrupo() {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $usuarios = array();

        $usu = "SELECT * FROM Usuario WHERE grupo IS NULL";
        $result = $conn->query($usu);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
        }
        $result->free();
        return $usuarios;
    }

    public static function getUsuariosPorGrupo($idGrupo) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $usuarios = array();

        $usu = sprintf("SELECT * FROM Usuario WHERE grupo = %d", $idGrupo);
        $result = $conn->query($usu);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
        }
        $result->free();
        return $usuarios;
    }

    public static function getGrupoIdUsuarioSesion()
    {
        if (isset($_SESSION['nombre'])) {
            $usuario = self::buscaUsuario($_SESSION['nombre']);
            if ($usuario) {
                return $usuario->getGrupoId();
            }
        }
        return null;
    }

    private $nombre;

    private $email;

    private $password;

    private $foto;

    private $roles;

    /* JORGE */
    private $grupo;

    private function __construct($nombre, $email, $password, $foto = null, $roles = [], $grupo = null)
    {
        $this->nombre = $nombre;
        $this->email = $email;
        $this->password = $password;
        $this->foto = $foto;
        $this->roles = $roles;
        $this->grupo = $grupo;
    }

    public function getGrupoId()
    {
        return $this->grupo;
    }
    
    public function getNombre()
    {
        return $this->nombre;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getFoto()
    {
        return $this->foto;
    }

    public function añadeRol($role)
    {
        $this->roles[] = $role;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function tieneRol($role)
    {
        if ($this->roles == null) {
            self::cargaRoles($this);
        }
        return array_search($role, $this->roles) !== false;
    }

    public function compruebaPassword($password)
    {
        return password_verify($password, $this->password);
    }

    public function cambiaPassword($nuevoPassword)
    {
        $this->password = self::hashPassword($nuevoPassword);
    }
    
    public function guarda()
    {
        // if ($this->id !== null) {
        //     return self::actualiza($this);
        // }
        return self::inserta($this);
    }
    
    public function borrate()
    {
        if ($this->id !== null) {
            return self::borra($this);
        }
        return false;
    }


    public function encontrarSeguidos() {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $seguidos = [];
    
        $query = sprintf("SELECT U.Nombre, U.Email, U.Password_hash, U.Foto_de_perfil FROM Usuario U INNER JOIN Seguidores S ON U.Nombre = S.Seguido WHERE S.Seguidor='%s'", $conn->real_escape_string($this->nombre));
    
        $rs = $conn->query($query);
        if ($rs) {
            while ($fila = $rs->fetch_assoc()) {
                $usuario = new Usuario($fila['Nombre'], $fila['Email'], $fila['Password_hash'], $fila['Foto_de_perfil']);
                array_push($seguidos, $usuario);
            }
            $rs->free();
        } else {
            error_log("Error SQL ({$conn->errno}): {$conn->error}");
        }
    
        return $seguidos;
    }

    public function seguirUsuario($nombreUsuarioASeguir) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        
        $query = sprintf("INSERT INTO Seguidores (Seguidor, Seguido) VALUES ('%s', '%s')",
                         $conn->real_escape_string($this->nombre),
                         $conn->real_escape_string($nombreUsuarioASeguir));
    
        if ($conn->query($query)) {
            return true; // Empezó a seguir
        } else {
            error_log("Error al intentar seguir al usuario ({$conn->errno}): {$conn->error}");
            return false; // Error al seguir al usuario
        }
    }
    
    public function dejarDeSeguirUsuario($nombreUsuarioADejarDeSeguir) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf("DELETE FROM Seguidores WHERE Seguidor='%s' AND Seguido='%s'",
                         $conn->real_escape_string($this->nombre),
                         $conn->real_escape_string($nombreUsuarioADejarDeSeguir));
    
        if ($conn->query($query)) {
            return true; // Dejó de seguir
        } else {
            error_log("Error al intentar dejar de seguir al usuario ({$conn->errno}): {$conn->error}");
            return false; // Error al dejar de seguir al usuario
        }
    }
    

    public function perfilUsuario() {

        $rutaFoto = $this->foto ? $this->foto : './img/basic/user.png';
    
        return [
            'rutaFoto' => htmlspecialchars($rutaFoto),
            'nombre' => htmlspecialchars($this->nombre),
            'email' => htmlspecialchars($this->email)
        ];
    }
    public function setFotoDePerfil($Foto_de_perfil) {
        $this->foto = $Foto_de_perfil;

        $conn = Aplicacion::getInstance()->getConexionBd();

        $query = sprintf("UPDATE Usuario U SET U.Foto_de_perfil='%s' WHERE U.Nombre='%s'",
                        $conn->real_escape_string($Foto_de_perfil),
                        $conn->real_escape_string($this->nombre));

        if ($conn->query($query) === TRUE) {
            return true; // Actualización exitosa
        } else {
            error_log("Error al actualizar la foto de perfil: " . $conn->error);
            return false; // Error al actualizar
        }
    }

    public static function getAllUser() {
    
        $conn = Aplicacion::getInstance()->getConexionBd();
        
        $query = "SELECT * FROM Usuario";
        $rs = $conn->query($query);
        if ($rs->num_rows > 0) {
            while($fila = $rs->fetch_assoc()){
          
            $usuarios[]= new Usuario($fila['Nombre'], $fila['Email'], $fila['Password_hash'], $fila['Foto_de_perfil'], $fila['Rol']);    
        }
        } else
        {
            $usuarios = array();
        }
        $rs->free();
        
        return $usuarios;
    }
    
    public function deleteFoto(){
      
        self::setFotoDePerfil("./img/perfiles/Default.png");
    }
    
    public function deleteUser(){
        $conn = Aplicacion::getInstance()->getConexionBd();

        $query = sprintf("DELETE FROM Usuario WHERE Nombre='%s'",
                        $conn->real_escape_string($this->nombre));

        if ($conn->query($query) === TRUE) {
            return true;
        } else {
            error_log("Error al eliminar el usuario: " . $conn->error);
            return false;
        }
    }

}
