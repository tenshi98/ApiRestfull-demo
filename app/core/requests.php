<?php
/**
 * Clase Request
 *
 * Encapsula la información de la petición HTTP actual.
 * Se encarga de:
 * - Detectar método HTTP y URI
 * - Parsear datos de entrada (GET, POST, JSON)
 * - Sanitizar datos para prevenir XSS
 * - Validar inputs
 *
 * Actúa como una capa de abstracción sobre las variables globales ($_GET, $_POST, $_SERVER).
 *
 * @package App\Core
 */
class Request {
    private $data;   // Datos de entrada ya parseados y sanitizados
    private $method; // Método HTTP de la petición (GET, POST, PUT, DELETE, etc.)
    private $uri;    // URI de la petición sin query string

    /**
     * Constructor.
     *
     * Inicializa las propiedades principales de la instancia a partir del entorno HTTP:
     * - Método HTTP de la solicitud.
     * - URI normalizada (solo la ruta, sin parámetros de query).
     * - Datos de entrada procesados y sanitizados.
     *
     * Comportamiento:
     * - Obtiene el método HTTP desde la variable global $_SERVER.
     * - Extrae la ruta de la URI utilizando parse_url, descartando query strings.
     * - Procesa el input de la solicitud mediante el método parseInputString().
     *
     * Nota:
     * - Se asume que las variables $_SERVER['REQUEST_METHOD'] y $_SERVER['REQUEST_URI'] están definidas.
     * - Se asume que parseInputString() retorna un arreglo de datos sanitizados.
     *
     * @return void No retorna ningún valor.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public function __construct() {

        // Obtener el método HTTP de la solicitud (GET, POST, etc.)
        $this->method = $_SERVER['REQUEST_METHOD'];

        // Obtener la URI normalizada (solo la ruta, sin parámetros)
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Procesar y sanitizar los datos de entrada de la solicitud
        $this->data = $this->parseInputString();
    }

    /**
     * Parsea y normaliza los datos de entrada de la petición HTTP.
     *
     * Esta implementación proporciona soporte real para métodos REST:
     * - GET
     * - POST
     * - PUT
     * - PATCH
     * - DELETE
     *
     * Basándose en:
     * - Método HTTP
     * - Content-Type de la petición
     *
     * Fuentes de datos soportadas:
     * - Query params (GET) → $_GET
     * - Form-data (POST) → $_POST
     * - JSON (application/json) → php://input
     * - URL Encoded (PUT, PATCH, DELETE) → php://input
     *
     * Flujo:
     * 1. Detecta método HTTP
     * 2. Obtiene Content-Type
     * 3. Lee el body crudo (php://input)
     * 4. Parsea según el tipo de contenido
     * 5. Sanitiza los datos antes de retornarlos
     *
     * Prioridad de parsing:
     * - application/json → se decodifica como array
     * - application/x-www-form-urlencoded → se parsea con parse_str
     * - POST clásico → $_POST
     *
     * Consideraciones:
     * - PUT, PATCH y DELETE no llenan $_POST automáticamente en PHP
     * - php://input es la única fuente confiable para estos métodos
     * - Se evita mezclar múltiples fuentes para prevenir inconsistencias
     *
     * Seguridad:
     * - Se aplica sanitización recursiva para mitigar XSS
     * - Se recomienda complementar con validación adicional
     *
     * @return array<string, mixed> Datos de entrada sanitizados
     */
    private function parseInputString(): array {

        // Obtener método HTTP actual
        $method = $this->method;

        // Extraer y normalizar el Content-Type (sin parámetros adicionales como charset)
        $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));

        // GET → parámetros de query string
        if ($method === 'GET') {return $this->sanitize($_GET);}

        // Obtener body crudo (para métodos distintos de GET)
        $rawInput = file_get_contents('php://input') ?: '';

        // Inicializar contenedor de datos
        $data = [];

        // Parsing según Content-Type
        // --- JSON (API moderna) ---
        if (str_contains($contentType, 'application/json')) {
            // Decodificar contenido JSON a arreglo asociativo
            $json = json_decode($rawInput, true);
            // Validar que el resultado sea un arreglo
            if (is_array($json)) {$data = $json;}
        }
        // --- Form-data clásico (solo POST) ---
        elseif ($method === 'POST') {
            // Utilizar directamente los datos enviados por formulario
            $data = $_POST;
        }
        // --- URL Encoded (PUT, PATCH, DELETE) ---
        elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            // Convertir el string codificado en arreglo
            parse_str($rawInput, $data);
        }

        // Retornar datos sanitizados
        return $this->sanitize($data);

    }

    /**
     * Sanitiza datos de entrada de forma recursiva, eliminando etiquetas HTML,
     * espacios innecesarios y convirtiendo caracteres especiales a entidades HTML.
     *
     * Este método admite tanto valores escalares como arreglos:
     * - Si el input es un arreglo, recorre cada elemento aplicando sanitización recursiva.
     * - Si el input es un valor escalar, aplica:
     *   - trim(): elimina espacios al inicio y fin
     *   - strip_tags(): elimina etiquetas HTML
     *   - htmlspecialchars(): convierte caracteres especiales a entidades HTML
     *
     * Nota:
     * - Se asume que los valores son compatibles con funciones de string.
     * - No se realiza validación de tipos más allá de verificar si es arreglo.
     *
     * @param mixed $input Valor o estructura de datos a sanitizar.
     *
     * @return mixed Retorna el valor sanitizado, manteniendo la estructura original.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    private function sanitize($input) {
        // Verificar si el input es un arreglo para aplicar sanitización recursiva
        if (is_array($input)) {
            // Recorrer cada elemento del arreglo
            foreach ($input as $key => $value) {
                // Aplicar sanitización recursiva a cada valor
                $input[$key] = $this->sanitize($value);
            }
            // Retornar arreglo sanitizado
            return $input;
        }
        // Sanitizar valor escalar:
        // - trim: elimina espacios en blanco
        // - strip_tags: elimina etiquetas HTML
        // - htmlspecialchars: convierte caracteres especiales
        return htmlspecialchars(strip_tags(trim($input)));
    }

    /**
     * Obtiene un valor específico de los datos de entrada procesados.
     *
     * Este método permite acceder a un campo individual dentro del arreglo
     * de datos previamente sanitizados. Si el campo no existe, retorna
     * un valor por defecto.
     *
     * @param string $key Nombre del campo a obtener.
     * @param mixed $default Valor por defecto en caso de que el campo no exista.
     *
     * @return mixed Valor del campo si existe, de lo contrario el valor por defecto.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public function input($key, $default = null) {
        // Verifica si el índice existe en el arreglo de datos
        // Si existe, retorna su valor; de lo contrario retorna el valor por defecto
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * Retorna todos los datos de entrada procesados.
     *
     * Este método devuelve el arreglo completo de datos que fueron
     * previamente obtenidos y sanitizados.
     *
     * @return array<string, mixed> Arreglo asociativo con todos los datos de entrada.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public function all() {
        // Retorna el arreglo completo de datos
        return $this->data;
    }

    /**
     * Valida los datos de entrada según un conjunto de reglas definidas.
     *
     * Reglas soportadas actualmente:
     * - required: el campo debe existir y no estar vacío.
     *
     * Ejemplo de uso:
     * $request->validate([
     *   'email' => 'required',
     *   'name'  => 'required'
     * ]);
     *
     * Comportamiento:
     * - Recorre cada campo definido en las reglas.
     * - Aplica validaciones según las reglas indicadas.
     * - Si existen errores, delega la respuesta a Response::error().
     * - Si la validación es exitosa, retorna todos los datos.
     *
     * @param array<string, string> $rules Arreglo asociativo donde la clave es el campo
     *                                    y el valor es una cadena de reglas separadas por '|'.
     *
     * @return array<string, mixed> Datos de entrada si la validación es exitosa.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public function validate($rules) {

        // Inicializa el contenedor de errores
        $errors = [];

        // Itera sobre cada campo y sus reglas asociadas
        foreach ($rules as $field => $ruleString) {

            // Divide las reglas por el separador '|'
            $fieldRules = explode('|', $ruleString);

            // Obtiene el valor del campo desde los datos de entrada
            $value = $this->input($field);

            // Itera sobre cada regla definida para el campo
            foreach ($fieldRules as $rule) {
                // Regla: requerido
                // Valida que el valor no sea null ni cadena vacía
                if ($rule === 'required' && ($value === null || $value === '')) {
                    // Registra el error asociado al campo
                    $errors[$field][] = "El campo '$field' es obligatorio.";
                }
                // Extensible: min, max, email, etc.
            }
        }

        // Si existen errores de validación
        if (!empty($errors)) {
            // Delega la respuesta de error (probablemente termina la ejecución)
            Response::error('Error de validación', 422, $errors);
        }

        // Retorna todos los datos si la validación es exitosa
        return $this->all();
    }

    /**
     * Obtiene el método HTTP de la petición.
     *
     * @return string Método HTTP (GET, POST, PUT, DELETE, etc.).
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public function getMethod() {
        // Retorna el método HTTP almacenado
        return $this->method;
    }

    /**
     * Obtiene la URI de la petición.
     *
     * @return string URI normalizada de la solicitud.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public function getUri() {
        // Retorna la URI almacenada
        return $this->uri;
    }
}
