<?php
/**
 * Router Implementation
 * Simple routing system based on HTTP method and URI.
 */

// Permite separar la lógica de rutas por método HTTP
$routes = [
    'GET'    => [],
    'POST'   => [],
    'PUT'    => [],
    'DELETE' => []
];

/**
 * Registra una ruta en el sistema de enrutamiento.
 *
 * Esta función permite asociar un método HTTP y un patrón de URL
 * a un controlador y una acción específica.
 */
function addRoute($method, $pattern, $controller, $action, $middlewares = []) {
    global $routes;

    // Normaliza el método HTTP
    $method = strtoupper($method);

    // Valida que el método exista en el router
    if (!isset($routes[$method])) {
        throw new InvalidArgumentException("Método HTTP no soportado: {$method}");
    }

    // Transforma rutas tipo /api/items/{id} en expresiones regulares capturables
    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $pattern);

    // Delimita la expresión regular para coincidencia exacta
    $pattern = "#^" . $pattern . "$#";

    // Registra la ruta
    $routes[$method][$pattern] = [
        'controller'  => $controller,
        'action'      => $action,
        'middlewares' => $middlewares
    ];
}

/* =========================================
   Registro de Endpoints de la API
   ========================================= */

// Autenticación
addRoute('POST', '/api/login',  'AuthController', 'login',  ['rate_limit']);
addRoute('POST', '/api/logout', 'AuthController', 'logout', ['auth']);

// CRUD Items
addRoute('GET',    '/api/items',        'ItemController', 'list',   ['auth']);
addRoute('POST',   '/api/items/search', 'ItemController', 'list',   ['auth']);
addRoute('GET',    '/api/items/{id}',   'ItemController', 'show',   ['auth']);
addRoute('POST',   '/api/items',        'ItemController', 'create', ['auth']);
addRoute('PUT',    '/api/items/{id}',   'ItemController', 'update', ['auth']);
addRoute('DELETE', '/api/items/{id}',   'ItemController', 'delete', ['auth']);

/**
 * Despacha la petición actual al controlador correspondiente.
 *
 * Esta función:
 * - Obtiene el método HTTP y la URI desde el objeto Request.
 * - Normaliza la URI eliminando la barra final.
 * - Busca coincidencias en las rutas registradas según el método HTTP.
 * - Evalúa cada patrón (expresión regular).
 * - Extrae parámetros dinámicos definidos en la ruta (ej: {id}).
 * - Instancia dinámicamente el controlador.
 * - Ejecuta la acción correspondiente, pasando parámetros si existen.
 *
 * Flujo general:
 * Request -> Match Route -> Extract Params -> Controller -> Action
 *
 * Ejemplo:
 * Ruta definida: /users/{id}
 * URI solicitada: /users/10
 * Resultado:
 *   $params = ['id' => 10]
 *
 * @param Request $request Instancia de la petición HTTP actual.
 *
 * @return void
 *
 * @throws RuntimeException Si el controlador o método no existe.
 */
function dispatch(Request $request, $dbpush = null) {
    global $routes;

    // Obtener datos de la request
    $method = $request->getMethod();
    $uri    = $request->getUri();

    // Normalizar URI (elimina trailing slash, excepto raíz)
    $uri = rtrim($uri, '/') ?: '/';

    // Validar existencia del método HTTP
    if (!isset($routes[$method])) {
        Response::error("Método HTTP no soportado ({$method})", 405);
        return;
    }

    // Recorrer rutas registradas para el método
    foreach ($routes[$method] as $pattern => $handler) {

        // Evaluar la expresión regular configurada
        if (preg_match($pattern, $uri, $matches)) {
            $params = [];

            // Extraer parámetros enviados por URL (solo claves string provenientes de (?P<name>...))
            $params = array_filter(
                $matches,
                fn($key) => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            // Traspaso de los handler
            $controllerName = $handler['controller'];
            $actionName     = $handler['action'];
            $middlewares    = $handler['middlewares'] ?? [];

            // Ejecucion de Middlewares antes de la carga del controlador
            if (in_array('rate_limit', $middlewares) && RateLimiter::isBlocked(($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), Configs::Software["rateLimiterMaxAttempts"], Configs::Software["rateLimiterWindowSeconds"])) {
                Response::error("Demasiados intentos. Intente más tarde.", 429);
            }

            // Se valida sesion en el controlador
            if (in_array('auth', $middlewares)) {
                UserAuth::checkSession();
            }

            // Construir ruta del controlador
            $controllerPath = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

            // Validar existencia del archivo
            if (!file_exists($controllerPath)) {
                Response::error("Controlador no encontrado ({$controllerName})", 404);
            }

            // Llamar al controlador
            require_once $controllerPath;

            // Validar existencia de la clase
            if (!class_exists($controllerName)) {
                Response::error("Clase no definida ({$controllerName})", 404);
            }

            // Instanciar controlador pasandole las dependencias
            $controllerInstance = new $controllerName($request, $dbpush);

            // Validar método (acción)
            if (!method_exists($controllerInstance, $actionName)) {
                Response::error("Método no encontrado: {$actionName} en {$controllerName}", 404);
            }

            /**
             * Ejecutar acción
             * - Si hay parámetros → se pasan en orden
             * - Si no → ejecución simple
             */
            if (!empty($params)) {
                call_user_func_array([$controllerInstance, $actionName], array_values($params));
            } else {
                $controllerInstance->$actionName();
            }

            // Ejecución exitosa
            return;

        }
    }

    // Si no hubo coincidencia (404)
    Response::error('Este Endpoint no existe (404 Not Found)', 404);
}
