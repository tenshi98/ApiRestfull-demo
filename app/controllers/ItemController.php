<?php
/**
 * ItemController Class
 * Demonstration of CRUD operations with session validation.
 */


class ItemController extends BaseController {

    // Simulated Database Storage (In-memory)
    private static $items = [
        1 => ['id' => 1, 'name' => 'Laptop Gamer', 'price' => 1200],
        2 => ['id' => 2, 'name' => 'Monitor 4K', 'price' => 400],
    ];

    /**
     * GET /api/items -> Listar todos
     */
    public function list() {

        // Obtener los datos/filtros enviados (sirve tanto para query params en GET como en el body de POST)
        $filters = $this->request->all();
        $filteredItems = array_values(self::$items);

        if (!empty($filters)) {
            $filteredItems = array_filter($filteredItems, function($item) use ($filters) {
                foreach ($filters as $key => $value) {
                    // Validamos la key existe en el item y el valor de busqueda no está en blanco
                    if (array_key_exists($key, $item) && $value !== '') {
                        // Para cadenas de texto, hacemos una búsqueda parcial case-insensitive
                        if (is_string($value) && is_string($item[$key])) {
                            if (stripos($item[$key], $value) === false) {
                                return false; // No hay coincidencia
                            }
                        } else {
                            // Para otros tipos de datos (como numéricos), usamos la coincidencia exacta
                            if ($item[$key] != $value) {
                                return false; // No coincide
                            }
                        }
                    }
                }
                return true; // Pasa todas las validaciones de búsqueda
            });
            // Re-indexar el array para evitar saltos en las posiciones
            $filteredItems = array_values($filteredItems);
        }

        Response::success('Items obtenidos', $filteredItems);
    }

    /**
     * GET /api/items/{id} -> Obtener uno
     */
    public function show($id) {
        if (isset(self::$items[$id])) {
            Response::success('Item obtenido', self::$items[$id]);
        } else {
            Response::error('Item no encontrado', 404);
        }
    }

    /**
     * POST /api/items -> Crear
     */
    public function create() {
        
        $this->request->validate([
            'name' => 'required',
            'price' => 'required'
        ]);

        $data = $this->request->all();
        $newId = count(self::$items) > 0 ? max(array_keys(self::$items)) + 1 : 1;
        
        $newItem = [
            'id' => $newId,
            'name' => $data['name'],
            'price' => $data['price']
        ];
        
        self::$items[$newId] = $newItem;

        // Registrar en Auditoría (obtener UID temporal asumiendo payload en variables)
        $userPayload = UserAuth::validateToken(UserAuth::getBearerToken());
        $uid = $userPayload ? $userPayload['uid'] : 'Desconocido';
        AuditLogger::log('CREATE', "Usuario $uid creó el ítem ID $newId llamado '{$data['name']}'");
        
        Response::success('Item creado', $newItem, 201);
    }

    /**
     * PUT /api/items/{id} -> Actualizar
     */
    public function update($id) {
        
        if (!isset(self::$items[$id])) {
            Response::error('Item no encontrado', 404);
        }

        $this->request->validate([
            'name' => 'required',
            'price' => 'required'
        ]);

        $data = $this->request->all();
        
        self::$items[$id]['name'] = $data['name'];
        self::$items[$id]['price'] = $data['price'];
        
        $userPayload = UserAuth::validateToken(UserAuth::getBearerToken());
        $uid = $userPayload ? $userPayload['uid'] : 'Desconocido';
        AuditLogger::log('UPDATE', "Usuario $uid actualizó el ítem ID $id: '{$data['name']}' con precio {$data['price']}");

        Response::success('Item actualizado', self::$items[$id]);
    }

    /**
     * DELETE /api/items/{id} -> Eliminar
     */
    public function delete($id) {
        
        if (!isset(self::$items[$id])) {
            Response::error('Item no encontrado', 404);
        }

        $userPayload = UserAuth::validateToken(UserAuth::getBearerToken());
        $uid = $userPayload ? $userPayload['uid'] : 'Desconocido';
        AuditLogger::log('DELETE', "Usuario $uid eliminó el ítem ID $id");

        unset(self::$items[$id]);
        Response::success('Item eliminado correctamente');
    }
}
