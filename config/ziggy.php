<?php
/**
 * Aqui definimos las rutas que queremos que se generen en ziggy
 * las cuales estan en routes/web.php y van a ser visibles en el frontend
 * 
 * Ver la doc de ziggy para mas informacion sobre 'only' y 'except'
 * https://github.com/tighten/ziggy
 */

return [
    'only' => [
        'admin.categories.index',
        'admin.categories.create',
        'admin.categories.edit',
        'admin.categories.destroy',
    ]
];
