<?php
// requerir modelos tareas, categorias, etiquetas

require_once 'models/Tarea.php';
require_once 'models/Categoria.php';
require_once 'models/Etiqueta.php';

class TareaController
{
    //metodo listar con filtros
    public function listar($params)
    {
        session_start();

        //verificar si hay sesion activa
        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $tarea = new Tarea();

        //obtener filtros
        $estado = $params['estado'] ?? null;
        $prioridad = $params['prioridad'] ?? null;
        $categoria_id = $params['categoria_id'] ?? null;

        if ($estado) {
            $tareas = $tarea->listarPorEstado($_SESSION['usuario_id'], $estado);
        } else if ($prioridad) {
            $tareas = $tarea->listarPorPrioridad($_SESSION['usuario_id'], $prioridad);
        } else if ($categoria_id) {
            //verificar que la categoria pertenezca a un usuario
            $categoria = new Categoria();
            if ($categoria->buscarPorId($categoria_id) && $categoria->getUsuarioId() == $_SESSION['usuario_id']) {
                $tareas = $tarea->listarPorCategoria($_SESSION['usuario_id'], $categoria_id);
            } else {
                return [
                    'success' => false,
                    'message' => 'Categoría no válida'
                ];
            }
        } else {
            $tareas = $tarea->listarPorUsuario($_SESSION['usuario_id']);
        }
        return [
            'success' => true,
            'data' => $tareas
        ];
    }

    //metodo crear tarea 
    public function crear($datos)
    {
        session_start();

        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        //validar titulo obligatoiro
        if (empty($datos['titulo'])) {
            return [
                'success' => false,
                'message' => 'El título es obligatorio'
            ];
        }

        //so viene categoria_id, verificar que pertenezca al usuario
        if (!empty($datos['categoria_id'])) {
            $categoria = new Categoria();
            if (
                $categoria->buscarPorId($datos['categoria_id']) &&
                $categoria->getUsuarioId() == $_SESSION['usuario_id']
            ) {
                return [
                    'success' => false,
                    'message' => 'Categoría no válida'
                ];
            }
        }

        $tarea = new Tarea();
        $tarea->setTitulo($datos['titulo']);
        $tarea->setDescripcion($datos['descripcion'] ?? '');
        $tarea->setEstado($datos['estado'] ?? 'pendiente');
        $tarea->setPrioridad($datos['prioridad'] ?? 'media');
        $tarea->setFechaLimite($datos['fecha_limite'] ?? null);
        $tarea->setUsuarioId($_SESSION['usuario_id']);
        $tarea->setCategoriaId($datos['categoria_id'] ?? null);

        $resultado = $tarea->guardar();
        return $resultado;
    }

    //metodo obtener detalle de una tarea especifica
    public function obtener($params)
    {
        session_start();
        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return [
                'success' => false,
                'message' => 'ID no proporcionado'
            ];
        }

        $tarea = new Tarea();

        if ($tarea->buscarPorId($id)) {
            // verificar que la tarea pertenezca al usuario
            if ($tarea->getUsuarioId() != $_SESSION['usuario_id']) {
                return [
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta tarea'
                ];
            }

            //obtener etiquetas asociadas
            $etiquetas = $tarea->obtenerEtiquetas();

            return [
                'success' => true,
                'data' => [
                    'id' => $tarea->getId(),
                    'titulo' => $tarea->getTitulo(),
                    'descripcion' => $tarea->getDescripcion(),
                    'estado' => $tarea->getEstado(),
                    'prioridad' => $tarea->getPrioridad(),
                    'fecha_limite' => $tarea->getFechaLimite(),
                    'categoria_id' => $tarea->getCategoriaId(),
                    'etiquetas' => $etiquetas
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Tarea no encontrada'
        ];
    }

    //actualizar tarea existente
    public function actualizar($datos)
    {
        session_start();

        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizdo'
            ];
        }

        $id = $datos['id'] ?? null;
        if (!$id) {
            return [
                'success' => false,
                'message' => 'ID no proporcionado'
            ];
        }

        $tarea = new Tarea();

        //cargar tarea
        if (!$tarea->buscarPorId($id)) {
            return [
                'success' => false,
                'message' => 'Tarea no encontrada'
            ];
        }

        //verificar propiedad
        if ($tarea->getUsuarioId() != $_SESSION['usuario_id']) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para modificar esta tarea'
            ];
        }

        //si viene categoria_id, verificar que pertenezca a un usuario
        if (isset($datos['categoria_id']) && !empty($datos['usuario_id'])) {
            $categoria = new Categoria();

            if (
                $categoria->buscarPorId($datos['categoria_id']) &&
                $categoria->getUsuarioId() != $_SESSION['usuario_id']
            ) {
                return [
                    'success' => false,
                    'message' => 'Categoria no valida'
                ];
            }
        }

        //asignar nuevos valores
        if (!empty($datos['titulo'])) {
            $tarea->setTitulo($datos['titulo']);
        }
        if (isset($datos['descripcion'])) {
            $tarea->setDescripcion($datos['descripcion']);
        }
        if (!empty($datos['estado'])) {
            $tarea->setEstado($datos['estado']);
        }
        if (!empty($datos['prioridad'])) {
            $tarea->setPrioridad($datos['prioridad']);
        }
        if (isset($datos['fecha_limite'])) {
            $tarea->setFechaLimite($datos['fecha_limite'] ?: null);
        }
        if (isset($datos['categoria_id'])) {
            $tarea->setCategoriaId($datos['categoria_id'] ?: null);
        }

        return $tarea->actualizar();
    }

    //metodo cambiar estado accion rapida
    public function cambiarEstado($datos)
    {
        session_start();
        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $id = $datos['id'] ?? null;
        $estado = $datos['estado'] ?? null;

        if (!$id || !$estado) {
            return [
                'success' => false,
                'message' => 'ID y estado son obligatorios'
            ];
        }

        $tarea = new Tarea();

        //cargar y verificar propiedad
        if (!$tarea->buscarPorId($id)) {
            return [
                'success' => false,
                'message' => 'Tarea no encontrada'
            ];
        }

        if ($tarea->getUsuarioId() != $_SESSION['usuario_id']) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        return $tarea->cambiarEstado($estado);
    }

    //eliminar tarea
    public function eliminar($datos)
    {
        session_start();

        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $id = $datos['id'] ?? null;
        if (!$id) {
            return [
                'success' => false,
                'message' => 'ID no proporcionado'
            ];
        }

        $tarea = new Tarea();

        //cargar y verficiar propiedad
        if (!$tarea->buscarPorId($id)) {
            return [
                'success' => false,
                'message' => 'Tarea no encontrada'
            ];
        }

        if ($tarea->getUsuarioId() != $_SESSION['usuario_id']) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        return $tarea->eliminar();
    }

    //agregar etiquetas
    public function agregarEtiqueta($datos)
    {

        session_start();

        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $tarea_id = $datos['tarea_id'] ?? null;
        $etiqueta_id = $datos['etiqueta_id'] ?? null;

        if (!$tarea_id || !$etiqueta_id) {
            return [
                'success' => false,
                'message' => 'Tarea ID y Etiqueta ID son obligatorios'
            ];
        }

        //verificar que la tarea pertenezca al usuario
        $tarea = new Tarea();
        if (!$tarea->buscarPorId($tarea_id) || $tarea->getUsuarioId() != $_SESSION['usuario_id']) {
            return [
                'success' => false,
                'message' => 'Tarea no valida'
            ];
        }

        //verificar que la etiqueta existea
        $etiqueta = new Etiqueta();
        if (!$etiqueta->buscarPorId($etiqueta_id)) {
            return [
                'success' => false,
                'message' => 'Etiqueta no valida'
            ];
        }

        return $tarea->agregarEtiqueta($etiqueta_id);
    }

    //quitar etiqueta
    public function quitarEtiqueta($datos)
    {
        session_start();

        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $tarea_id = $datos['tarea_id'] ?? null;
        $etiqueta_id = $datos['etiqueta_id'] ?? null;

        if (!$tarea_id || !$etiqueta_id) {
            return [
                'success' => false,
                'message' => 'Tarea ID y Etiqueta ID son obligatorios'
            ];
        }

        //verificar que la tarea pertenezca al usuario
        $tarea = new Tarea();
        if (!$tarea->buscarPorId($tarea_id) || $tarea->getUsuarioId() != $_SESSION['usuario_id']) {
            return [
                'success' => false,
                'message' => 'Tarea no valida'
            ];
        }

        return $tarea->quitarEtiqueta($etiqueta_id);
    }

    //metodo obtener estadisticas()

    public function estadisticas()
    {
        session_start();

        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $tarea = new Tarea();

        //obtener todas las tareas del usuario
        $tareas = $tarea->listarPorUsuario($_SESSION['usuario_id']);

        $estadisticas = [
            'total' => count($tareas),
            'pendientes' => 0,
            'en_progreso' => 0,
            'completadas' => 0,
            'por_prioridad' => [
                'alta' => 0,
                'media' => 0,
                'baja' => 0
            ],
            'vencidas' => 0,
            'sin_categoria' => 0
        ];

        $hoy = date('Y-m-d');

        foreach ($tareas as $t) {
            //contar por estado
            switch ($t['estado']) {
                case 'pendiente':
                    $estadisticas['pendientes']++;
                    break;
                case 'en_progreso':
                    $estadisticas['en_progreso']++;
                    break;
                case 'completada':
                    $estadisticas['completadas']++;
                    break;
            }

            //contar por prioridad
            if (isset($estadisticas['por_prioridad'][$t['prioridad']])) {
                $estadisticas['por_prioridad'][$t['prioridad']]++;
            }

            // Contar vencidas (fecha límite pasada y no completadas)
            if ($t['fecha_limite'] && $t['fecha_limite'] < $hoy && $t['estado'] != 'completada') {
                $estadisticas['vencidas']++;
            }

            //contar sin categoria
            if (!$t['categoria_id']) {
                $estadisticas['sin_categoria']++;
            }
        }

        return [
            'success' => true,
            'data' => $estadisticas
        ];
    }
}
