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
        'admin.users.index',
        'admin.users.show',
        // 'admin.users.create',
        // 'admin.users.store',
        'admin.users.edit',
        'admin.users.change-status',
        'admin.users.destroy',
        'admin.users.restore',
        'admin.users.force-delete',
        // 'admin.users.update',

        'admin.categories.index',
        'admin.categories.show',
        'admin.categories.create',
        'admin.categories.store',
        'admin.categories.edit',
        'admin.categories.update',
        'admin.categories.destroy',

        'admin.characters.index',
        'admin.characters.show',
        'admin.characters.create',
        'admin.characters.store',
        'admin.characters.edit',
        'admin.characters.update',
        'admin.characters.destroy',
        
        'admin.surveys.index',
        'admin.surveys.show',
        'admin.surveys.create',
        'admin.surveys.store',
        'admin.surveys.edit',
        'admin.surveys.update',
        'admin.surveys.destroy',
        'admin.surveys.vote',

        'ajax.categories.characters',

        'categories.public.index',
        'categories.public.show',

        'surveys.public.index',
        'surveys.public.show',
        'surveys.vote.store',
        // 'surveys.public.next_combination',

        'api.public.surveys.next_combination',
    ]
];
