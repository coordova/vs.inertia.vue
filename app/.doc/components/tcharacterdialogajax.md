# Perplexity ai

/_-------------------------------------------_/
// TCharacterDialogAjax.vue - producción (reloadOnOpen:boolean)
/_-------------------------------------------_/
hay escenarios donde quieres cachear una sola vez (lazy load) y otros donde quieres refrescar siempre los datos al abrir el diálogo. Lo más limpio es exponer esa decisión como una prop de configuración, p.ej. reloadOnOpen.

reloadOnOpen = false → comportamiento actual (lazy + cache, solo primera vez).

reloadOnOpen = true → cada vez que abras el diálogo, hace de nuevo la petición AJAX y muestra datos frescos.

Además, hay un matiz: si characterId cambia (el padre cambia el id), tiene sentido limpiar el estado para el nuevo id. Eso se puede hacer con un watch.

Cómo usarlo según el escenario

1. Escenario lazy + cache (por defecto)
   Cargas una vez y reutilizas:

text
<TCharacterDialogAjax :character-id="character.id">
<template #trigger>

<!-- tu trigger custom -->
</template>
</TCharacterDialogAjax>
(Equivalente a reloadOnOpen="false".)

2. Escenario “datos siempre frescos”
   Cada vez que abras el diálogo, vuelve a llamar a la API:

text
<TCharacterDialogAjax
:character-id="character.id"
:reload-on-open="true"

> <template #trigger>

    <!-- trigger -->

  </template>
</TCharacterDialogAjax>
O en TSX-like / kebab-free:

text
<TCharacterDialogAjax
:character-id="character.id"
:reloadOnOpen="true"

> ...
> </TCharacterDialogAjax> 3. Escenario donde cambia characterId dinámicamente
> El watch sobre characterId:

Limpia el estado anterior.

Si el diálogo está abierto, recarga automáticamente para el nuevo id.

Esto cubre el caso que comentas: si la página/ sección se recarga vía AJAX y cambia el id que le pasas al componente, verás datos actualizados al abrirlo (y si está abierto, se refresca en caliente).

En estos casos, lo mejor es justo lo que propones: dar al componente una política de refresco configurable. Así eliges por instancia según el contexto:

“Esto casi no cambia → cachea.”

“Esto se actualiza tras cada acción → recarga siempre.”

Sin duplicar componentes ni lógica.

/_-------------------------------------------_/
// TCharacterDialogAjax.v0.vue - lazy loading + cache (idempotente)
/_-------------------------------------------_/
TCharacterDialog donde el contenido del diálogo se cargue bajo demanda por id usando Axios, manteniendo el mismo layout y trigger con slot.

Aquí tienes TCharacterDialogAjax.vue listo para producción, con:

Prop characterId: number.

Slot trigger (con fallback).

Carga lazy al abrir (solo la primera vez).

Estados loading y error.

Tipado con CharacterResource.

Uso de route() (asumiendo Ziggy disponible en el entorno global, como ya haces en otros componentes).

Uso en tu componente
Trigger por defecto:
text
<TCharacterDialogAjax :character-id="character.id" />
Trigger custom (por ejemplo, avatar + nombre como antes):
text
<TCharacterDialogAjax :character-id="character.id">
<template #trigger>

<div class="flex items-center gap-3 rounded-lg border p-3 hover:bg-accent cursor-pointer">
<div class="relative aspect-square w-12 overflow-hidden rounded-full border">
<img
          v-if="character.picture_url"
          :src="character.picture_url"
          :alt="character.fullname"
          class="h-full w-full object-cover"
        />
<div
          v-else
          class="flex h-full w-full items-center justify-center bg-muted"
        >
<span class="text-xs text-muted-foreground">No img</span>
</div>
</div>

      <div class="flex-1 truncate">
        <p class="truncate text-sm font-medium">
          {{ character.fullname }}
        </p>
        <p
          v-if="character.nickname"
          class="truncate text-xs text-muted-foreground"
        >
          {{ character.nickname }}
        </p>
      </div>
    </div>

  </template>
</TCharacterDialogAjax>
Notas de buenas prácticas aplicadas
Lazy loading: solo se llama al endpoint al abrir el diálogo la primera vez.

Idempotencia: si ya se cargó, no se re-pide.

Estados claros: loading, error, character.

Separation of concerns: el componente solo sabe de characterId y de cómo dibujar el dialog; no depende del padre para la data.

Slot trigger con fallback mantiene DRY/KISS y máxima reutilización.

Si tu endpoint AJAX devuelve un shape distinto ({ data: character }, CharacterResourceResource envuelto, etc.), solo ajusta la línea:

ts
character.value = response.data
// -> o: character.value = response.data.data

/_-------------------------------------------_/
