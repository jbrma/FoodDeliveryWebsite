<?php
namespace es\ucm\fdi\aw\grupos;

use es\ucm\fdi\aw\Aplicacion;
use es\ucm\fdi\aw\usuarios\Usuario;
use es\ucm\fdi\aw\MagicProperties;

class Grupo {
    use Magicproperties;

    private $id;
    private $nombre;
    private $tamano;
    private $imagen;
    private $creadorId;

    public function __construct($nombre, $tamano, $imagen, $creadorId = null, $id = null) {
        $this->nombre = $nombre;
        $this->tamano = $tamano;
        $this->imagen = $imagen;
        $this->creadorId = $creadorId ?? $_SESSION['nombre'];
        $this->id = $id;

    }

    public function guarda($miembros) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf(
            "INSERT INTO Grupos (nombre, tamano, imagen, id_creador) VALUES ('%s', '%d', '%s', '%s')",
            $conn->real_escape_string($this->nombre),
            $this->tamano,
            $conn->real_escape_string($this->imagen),
            $conn->real_escape_string($this->creadorId)
        );
        $rs = $conn->query($query);
        if ($rs) {
            $this->id = $conn->insert_id; /*lo utilizo para obtener el ID del último registro insertado en la tabla grupos */
            foreach ($miembros as $miembroId) {
                $miembroNombre = $conn->real_escape_string($miembroId);
                $queryMiembro = sprintf(
                    "UPDATE Usuario SET grupo = %d WHERE nombre = '%s'",
                    $this->id,
                    $miembroNombre
                );
                if (!$conn->query($queryMiembro)) {
                    return false;
                }
            }
            return true;
        }
        $rs->free();
        
        return false;
    }


    public static function buscaGrupo($id)
    {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf(
            "SELECT * FROM Grupos G WHERE G.id='%s'",
            $conn->real_escape_string($id)
        );
        $rs = $conn->query($query);
        $result = false;
        if ($rs) {
            $fila = $rs->fetch_assoc();
            if ($fila) {
                $result = new Grupo($fila['nombre'], $fila['tamano'], $fila['imagen'], $fila['id_creador'], $fila['id']);
            }
            $rs->free();
        } else {
            error_log("Error BD ({$conn->errno}): {$conn->error}");
        }
        return $result;
    }

    public static function buscaIdPorNombre($nombre) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf("SELECT id FROM Grupos WHERE nombre = '%s'", $conn->real_escape_string($nombre));
        $rs = $conn->query($query);
        if ($rs && $rs->num_rows == 1) {
            $fila = $rs->fetch_assoc();
            $id = $fila['id'];
            $rs->free();
            return $id;
        }
        $rs->free();
        return false;
    }


    public static function todosLosGrupos() {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = "SELECT * FROM Grupos";
        $rs = $conn->query($query);
        $grupos = array();
        while ($fila = $rs->fetch_assoc()) {
            $grupo = new Grupo($fila['nombre'], $fila['tamano'], $fila['imagen'], $fila['id_creador']);
            $grupo->id = $fila['id'];
            $grupo->cargaMiembros();
            $grupos[] = $grupo;
        }
        $rs->free();
        return $grupos;
    }

    private function cargaMiembros() {
        return Usuario::getUsuariosPorGrupo($this->id);
    }

    public static function contarUsuariosEnGrupo($idGrupo) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf(
            "SELECT COUNT(*) as total FROM Usuario WHERE grupo = %d",
            $conn->real_escape_string($idGrupo)
        );
        $rs = $conn->query($query);
    
        if ($rs) {
            $fila = $rs->fetch_assoc();
            return (int)$fila['total'];
        } else {
            error_log("Error en la consulta SQL: " . $conn->error);
            return 0;
        }
    }

    public function actualiza($tamano, $imagen, $miembros = null) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf(
            "UPDATE Grupos SET tamano='%d', imagen='%s' WHERE id='%d'", $tamano, $conn->real_escape_string($imagen), $this->id);
        $rs = $conn->query($query);
        if ($rs) {
            $this->tamano = $tamano;
            $this->imagen = $imagen;
            return $this->actualizaMiembros($miembros);
        }
        return false;
    }
    

    private function actualizaMiembros($miembros) {
        if ($miembros !== null) {
            $conn = Aplicacion::getInstance()->getConexionBd();
    
            foreach ($miembros as $miembroNombre) {
                $miembroNombre = $conn->real_escape_string($miembroNombre);
                $queryMiembro = sprintf("UPDATE Usuario SET grupo = %d WHERE nombre = '%s'", $this->id, $miembroNombre);
                if (!$conn->query($queryMiembro)) {
                    error_log("Error al actualizar el miembro '$miembroNombre': " . $conn->error);
                    return false;
                }
            }
        }
        return true;
    }

    public static function eliminaMiembro($nombreUsuario) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf("UPDATE Usuario SET grupo = NULL WHERE nombre = '%s'", $conn->real_escape_string($nombreUsuario));
        $rs = $conn->query($query);
        if ($rs) {
            return true;
            $rs->free();
        }
        return false;
    }

    /* No dice nada de que se eliminen grupos, si se eliminaran, así se haría el método */
    public static function elimina($id) {
        $conn = Aplicacion::getInstance()->getConexionBd();
        $query = sprintf("DELETE FROM Grupos WHERE id = %d", $id);
        return $conn->query($query);
    }

    public function id() {
        return $this->id;
    }

    public function creadorId() {
        return $this->creadorId;
    }

    public function getImagen() {
        return $this->imagen;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function getTamano() {
        return $this->tamano;
    }

    public function getMiembros($idGrupo) {
        return \es\ucm\fdi\aw\usuarios\Usuario::getUsuariosPorGrupo($idGrupo);
    }
}
?>
