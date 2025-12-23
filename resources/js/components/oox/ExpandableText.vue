<!-- ExpandableText.vue -->
<!-- GUIA DE USO 
<template>
  <div class="max-w-xl mx-auto p-6 bg-white rounded-lg shadow">
    <ExpandableText
      :text="textoLargo"
      :max-lines="4"
      :clickable-text="true"
      expand-label="... leer más"
      collapse-label="leer menos"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import ExpandableText from '@/components/ExpandableText.vue'

const textoLargo = ref(
  'Este es un párrafo de texto muy largo que queremos truncar a solo unas pocas líneas para que el diseño sea más limpio. ' +
  'Cuando el usuario haga clic en el texto, queremos mostrar todo el contenido. ' +
  'Usaremos Tailwind y un componente reutilizable de Vue para manejar esto de forma limpia y mantenible.'
)
</script>

 -->
<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
    /**
     * Texto completo a mostrar.
     */
    text: string

    /**
     * Número de líneas visibles cuando está colapsado.
     * Debe existir como clase line-clamp-X en Tailwind.
     */
    maxLines?: number

    /**
     * Mostrar o no el enlace "ver menos" cuando está expandido.
     */
    showCollapseLabel?: boolean

    /**
     * Texto para el enlace "ver más".
     */
    expandLabel?: string

    /**
     * Texto para el enlace "ver menos".
     */
    collapseLabel?: string

    /**
     * Si es true, todo el texto es clicable.
     * Si es false, solo el enlace "Ver más"/"Ver menos" es clicable.
     */
    clickableText?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    maxLines: 4,
    showCollapseLabel: true,
    expandLabel: '... ver más',
    collapseLabel: 'ver menos',
    clickableText: false,
})

const isExpanded = ref(false)

const clampClass = computed(() =>
    isExpanded.value ? 'line-clamp-none' : `line-clamp-${props.maxLines}`
)

const canToggle = computed(() => props.text.length > 0) // Puedes refinar si quieres
const toggle = () => {
    if (!canToggle.value) return
    isExpanded.value = !isExpanded.value
}
</script>

<template>
    <div class="max-w-lg">
        <!-- Versión donde todo el párrafo es clicable -->
        <p v-if="clickableText"
            class="text-sm text-muted-foreground leading-relaxed transition-all duration-200 ease-in-out"
            :class="[clampClass, canToggle ? 'cursor-pointer select-none' : '']" @click="toggle">
            {{ text }}
            <span v-if="!isExpanded && canToggle" class="text-primary font-semibold">
                {{ expandLabel }}
            </span>
            <span v-else-if="isExpanded && showCollapseLabel && canToggle" class="ml-1 text-primary font-semibold">
                ({{ collapseLabel }})
            </span>
        </p>

        <!-- Versión con enlace separado "ver más / ver menos" -->
        <div v-else>
            <p class="text-sm text-muted-foreground leading-relaxed transition-all duration-200 ease-in-out"
                :class="clampClass">
                {{ text }}
            </p>

            <button v-if="canToggle" type="button"
                class="mt-1 text-xs font-semibold text-muted-foreground hover:text-primary focus:outline-none"
                @click="toggle">
                <span v-if="!isExpanded">{{ expandLabel }}</span>
                <span v-else-if="showCollapseLabel">{{ collapseLabel }}</span>
            </button>
        </div>
    </div>
</template>
