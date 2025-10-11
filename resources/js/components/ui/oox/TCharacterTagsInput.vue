<!--
  📝 Componente: CharacterTagsInput
  🎯 Propósito: Selector de personajes con tags y búsqueda dinámica por categoría
  🛠️ Tecnologías: Vue 3 + TypeScript + ShadCN UI + Inertia.js
  👥 Uso: Seleccionar múltiples personajes para una encuesta basados en categoría

  🧩 Características principales:
  - ✅ Carga dinámica de personajes por categoría (AJAX)
  - ✅ Sistema de tags con eliminación individual
  - ✅ Búsqueda en tiempo real
  - ✅ Prevención de duplicados
  - ✅ Filtrado de opciones ya seleccionadas
  - ✅ Integración completa con v-model
  - ✅ Compatibilidad con Inertia.js y formularios reactivos

  📦 Props requeridas:
  - categoryId: number | null - ID de categoría para filtrar personajes
  - modelValue: number[] - Array de IDs de personajes seleccionados

  📤 Emits:
  - update:modelValue - Emite array de IDs cuando cambia la selección

  🎯 Ejemplo de uso:
  <CharacterTagsInput
    v-model="form.characters"
    :category-id="form.category_id"
  />

  ================================================

  Explicación de conceptos clave para juniors

1. ¿Qué es v-model en componentes personalizados?
// En el padre:
<CharacterTagsInput v-model="form.characters" />

// Es equivalente a:
<CharacterTagsInput
  :model-value="form.characters"
  @update:model-value="(value) => form.characters = value"
/>

2. ¿Por qué usar watch con { immediate: true }?
Immediate: Ejecuta el watcher inmediatamente al montar el componente
Deep: Observa cambios profundos en objetos/arrays

3. ¿Por qué comparar con JSON.stringify?
// Evitar bucles infinitos de watchers
if (JSON.stringify(ids) !== JSON.stringify(props.modelValue)) {
  emit('update:modelValue', ids)
}

4. ¿Qué hace as-child en ShadCN?
Permite que un componente use el elemento hijo directamente como su representación visual, manteniendo estilos pero pasando props.

-->

<script setup lang="ts">
import { useFilter } from 'reka-ui'
import { computed, ref, watch } from 'vue'
import axios from 'axios'

// Componentes UI de ShadCN
import {
  Combobox,
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxGroup,
  ComboboxInput,
  ComboboxItem,
  ComboboxList
} from '@/components/ui/combobox'
import {
  TagsInput,
  TagsInputInput,
  TagsInputItem,
  TagsInputItemDelete,
  TagsInputItemText
} from '@/components/ui/tags-input'

/**
 * 📋 Interface para definir la estructura de un personaje
 * @property {number} value - ID del personaje (para backend)
 * @property {string} label - Nombre del personaje (para mostrar)
 */
interface CharacterOption {
  value: number
  label: string
}

/**
 * 📥 Props del componente
 * @property {number | null} categoryId - ID de categoría seleccionada
 * @property {number[]} modelValue - IDs de personajes seleccionados
 */
const props = defineProps<{
  categoryId: number | null
  modelValue: number[]
}>()

/**
 * 📤 Emits del componente
 * @event update:modelValue - Emite nuevos IDs seleccionados
 */
const emit = defineEmits<{
  (e: 'update:modelValue', value: number[]): void
}>()

/**
 * 📊 Estados reactivos del componente
 */
const selectedLabels = ref<string[]>([]) // Labels mostrados en UI (tags)
const open = ref(false)                   // Estado de apertura del combobox
const searchTerm = ref('')               // Término de búsqueda actual

/**
 * 📦 Datos cargados dinámicamente
 */
const availableCharacters = ref<CharacterOption[]>([]) // Personajes disponibles
const loading = ref(false)                            // Estado de carga

/**
 * 🔍 Sistema de filtrado para búsqueda
 * @description Filtra personajes disponibles excluyendo los ya seleccionados
 * y aplicando búsqueda por texto
 */
const { contains } = useFilter({ sensitivity: 'base' })
const filteredCharacters = computed(() => {
  // Excluir personajes ya seleccionados
  const options = availableCharacters.value.filter(
    char => !selectedLabels.value.includes(char.label)
  )
  
  // Aplicar filtro de búsqueda si hay término
  return searchTerm.value 
    ? options.filter(option => contains(option.label, searchTerm.value)) 
    : options
})

/**
 * 🔄 WATCHER: Sincronizar con props.modelValue
 * @description Cuando cambian los IDs seleccionados desde el padre,
 * convertirlos a labels para mostrar en la UI
 */
watch(() => props.modelValue, (newIds) => {
  // Solo procesar si hay IDs y personajes cargados
  if (newIds && newIds.length > 0 && availableCharacters.value.length > 0) {
    // Crear mapa de ID → Label para conversión rápida
    const idToLabelMap = new Map(availableCharacters.value.map(c => [c.value, c.label]))
    
    // Convertir IDs a labels, filtrando valores válidos
    const validLabels = newIds
      .map(id => idToLabelMap.get(id))                    // Buscar label por ID
      .filter((label): label is string => label !== undefined) // Solo labels válidos
    
    // Actualizar solo si hay cambios reales (evitar bucles infinitos)
    if (JSON.stringify(selectedLabels.value) !== JSON.stringify(validLabels)) {
      selectedLabels.value = validLabels
    }
  } 
  // Si no hay IDs seleccionados, limpiar UI
  else if (newIds && newIds.length === 0) {
    selectedLabels.value = []
  }
}, { immediate: true, deep: true })

/**
 * 🔄 WATCHER: Emitir cambios al componente padre
 * @description Cuando cambian los labels seleccionados en UI,
 * convertirlos a IDs y emitir al padre
 */
watch(selectedLabels, (newLabels) => {
  // Crear mapa de Label → ID para conversión rápida
  const labelToIdMap = new Map(availableCharacters.value.map(c => [c.label, c.value]))
  
  // Convertir labels a IDs, filtrando valores válidos
  const ids = newLabels
    .map(label => labelToIdMap.get(label))              // Buscar ID por label
    .filter((id): id is number => id !== undefined)     // Solo IDs válidos
  
  // Emitir solo si hay cambios reales (evitar bucles infinitos)
  if (JSON.stringify(ids) !== JSON.stringify(props.modelValue)) {
    emit('update:modelValue', ids)
  }
}, { deep: true })

/**
 * 🔄 WATCHER: Cargar personajes por categoría
 * @description Cuando cambia la categoría, cargar personajes disponibles
 */
watch(() => props.categoryId, async (newCategoryId) => {
  // Si no hay categoría seleccionada, limpiar todo
  if (!newCategoryId) {
    availableCharacters.value = []
    selectedLabels.value = []
    emit('update:modelValue', [])
    return
  }

  // Iniciar carga
  loading.value = true
  try {
    // 🌐 Cargar personajes de la categoría seleccionada
    const response = await axios.get(route('ajax.categories.characters', newCategoryId))
    availableCharacters.value = response.data
    
    // Limpiar selección actual pero mantener sincronización
    selectedLabels.value = []
    emit('update:modelValue', [])
  } catch (error) {
    console.error('❌ Error loading characters:', error)
    availableCharacters.value = []
  } finally {
    loading.value = false
  }
}, { immediate: true })
</script>

<template>
  <div class="space-y-2">
    <!-- 🏷️ Etiqueta del campo -->
    <label class="text-sm font-medium leading-none">Characters in survey</label>
    
    <!-- 🎯 Combobox principal con sistema de tags -->
    <Combobox 
      v-model="selectedLabels" 
      v-model:open="open" 
      :ignore-filter="true"
      multiple
    >
      <ComboboxAnchor as-child>
        <!-- 🏷️ Componente de tags input -->
        <TagsInput v-model="selectedLabels" class="px-2 gap-2 w-full">
          <!-- 📦 Contenedor de tags seleccionados -->
          <div class="flex gap-2 flex-wrap items-center">
            <TagsInputItem 
              v-for="(label, index) in selectedLabels" 
              :key="index" 
              :value="label"
            >
              <TagsInputItemText />
              <TagsInputItemDelete />
            </TagsInputItem>
          </div>

          <!-- 🔍 Input de búsqueda -->
          <ComboboxInput 
            v-model="searchTerm" 
            as-child
          >
            <TagsInputInput 
              placeholder="Select characters..." 
              class="min-w-[150px] w-full p-0 border-none focus-visible:ring-0 h-auto"
              @keydown.enter.prevent
            />
          </ComboboxInput>
        </TagsInput>
      </ComboboxAnchor>

      <!-- 📋 Lista de opciones disponibles -->
      <ComboboxList class="w-[--reka-popper-anchor-width]">
        <!-- 😔 Mensaje cuando no hay resultados -->
        <ComboboxEmpty v-if="!loading && filteredCharacters.length === 0" class="py-2 text-center text-sm text-muted-foreground p-2">
          No characters found
        </ComboboxEmpty>
        
        <!-- ⏳ Indicador de carga -->
        <div v-if="loading" class="py-2 text-center text-sm text-muted-foreground p-2">
          Loading characters...
        </div>
        
        <!-- 📋 Grupo de personajes disponibles -->
        <ComboboxGroup v-else>
            <ComboboxItem
                v-for="character in filteredCharacters"
                :key="character.value"
                :value="character.label"
                @select.prevent="(ev) => {
                    if (typeof ev.detail.value === 'string') {
                    searchTerm = ''
                    // ✅ Prevención de duplicados
                    if (!selectedLabels.includes(ev.detail.value)) {
                        selectedLabels.push(ev.detail.value)
                    }
                    }
                    
                    // ✅ Cerrar dropdown si no quedan más opciones
                    if (filteredCharacters.length === 0) {
                    open = false
                    }
                }"
            >
                {{ character.label }}
            </ComboboxItem>
        </ComboboxGroup>
      </ComboboxList>
    </Combobox>
  </div>
</template>