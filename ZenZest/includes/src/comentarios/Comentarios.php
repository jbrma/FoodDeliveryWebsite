<?php
namespace es\ucm\fdi\aw\comentarios;

use es\ucm\fdi\aw\Aplicacion;
use es\ucm\fdi\aw\MagicProperties;
class Comentarios {
    use MagicProperties;
    private $ID;
    private $Usuario;
    private $Cafeteria_Comentada;
    private $Valoracion;
    private $Mensaje;

    public function __construct($ID, $Usuario, $Cafeteria_Comentada, $Valoracion, $Mensaje) {
        $this->ID = $ID;
        $this->Usuario = $Usuario;
        $this->Cafeteria_Comentada = $Cafeteria_Comentada;
        $this->Valoracion = $Valoracion;
        $this->Mensaje = $Mensaje;
    }

    public static function getComentariosDeSeguidos($usuariosSeguidos) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        

        $nombresSeguidos = array_map([$conn, 'real_escape_string'], $usuariosSeguidos);
        
        // Construye una parte de la consulta SQL para usar con IN()
        $inQuery = "'" . join("','", $nombresSeguidos) . "'";
        
        // Ahora $inQuery contiene los nombres de usuario seguidos, listos para ser usados en la consulta
        $query = "SELECT * FROM Comentarios WHERE Usuario IN ($inQuery) ORDER BY ID DESC";
        
        $result = $conn->query($query);
        $comentarios = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $comentarios[] = new Comentarios($row['ID'], $row['Usuario'], $row['Cafeteria_Comentada'], $row['Valoracion'], $row['Mensaje']);
            }
            $result->free();
        }
        return $comentarios;
    }
    public static function getComentariosPorUsuario($usuario) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $comentarios = [];
        
        // Prepara la consulta SQL para evitar inyecciones SQL
        $query = $conn->prepare("SELECT * FROM Comentarios WHERE Usuario = ?");
        $query->bind_param("s", $usuario); // "s" indica que el parámetro es una cadena (string)
        $query->execute();
        
        $resultado = $query->get_result();
        
        while ($fila = $resultado->fetch_assoc()) {
            $comentarios[] = new Comentarios(
                $fila['ID'],
                $fila['Usuario'],
                $fila['Cafeteria_Comentada'],
                $fila['Valoracion'],
                $fila['Mensaje']
            );
        }
        
        $query->close();
        
        return $comentarios;
    }

    public static function getComentariosPorCafeteria($nombreCafeteria) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $comentarios = [];

        // Prepara la consulta SQL para evitar inyecciones SQL
        $query = $conn->prepare("SELECT * FROM Comentarios WHERE Cafeteria_Comentada = ? ORDER BY ID DESC");
        $query->bind_param("s", $nombreCafeteria); // "s" indica que el parámetro es una cadena (string)
        $query->execute();

        $resultado = $query->get_result();

        while ($fila = $resultado->fetch_assoc()) {
            $comentarios[] = new Comentarios(
                $fila['ID'],
                $fila['Usuario'],
                $fila['Cafeteria_Comentada'],
                $fila['Valoracion'],
                $fila['Mensaje']
            );
        }

        $query->close();

        return $comentarios;
    }
    
    public static function guardarComentario($usuario, $cafeteria, $mensaje, $valoracion) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $stmt = $conn->prepare("INSERT INTO Comentarios (Usuario, Cafeteria_Comentada, Mensaje, Valoracion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $usuario, $cafeteria, $mensaje, $valoracion);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public static function getAllComentarios() {
    
        $conn = Aplicacion::getInstance()->getConexionBd();
        
        $query = "SELECT * FROM Comentarios";
        $rs = $conn->query($query);
        if ($rs->num_rows > 0) {
            while ($fila = $rs->fetch_assoc()) {
                $comentarios[] = new Comentarios(
                    $fila['ID'],
                    $fila['Usuario'],
                    $fila['Cafeteria_Comentada'],
                    $fila['Valoracion'],
                    $fila['Mensaje']
                );
            }
        } else
        {
            $comentarios = array();
        }
        $rs->free();
        // Return the results
        return $comentarios;
    }
    

    // Getters and Setters
    public function getID() {
        return $this->ID;
    }

    public function setID($ID) {
        $this->ID = $ID;
    }

    public function getUsuario() {
        return $this->Usuario;
    }

    public function setUsuario($Usuario) {
        $this->Usuario = $Usuario;
    }

    public function getCafeteriaComentada() {
        return $this->Cafeteria_Comentada;
    }

    public function setCafeteriaComentada($Cafeteria_Comentada) {
        $this->Cafeteria_Comentada = $Cafeteria_Comentada;
    }

    public function getValoracion() {
        return $this->Valoracion;
    }

    public function setValoracion($Valoracion) {
        $this->Valoracion = $Valoracion;
    }

    public function getMensaje() {
        return $this->Mensaje;
    }

    public function setMensaje($Mensaje) {
        $this->Mensaje = $Mensaje;
    }


    public function deleteComentario($id) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf("DELETE FROM Comentarios WHERE ID = %d",
            $id
        );
        $result = $conn->query($query);
        if ($result) {
            return true;
        } else {
            return false;
        }
        $result->free();
    }
    
    public static function getComentarioById($id){
        $conn = Aplicacion::getInstance()->getConexionBd();
        
        $query = sprintf("SELECT * FROM Comentarios C WHERE C.ID = '%d'", $id);
        $rs = $conn->query($query);
        if ($rs->num_rows > 0) {
            $fila = $rs->fetch_assoc();
            $cafeteria = new Comentarios($fila['ID'], $fila['Usuario'], $fila['Cafeteria_Comentada'], $fila['Valoracion'], $fila['Mensaje']);
            $result=$cafeteria;
            $rs->free();
        } else
        {
            $result = false;
        }
        return $result;

    }
}