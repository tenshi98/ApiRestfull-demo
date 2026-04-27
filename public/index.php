<?php
/**
 * Entry Point (Front Controller)
 * Intercepta y rutea todas las peticiones desde el cliente hacia el backend.
 */

// Manejo seguro de Errores (no exponer lógica a los usuarios)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ocultar errores puros en producción

set_exception_handler(function($exception) {
    // Loguear el error real de forma segura (invisible al cliente)
    $logFile = __DIR__ . '/../security/error.log';
    $errorMessage = "[" . date('Y-m-d H:i:s') . "] " . $exception->getMessage() . " en " . $exception->getFile() . ":" . $exception->getLine() . PHP_EOL;
    @file_put_contents($logFile, $errorMessage, FILE_APPEND);

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 500,
        'message' => 'Error Interno del Servidor',
        'data'    => 'Ha ocurrido un problema inesperado. Por favor, inténtelo más tarde.',
    ]);
    exit;
});


// Cargar la configuracion de la base de datos
$dbpush = ''; //nada de momento

// Archivos con funciones/configs globales → carga explícita obligatoria
require_once __DIR__ . '/../app/Functions.php';
require_once __DIR__ . '/../app/Configs.php';
require_once __DIR__ . '/../app/AuditLogger.php';
require_once __DIR__ . '/../app/RateLimiter.php';
require_once __DIR__ . '/../app/BaseController.php';

// Clases → autoloader
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../app/core/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Inicializar la Request global
$request = new Request();

// Cargar Rutas Globales
require_once __DIR__ . '/../routes/api.php';

// Iniciar Rutaje y Controlador inyectando DB
dispatch($request, $dbpush);
