<?php
//conxion a la base de datos
require_once 'config/database.php';

class Etiqueta
{
    private $pdo;

    private $id;
    private $nombre;
    private $color;

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
        $this->nombre = trim($nombre); //limpiar espacios
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor($color)
    {
        //validar formato hexadecimal
        if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            $this->color = $color;
        } else {
            $this->color = '#3498db'; // Color por defecto
        }
    }

    //verificar si existe etiqueta con el mismo nombre
    public function nombreExiste($nombre, $excluir_id = null)
    {
        $sql = "SELECT id FROM etiquetas WHERE nombre = ?";
        $params = [$nombre];

        if ($excluir_id) {
            $sql .= " AND id != ?";
            $params[] = $excluir_id;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    //guadar etiqueta
    public function guardar()
    {
        //validar nombre obligatorio
        if (empty($this->nombre)) {
            return [
                'success' => false,
                'message' => 'El nombre de la etiqueta es obligatorio'
            ];
        }

        //validar nombre unico
        if ($this->nombreExiste($this->nombre)) {
            return [
                'success' => false,
                'message' => 'Ya existe una etiqueta con el nombre: ' . $this->nombre

            ];
        }

        //asignar color por defecto si no se ha establecido
        if (empty($this->color)) {
            $this->color = '#3498db'; // Color por defecto
        }

        //guardar en la base de datos
        try {
            $sql = "INSERT INTO etiquetas (nombre, color) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);

            $resultado = $stmt->execute([
                $this->nombre,
                $this->color
            ]);

            if ($resultado) {
                $this->id = $this->pdo->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Etiqueta creada exitosamente',
                    'etiqueta_id' => $this->id
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al guardar la etiqueta: ' . $e->getMessage()
            ];
        }
    }

    //buscar por id
    public function buscarPorId($id)
    {
        $sql = "SELECT * FROM etiquetas WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $etiquetaData = $stmt->fetch();

        if ($etiquetaData) {
            $this->id = $etiquetaData['id'];
            $this->nombre = $etiquetaData['nombre'];
            $this->color = $etiquetaData['color'];
            return true;
        }
        return false;
    }

    //listar todas las etiquetas globales
    public function listarTodas()
    {
        $sql = "SELECT * FROM etiquetas ORDER BY nombre ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    //buscar por nombre
    public function buscarPorNombre($termino)
    {
        $sql = "SELECT * FROM etiquetas 
        WHERE nombre LIKE ? 
        ORDER BY nombre ASC 
        LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["%$termino%"]);
        return $stmt->fetchAll();
    }

    //actualizar etiqueta
    public function actualizar()
    {
        if (empty($this->id)) {
            return [
                'success' => false,
                'message' => 'No se ha especificado la etiqueta a actualizar'
            ];
        }

        if (empty($this->nombre)) {
            return [
                'success' => false,
                'message' => 'El nombre es obligatorio'
            ];
        }

        //validar nombre unico
        if ($this->nombreExiste($this->nombre, $this->id)) {
            return [
                'success' => false,
                'message' => 'Ya existe otra etiqueta con el nombre' . $this->nombre
            ];
        }

        //conexion base de datos para actualizar
        try {
            $sql = "UPDATE etiquetas 
            SET nombre = ?, color = ?
            WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);

            $resultado = $stmt->execute([
                $this->nombre,
                $this->color,
                $this->id
            ]);

            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Etiqueta actualizada correctamente'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar la etiqueta: ' . $e->getMessage()
            ];
        }
    }

    //eliminar etiqueta, borra etiqueta y todas sus relaciones con tareas
    public function eliminar(){
        if(empty($this->id)){
            return[
                'success' => false,
                'message' => 'No se ha especificado que etiqueta eliminar'
            ];
        }

        //verificar cuantas tareas tienen esta etiqueta
        $tareas_uso = $this->contarTareas();

        //conexion a base de datos para eliminar
        try{
            $sql = "DELETE FROM etiquetas WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $resultado = $stmt->execute([$this->id]);

            if($resultado){
                $this->id = null;
                $this->nombre = null;
                $this->color = null;

                return[
                    'success' => true,
                    'message' => "Etiqueta eliminada correctamente, se removió de $tareas_uso tarea(s)"

                ];
            }

        }catch(PDOException $e){
            return[
                'success' => false,
                'message' => 'Error al eliminar la etiqueta: ' . $e->getMessage()
            ];
        }
    }

    //contar cuantas tareas tienen esta etiqueta
    public function contarTareas(){
        $sql = "SELECT COUNT(*) 
        FROM tarea_etiqueta 
        WHERE etiqueta_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->id]);
        return $stmt->fetchColumn();
    }

    //obtener tareas que tienen esta etiqueta
    public function obtenerTareas(){
        $sql = "SELECT t.* FROM tareas t
        INNER JOIN tarea_etiqueta te ON t.id = te.tarea_id
        WHERE te.etiqueta_id = ?
        ORDER BY t.fecha_creacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }

    //etiquetas mas usadas
    public function etiquetasMasUsadas($limite = 10){
        $sql = "SELECT e.*, COUNT(te.tarea_id) as total_tareas
        FROM etiquetas e
        LEFT JOIN tarea_etiqueta te ON e.id = te.etiqueta_id
        GROUP BY e.id
        ORDER BY total_tareas DESC
        LIMIT ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }
}
