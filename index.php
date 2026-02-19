<?php
// index.php - Punto de entrada de la aplicación front controller

//Manejo de CORS (para ajax desde diferentes dominios)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

//incluir controladores
require_once 'controllers/AuthController.php';
require_once 'controllers/CategoriaController.php';
require_once 'controllers/TareaController.php';
require_once 'controllers/EtiquetaController.php';
require_once 'controllers/UsuarioController.php';

//obtener la occion solicitada
$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

//enrrutador - dirige las peticiones al controlador correcto
try {
    switch ($accion) {
        // AUTH
        case 'registrar':
            $aut = new AuthController();
            echo json_encode($aut->registrar($_POST));
            break;
        case 'login':
            $aut = new AuthController();
            echo json_encode($aut->login($_POST));
            break;
        case 'logout':
            $aut = new AuthController();
            echo json_encode($aut->logout());
            break;
        case 'verificar_sesion':
            $aut = new AuthController();
            echo json_encode($aut->verificarSesion());
            break;

        // USUARIOS
        case 'usuario_listar':
            $usu = new UsuarioController();
            echo json_encode($usu->listar());
            break;
        case 'usuario_obtener':
            $usu = new UsuarioController();
            echo json_encode($usu->obtenerPorId($_GET));
            break;
        case 'usuario_perfil':
            $usu = new UsuarioController();
            echo json_encode($usu->perfil());
            break;
        case 'usuario_actualizar':
            $usu = new UsuarioController();
            echo json_encode($usu->actualizarPerfil($_POST));
            break;
        case 'usuario_cambiar_password':
            $usu = new UsuarioController();
            echo json_encode($usu->cambiarPassword($_POST));
            break;
        case 'usuario_eliminar':
            $usu = new UsuarioController();
            echo json_encode($usu->eliminarCuenta());
            break;

        // CATEGORIAS
        case 'categoria_listar':
            $cat = new CategoriaController();
            echo json_encode($cat->listar());
            break;

        case 'categoria_crear':
            $cat = new CategoriaController();
            echo json_encode($cat->crear($_POST));
            break;

        case 'categoria_obtener':
            $cat = new CategoriaController();
            echo json_encode($cat->obtener($_GET));
            break;

        case 'categoria_actualizar':
            $cat = new CategoriaController();
            echo json_encode($cat->actualizar($_POST));
            break;

        case 'categoria_eliminar':
            $cat = new CategoriaController();
            echo json_encode($cat->eliminar($_POST));
            break;

        case 'categoria_estadisticas':
            $cat = new CategoriaController();
            echo json_encode($cat->estadisticas());
            break;

            //Tareas
        case 'tarea_listar':
            $tar = new TareaController();
            echo json_encode($tar->listar($_GET));
            break;
            
         case 'tarea_crear':
            $tar = new TareaController();
            echo json_encode($tar->crear($_POST));
            break;
        
         case 'tarea_obtener':
            $tar = new TareaController();
            echo json_encode($tar->obtener($_GET));
            break;
            
         case 'tarea_actualizar':
            $tar = new TareaController();
            echo json_encode($tar->actualizar($_POST));
            break;
            
         case 'tarea_cambiar_estado':
            $tar = new TareaController();
            echo json_encode($tar->cambiarEstado($_POST));
            break;

         case 'tarea_eliminar':
            $tar = new TareaController();
            echo json_encode($tar->eliminar($_POST));
            break;

         case 'tarea_agregar_etiqueta':
            $tar = new TareaController();
            echo json_encode($tar->agregarEtiqueta($_POST));
            break;
            
         case 'tarea_quitar_etiqueta':
            $tar = new TareaController();
            echo json_encode($tar->quitarEtiqueta($_POST));
            break;
            
         case 'tarea_estadisticas':
            $tar = new TareaController();
            echo json_encode($tar->estadisticas());
            break;   

    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
