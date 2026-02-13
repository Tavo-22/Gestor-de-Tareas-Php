<?php
//login, registro, logout

//requerir el modelo de usuario
require_once 'models/Usuario.php';

class AuthController
{

    /**
     * MÉTODO: registrar()
     * PROPÓSITO: Procesa el formulario de registro
     * RECIBE: POST con nombre, email, password
     * DEVUELVE: JSON (para AJAX) o redirección (para HTML)
     */
    public function registrar($datos)
    {
        //validar datos
        if (empty($datos['nombre']) || empty($datos['email']) || empty($datos['password'])) {
            return [
                'success' => false,
                'message' => 'Todos los campos son obligatorios'
            ];
        }
        //validar formato de email
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Formato de email no válido'
            ];
        }
        //validar contraseña (mínimo 6 caracteres)
        if (strlen($datos['password']) < 6) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ];
        }

        //crear objeto usuario y asignar datos
        $usuario = new Usuario();
        $usuario->setNombre($datos['nombre']);
        $usuario->setEmail($datos['email']);
        $usuario->setPassword($datos['password']); //el modelo se encargará de hashear la contraseña

        //guardar usuario en la base de datos
        $resultado = $usuario->guardar();

        //si se registro exitosamente, iniciar sesión automáticamente
        if ($resultado['success']) {
            session_start();
            $_SESSION['usuario_id'] = $resultado['usuario_id'];
            $_SESSION['usuario_nombre'] = $datos['nombre'];
            $_SESSION['usuario_email'] = $datos['email'];
        }

        //devolver resultado
        return $resultado;
    }

    /**
     * MÉTODO: login()
     * PROPÓSITO: Procesa el inicio de sesión
     */
    public function login($datos){
        //validar datos obligatorios
        if(empty($datos['email']) || empty($datos['password'])){
            return [
                'success' => false,
                'message' => 'Email y contraseña son obligatorios'
            ];
        }

        //intentar login
        $usuario = new Usuario();
        $resultado = $usuario->login($datos['email'], $datos['password']);

        //si el login fue exitoso, crear sesión
        if($resultado['success']){
            session_start();
            $_SESSION['usuario_id'] = $resultado['usuario']['id'];
            $_SESSION['usuario_nombre'] = $resultado['usuario']['nombre'];
            $_SESSION['usuario_email'] = $resultado['usuario']['email'];
        }

        return $resultado;
    }

    /**
     * MÉTODO: logout()
     * PROPÓSITO: Cierra la sesión del usuario
     */
    public function logout(){
        session_start();
        session_destroy();
        return [
            'success' => true,
            'message' => 'Has cerrado sesión exitosamente'
        ];
    }

     /**
     * MÉTODO: verificarSesion()
     * PROPÓSITO: Verifica si hay un usuario logueado
     */
    public function verificarSesion(){
        session_start();
        if(isset($_SESSION['usuario_id'])){
            return [
                'logged_in' => true,
                'usuario' => [
                    'id' => $_SESSION['usuario_id'],
                    'nombre' => $_SESSION['usuario_nombre'],
                    'email' => $_SESSION['usuario_email']
                ]
            ];
        } else {
            return [
                'logged_in' => false
            ];
        }
    }



}
