<?php
/**
 * Controlador Base (Abstract Controller)
 *
 * Clase abstracta que actúa como base para todos los controladores de la aplicación.
 * Proporciona acceso a dependencias comunes mediante inyección en el constructor,
 * como el objeto Request (petición procesada) y una instancia de conexión a base de datos.
 *
 * Responsabilidades:
 * - Centralizar dependencias compartidas entre controladores.
 * - Facilitar el acceso a la petición HTTP procesada.
 * - Permitir la inyección opcional de una conexión a base de datos.
 */
abstract class BaseController {

    // Instancia de la petición HTTP procesada y sanitizada
    protected $request;

    // Instancia de conexión a base de datos o servicio relacionado
    protected $dbpush;

    /**
     * Constructor general.
     *
     * Inicializa las dependencias principales del controlador:
     * - Request: objeto que contiene los datos de la petición.
     * - dbpush: conexión o servicio de base de datos (opcional).
     *
     * @param Request $request Objeto de la petición ya procesada y sanitizada.
     * @param mixed $dbpush Instancia opcional de conexión a base de datos.
     *
     * @return void No retorna ningún valor.
     *
     * @throws Ninguna excepción es lanzada explícitamente.
     */
    public function __construct(Request $request, $dbpush = null) {

        // Asignar objeto Request a la propiedad protegida
        $this->request = $request;

        // Asignar instancia de base de datos (si fue proporcionada)
        $this->dbpush  = $dbpush;
    }
}
