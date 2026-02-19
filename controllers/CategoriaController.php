<?php
//requerir el modelo de categoria
require_once 'models/Categoria.php';

class CategoriaController
{
    //metodo listar
    public function listar()
    {
        session_start();

        //verficar si hay sesion activa
        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $categoria = new Categoria();
        $categorias = $categoria->listarPorUsuario($_SESSION['usuario_id']);

        return [
            'success' => true,
            'data' => $categorias
        ];
    }

    //metodo crear
    public function crear($datos)
    {
        session_start();

        //verficar si hay sesion activa
        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        //validar datos obligatorios
        if (empty($datos['nombre'])) {
            return [
                'success' => false,
                'message' => 'El nombre es obligatorio'
            ];
        }

        $categoria = new Categoria();
        $categoria->setNombre($datos['nombre']);
        $categoria->setDescripcion($datos['descripcion'] ?? '');
        $categoria->setUsuarioId($_SESSION['usuario_id']);

        $resultado = $categoria->guardar();
    }

    //obtener categoria por id
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

        $categoria = new Categoria();
        if ($categoria->buscarPorId($id)) {
            //verificar que la categoria pertenezca al usuario
            if ($categoria->getUsuarioId() != $_SESSION['usuario_id']) {
                return [
                    'success' => false,
                    'message' => 'No autorizado'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $categoria->getId(),
                    'nombre' => $categoria->getNombre(),
                    'descripcion' => $categoria->getDescripcion(),
                    'fecha_creacion' => $categoria->getFechaCreacion()
                ]
            ];
        }
        return [
            'success' => false,
            'message' => 'Categoría no encontrada'
        ];
    }

    //actualizar categoria
    public function actualizar($datos){
        session_start();

        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $id = $datos['id'] ?? null;
        if(!$id){
            return[
                'success' => false,
                'message' => 'ID no proporcionado'
            ];
        }

        //cargar categoria
        $categoria = new Categoria();
        if(!$categoria->buscarPorId($id)){
            return [
                'success' => false,
                'message' => 'Categoría no encontrada'
            ];
        }

        //verificar propiedad
        if($categoria->getUsuarioId() != $_SESSION['usuario_id']){
            return[
                'success' => false,
                'message' => 'No tienes permiso para editar esta categoría'
            ];
        }

        //asignar nuevos valores
        $categoria->setNombre($datos['nombre'] ?? $categoria->getNombre());
        $categoria->setDescripcion($datos['descripcion'] ?? $categoria->getDescripcion());
        
        return $categoria->actualizar();
    }

    //eliminar categoria solo si no tiene tareas asociadas
    public function eliminar($params){
        session_start();

        if(!isset($_SESSION['usuario_id'])){
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $id = $params['id'] ?? null;
        if(!$id){
            return [
                'success' => false,
                'message' => 'ID no proporcionado'
            ];
        }

        //cargar categoria
        $categoria = new Categoria();
        if(!$categoria->buscarPorId($id)){
            return [
                'success' => false,
                'message' => 'Categoría no encontrada'
            ];
        }

        //verificar propiedad
        if($categoria->getUsuarioId() != $_SESSION['usuario_id']){
            return [
                'success' => false,
                'message' => 'No tienes permiso para eliminar esta categoría'
            ];
        }

        return $categoria->eliminar();
    }

    //metodo estadisticas obtener resumen de categorias del usuario
    public function estadisticas(){
        session_start();

        if(!isset($_SESSION['usuario_id'])){
            return[
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $categoria = new Categoria();

        $categorias = $categoria->listarPorUsu($_SESSION['usuario_id']);

        $total_categorias = count($categorias);
        $categorias_con_tareas = 0;
        $total_tareas_en_categorias = 0;

        foreach ($categorias as $cat) {
            if ($cat['total_tareas'] > 0) {
                $categorias_con_tareas++;
                $total_tareas_en_categorias += $cat['total_tareas'];
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'total_categorias' => $total_categorias,
                'categorias_con_tareas' => $categorias_con_tareas,
                'categorias_vacias' => $total_categorias - $categorias_con_tareas,
                'total_tareas_en_categorias' => $total_tareas_en_categorias
            ]
        ];
    }
}
