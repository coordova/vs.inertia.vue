### Origen de la confusión: ¿Por qué tienes `props.category.data.id`?

El origen del problema es **cómo Laravel Resource transforma el modelo** y cómo Inertia entrega la estructura al frontend:

- Cuando envías una `CategoryResource` como prop desde PHP a Inertia:
    ```php
    return Inertia::render('Admin/Categories/Show', [
      'category' => new CategoryResource($category),
    ]);
    ```
    El objeto que se recibe en Vue **no es plano**, sino un "resource wrapper" con una propiedad `data` que contiene los atributos reales. Es el comportamiento estándar de Laravel cuando retornas una instancia de Resource y NO usas `->toArray()` o `->resolve()` explícitamente.
- Así, en JS terminas con algo como:
    ```js
    props.category.data.id;
    ```
    en vez de simplemente:
    ```js
    props.category.id;
    ```

### Soluciones reales para tener un objeto plano en Vue

Tienes varias opciones **dependiendo de cuál prefieras en tu proyecto**:

#### 1. Usar `CategoryResource::make(...)->resolve()` en el controlador

Esto "desenvuelve" el resource y solo entrega los datos directamente:

```php
return Inertia::render('Admin/Categories/Show', [
  'category' => CategoryResource::make($category)->resolve(),
]);
```

Ahora, en Vue puedes acceder a `props.category.id` directamente.

#### 2. Usar `->toArray(request())` (más explícito)

```php
return Inertia::render('Admin/Categories/Show', [
  'category' => (new CategoryResource($category))->toArray(request()),
]);
```

Esto también "desenvuelve" el array y lo deja plano para Vue.

#### 3. Usar solo el modelo directamente (no recomendado si necesitas formateo/transformación)

Si no usas ninguna transformation, puedes pasar el modelo directo:

```php
return Inertia::render('Admin/Categories/Show', [
  'category' => $category,
]);
```

Pero pierdes los formatos personalizados del Resource.

#### ¿Por qué Laravel lo envuelve en `data`?

- Laravel Resource lo hace para poder adjuntar metadatos, colecciones, y otros, por eso el "wrapper".
- Si envías la instancia del Resource y no el array, Vue recibe el objeto tal cual, con la propiedad `data`.

### Recomendación práctica (con transformación):

La forma más común y limpia:

```php
return Inertia::render('Admin/Categories/Show', [
  'category' => CategoryResource::make($category)->resolve(),
]);
```

Ahora en Vue:

```vue
<p>{{ props.category.id }}</p>
<p>{{ props.category.name }}</p>
```

Y tu TypeScript interface **coincide** con lo que usas en el frontend.

---

**Resumen:**

- El envoltorio `data` ocurre por cómo envías la instancia del Resource.
- "Resuelve" el resource usando `.resolve()` antes de enviarlo a Inertia para obtener un objeto plano y usar `props.category.id` con seguridad en Vue.

[1](https://laracasts.com/discuss/channels/inertia/how-to-get-data-from-controller-show)
[2](https://inertiajs.com/responses)
[3](https://stackoverflow.com/questions/72180589/how-to-show-inertia-data-to-vue-componets)
[4](https://kinsta.com/blog/laravel-inertia/)
[5](https://devrims.com/blog/laravel-inertia/)
[6](https://inertiajs.com/manual-visits)
[7](https://jetstream.laravel.com/stacks/inertia.html)
[8](https://laracasts.com/discuss/channels/inertia/how-can-i-perform-live-search-in-inertia-with-react-and-laravel)
