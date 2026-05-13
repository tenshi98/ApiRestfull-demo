<?php
/**
 * Clase UserAuth (Autenticación JWT)
 *
 * Gestiona:
 * - Generación de tokens JWT (HMAC SHA256)
 * - Validación de tokens (firma, expiración, integridad)
 * - Login de usuarios
 * - Control de sesión (Bearer Token)
 * - Logout mediante blacklist
 *
 * NOTA:
 * Implementación manual de JWT (sin librerías externas).
 * Adecuada para proyectos controlados; en producción se recomienda
 * usar librerías auditadas (ej: firebase/php-jwt).
 *
 * @package App\Auth
 */
class UserAuth {

    /****************************************************************************************/
    /*                                   Metodos Publicos                                   */
    /****************************************************************************************/
    /**
     * Genera un token JWT firmado utilizando el algoritmo HMAC SHA256.
     *
     * El token resultante sigue la estructura estándar:
     * header.payload.signature
     *
     * Componentes:
     * - Header:
     *   Define el tipo de token (JWT) y el algoritmo de firma (HS256).
     *
     * - Payload:
     *   Contiene los datos personalizados proporcionados, junto con:
     *   - iat (Issued At): timestamp de emisión del token.
     *   - exp (Expiration): timestamp de expiración (1 hora desde la emisión).
     *
     * - Signature:
     *   Se genera aplicando HMAC SHA256 sobre el header y payload codificados,
     *   utilizando una clave secreta definida en la configuración.
     *
     * Proceso:
     * 1. Codificar header y payload en JSON.
     * 2. Codificarlos en formato Base64 URL-safe.
     * 3. Generar la firma utilizando la clave secreta.
     * 4. Construir el token concatenando las tres partes con '.'.
     *
     * @param array<string, mixed> $payload_data Datos personalizados a incluir en el payload.
     *
     * @return string Token JWT generado en formato string.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function generateToken($payload_data) {

        // Construcción del header JWT con tipo y algoritmo
        $header  = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        // Construcción del payload combinando datos del usuario con metadatos del token
        $payload = json_encode(array_merge($payload_data, [
            'iat' => time(),                                   // Hora de Creación
            'exp' => time() + Configs::Software["JWTduration"] // Hora de Expiración
        ]));

        // Codificación Base64 URL-safe del header
        $base64UrlHeader = self::base64UrlEncode($header);

        // Codificación Base64 URL-safe del payload
        $base64UrlPayload = self::base64UrlEncode($payload);

        // Firma HMAC SHA256 del header y payload concatenados
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, Configs::Software["JWTsecretKey"], true);

        // Codificación Base64 URL-safe de la firma
        $base64UrlSignature = self::base64UrlEncode($signature);

        // Construcción final del token JWT
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Valida un token JWT verificando su integridad, vigencia y relación con un usuario válido.
     *
     * Validaciones realizadas:
     * - El token no debe estar en la lista negra.
     * - Debe tener exactamente 3 partes (header.payload.signature).
     * - La firma debe ser válida (HMAC SHA256).
     * - El payload debe ser decodificable y contener un identificador de usuario (uid).
     * - El token no debe estar expirado (exp).
     * - El usuario debe existir y estar activo en el sistema.
     *
     * Proceso:
     * 1. Verifica blacklist.
     * 2. Divide el token en sus componentes.
     * 3. Recalcula la firma esperada y la compara de forma segura.
     * 4. Decodifica el payload.
     * 5. Valida estructura y contenido del payload.
     * 6. Verifica expiración.
     * 7. Verifica existencia y estado del usuario.
     *
     * @param string $token Token JWT a validar.
     *
     * @return array<string, mixed>|false Retorna el payload decodificado si el token es válido,
     *                                   o false si falla alguna validación.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function validateToken($token) {

        // Verificar si el token está en la lista negra
        if (TokenBlacklist::isBlacklisted($token)) {return false; }

        // Separar el token en sus tres partes: header, payload y firma
        $parts = explode('.', $token);

        // Validar que el formato del token sea correcto
        if (count($parts) !== 3) {
            return false;
        }

        // Asignar cada parte a variables individuales
        [$header, $payload, $signatureProvided] = $parts;

        // Recalcular la firma esperada utilizando HMAC SHA256
        $signatureExpected = self::base64UrlEncode(hash_hmac('sha256', $header . "." . $payload, Configs::Software["JWTsecretKey"], true));

        // Comparación segura para evitar ataques de timing
        if (!hash_equals($signatureExpected, $signatureProvided)){return false; }

        // Decodificar el payload desde Base64 URL-safe
        $user_payload = json_decode(self::base64UrlDecode($payload), true);

        // Validar que el payload sea un arreglo válido y contenga el identificador de usuario
        if (!is_array($user_payload) || empty($user_payload['uid'])){return false;}

        // Validar expiración del token
        if (isset($user_payload['exp']) && $user_payload['exp'] < time()){return false;}

        // Validar existencia del usuario en el sistema
        $user = new user();

        // Verificar que el usuario exista y tenga estado activo
        if (!$user->find($user_payload['uid']) || $user->status !== 'A'){return false;}

        // Retornar el payload si todas las validaciones son exitosas
        return $user_payload;
    }

    /**
     * Obtiene el token Bearer desde los headers HTTP de la solicitud.
     *
     * Este método intenta recuperar el header Authorization desde distintas fuentes
     * para asegurar compatibilidad con diferentes entornos de ejecución:
     *
     * - $_SERVER['Authorization']
     * - $_SERVER['HTTP_AUTHORIZATION']
     * - apache_request_headers() (en entornos Apache)
     *
     * Formato esperado del header:
     * Authorization: Bearer <token>
     *
     * Proceso:
     * 1. Busca el header Authorization en distintas variables del entorno.
     * 2. Normaliza los headers en caso de usar apache_request_headers().
     * 3. Extrae el token usando una expresión regular.
     *
     * @return string|null Retorna el token JWT si existe, o null si no se encuentra.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function getBearerToken() {

        // Inicializar contenedor de headers
        $headers = null;

        // Intentar obtener el header desde $_SERVER['Authorization']
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);

        // Intentar obtener el header desde $_SERVER['HTTP_AUTHORIZATION']
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);

        // Intentar obtener headers desde Apache si la función está disponible
        } elseif (function_exists('apache_request_headers')) {

            // Obtener todos los headers de la petición
            $requestHeaders = apache_request_headers();

            // Normalizar claves de headers (primera letra en mayúscula)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));

            // Verificar si existe el header Authorization
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        // Extraer token Bearer utilizando expresión regular
        if (!empty($headers)&&preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            // Retornar el token extraído
            return $matches[1];
        }

        // Retornar null si no se encontró un token válido
        return null;
    }

    /**
     * Verifica la sesión del usuario utilizando autenticación basada en JWT.
     *
     * Este método realiza el flujo completo de validación de sesión:
     * - Extrae el token Bearer desde los headers HTTP.
     * - Valida el token JWT (integridad, expiración y estado del usuario).
     * - Retorna el payload si el token es válido.
     * - Finaliza la ejecución con un error HTTP 401 si falla alguna validación.
     *
     * Flujo de ejecución:
     * 1. Obtiene el token desde los headers mediante getBearerToken().
     * 2. Si no existe token, responde con error 401.
     * 3. Valida el token utilizando validateToken().
     * 4. Si la validación falla, responde con error 401.
     * 5. Si todo es correcto, retorna el payload del usuario autenticado.
     *
     * @return array<string, mixed> Datos del usuario autenticado extraídos del payload.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function checkSession() {

        // Obtiene el token desde los headers HTTP
        $token = self::getBearerToken();

        // Validar existencia del token
        if (!$token) {
            // Responder con error si no se proporciona token
            Response::error('Token Authorization no proveído', 401);
        }

        // Validar el token JWT
        $payload = self::validateToken($token);

        // Verificar si la validación fue exitosa
        if (!$payload) {
            // Responder con error si el token es inválido o expirado
            Response::error('Token inválido o expirado', 401);
        }

        // Retornar el payload del usuario autenticado
        return $payload;

    }

    /**
     * Procesa el login de usuario autenticando credenciales y generando un token JWT.
     *
     * Flujo de ejecución:
     * - Valida que los parámetros de entrada no estén vacíos.
     * - Busca el usuario en el sistema.
     * - Verifica la contraseña proporcionada.
     * - Verifica que el usuario esté activo.
     * - Genera un token JWT si la autenticación es exitosa.
     * - Retorna una respuesta estructurada con el resultado.
     *
     * Comportamiento:
     * - Si faltan parámetros, retorna un error 400.
     * - Si el usuario no existe o la contraseña es incorrecta, retorna respuesta no autorizada.
     * - Si el usuario está inactivo, retorna respuesta específica.
     * - Si el login es exitoso, retorna token y datos del usuario.
     * - Si ocurre un caso no contemplado, retorna un error genérico.
     *
     * @param string $username Identificador del usuario.
     * @param string $password Contraseña en texto plano.
     *
     * @return array<string, mixed> Respuesta estructurada con estado, mensaje y datos.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function login($username, $password) {

        // Validación básica de entrada: verifica que ambos parámetros estén presentes
        if (empty($username) || empty($password)) {
            return ['status' => 400, 'message' => 'Parámetros incompletos.'];
        }

        // Instancia de la clase de usuario
        $user = new user();
        // Validaciones
        if (!$user->find($username)) {        return self::unauthorized();}  // Verificar existencia del usuario
        if ($user->pass !== md5($password)){  return self::unauthorized();}  // Validar contraseña comparando con hash MD5 almacenado
        if ($user->status !== 'A') {          return self::userInactive();}  // Verificar estado del usuario (debe estar activo)

        // Si el usuario tiene un identificador válido, se considera login exitoso
        if(!empty($user->id)){
            return [
                'status' => 200,
                'message' => 'Login exitoso',
                'data' => [
                    'token'       => self::generateToken(['uid' => $user->id, 'uname' => $user->name]),
                    'userid'      => $user->id,
                    'idformatted' => self::formatText($user->id),
                    'name'        => self::formatText($user->name),
                    'lastn'       => self::formatText($user->lastn),
                    'lastnm'      => self::formatText($user->lastnm),

                ]
            ];
        }

        // Retorno por defecto en caso de error no contemplado
        return [
            'status' => 400,
            'message' => 'Error desconocido.'
        ];

    }

    /**
     * Invalida el token JWT actual agregándolo a la lista negra.
     *
     * Este método obtiene el token Bearer desde los headers HTTP y, si existe,
     * lo registra en la blacklist mediante TokenBlacklist::add(), evitando que
     * pueda ser reutilizado en futuras solicitudes.
     *
     * Comportamiento:
     * - Si existe un token, se agrega a la lista negra.
     * - Si no existe token, no realiza ninguna acción adicional.
     * - Siempre retorna true independientemente del resultado interno.
     *
     * Nota:
     * - La persistencia depende de la implementación de TokenBlacklist.
     * - No se valida el token antes de agregarlo.
     *
     * @return bool Retorna true al finalizar la operación.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function logout() {

        // Obtener el token desde los headers HTTP
        $token = self::getBearerToken();

        // Verificar si existe un token para invalidar
        if ($token) {
            // Agregar el token a la lista negra
            TokenBlacklist::add($token);
        }
        // Retornar confirmación de ejecución
        return true;
    }

    /****************************************************************************************/
    /*                                   Metodos Privados                                   */
    /****************************************************************************************/

    // Codifica en Base64 URL safe (RFC 7515)
    private static function base64UrlEncode(string $data): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    // Decodifica Base64 URL safe
    private static function base64UrlDecode(string $data): string {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    // Respuesta estándar: credenciales inválidas
    private static function unauthorized() {
        return [
            'status'  => 401,
            'message' => 'Usuario y contraseña incorrectos'
        ];
    }

    // Respuesta estándar: usuario inactivo
    private static function userInactive() {
        return [
            'status'  => 403,
            'message' => 'Usuario inactivo'
        ];
    }

    // Formatea texto (normaliza capitalización)
    private static function formatText($value) {
        return ucwords(mb_strtolower($value, 'UTF-8'));
    }
}
