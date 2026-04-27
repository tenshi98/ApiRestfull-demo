<?php
/**
 * Clase RateLimiter
 *
 * Implementa un mecanismo básico de protección contra ataques
 * de fuerza bruta basado en IP, utilizando almacenamiento en archivo.
 *
 * Funcionalidad:
 * - Limita la cantidad de intentos por IP en una ventana de tiempo
 * - Bloquea temporalmente IPs que exceden el límite
 * - Permite limpiar contadores tras autenticación exitosa
 *
 * Estrategia:
 * - Almacena intentos en un archivo JSON
 * - Usa bloqueo de archivo (flock) para evitar condiciones de carrera
 * - Limpia registros expirados en cada ejecución
 *
 * Estructura del archivo:
 * {
 *   "192.168.1.1": {
 *     "count": 3,
 *     "timestamp": 1710000000
 *   }
 * }
 *
 * @package App\Security
 */
class RateLimiter {

    /**
     * Verifica si una IP ha excedido el límite de intentos permitidos.
     *
     * Flujo:
     * 1. Abre archivo de almacenamiento
     * 2. Aplica bloqueo exclusivo (LOCK_EX)
     * 3. Lee y decodifica el contenido JSON
     * 4. Elimina registros expirados (ventana de tiempo)
     * 5. Incrementa contador de la IP actual
     * 6. Evalúa si supera el máximo permitido
     * 7. Guarda el estado actualizado
     *
     * Estrategia de seguridad:
     * - "Fail-open": si el archivo no puede abrirse, permite el acceso
     *   (prioriza disponibilidad sobre bloqueo)
     *
     * @param string $ip             Dirección IP del cliente
     * @param int    $maxAttempts    Número máximo de intentos permitidos
     * @param int    $windowSeconds  Ventana de tiempo en segundos
     *
     * @return bool True si la IP está bloqueada, false si puede continuar
     *
     * @example
     * if (RateLimiter::isBlocked($ip)) {
     *     Response::error('Demasiados intentos. Intente más tarde.', 429);
     * }
     */
    public static function isBlocked($ip, $maxAttempts = 5, $windowSeconds = 60) {

        // Se abre el archivo en modo lectura/escritura; se crea si no existe
        $fp = @fopen(Configs::Software["rateLimiterFile"], 'c+');

        // Fail-open: si no se puede acceder al archivo, no bloquear
        if (!$fp) {return false;}

        // Variable que indica si la IP debe ser bloqueada
        $blocked = false;

        // Se adquiere un bloqueo exclusivo para evitar condiciones de carrera
        if (flock($fp, LOCK_EX)) {

            // Leer contenido actual del archivo
            $content = stream_get_contents($fp);

            // Decodificar el contenido JSON a un arreglo asociativo
            $logs    = $content ? json_decode($content, true) : [];

            // Validar que el resultado sea un arreglo válido
            if (!is_array($logs)) {$logs = [];}

            // Obtener timestamp actual
            $now = time();

            // Limpieza de registros expirados fuera de la ventana de tiempo
            foreach ($logs as $logIp => $data) {
                if ($now - $data['timestamp'] > $windowSeconds) {
                    unset($logs[$logIp]);
                }
            }

            // Verificar si la IP ya tiene registros previos
            if (isset($logs[$ip])) {

                // Incrementar contador de intentos
                $logs[$ip]['count']++;

                // Evaluar si supera el máximo permitido
                if ($logs[$ip]['count'] > $maxAttempts) {
                    $blocked = true;
                }

            } else {

                // Registrar nueva IP con contador inicial y timestamp actual
                $logs[$ip] = [
                    'count' => 1,
                    'timestamp' => $now
                ];
            }

            // Persistir cambios en el archivo
            ftruncate($fp, 0);                                  // Truncar el contenido existente
            rewind($fp);                                        // Reposicionar el puntero al inicio
            fwrite($fp, json_encode($logs, JSON_PRETTY_PRINT)); // Escribir el nuevo estado en formato JSON
            fflush($fp);                                        // Forzar escritura en disco
            flock($fp, LOCK_UN);                                // Liberar el bloqueo del archivo
        }

        // Cerrar el archivo
        fclose($fp);

        // Retornar si la IP debe ser bloqueada
        return $blocked;
    }

    /**
     * Limpia el contador de intentos para una IP específica.
     *
     * Uso típico:
     * - Después de un login exitoso
     * - Reinicia el estado del rate limiter para el usuario
     *
     * @param string $ip Dirección IP del cliente
     *
     * @return void
     */
    public static function clear($ip) {

        // Se abre el archivo en modo lectura/escritura; se crea si no existe
        $fp = @fopen(Configs::Software["rateLimiterFile"], 'c+');

        // Si no se puede abrir el archivo, se termina la ejecución
        if (!$fp) {return;}

        // Se adquiere un bloqueo exclusivo para evitar accesos concurrentes
        if (flock($fp, LOCK_EX)) {

            // Leer contenido actual del archivo
            $content = stream_get_contents($fp);

            // Decodificar el contenido JSON a un arreglo asociativo
            $logs = $content ? json_decode($content, true) : [];

            // Validar que el contenido sea un arreglo y que la IP exista
            if (is_array($logs) && isset($logs[$ip])) {
                unset($logs[$ip]);                                  // Eliminar el registro de la IP
                ftruncate($fp, 0);                                  // Truncar el archivo para sobrescribirlo
                rewind($fp);                                        // Reposicionar el puntero al inicio
                fwrite($fp, json_encode($logs, JSON_PRETTY_PRINT)); // Escribir el nuevo estado en formato JSON
                fflush($fp);                                        // Forzar escritura en disco
            }

            // Liberar el bloqueo del archivo
            flock($fp, LOCK_UN);
        }

        // Cerrar el archivo
        fclose($fp);
    }
}
