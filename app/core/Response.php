<?php
/**
 * Clase Response
 *
 * Encapsula la generación de respuestas HTTP en formato JSON
 * para la API, asegurando una estructura estandarizada.
 *
 * Responsabilidades:
 * - Definir códigos HTTP
 * - Enviar headers adecuados
 * - Formatear respuestas JSON consistentes
 * - Finalizar la ejecución del script
 *
 * Estructura estándar de respuesta:
 * {
 *   "status": 200,
 *   "message": "Mensaje descriptivo",
 *   "data": {...}
 * }
 *
 * @package App\Core
 */
class Response {
    /**
     * Envía una respuesta JSON al cliente y finaliza la ejecución.
     *
     * Este método:
     * - Define el código HTTP
     * - Establece headers necesarios (JSON + CORS básico)
     * - Codifica la respuesta en formato JSON
     * - Termina la ejecución del script con exit
     *
     * Headers incluidos:
     * - Content-Type: application/json
     * - Access-Control-Allow-Origin: * (CORS abierto)
     *
     * @param int         $status  Código HTTP (200, 201, 400, 404, etc.)
     * @param string      $message Mensaje descriptivo de la respuesta
     * @param mixed|null  $data    Datos adicionales (payload)
     *
     * @return void
     *
     * @example
     * Response::json(200, 'OK', ['id' => 1]);
     */
    public static function json($status, $message, $data = null): void {

        // Definir código de respuesta HTTP
        http_response_code($status);

        // Headers de respuesta
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Origin: *');

        // Estructura estándar de respuesta
        $response = [
            'status'  => $status,
            'message' => $message,
            'data'    => $data
        ];

        // Codificar y enviar respuesta
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        // Finalizar ejecución
        exit;
    }

    /**
     * Envía una respuesta exitosa.
     *
     * Método helper para simplificar respuestas HTTP exitosas.
     *
     * Códigos típicos:
     * - 200 OK
     * - 201 Created
     *
     * @param string     $message Mensaje de éxito
     * @param mixed|null $data    Datos de respuesta
     * @param int        $status  Código HTTP (default: 200)
     *
     * @return void
     *
     * @example
     * Response::success('Usuario creado', ['id' => 10], 201);
     */
    public static function success($message = 'Success', $data = null, $status = 200) {
        self::json($status, $message, $data);
    }

    /**
     * Envía una respuesta de error.
     *
     * Método helper para respuestas de fallo.
     *
     * Códigos típicos:
     * - 400 Bad Request
     * - 401 Unauthorized
     * - 404 Not Found
     * - 422 Unprocessable Entity
     * - 500 Internal Server Error
     *
     * @param string     $message Mensaje de error
     * @param int        $status  Código HTTP (default: 400)
     * @param mixed|null $data    Información adicional (ej: errores de validación)
     *
     * @return void
     *
     * @example
     * Response::error('No autorizado', 401);
     * Response::error('Error de validación', 422, ['email' => ['Requerido']]);
     */
    public static function error($message = 'Error', $status = 400, $data = null) {
        self::json($status, $message, $data);
    }
}
