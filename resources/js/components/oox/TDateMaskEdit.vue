<!-- components/ui/oox/TMaskDateEdit.vue -->
<script setup lang="ts">
import { cn } from '@/lib/utils';

// -----------------------------------------------------------------------------
// --- SOLUCIÓN: TIPADO EXPLÍCITO Y ROBUSTO DE PROPS ---
// -----------------------------------------------------------------------------

// ✅ Usamos la sintaxis con genéricos de TypeScript para definir las props.
// Esto nos da control total y claridad sobre los tipos.
const props = defineProps<{
    modelValue: string | null; // Aceptamos explícitamente string o null
    id: string; // <-- Añade esta prop
}>();

// ✅ El emit se mantiene igual, ya que el valor del input será un string.
const emit = defineEmits(['update:modelValue']);

// -----------------------------------------------------------------------------
</script>

<template>
    <div class="relative">
        <!-- 
            ✅ CORRECCIÓN SUTIL PERO IMPORTANTE EN EL BINDING
            En lugar de v-model completo, lo descomponemos para manejar el `null`.
            El input nativo no maneja bien `null` como valor, prefiere `''`.
        -->
        <input :id="props.id" type="date" :value="props.modelValue ?? ''"
            @input="emit('update:modelValue', ($event.target as HTMLInputElement).value || null)" :class="cn(
                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background',
                'file:border-0 file:bg-transparent file:text-sm file:font-medium',
                'placeholder:text-muted-foreground',
                'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none',
                'disabled:cursor-not-allowed disabled:opacity-50',
                'pr-8',
            )
                " />
        <!-- El icono se mantiene igual -->
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-calendar-days absolute top-1/2 right-2 h-4 w-4 -translate-y-1/2 text-muted-foreground">
            <path d="M8 2v4" />
            <path d="M16 2v4" />
            <rect width="18" height="18" x="3" y="4" rx="2" />
            <path d="M3 10h18" />
            <path d="M8 14h.01" />
            <path d="M12 14h.01" />
            <path d="M16 14h.01" />
            <path d="M8 18h.01" />
            <path d="M12 18h.01" />
            <path d="M16 18h.01" />
        </svg>
    </div>
</template>

<style scoped>
/* Tu estilo se mantiene igual */
input[type='date']::-webkit-inner-spin-button,
input[type='date']::-webkit-calendar-picker-indicator {
    display: none;
    -webkit-appearance: none;
}
</style>