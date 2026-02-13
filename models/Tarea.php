<?php
require_once 'config/database.php';

class Tarea
{
    private $pdo;

    // Propiedades = columnas de la tabla
    private $id;
    private $titulo;
    private $descripcion;
    private $estado;
    private $prioridad;
    private $fecha_limite;
    private $fecha_creacion;
    private $usuario_id;
    private $categoria_id;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitulo()
    {
        return $this->titulo;
    }

    public function setTitulo($titulo)
    {
        $this->titulo = $titulo;
    }

    public function getDescripcion()
    {
        return $this->descripcion;
    }

    public function setDescripcion($descripcion)
    {
        $this->descripcion = $descripcion;
    }

    public function getEstado()
    {
        return $this->estado;
    }

    public function setEstado($estado)
    {
        // Validar que sea un estado permitido
        $estados_validos = ['pendiente', 'en_progreso', 'completada'];
        if (in_array($estado, $estados_validos)) {
            $this->estado = $estado;
        }
    }

    public function getPrioridad()
    {
        return $this->prioridad;
    }

    public function setPrioridad($prioridad)
    {
        // Validar que sea una prioridad permitida
        $prioridades_validas = ['baja', 'media', 'alta'];
        if (in_array($prioridad, $prioridades_validas)) {
            $this->prioridad = $prioridad;
        }
    }

    public function getFechaLimite()
    {
        return $this->fecha_limite;
    }

    public function setFechaLimite($fecha_limite)
    {
        $this->fecha_limite = $fecha_limite;
    }

    public function getFechaCreacion()
    {
        return $this->fecha_creacion;
    }

    public function getUsuarioId()
    {
        return $this->usuario_id;
    }

    public function setUsuarioId($usuario_id)
    {
        $this->usuario_id = $usuario_id;
    }

    public function getCategoriaId()
    {
        return $this->categoria_id;
    }

    public function setCategoriaId($categoria_id)
    {
        $this->categoria_id = $categoria_id;
    }

    //guardar
    public function guardar()
    {
        //validacion campos obligatorios
        if (empty($this->titulo)) {
            return [
                'success' => false,
                'message' => 'El título es obligatorio'
            ];
        }
        if (empty($this->usuario_id)) {
            return [
                'success' => false,
                'message' => 'La tarea debe estar asociada a un usuario'
            ];
        }

        //validacion fecha limite(si viene debe ser valida)
        if ($this->fecha_limite && !$this->validarFecha($this->fecha_limite)) {
            return [
                'success' => false,
                'message' => 'La fecha límite no es válida'
            ];
        }
        //insertar en la base de datos
        try {
            $sql = "INSERT INTO tareas (titulo, descripcion, estado, prioridad, fecha_limite, usuario_id, categoria_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);

            //si categoria_id es vacia, guardamos como null
            $categoria = !empty($this->categoria_id) ? $this->categoria_id : null;

            $resultado = $stmt->execute([
                $this->titulo,
                $this->descripcion,
                $this->estado ?? 'pendiente', // si no hay estado, por defecto es pendiente
                $this->prioridad ?? 'media', // si no hay prioridad, por defecto es media
                $this->fecha_limite,
                $this->usuario_id,
                $categoria
            ]);

            if ($resultado) {
                $this->id = $this->pdo->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Tarea guardada exitosamente',
                    'tarea_id' => $this->id
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al guardar la tarea: ' . $e->getMessage()
            ];
        }
    }

    //validar fecha
    private function validarFecha($fecha)
    {
        //verificar formato
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $fecha)) {
            return true;
        }
        return false;
    }

    //buscar por id carga una tarea especifica
    public function buscarPorId($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM tareas WHERE id = ?");
        $stmt->execute([$id]);
        $tareaData = $stmt->fetch();

        if ($tareaData) {
            //hidrtar el objeto con los datos de la tarea
            $this->id = $tareaData['id'];
            $this->titulo = $tareaData['titulo'];
            $this->descripcion = $tareaData['descripcion'];
            $this->estado = $tareaData['estado'];
            $this->prioridad = $tareaData['prioridad'];
            $this->fecha_limite = $tareaData['fecha_limite'];
            $this->fecha_creacion = $tareaData['fecha_creacion'];
            $this->usuario_id = $tareaData['usuario_id'];
            $this->categoria_id = $tareaData['categoria_id'];
            return true;
        }
        return false;
    }

    //listar tareas por usuario
    public function listarPorUsuario($usuario_id)
    {
        $stmt = $this->pdo->prepare("SELECT t.*, c.nombre AS categoria_nombre 
                                     FROM tareas t 
                                     LEFT JOIN categorias c ON t.categoria_id = c.id 
                                     WHERE t.usuario_id = ? 
                                     ORDER BY t.fecha_creacion DESC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll();
    }

    //listar tareas por estado 
    public function listarPorEstado($usuario_id, $estado)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM tareas 
            WHERE usuario_id = ? AND estado = ?
            ORDER BY fecha_limite ASC, prioridad DESC");
        $stmt->execute([$usuario_id, $estado]);
        return $stmt->fetchAll();
    }

    //listar por prioridad
    public function listarPorPrioridad($usuario_id, $prioridad)
    {
        $stmt = $this->pdo->prepare("
        SELECT * FROM tareas
        WHERE usuario_id = ? AND prioridad = ?
        ORDER BY fecha_limite ASC");
        $stmt->execute([$usuario_id, $prioridad]);
        return $stmt->fetchAll();
    }

    //actualizar tarea
    public function actualizar()
    {
        //validar id
        if (empty($this->id)) {
            return [
                'success' => false,
                'message' => 'El ID de la tarea es obligatorio para actualizar'
            ];
        }
        //validar titulo
        if (empty($this->titulo)) {
            return [
                'success' => false,
                'message' => 'El título es obligatorio'
            ];
        }

        try {
            $sql = "UPDATE tareas SET titulo = ?, descripcion = ?, estado = ?, prioridad = ?, fecha_limite = ?, categoria_id = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);

            //si categoria_id es vacia, guardamos como null
            $categoria = !empty($this->categoria_id) ? $this->categoria_id : null;

            $resultado = $stmt->execute([
                $this->titulo,
                $this->descripcion,
                $this->estado,
                $this->prioridad,
                $this->fecha_limite,
                $categoria,
                $this->id
            ]);

            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Tarea actualizada exitosamente'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar la tarea: ' . $e->getMessage()
            ];
        }
    }

    //cambiar estado de la tarea
    public function cambiarEstado($nuevo_estado)
    {
        if (empty($this->id)) {
            return [
                'success' => false,
                'message' => 'El ID de la tarea es obligatorio para cambiar el estado'
            ];
        }

        $estados_validos = ['pendiente', 'en_progreso', 'completada'];
        if (!in_array($nuevo_estado, $estados_validos)) {
            return [
                'success' => false,
                'message' => 'El estado proporcionado no es válido'
            ];
        }

        try {
            $sql = "UPDATE tareas SET estado = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $resultado = $stmt->execute([$nuevo_estado, $this->id]);

            if ($resultado) {
                $this->estado = $nuevo_estado; // Actualizar el estado en el objeto
                return [
                    'success' => true,
                    'message' => 'Estado de la tarea actualizado exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No se pudo actualizar el estado de la tarea'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado de la tarea: ' . $e->getMessage()
            ];
        }
    }

    //eliminar tarea
    public function eliminar()
    {
        if (empty($this->id)) {
            return [
                'success' => false,
                'message' => 'No se ha encontrado qué tarea eliminar'
            ];
        }

        try {
            $sql = "DELETE FROM tareas WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $resultado = $stmt->execute([$this->id]);

            if ($resultado) {
                // Limpiar propiedades del objeto
                $this->id = null;
                $this->titulo = null;
                $this->descripcion = null;
                $this->estado = null;
                $this->prioridad = null;
                $this->fecha_limite = null;
                $this->fecha_creacion = null;
                $this->usuario_id = null;
                $this->categoria_id = null;

                return [
                    'success' => true,
                    'message' => 'Tarea eliminada correctamente'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar la tarea: ' . $e->getMessage()
            ];
        }
    }

    //obtener categoria devuelve el objeto categoria asociado a la tarea
    public function obtenerCategoria()
    {
        if (empty($this->categoria_id)) {
            return null; // La tarea no tiene categoría asignada
        }

        require_once 'Categoria.php';
        $categoria = new Categoria();
        $categoria->buscarPorId($this->categoria_id);
        return $categoria;
    }

    //obtener usuario devuelve el objeto usuario asociado a la tarea
    public function obtenerUsuario()
    {
        require_once 'Usuario.php';
        $usuario = new Usuario();
        $usuario->buscarPorId($this->usuario_id);
        return $usuario;
    }

    //agregar etiquetas a la tarea
    public function agregarEtiqueta($etiquetas_id)
    {
        if (empty($this->id)) {
            return [
                'success' => false,
                'message' => 'Debe ser una tarea existente'
            ];
        }

        //verficar que existea la relacion
        $sql = "SELECT *FROM tarea_etiqueta 
        WHERE tarea_id = ? AND etiqueta_id = ?";
        $stmt = $this->pdo->prepare($sql);

        if ($stmt->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'La tarea ya tiene asignada esa etiqueta'
            ];
        }

        //crear la relacion
        try {
            $sql = "INSERT INTO tarea_etiqueta (tarea_id, etiqueta_id)
            VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $resultado = $stmt->execute([$this->id, $etiquetas_id]);

            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Etiqueta agregada a la tarea correctamente'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al agregar la etiqueta a la tarea: ' . $e->getMessage()
            ];
        }
    }

    //eliminar etiqueta, quitar asociacion
    public function eliminarEtiqueta($etiqueta_id)
    {
        if (empty($this->id)) {
            return [
                'success' => false,
                'message' => 'Tarea no especificada'
            ];
        }

        try {
            $sql = "DELETE FROM tarea_etiqueta
            WHERE tarea_id = ? AND etiqueta_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $resultado = $stmt->execute([$this->id, $etiqueta_id]);

            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Etiqueta eliminada de la tarea correctamente'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar la etiqueta de la tarea: ' . $e->getMessage()
            ];
        }
    }

    //obtener todas las etiqeutas de esta tarea
    public function obtenerEtiquetas() {
        if(empty($this->id)) {
            return [];
        }
        $sql = "SELECT e.* FROM etiquetas e
            INNER JOIN tarea_etiqueta te ON e.id = te.etiqueta_id
            WHERE te.tarea_id = ?
            ORDER BY e.nombre ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
}
