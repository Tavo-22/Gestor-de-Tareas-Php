<?php
require_once 'config/database.php';

class Usuario
{
    private $pdo;
    private $id;
    private $nombre;
    private $email;
    private $password;
    private $fecha_registro;

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

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        // Encriptamos al asignar, no al guardar
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function getFechaRegistro()
    {
        return $this->fecha_registro;
    }

    // verficar si email existe
    public function emailExiste($email)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }

    public function guardar()
{
    // 1. Validar datos obligatorios
    if (empty($this->nombre) || empty($this->email) || empty($this->password)) {
        return [
            'success' => false,
            'message' => 'Todos los campos son obligatorios'
        ];
    }
    
    // 2. Validar si el email ya existe
    if ($this->emailExiste($this->email)) {
        return [
            'success' => false,
            'message' => 'El email ' . $this->email . ' ya está registrado.'
        ];
    }
    
    // 3. Guardar en BD
    try {
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
        $resultado = $stmt->execute([
            $this->nombre,
            $this->email,
            $this->password
        ]);

        if ($resultado) {
            $this->id = $this->pdo->lastInsertId();
            return [
                'success' => true,
                'message' => 'Usuario registrado exitosamente.',
                'user_id' => $this->id
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al guardar el usuario: ' . $e->getMessage()
        ];
    }
}

    public function listarTodos() {
        $stmt = $this->pdo->query("
            SELECT id, nombre, email, fecha_registro 
            FROM usuarios 
            ORDER BY fecha_registro DESC
        ");
        return $stmt->fetchAll();
    }

     public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuarioData = $stmt->fetch();
        
        if($usuarioData) {
            $this->id = $usuarioData['id'];
            $this->nombre = $usuarioData['nombre'];
            $this->email = $usuarioData['email'];
            $this->fecha_registro = $usuarioData['fecha_registro'];
            return true;
        }
        return false;
    }

    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuarioData = $stmt->fetch();
        
        if($usuarioData && password_verify($password, $usuarioData['password'])) {
            $this->id = $usuarioData['id'];
            $this->nombre = $usuarioData['nombre'];
            $this->email = $usuarioData['email'];
            $this->fecha_registro = $usuarioData['fecha_registro'];
            
            return [
                'success' => true, 
                'message' => 'Login exitoso',
                'usuario' => [
                    'id' => $this->id,
                    'nombre' => $this->nombre,
                    'email' => $this->email
                ]
            ];
        }
        
        return [
            'success' => false, 
            'message' => 'Email o contraseña incorrectos'
        ];
    }

    public function actualizar() {
        // 1. Validar que tenemos un ID
        if(empty($this->id)) {
            return [
                'success' => false, 
                'message' => 'No se ha especificado qué usuario actualizar'
            ];
        }
        
        // 2. Validar datos obligatorios
        if(empty($this->nombre) || empty($this->email)) {
            return [
                'success' => false, 
                'message' => 'Nombre y email son obligatorios'
            ];
        }
        
        // 3. Verificar si el nuevo email ya existe (y no es el suyo)
        $stmt = $this->pdo->prepare("
            SELECT id FROM usuarios 
            WHERE email = ? AND id != ?
        ");
        $stmt->execute([$this->email, $this->id]);
        
        if($stmt->rowCount() > 0) {
            return [
                'success' => false, 
                'message' => 'El email ' . $this->email . ' ya está en uso por otro usuario'
            ];
        }
        
        // 4. Actualizar en BD
        try {
            $sql = "UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $resultado = $stmt->execute([$this->nombre, $this->email, $this->id]);
            
            if($resultado) {
                return [
                    'success' => true, 
                    'message' => 'Usuario actualizado correctamente'
                ];
            }
            
        } catch(PDOException $e) {
            return [
                'success' => false, 
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ];
        }
    }

    public function cambiarPassword($password) {
        if(empty($this->id)) {
            return ['success' => false, 'message' => 'Usuario no especificado'];
        }
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare("
            UPDATE usuarios SET password = ? WHERE id = ?
        ");
        $resultado = $stmt->execute([$passwordHash, $this->id]);
        
        if($resultado) {
            $this->password = $passwordHash;
            return ['success' => true, 'message' => 'Contraseña actualizada'];
        }
        
        return ['success' => false, 'message' => 'Error al actualizar contraseña'];
    }

    public function eliminar() {
        // 1. Validar que tenemos un ID
        if(empty($this->id)) {
            return [
                'success' => false, 
                'message' => 'No se ha especificado qué usuario eliminar'
            ];
        }
        
        // 2. Confirmar que existe
        if(!$this->buscarPorId($this->id)) {
            return [
                'success' => false, 
                'message' => 'El usuario no existe'
            ];
        }
        
        // 3. Eliminar de BD
        try {
            $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $resultado = $stmt->execute([$this->id]);
            
            if($resultado) {
                // Limpiar las propiedades del objeto
                $this->id = null;
                $this->nombre = null;
                $this->email = null;
                $this->password = null;
                $this->fecha_registro = null;
                
                return [
                    'success' => true, 
                    'message' => 'Usuario eliminado correctamente'
                ];
            }
            
        } catch(PDOException $e) {
            // Error por llaves foráneas (tiene tareas, etc)
            return [
                'success' => false, 
                'message' => 'No se puede eliminar: el usuario tiene registros relacionados'
            ];
        }
    }
}
