<?php
/**
 * Clase AuditLogger
 *
 * Sistema de registro (logging) de auditoría para operaciones del sistema.
 *
 * Permite almacenar eventos relevantes de negocio en un archivo,
 * facilitando trazabilidad, debugging y cumplimiento (auditoría).
 *
 * Características:
 * - Escritura simple en archivo (append)
 * - Formato estructurado por línea
 * - Bajo acoplamiento (uso estático)
 * - Uso de bloqueo para evitar condiciones de carrera
 *
 * Formato de log:
 * [YYYY-MM-DD HH:MM:SS] [ACTION] -> Detalle del evento
 *
 * Ejemplo:
 * [2026-04-17 12:00:00] [UPDATE] -> El usuario 10 actualizó su perfil
 *
 * @package App\Utils
 */
class AuditLogger {

    /**
     * Registra un evento de auditoría en un archivo de log con rotación diaria.
     *
     * Este método construye un mensaje estructurado que incluye:
     * - Timestamp formateado
     * - Tipo de acción (en mayúsculas)
     * - Detalle del evento
     *
     * El archivo de salida se genera dinámicamente por fecha, permitiendo
     * una organización diaria de los logs (rotación por día).
     *
     * Formato del mensaje:
     * [YYYY-MM-DD HH:MM:SS] [ACTION] -> Detalle del evento
     *
     * Comportamiento:
     * - El log se escribe al final del archivo correspondiente al día actual.
     * - Si el archivo no existe, se crea automáticamente.
     * - Se utiliza bloqueo exclusivo para evitar problemas de concurrencia.
     * - Se suprimen errores mediante el operador @.
     *
     * @param string $action Tipo de acción realizada (ej: CREATE, UPDATE, DELETE).
     * @param string $details Descripción detallada del evento a registrar.
     *
     * @return void No retorna ningún valor.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public static function log($action, $details) {

        // Obtener timestamp formateado para el registro
        $timestamp = Functions::fechaActual("Y-m-d H:i:s");

        // Obtener fecha actual para la rotación del archivo
        $date = Functions::fechaActual();

        // Construir mensaje de log
        // Formato estándar: [2026-04-17 12:00:00] [UPDATE] -> El usuario modificó...
        $message = sprintf("[%s] [%s] -> %s%s", $timestamp, strtoupper($action), $details, PHP_EOL);

        // Definir ruta del archivo de log con rotación diaria
        $logFile = Configs::Software["auditLoggerPath"].'audit-'.$date.'.log';

        /**
         * Escribir en archivo de auditoría
         *
         * Flags:
         * - FILE_APPEND: agrega al final del archivo
         * - LOCK_EX: asegura exclusión mutua durante la escritura
         *
         * Nota:
         * file_put_contents es atómico para escrituras pequeñas,
         * pero LOCK_EX agrega una capa adicional de seguridad en concurrencia.
         *
         * El operador @ suprime errores (modo silencioso).
         */
        @file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);

    }
}
