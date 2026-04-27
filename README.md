# GoTruck Operaciones V2 (API REST)

Una API REST backend modular y escalable construida en PHP puro sin frameworks externos ni dependencias, implementando autenticación JWT manual y una arquitectura limpia de Rutas/Controladores (MVC simplificado).

## Tabla de Contenidos
1. [Tecnologías utilizadas](#tecnologías-utilizadas)
2. [Características](#características)
3. [Requisitos](#requisitos)
4. [Configuración](#configuración)
5. [Cómo ejecutar el proyecto](#cómo-ejecutar-el-proyecto)
6. [Estructura de carpetas completa del proyecto](#estructura-de-carpetas-completa-del-proyecto)
7. [Explicaciones breves de cada módulo](#explicaciones-breves-de-cada-módulo)
8. [Ejemplos de uso](#ejemplos-de-uso)
9. [Solución de Problemas](#solución-de-problemas)
10. [Notas Adicionales](#notas-adicionales)

## Tecnologías utilizadas
* **PHP Base (Nativo):** Lógica del backend. Sin frameworks (Laravel, Symfony, etc).
* **JWT (JSON Web Tokens):** Para la autenticación manual de usuarios sin estado, usando el algoritmo `HS256`.
* **Manejo de Rutas Base:** Router HTTP hecho a medida con expresiones regulares.

## Características
* **Autenticación sin estado:** Uso de JWT validando firmas y expiración de sesión, más sistema transaccional para Blacklist de tokens salientes.
* **Arquitectura Modular Avanzada (Inyección de Dependencias):** Se integró un `BaseController` a modo de clase abstracta madre de la cual los demás Controladores heredan (`extends BaseController`). Otorga acceso inmediato y automático a los objetos `$this->request` y la conexión `$this->dbpush`, aplicando el principio arquitectónico de no-repetición (DRY).
* **Middlewares Activos en el Router:** La función de enrutamiento soporta arreglos de "Middlewares" (`['auth', 'rate_limit']`). Por ejemplo, al proteger un endpoint con Token, la función global `dispatch()` frena e invalida cualquier intento devolviendo un HTTP 401 crudo, sin llegar a instanciar la memoria de los Controladores ni la Base de Datos, ahorrando masivamente recursos del procesador y memoria RAM (Defensa en Profundidad).
* **Autocarga PSR-4:** Uso de `spl_autoload_register` para simplificar la importación de clases.
* **Rutas Dinámicas:** Soporte para parámetros dinámicos en la URL (`/api/items/{id}`) y diferentes verbos HTTP (GET, POST, PUT, DELETE).
* **Buscador/Filtro Dinámico:** Endpoint para búsqueda multi-criterio.
* **Respuestas Estandarizadas Seguras:** Retorno unificado JSON. Las excepciones internas y PHP crudos no se exponen al cliente, sino que se enrutan de forma silenciosa e interna hacia `security/error.log`.
* **Protección de Navegación HTTP Directa:** Carpetas núcleo aseguradas a nivel de servidor usando directivas de denegación por `.htaccess`.
* **Auditoría Transaccional (Cajas Negras):** Un Logger silencioso incrustado que opera de manera desatendida interceptando las lógicas de negocio. Graba de forma perenne en plano (en `security/audit.log`) y con timestamp cronológico los detalles sensibles de qué operador (con número UID) creó, editó o eliminó qué registros.
* **Rate Limiting Defensivo:** Prevención activa contra Ataques por Fuerza Bruta sobre endpoints sensibles como el Login. Mide heurísticamente cuántas veces una IP específica falla un intento valiéndose de un temporizador con *File Locking*. Si sobrepasa el umbral (Ej: 5 intentos al minuto), la API lo expulsa temporalmente devolviendo un contundente error `429 Too Many Requests`.

## Requisitos
* PHP 7.4 o superior (recomendado PHP 8.x).
* Servidor Web (Apache, Nginx) o utilizar el servidor de desarrollo de PHP.
* Cliente REST para probar endpoints (Postman, Insomnia, cURL).

## Configuración
Para entornos como Apache, en producción deberás asegurar que las directivas `AllowOverride All` estén habilitadas para que el archivo `public/index.php` reciba todo el tráfico. Y asegurarte de interceptar adecuadamente la cabecera `Authorization` de JWT.

## Estructura de carpetas completa del proyecto
```text
gotruck_operaciones_v2/
├── app/
│   ├── .htaccess                 # Bloqueo directo Apache
│   ├── controllers/
│   │   ├── AuthController.php    # (Extends BaseController) Login/logout API
│   │   ├── BaseController.php    # Controlador Abstracto (Mecanismo DRY)
│   │   └── ItemController.php    # (Extends BaseController) CRUD items
│   ├── AuditLogger.php           # Caja Negra de auditoría (logs)
│   ├── RateLimiter.php           # Bloqueo heurístico por IP temporal
│   ├── requests.php              # Clase que sanitiza y maneja parámetros (GET, POST)
│   ├── responses.php             # Fábrica de respuestas JSON estandarizadas
│   ├── tokenBlacklist.php        # Gestor de invalidación de JWT (Logout) con manejo de Race Conditions
│   └── userAuth.php              # Clase utilitaria Auth que maneja firma JWT y reglas de login
├── public/
│   └── index.php                 # Front-Controller, Autoloader y Manejo Silencioso de Excepciones 500
├── routes/
│   ├── .htaccess                 # Bloqueo directo Apache
│   └── api.php                   # Definición centralizada de todas las rutas de la API, y el Router
└── security/
    ├── .htaccess                 # Bloqueo directo Apache
    ├── audit.log                 # Bitácora de operaciones (Qué usuario cambió qué y cuándo)
    ├── blacklisted_tokens.json   # Archivo protegido persistente bloqueado para tokens invalidados
    ├── error.log                 # Bitácora cruda e invisible de excepciones de sistema
    └── rate_limit.json           # Trackers de IPs activas contra fuerza bruta
```

## Explicaciones breves de cada módulo

* **`/public/index.php`**: El despachador principal (Front-controller). Toda petición web entra por aquí. Contiene reglas para proteger la información frente a una falla 500, interceptando `exceptions` para arrojarlas localmente en rutinas log sin exponer el stack trace, auto-carga módulos (`spl_autoload_register`) y, tras iniciar instancias (como `Request` y la conexión de Inyección `iDB`), corre el ruteador web.
* **`/routes/api.php`**: Archivo de enrutamiento con motor de **Middlewares pre-cargados**. Verifica de forma agresiva requerimientos de sesión o limitación de tráfico antes de permitir instanciaciones lógicas. Contiene la función que guarda rutas en memoria y un método `dispatch(...)` que localiza a qué Controlador iterar basándose en arreglos de expresiones regulares que cotejan la URL del cliente.
* **`/app/requests.php`**: Clase `Request`. Agrupa y simplifica los métodos globales como `$_GET`, `$_POST`, o lee datos directos en formato crudo `php://input` JSON.
* **`/app/responses.php`**: Clase estática `Response`. Retorna formato estándar JSON, deteniendo la ejecución automáticamente en caso de errores para estructurar estandarizadamente toda comunicación visual de la App.
* **`/app/tokenBlacklist.php`**: Clase defensiva encargada del registro persistente y atómico (protegido frente a Concurrencia/Race Conditions empleando flocks) de tokens que hayan sido deliberadamente desautorizados o invalidados (Logout manual). Cuenta con un sistema interno de automantenimiento para rotar y eliminar tokens antiguos transparentemente según un límite de buffer en `configs.php`.
* **`/app/userAuth.php`**: Engloba las lógicas de encripción `HS256` de firmas JWT y sus validaciones complejas, verificación en la memoria del cliente activo y control maestro de acceso.
* **`/app/RateLimiter.php`**: Firewall primitivo que usa un mapeador local en `.json` para contabilizar intentos repetidos desde una única IP e implementar bloqueos matemáticos disuasivos por tiempo.
* **`/app/AuditLogger.php`**: Interfaz pasiva programada para recoger estelas de huellas de operadores cada que ocurre un CREATE, UPDATE o DELETE, y enrutarlas inmediatamente hacia el archivo bloqueado de auditoría del servidor central.
* **`/app/controllers/BaseController.php`**: Esqueleto lógico implementado. Todo controlador funcional de endpoints hereda de esta capa para la asimilación global y DRY del `Request` y `$dbpush`.
* **`/app/controllers/`**: Carpeta de controladores abstractos instanciados dinámicamente donde recae el *Single Responsibility Code* para despachar los datos, conectarse a BD y formar las respuestas lógicas a entregar.

## Arquitectura de Enrutamiento y Middlewares

Se agrega la arquitectura de middlewares, la función `addRoute` soporta directamente el "Arreglo de Middlewares":
```php
addRoute('POST', '/api/logout', 'AuthController', 'logout', ['auth']);
```
Si alguien intenta llamar esa API sin un token, la función global `dispatch()` frena el intento devolviendo 401 crudo sin siquiera "despertar" ni instanciar la memoria de `AuthController` ni consultar la BD.

## Guía de Desarrollo: Crear Nuevos Controladores y Rutas

Para escalar y añadir un nuevo módulo a la API, sigue estos sencillos pasos:

1. **Crear el Controlador:** Añade tu archivo en `app/controllers/NuevoController.php` asegurándote de que herede de `BaseController`.
```php
require_once __DIR__ . '/BaseController.php';

class NuevoController extends BaseController {
    public function procesar() {
        // Tienes acceso nativo a los parámetros de entrada y BD
        $parametros = $this->request->all();
        
        // Retornar JSON
        Response::success('Proceso exitoso', $parametros);
    }
}
```

2. **Registrar la Ruta y Protegerla:** Abre `routes/api.php` e invoca `addRoute()`. Pasa como elemento final el arreglo de protecciones `['auth']` para exigir un JWT válido, o déjalo vacío para que sea acceso público.
```php
// Ruta pública:
addRoute('GET', '/api/nuevo', 'NuevoController', 'procesar');

// Ruta protegida por Token JWT:
addRoute('POST', '/api/nuevo', 'NuevoController', 'procesar', ['auth']);
```

## Ejemplos de uso

### 1. Login (Generar JWT)
**POST** `http://localhost:8000/api/login`
```json
{
  "username": "admin",
  "password": "mi_password_real"
}
```
*Respuesta esperada:* Devuelve tu Token de tipo Bearer.

### 2. Obtener lista de Items
**GET** `http://localhost:8000/api/items`
Headers: `Authorization: Bearer <TU_TOKEN>`

### 3. Buscador Dinámico de múltiples parámetros
**POST** `http://localhost:8000/api/items/search`
Headers: `Authorization: Bearer <TU_TOKEN>`

*Request Body (JSON)*:
```json
{
  "name": "lap",
  "id": 1
}
```

### 4. Crear un nuevo Item
**POST** `http://localhost:8000/api/items`
Headers: `Authorization: Bearer <TU_TOKEN>`
*Request Body (JSON)*:
```json
{
  "name": "Teclado Mecánico",
  "price": 100
}
```
