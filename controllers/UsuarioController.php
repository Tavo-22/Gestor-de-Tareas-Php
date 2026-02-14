<?php
//requerir el modelo de usuario
require_once 'models/Usuario.php';

class UsuarioController
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

        $usuario = new Usuario();
        $usuarios = $usuario->listarTodos();

        return [
            'success' => true,
            'data' => $usuarios
        ];
    }

    //obtener usuario por id
    public function obtenerPorId($params)
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

        $usuario = new Usuario();

        if ($usuario->buscarPorId($id)) {
            return [
                'success' => true,
                'data' => [
                    'id' => $usuario->getId(),
                    'nombre' => $usuario->getNombre(),
                    'email' => $usuario->getEmail(),
                    'fecha_registro' => $usuario->getFechaRegistro()
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Usuario no encontrado'
        ];
    }

    //actualizar perfil de usuario loggeado
    public function actualizarPerfil($datos)
    {
        session_start();

        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $nombre = $datos['nombre'] ?? '';
        $email = $datos['email'] ?? '';

        if (empty($nombre) || empty($email)) {
            return [
                'success' => false,
                'message' => 'Nombre y email son obligatorios'
            ];
        }

        $usuario = new Usuario();
        $usuario->buscarPorId($_SESSION['usuario_id']);
        $usuario->setNombre($nombre);
        $usuario->setEmail($email);

        return $usuario->actualizar();
    }

    //cambiar contraseña del usuario loggeado
    public function cambiarPassword($datos)
    {
        session_start();

        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $password_actual = $datos['password_actual'] ?? '';
        $password_nueva = $datos['password_nueva'] ?? '';
        $password_confirmar = $datos['password_confirmar'] ?? '';

        //validaciones
        if (empty($password_actual) || empty($password_nueva)) {
            return [
                'success' => false,
                'message' => 'Todos los campos son obligatorios'
            ];
        }

        if ($password_nueva !== $password_confirmar) {
            return [
                'success' => false,
                'message' => 'Las contraseñas no coinciden'
            ];
        }

        //verificar contraseña actual
        $usuario = new Usuario();
        $login = $usuario->login($_SESSION['usuario_email'], $password_actual);

        if (!$login['success']) {
            return [
                'success' => false,
                'message' => 'Contraseña actual incorrecta'
            ];
        }

        //actualizar contraseña
        $usuario->buscarPorId($_SESSION['usuario_id']);
        $usuario->cambiarPassword($password_nueva);
    }

    //eliminar cuenta del usuario loggeado (con confirmacion)
    public function eliminarCuenta()
    {
        session_start();

        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => "No autorizado"
            ];
        }

        $confirmacion = $datos['confirmacion'] ?? '';

        if ($confirmacion !== 'ELIMINAR') {
            return [
                'success' => false,
                'message' => 'Debes escribir "ELIMINAR" para confirmar'
            ];
        }

        $usuario = new Usuario();
        $usuario->buscarPorId($_SESSION['usuario_id']);
        $resultado = $usuario->eliminar();

        if ($resultado['success']) {
            //cerrar sesion
            session_destroy();
        }

        return $resultado;
    }

    //obtener perfil del usuario loggeado
    public function perfil()
    {
        session_start();

        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => "No autorizado"
            ];
        }

        $usuario = new Usuario();
        $usuario->buscarPorId($_SESSION['usuario_id']);

        return [
            'success' => true,
            'data' => [
                'id' => $usuario->getId(),
                'nombre' => $usuario->getNombre(),
                'email' => $usuario->getEmail(),
                'fecha_registro' => $usuario->getFechaRegistro()
            ]
        ];
    }
}
