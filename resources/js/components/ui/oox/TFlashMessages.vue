<!-- resources/js/components/ui/oox/TFlashMessages.vue -->
<!-- O el nombre que hayas elegido, ej: FlashMessages.vue -->
<script setup lang="ts">
import { onMounted /*, watch */ } from 'vue';
import { usePage } from '@inertiajs/vue3';
// Importar tu useToast original
import { useToast } from '@/composables/useToast';

// --- Inicializar el composable de toast ---
// Importante: Llamamos a useToast() y obtenemos los métodos helper
const toastHelpers = useToast(); // <- Llamada correcta

// --- Obtener los mensajes flash de la página de Inertia ---
// Accedemos a page.props que contiene flash (configurado en HandleInertiaRequests)
const page = usePage();
// Asumiendo que flash se estructura como { success: '...', error: '...', ... }
// y está disponible en page.props.flash
const flashMessages = page.props.flash as Record<string, string> | undefined; // Cast para tipado

// --- Función para mostrar toasts basados en los mensajes flash ---
const showFlashMessages = () => {
    // Verificar y mostrar mensaje de éxito
    if (flashMessages?.success) {
        toastHelpers.success(flashMessages.success); // <- Uso correcto del helper
    }

    // Verificar y mostrar mensaje de error
    if (flashMessages?.error) {
        toastHelpers.error(flashMessages.error); // <- Uso correcto del helper
    }

    // Verificar y mostrar mensaje de advertencia (opcional)
    if (flashMessages?.warning) {
        toastHelpers.warning(flashMessages.warning); // <- Uso correcto del helper
    }

    // Verificar y mostrar mensaje de información (opcional)
    // Asumiendo que 'info' también se pasa desde el backend
    if (flashMessages?.info) {
        toastHelpers.info(flashMessages.info); // <- Uso correcto del helper
    }
    // Si usas otros tipos, añádelos aquí (e.g., 'danger' -> toastHelpers.error)
};

// --- Mostrar mensajes flash al montar el componente ---
onMounted(() => {
    showFlashMessages();
});

// --- (Opcional) Escuchar cambios en flashMessages ---
// Generalmente no es necesario si solo se establecen una vez por solicitud.
// Si se actualizan dinámicamente, descomentar el watch.
// watch(flashMessages, () => {
//     showFlashMessages();
// }, { deep: true });
</script>

<template>
    <!-- El componente no necesita un template visible -->
    <!-- Se encarga de mostrar toasts basados en page.props.flash -->
</template>

<style scoped>
/* Estilos si es necesario */
</style>