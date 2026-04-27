<?php
/**
 * Clase Configs
 *
 * Centraliza los valores de configuración utilizados en el sistema.
 * Define constantes relacionadas con seguridad, almacenamiento de logs
 * y limitación de peticiones, junto con métodos de acceso controlado.
 *
 * Responsabilidades:
 * - Proveer una clave secreta para mecanismos de autenticación.
 * - Definir límites operacionales (ej: blacklist de tokens).
 * - Establecer rutas de almacenamiento para archivos de seguridad.
 *
 * Todas las propiedades son constantes, lo que garantiza inmutabilidad
 * durante la ejecución.
 */
class Configs {

    /************************************************************************************************************/
	// Datos de configuracion
    const Software = [
        /***********************************************************************
         * Login
         * JWTsecretKey: Clave secreta utilizada para procesos de autenticación (ej: JWT)
         * JWTduration: Duracion del JWT (3600 = 1 Hora)
         **********************************************************************/
        'JWTsecretKey' => 'MiSecr3toSuperSeguroParaElJWT2026',
        'JWTduration'  => 3600,

        /***********************************************************************
         * Seguridad
         * blacklistTokensMax: Número máximo de tokens permitidos en la lista negra
         * blacklistTokensFile: Ruta del archivo JSON donde se almacenan los tokens en lista negra
         * auditLoggerPath: Ruta base donde se almacenan los archivos de auditoría
         * rateLimiterFile: Ruta del archivo utilizado para el control de rate limiting
         * rateLimiterMaxAttempts: Número máximo de intentos permitidos
         * rateLimiterWindowSeconds: Ventana de tiempo en segundos
         **********************************************************************/
        'blacklistTokensMax'       => 50,
        'blacklistTokensFile'      => __DIR__ . '/../security/blacklisted_tokens.json',
        'auditLoggerPath'          => __DIR__ . '/../security/',
        'rateLimiterFile'          => __DIR__ . '/../security/rate_limit.json',
        'rateLimiterMaxAttempts'   => 5,
        'rateLimiterWindowSeconds' => 60,


    ];



}
