<?php
/**
 * Clase TokenBlacklist
 *
 * Gestiona una lista negra de tokens utilizando un archivo JSON como almacenamiento persistente.
 * Implementa control de concurrencia mediante bloqueo de archivos (flock) para evitar condiciones
 * de carrera durante operaciones de lectura y escritura.
 *
 * Responsabilidades:
 * - Leer tokens almacenados.
 * - Agregar nuevos tokens a la lista negra.
 * - Sobrescribir completamente la lista.
 * - Eliminar tokens específicos.
 * - Verificar si un token está en la lista negra.
 * - Mantener automáticamente el tamaño máximo permitido de la lista.
 */
class TokenBlacklist {

    /**
     * Leer todos los tokens guardados (Lectura protegida por bloqueo compartido).
     *
     * Comportamiento:
     * - Si el archivo no existe, retorna un arreglo vacío.
     * - Utiliza LOCK_SH para permitir múltiples lecturas concurrentes.
     * - Decodifica el contenido JSON a un arreglo.
     *
     * @return array Lista de tokens almacenados.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function readAll() {
        // Verificar si el archivo existe
        if (!file_exists(Configs::Software["blacklistTokensFile"])) {
            return [];
        }

        // Abrir archivo en modo lectura
        $fp = @fopen(Configs::Software["blacklistTokensFile"], 'r');

        // Si no se puede abrir, retornar arreglo vacío
        if (!$fp) {return [];}

        flock($fp, LOCK_SH);                 // Adquirir bloqueo compartido para lectura
        $content = stream_get_contents($fp); // Leer contenido del archivo
        flock($fp, LOCK_UN);                 // Liberar bloqueo
        fclose($fp);                         // Cerrar archivo

        // Decodificar JSON a arreglo
        $data = json_decode($content, true);

        // Validar estructura y retornar
        return is_array($data) ? $data : [];
    }

    /**
     * Guardar (Agregar) un token de manera atómica.
     *
     * Comportamiento:
     * - Abre el archivo en modo lectura/escritura.
     * - Aplica bloqueo exclusivo (LOCK_EX).
     * - Evita duplicados.
     * - Aplica mantenimiento automático si se excede el límite.
     * - Persiste los cambios en formato JSON.
     *
     * @param string $token Token a agregar a la lista negra.
     *
     * @return void No retorna ningún valor.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function add($token) {

        // Abrir archivo en modo lectura/escritura
        $fp = @fopen(Configs::Software["blacklistTokensFile"], 'c+');

        // Si no se puede abrir, terminar ejecución
        if (!$fp) {return;}

        // Adquirir bloqueo exclusivo
        if (flock($fp, LOCK_EX)) {

            // Leer contenido actual
            $content = stream_get_contents($fp);

            // Inicializar arreglo de tokens
            $tokens = [];

            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $tokens = $decoded;
                }
            }

            // Verificar si el token ya existe
            if (!in_array($token, $tokens)) {

                // Agregar token
                $tokens[] = $token;

                // Aplicar automantenimiento (límite máximo)
                $tokens = self::autoMaintenance($tokens);

                ftruncate($fp, 0);                                                  // Truncar archivo para sobrescribir
                rewind($fp);                                                        // Reposicionar puntero
                fwrite($fp, json_encode(array_values($tokens), JSON_PRETTY_PRINT)); // Escribir contenido actualizado
                fflush($fp);                                                        // Forzar escritura en disco
            }

            // Liberar bloqueo
            flock($fp, LOCK_UN);
        }
        // Cerrar archivo
        fclose($fp);
    }

    /**
     * Editar/Sobrescribir la lista completa de tokens de manera atómica.
     *
     * Comportamiento:
     * - Abre el archivo en modo escritura.
     * - Aplica bloqueo exclusivo.
     * - Sobrescribe completamente el contenido.
     *
     * @param array $tokensArray Lista completa de tokens a guardar.
     *
     * @return void No retorna ningún valor.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function save($tokensArray) {

        // Abrir archivo en modo escritura
        $fp = @fopen(Configs::Software["blacklistTokensFile"], 'c');

        // Si no se puede abrir, terminar ejecución
        if (!$fp) {return;}

        // Adquirir bloqueo exclusivo
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);                                                       // Limpiar contenido del archivo
            rewind($fp);                                                             // Reposicionar puntero
            fwrite($fp, json_encode(array_values($tokensArray), JSON_PRETTY_PRINT)); // Escribir nueva lista de tokens
            fflush($fp);                                                             // Forzar escritura en disco
            flock($fp, LOCK_UN);                                                     // Liberar bloqueo
        }

        // Cerrar archivo
        fclose($fp);
    }

    /**
     * Eliminar (Editar) un token específico de manera atómica.
     *
     * Comportamiento:
     * - Abre el archivo en modo lectura/escritura.
     * - Aplica bloqueo exclusivo.
     * - Busca el token y lo elimina si existe.
     * - Persiste los cambios.
     *
     * @param string $token Token a eliminar.
     *
     * @return void No retorna ningún valor.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function remove($token) {

        // Abrir archivo en modo lectura/escritura
        $fp = @fopen(Configs::Software["blacklistTokensFile"], 'c+');

        // Si no se puede abrir, terminar ejecución
        if (!$fp) {return;}

        // Adquirir bloqueo exclusivo
        if (flock($fp, LOCK_EX)) {

            // Leer contenido actual
            $content = stream_get_contents($fp);

            // Inicializar arreglo de tokens
            $tokens = [];

            // Decodificar contenido si existe
            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $tokens = $decoded;
                }
            }

            // Buscar índice del token
            $index = array_search($token, $tokens);

            // Buscar índice del token
            if ($index !== false) {
                unset($tokens[$index]);
                ftruncate($fp, 0);                                                  // Truncar archivo para sobrescribir
                rewind($fp);                                                        // Reposicionar puntero
                fwrite($fp, json_encode(array_values($tokens), JSON_PRETTY_PRINT)); // Escribir contenido actualizado
                fflush($fp);                                                        // Forzar escritura en disco
            }

            // Liberar bloqueo
            flock($fp, LOCK_UN);
        }
        // Cerrar archivo
        fclose($fp);
    }

    /**
     * Verificar si un token está en la lista negra.
     *
     * Comportamiento:
     * - Obtiene todos los tokens almacenados.
     * - Verifica si el token existe en la lista.
     *
     * @param string $token Token a verificar.
     *
     * @return bool true si el token está en la lista negra, false en caso contrario.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function isBlacklisted($token) {
        // Obtener lista de tokens
        $tokens = self::readAll();
        // Verificar existencia del token
        return in_array($token, $tokens);
    }

    /**
     * Automantenimiento para purgar tokens cuando se excede el límite configurado.
     *
     * Comportamiento:
     * - Obtiene el límite máximo desde la configuración.
     * - Si la cantidad de tokens supera el límite, conserva solo los más recientes.
     *
     * @param array $tokens Lista actual de tokens.
     *
     * @return array Lista ajustada según el límite permitido.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    private static function autoMaintenance($tokens) {
        // Obtener límite máximo permitido
        $max = Configs::Software["blacklistTokensMax"];
        // Verificar si se excede el límite
        if (count($tokens) > $max) {
            // Mantener únicamente los últimos elementos (más recientes)
            $tokens = array_slice($tokens, -$max);
        }
        // Retornar lista ajustada
        return $tokens;
    }
}
