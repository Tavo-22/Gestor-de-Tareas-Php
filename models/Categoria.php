<?php
require_once 'config/database.php';

class Categoria
{
    private $pdo;
    private $id;
    private $nombre;
    private $descripcion;
    private $usuario_id;
    private $fecha_creacion;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getNombre()
    {
        return $this->nombre;
    }

    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    public function getDescripcion()
    {
        return $this->descripcion;
    }

    public function setDescripcion($descripcion)
    {
        $this->descripcion = $descripcion;
    }

    public function getUsuarioId()
    {
        return $this->usuario_id;
    }

    public function setUsuarioId($usuario_id)
    {
        $this->usuario_id = $usuario_id;
    }

    public function getFechaCreacion()
    {
        return $this->fecha_creacion;
    }

    //verificar si la categoria existe por nombre
    public function nombreExiste($nombre, $usuario_id, $excluir_id = null)
    {
        $sql = "SELECT id FROM categorias WHERE nombre = ? AND usuario_id =?";
        $params = [$nombre, $usuario_id];

        if ($excluir_id) {
            $sql .= " AND id != ?";
            $params[] = $excluir_id;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    //guardar categoria
    public function guardar()
    {
        //validadr campos obligatorios
        if (empty($this->nombre) || empty($this->usuario_id)) {
            return [
                'success' => false,
                'message' => 'El nombre y el usuario son obligatorios'
            ];
        }
        //verificar si el nombre ya existe para el mismo usuario
        if ($this->nombreExiste($this->nombre, $this->usuario_id)) {
            return [
                'success' => false,
                'message' => 'Ya tienes una categoría con ese nombre' . $this->nombre
            ];
        }

        //guardar en la base de datos
        try {
            $stmt = $this->pdo->prepare("INSERT INTO categorias (nombre, descripcion, usuario_id) VALUES (?,?,?)");
            $resultado = $stmt->execute([
                $this->nombre,
                $this->descripcion,
                $this->usuario_id
            ]);

            if ($resultado) {
                $this->id = $this->pdo->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Categoría guardada correctamente',
                    'categoria_id' => $this->id
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al guardar la categoría'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al guardar la categoría: ' . $e->getMessage()
            ];
        }
    }

    //buscar una categoria por id
    public function buscarPorId($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        $categoriaData = $stmt->fetch();

        if ($categoriaData) {
            $this->id = $categoriaData['id'];
            $this->nombre = $categoriaData['nombre'];
            $this->descripcion = $categoriaData['descripcion'];
            $this->usuario_id = $categoriaData['usuario_id'];
            $this->fecha_creacion = $categoriaData['fecha_creacion'];
            return true;
        } else {
            return false;
        }
    }

    //listar todas las categorias de un usuario
    public function listarPorUsuario($usuario_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY nombre ASC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll();
    }

    //Obtiene el usuario asociado a la categoría
    public function getUsuario()
    {
        require_once 'Usuario.php';
        $usuario = new Usuario();
        $usuario->buscarPorId($this->usuario_id);
        return $usuario;
    }

    //Contar el número de tareas asociadas a esta categoría
    public function contarTareas()
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM tareas 
            WHERE categoria_id = ?
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchColumn();
    }

    //actualizar categoria
    public function actualizar()
    {
        if (empty($this->id)) {
            return [
                'success' => false,
                'message' => 'ID de categoría no especificado'
            ];
        }
        if (empty($this->nombre)) {
            return [
                'success' => false,
                'message' => 'El nombre es obligatorio'
            ];
        }

        //validar nombre dupicado 
        if ($this->nombreExiste($this->nombre, $this->usuario_id, $this->id)) {
            return [
                'success' => false,
                'message' => 'Ya tienes una categoría con ese nombre' . $this->nombre
            ];
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?");
            $resultado = $stmt->execute([
                $this->nombre,
                $this->descripcion,
                $this->id
            ]);

            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Categoría actualizada correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al actualizar la categoría'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar la categoría: ' . $e->getMessage()
            ];
        }
    }

    //eliminar categoria
    public function eliminar()
    {
        if (empty($this->id)) {
            return [
                'success' => false,
                'message' => 'No se ha especificado el ID de la categoría a eliminar'
            ];
        }

        //verificar si tiene tareas asociadas
        if ($this->contarTareas() > 0) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar la categoría porque tiene tareas asociadas: ' . $this->contarTareas()
            ];
        }
        try {
            $stmt = $this->pdo->prepare("DELETE FROM categorias WHERE id = ?");
            $resultado = $stmt->execute([$this->id]);

            if ($resultado) {
                // Limpiar propiedades
                $this->id = null;
                $this->nombre = null;
                $this->descripcion = null;
                $this->usuario_id = null;
                $this->fecha_creacion = null;

                return [
                    'success' => true,
                    'message' => 'Categoría eliminada correctamente'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar la categoría: ' . $e->getMessage()
            ];
        }
    }

    public function listarPorUsu($usuario_id)
    {
        $sql = "SELECT c.*, COUNT(t.id) as total_tareas 
        FROM categorias c
        LEFT JOIN tareas t ON c.id = t.categoria_id
        WHERE c.usuario_id = ? 
        GROUP BY c.id
        ORDER BY c.nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll();
    }
}
