<?php
/**
 * Controlador de Autenticación.
 *
 * Gestiona los flujos de autenticación de usuarios,
 * incluyendo login y logout.
 *
 * Este controlador actúa como intermediario entre:
 * - La capa HTTP (Request)
 * - La lógica de negocio (modelo UserAuth)
 * - La respuesta al cliente (Response)
 *
 * Responsabilidades:
 * - Validar datos de entrada
 * - Delegar autenticación al modelo
 * - Retornar respuestas estructuradas
 *
 * @package App\Controllers
 */


class AuthController extends BaseController {


    /**
     * Maneja el inicio de sesión de un usuario.
     *
     * Endpoint:
     * POST /api/login
     *
     * Flujo:
     * 1. Valida que los campos requeridos estén presentes.
     * 2. Obtiene los datos enviados en la request.
     * 3. Ejecuta la lógica de autenticación en el modelo UserAuth.
     * 4. Retorna una respuesta según el resultado.
     *
     * Validaciones:
     * - username: requerido
     * - password: requerido
     *
     * Respuestas:
     * - 200: Login exitoso
     * - 4xx/5xx: Error en autenticación
     *
     * Ejemplo de request:
     * {
     *   "username": "admin",
     *   "password": "123456"
     * }
     *
     * @return void
     */
    public function login() {

        // Validación de entrada: asegura que username y password estén presentes
        $this->request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // Obtener todos los datos de entrada ya sanitizados
        $data = $this->request->all();

        /**
         * Validar credenciales
         *
         * Se espera una estructura estándar:
         * [
         *   'status'  => int,    //código de estado
         *   'message' => string, //mensaje descriptivo
         *   'data'    => mixed   //información adicional (ej: token)
         * ]
         */
        $result = UserAuth::login($data['username'], $data['password']);

        // Evaluar resultado de la autenticación
        if ($result['status'] === 200) {
            // Limpia registros de intentos fallidos asociados a la IP (RateLimiter)
            RateLimiter::clear($_SERVER['REMOTE_ADDR']);
            // Retornar respuesta exitosa al cliente
            Response::success($result['message'], $result['data']);
        } else {
            // Retornar respuesta de error según el resultado obtenido
            Response::error($result['message'], $result['status']);
        }
    }

    /**
     * Maneja el cierre de sesión del usuario autenticado.
     *
     * Endpoint:
     * POST /api/logout
     *
     * Flujo:
     * 1. Verifica que exista una sesión activa.
     * 2. Ejecuta el proceso de logout.
     * 3. Retorna confirmación al cliente.
     *
     * Seguridad:
     * - Requiere autenticación previa.
     * - Si no hay sesión válida, el método checkSession()
     *   debe manejar la excepción o respuesta de error.
     *
     * Respuestas:
     * - 200: Logout exitoso
     * - 401: No autenticado
     *
     * @return void
     */
    public function logout() {
        // Ejecutar lógica de cierre de sesión (invalidación de token)
        UserAuth::logout();

        // Retornar respuesta exitosa al cliente
        Response::success('Sesión cerrada correctamente');

    }
}
